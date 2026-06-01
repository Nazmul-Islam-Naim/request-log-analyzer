<?php

namespace NIN\RequestLogAnalyzer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use NIN\RequestLogAnalyzer\Contracts\ErrorRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\GeoIpResolverInterface;
use NIN\RequestLogAnalyzer\Contracts\QueryRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\RequestRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\StepRepositoryInterface;

/**
 * PersistRequestLog â€” async queue job for writing request log data.
 *
 * Dispatched by TrackRequest::terminate() when `async_logging` is enabled.
 * All in-memory data (queries, errors, steps) is serialized into the job
 * payload so that queue workers can process it independently of the web
 * process, completely removing post-response blocking from the web tier.
 *
 * The GeoIP lookup also moves here, eliminating the external HTTP call
 * from the web worker entirely.
 *
 * SOLID notes:
 *  - handle() receives repository interfaces via Laravel's DI â€” fully mockable.
 *  - No direct DB::table() calls â€” all persistence is delegated to repositories.
 *
 * Retry/timeout policy:
 *   - 3 retries on failure (transient DB/network errors)
 *   - 30 s job timeout (ample for GeoIP + bulk inserts)
 *   - deleteWhenMissingModels = true (no orphaned jobs if package is removed)
 */
class PersistRequestLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public bool $deleteWhenMissingModels = true;

    /**
     * @param  array<string, mixed>  $snapshot  {method, url, uri, ip, user_agent, query_string}
     * @param  mixed  $userId  nullable
     * @param  array<string>  $tags
     * @param  array<int, array>  $queries  raw QueryCollector buffer
     * @param  array<int, array>  $exceptions  raw ExceptionCollector buffer
     * @param  array<int, array>  $steps  raw StepCollector buffer (steps already closed)
     * @param  string  $capturedAt  ISO-8601 timestamp from the web worker
     */
    public function __construct(
        private readonly array $snapshot,
        private readonly int $statusCode,
        private readonly int $responseTimeMs,
        private readonly int $memoryBytes,
        private readonly mixed $userId,
        private readonly ?string $sessionId,
        private readonly array $tags,
        private readonly array $queries,
        private readonly array $exceptions,
        private readonly array $steps,
        private readonly string $capturedAt,
    ) {}

    public function handle(
        GeoIpResolverInterface $geoIpResolver,
        RequestRepositoryInterface $requestRepo,
        ErrorRepositoryInterface $errorRepo,
        QueryRepositoryInterface $queryRepo,
        StepRepositoryInterface $stepRepo,
    ): void {
        // GeoIP resolves in the worker â€” zero impact on web response time.
        $geo = $geoIpResolver->lookup($this->snapshot['ip'] ?? '');

        $requestId = $requestRepo->create([
            'ulid' => (string) Str::ulid(),
            'method' => $this->snapshot['method'],
            'url' => $this->snapshot['url'],
            'uri' => $this->snapshot['uri'],
            'query_string' => $this->snapshot['query_string'],
            'ip' => $this->snapshot['ip'],
            'country' => $geo['country'],
            'city' => $geo['city'],
            'user_agent' => $this->snapshot['user_agent'],
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'status_code' => $this->statusCode,
            'response_time_ms' => $this->responseTimeMs,
            'memory_usage_bytes' => $this->memoryBytes,
            'request_headers' => $this->snapshot['request_headers'] ?? null,
            'response_headers' => $this->snapshot['response_headers'] ?? null,
            'tags' => ! empty($this->tags)
                ? json_encode(array_values($this->tags))
                : null,
            'created_at' => $this->capturedAt,
            'updated_at' => $this->capturedAt,
        ]);

        $queryRepo->bulkInsert($requestId, $this->queries, $this->capturedAt);
        $errorRepo->bulkInsert($requestId, $this->exceptions, $this->capturedAt);
        $stepRepo->bulkInsert($requestId, $this->steps, $this->capturedAt);
    }
}
