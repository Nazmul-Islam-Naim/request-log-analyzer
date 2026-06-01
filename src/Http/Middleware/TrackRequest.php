<?php

namespace NIN\RequestLogAnalyzer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use NIN\RequestLogAnalyzer\Contracts\ErrorRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\GeoIpResolverInterface;
use NIN\RequestLogAnalyzer\Contracts\QueryRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\RequestRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\RateUsageRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\StepRepositoryInterface;
use NIN\RequestLogAnalyzer\Jobs\PersistRequestLog;
use NIN\RequestLogAnalyzer\RequestLogAnalyzer;
use NIN\RequestLogAnalyzer\Services\AlertChecker;
use NIN\RequestLogAnalyzer\Services\AlertNotifier;
use NIN\RequestLogAnalyzer\Services\ExceptionCollector;
use NIN\RequestLogAnalyzer\Services\QueryCollector;
use NIN\RequestLogAnalyzer\Services\RateLimiterService;
use NIN\RequestLogAnalyzer\Services\SensitiveDataMasker;
use NIN\RequestLogAnalyzer\Services\StepCollector;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackRequest — high-performance request tracking middleware.
 *
 * Performance optimisations:
 *  1. Static-asset bypass — extension check before any snapshot is taken.
 *  2. Probabilistic sampling — configurable; overrides for errors + slow reqs.
 *  3. Async queue dispatch — when `async_logging` is on, all DB writes and
 *     the GeoIP lookup move to a background worker (PersistRequestLog).
 *  4. Synchronous fallback — writes happen in terminate() (post-response).
 *
 * SOLID notes:
 *  - Depends on interfaces (DIP), not concrete DB/HTTP drivers.
 *  - Single responsibility: orchestrate data capture; delegates all
 *    persistence to repository interfaces.
 *  - Fully injectable and mockable for testing.
 */
class TrackRequest
{
    /**
     * Static file extensions that should never be logged.
     * Checked in shouldIgnore() when `ignore_static_assets` is true.
     */
    private const STATIC_EXTENSIONS = [
        'css', 'js', 'mjs', 'map',
        'ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif', 'bmp',
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        'mp4', 'mp3', 'ogg', 'wav', 'webm',
        'pdf', 'zip', 'gz', 'tar',
        'txt', 'xml',
    ];

    public function __construct(
        private readonly QueryCollector $queryCollector,
        private readonly ExceptionCollector $exceptionCollector,
        private readonly StepCollector $stepCollector,
        private readonly GeoIpResolverInterface $geoIpResolver,
        private readonly RequestLogAnalyzer $analyzer,
        private readonly RequestRepositoryInterface $requestRepo,
        private readonly ErrorRepositoryInterface $errorRepo,
        private readonly QueryRepositoryInterface $queryRepo,
        private readonly StepRepositoryInterface $stepRepo,
        private readonly SensitiveDataMasker $masker,
        private readonly AlertChecker $alertChecker,
        private readonly AlertNotifier $alertNotifier,
        private readonly RateUsageRepositoryInterface $rateUsageRepo,
        private readonly RateLimiterService $rateLimiter,
    ) {}

    /** Monotonic start time in nanoseconds. */
    private int $startNs;

    /**
     * Request snapshot — populated for every non-ignored request so that
     * sampling overrides (always_capture_errors, always_capture_slow_ms) can
     * still log requests that were initially sampled out.
     */
    private array $snapshot = [];

    /**
     * True when the request passed shouldIgnore() AND passesSampleRate().
     * Steps are only opened/closed for tracked requests; overrides may still
     * log the request without full step data.
     */
    private bool $shouldTrack = false;

    /**
     * True when shouldIgnore() returned true — hard skip, no overrides apply.
     */
    private bool $hardIgnored = false;

    public function handle(Request $request, Closure $next): Response
    {
        $this->startNs = hrtime(true);

        if ($this->shouldIgnore($request)) {
            $this->hardIgnored = true;

            return $next($request);
        }

        // Always capture the snapshot for non-ignored requests (cheap string
        // copies). This allows sampling overrides to log the request even when
        // passesSampleRate() returned false.
        $this->snapshot = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'uri' => '/'.ltrim($request->path(), '/'),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'query_string' => $this->masker->maskQueryString((string) ($request->getQueryString() ?? '')),
            'request_headers' => config('request-log-analyzer.track_request_headers')
                ? $this->masker->maskHeaders($request->headers->all())
                : null,
        ];

        if ($this->passesSampleRate()) {
            $this->shouldTrack = true;

            // ── Start lifecycle tracking ───────────────────────────────────
            if (config('request-log-analyzer.track_steps', true)) {
                $this->stepCollector->setRequestStart($this->startNs);
                // "routing" covers: this middleware → RouteMatched event.
                // The ServiceProvider's RouteMatched listener closes this step
                // and opens the "controller" step.
                $this->stepCollector->openStep('routing', 'middleware');
            }
        }

        $response = $next($request);

        // Close the "controller" step once the full pipeline has returned.
        if ($this->shouldTrack && config('request-log-analyzer.track_steps', true)) {
            $this->stepCollector->closeStep('controller');
        }

        return $response;
    }

    /**
     * terminate() runs after the response is sent — zero user-perceived overhead.
     * With async_logging, even the DB writes and GeoIP lookup move off-process.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Hard-ignored paths (static assets, opted-out routes) — skip everything.
        if ($this->hardIgnored || empty($this->snapshot)) {
            $this->resetCollectors();

            return;
        }

        if (! config('request-log-analyzer.enabled', true)) {
            $this->resetCollectors();

            return;
        }

        // ── ignore_routes: checked here because the route is fully resolved
        //    by terminate() time even when TrackRequest is global middleware. ──
        $ignoreRoutes = config('request-log-analyzer.ignore_routes', []);
        if (! empty($ignoreRoutes) && $request->routeIs(...$ignoreRoutes)) {
            $this->resetCollectors();
            $this->reset();

            return;
        }

        $responseTimeMs = (int) round((hrtime(true) - $this->startNs) / 1_000_000);
        $memoryUsageBytes = memory_get_peak_usage(true);
        $statusCode = $response->getStatusCode();

        // ── Sampling overrides ────────────────────────────────────────────────
        // Even when passesSampleRate() said "skip", we still log the request if:
        //  a) It produced a 5xx error and always_capture_errors is true.
        //  b) It exceeded the always_capture_slow_ms threshold.
        $shouldLog = $this->shouldTrack;

        if (! $shouldLog && config('request-log-analyzer.always_capture_errors', true) && $statusCode >= 500) {
            $shouldLog = true;
        }

        if (! $shouldLog) {
            $slowMs = (int) config('request-log-analyzer.always_capture_slow_ms', 0);
            if ($slowMs > 0 && $responseTimeMs >= $slowMs) {
                $shouldLog = true;
            }
        }

        if (! $shouldLog) {
            $this->resetCollectors();
            $this->reset();

            return;
        }

        $userId = null;
        try {
            $userId = $request->user()?->getKey();
        } catch (\Throwable) {
        }

        $sessionId = null;
        try {
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        } catch (\Throwable) {
        }

        $tags = $this->analyzer->flushTags();
        $capturedAt = now()->toDateTimeString();

        // Capture response headers here — available in both sync and async paths.
        $this->snapshot['response_headers'] = config('request-log-analyzer.track_response_headers')
            ? $this->masker->maskHeaders($response->headers->all())
            : null;

        // ── Async path: dispatch job to queue worker ─────────────────────────
        if (config('request-log-analyzer.async_logging', false)) {
            // drain() closes open steps and resets each collector atomically.
            $queries = $this->queryCollector->drain();
            $exceptions = $this->exceptionCollector->drain();
            $steps = $this->stepCollector->drain();

            PersistRequestLog::dispatch(
                $this->snapshot,
                $statusCode,
                $responseTimeMs,
                $memoryUsageBytes,
                $userId,
                $sessionId,
                $tags,
                config('request-log-analyzer.track_queries', true) ? $queries : [],
                config('request-log-analyzer.track_errors', true) ? $exceptions : [],
                config('request-log-analyzer.track_steps', true) ? $steps : [],
                $capturedAt,
            )
                ->onConnection(config('request-log-analyzer.queue_connection') ?: null)
                ->onQueue(config('request-log-analyzer.queue_name', 'default'));

            $this->reset();

            // ── Check and send alerts (async path) ────────────────────────────
            if (config('request-log-analyzer.alerts.enabled', true)) {
                $this->checkAndSendAlerts();
            }

            return;
        }

        // ── Synchronous (inline) path ─────────────────────────────────────────
        // GeoIP runs after the response is sent — no client-visible latency.
        $geo = $this->geoIpResolver->lookup($this->snapshot['ip'] ?? '');

        $requestId = $this->requestRepo->create([
            'ulid' => (string) Str::ulid(),
            'method' => $this->snapshot['method'],
            'url' => $this->snapshot['url'],
            'uri' => $this->snapshot['uri'],
            'query_string' => $this->snapshot['query_string'],
            'ip' => $this->snapshot['ip'],
            'country' => $geo['country'],
            'city' => $geo['city'],
            'user_agent' => $this->snapshot['user_agent'],
            'user_id' => $userId,
            'session_id' => $sessionId,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'memory_usage_bytes' => $memoryUsageBytes,
            'request_headers' => $this->snapshot['request_headers'],
            'response_headers' => $this->snapshot['response_headers'],
            'tags' => ! empty($tags) ? json_encode(array_values($tags)) : null,
            'created_at' => $capturedAt,
            'updated_at' => $capturedAt,
        ]);

        // drain() returns the buffer and resets — no separate reset() needed.
        if (config('request-log-analyzer.track_queries', true)) {
            $this->queryRepo->bulkInsert($requestId, $this->queryCollector->drain(), $capturedAt);
        } else {
            $this->queryCollector->reset();
        }

        if (config('request-log-analyzer.track_errors', true)) {
            $this->errorRepo->bulkInsert($requestId, $this->exceptionCollector->drain(), $capturedAt);
        } else {
            $this->exceptionCollector->reset();
        }

        if (config('request-log-analyzer.track_steps', true)) {
            $this->stepRepo->bulkInsert($requestId, $this->stepCollector->drain(), $capturedAt);
        } else {
            $this->stepCollector->reset();
        }

        // ── Check and send alerts (sync path) ──────────────────────────────
        if (config('request-log-analyzer.alerts.enabled', true)) {
            $this->checkAndSendAlerts();
        }

        $this->reset();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resetCollectors(): void
    {
        $this->queryCollector->reset();
        $this->exceptionCollector->reset();
        $this->stepCollector->reset();
    }

    private function reset(): void
    {
        $this->snapshot = [];
        $this->shouldTrack = false;
        $this->hardIgnored = false;
    }

    /** Return true when this request must be skipped based on URI pattern or named route. */
    private function shouldIgnore(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        // ── Static-asset bypass ───────────────────────────────────────────────
        if (config('request-log-analyzer.ignore_static_assets', true)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext !== '' && in_array($ext, self::STATIC_EXTENSIONS, true)) {
                return true;
            }
        }

        $ignored = config('request-log-analyzer.ignored_paths', []);

        foreach ($ignored as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        // Named-route check — only reliable when the route is already resolved
        // (i.e. when TrackRequest is used as route middleware). The terminate()
        // guard handles the global-middleware case.
        $ignoreRoutes = config('request-log-analyzer.ignore_routes', []);
        if (! empty($ignoreRoutes) && $request->route() !== null) {
            if ($request->routeIs(...$ignoreRoutes)) {
                return true;
            }
        }

        return false;
    }

    private function passesSampleRate(): bool
    {
        $rate = (int) config('request-log-analyzer.sample_rate', 100);

        if ($rate <= 0) {
            return false;
        }

        if ($rate >= 100) {
            return true;
        }

        return mt_rand(1, 100) <= $rate;
    }

    /**
     * Check alert thresholds and send notifications if triggered.
     *
     * Runs after request data is persisted (both sync and async paths).
     * Safely wraps alert operations to prevent exceptions from affecting
     * the main request logging.
     */
    private function checkAndSendAlerts(): void
    {
        try {
            // Check error alert
            if ($errorAlert = $this->alertChecker->checkErrorAlert()) {
                $this->alertNotifier->sendErrorAlert($errorAlert);
            }

            // Check slow request alert
            if ($slowAlert = $this->alertChecker->checkSlowAlert()) {
                $this->alertNotifier->sendSlowAlert($slowAlert);
            }

            // Check rate limits
            if (config('request-log-analyzer.rate_limits.enabled')) {
                $this->checkAndTrackRateLimits();
            }
        } catch (\Throwable $e) {
            // Log alert errors without disrupting request logging
            \Illuminate\Support\Facades\Log::error('RLA Alert system error: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Check rate limits and send alerts if thresholds exceeded.
     */
    private function checkAndTrackRateLimits(): void
    {
        try {
            $userId = auth()->id();
            $ip = request()->ip();

            // Record the request
            $this->rateUsageRepo->recordRequest($userId, $ip, request()->path(), 'minute');

            // Check rate limits
            $alerts = $this->rateLimiter->checkAllLimits($userId, $ip, 'minute');

            // Send rate limit alerts via configured channels
            foreach ($alerts as $alert) {
                foreach (config('request-log-analyzer.rate_limits.channels', ['log']) as $channel) {
                    match ($channel) {
                        'log' => $this->alertNotifier->sendViaLog($alert),
                        'email' => $this->alertNotifier->sendViaEmail($alert),
                        'discord' => $this->alertNotifier->sendViaDiscord($alert),
                        default => null,
                    };
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('RLA Rate limit check error: '.$e->getMessage());
        }
    }
}
