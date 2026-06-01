<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/**
 * StepCollector — pure in-memory lifecycle step buffer.
 *
 * Responsibilities (SRP):
 *  - Register a DB::listen hook for query-step tracking via listenToQueries().
 *  - Open and close named lifecycle steps with nanosecond precision.
 *  - Expose the completed steps via drain() for persistence by StepRepository.
 *
 * Persistence is intentionally NOT this class's concern; it is handled by
 * StepRepository::bulkInsert() so that the collector can be unit-tested
 * without a real database.
 *
 * Tracked automatically:
 *   - "routing"    step: from TrackRequest::handle() to RouteMatched event.
 *   - "controller" step: from RouteMatched to $next() returning in TrackRequest.
 *   - One "query:…" step per DB query (type = 'query') via listenToQueries().
 *
 * Additional steps can be pushed by application code via openStep()/closeStep().
 * All timings are ms offsets from the request start, using hrtime() for accuracy.
 */
class StepCollector
{
    /** Completed steps ready to flush. */
    private array $buffer = [];

    /** Currently open steps keyed by step name. */
    private array $open = [];

    /** hrtime(true) captured when the request started. */
    private int $requestStartNs = 0;

    /** Auto-increment execution order. */
    private int $sequence = 0;

    /** Guard against double-registration of the DB listener. */
    private bool $listening = false;

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Called from TrackRequest::handle() with the monotonic clock timestamp
     * captured at the very beginning of the request.
     */
    public function setRequestStart(int $ns): void
    {
        $this->requestStartNs = $ns;
    }

    /**
     * Begin a named step.  Call closeStep() with the same $name to finish it.
     *
     * @param  array<string, mixed>  $meta
     */
    public function openStep(string $name, string $type, array $meta = []): void
    {
        $this->open[$name] = [
            'startNs' => hrtime(true),
            'type' => $type,
            'meta' => $meta,
        ];
    }

    /**
     * Finish a named step and push it into the completed buffer.
     */
    public function closeStep(string $name): void
    {
        if (! isset($this->open[$name])) {
            return;
        }

        $entry = $this->open[$name];
        $endNs = hrtime(true);
        $startedAtMs = $this->nsOffset($entry['startNs']);
        $durationMs = (int) round(($endNs - $entry['startNs']) / 1_000_000);

        $this->buffer[] = [
            'name' => $name,
            'type' => $entry['type'],
            'sequence' => $this->sequence++,
            'started_at_ms' => max(0, $startedAtMs),
            'duration_ms' => max(0, $durationMs),
            'metadata' => $entry['meta'] ?: null,
        ];

        unset($this->open[$name]);
    }

    /**
     * Register a DB::listen hook that records each query as a 'query' step.
     * Called once from the ServiceProvider during boot().
     *
     * The listener uses the monotonic clock to compute when the query *started*
     * relative to the request start:
     *   started_at_ms ≈ (now - requestStartNs) / 1e6  −  event->time
     *
     * This is an approximation (DB::listen fires right after query completion)
     * but is accurate to within a few microseconds in practice.
     */
    public function listenToQueries(): void
    {
        if ($this->listening) {
            return;
        }

        $this->listening = true;

        DB::listen(function (QueryExecuted $event): void {
            // Skip if we are outside a tracked request.
            if ($this->requestStartNs === 0) {
                return;
            }

            $endNs = hrtime(true);
            $durationMs = (int) round($event->time); // already in ms (float)
            $endedAtMs = $this->nsOffset($endNs);
            $startedAtMs = max(0, $endedAtMs - $durationMs);

            $slowThreshold = (float) config(
                'request-log-analyzer.slow_query_threshold_ms',
                200
            );

            $this->buffer[] = [
                'name' => 'query:'.$this->abbreviate($event->sql),
                'type' => 'query',
                'sequence' => $this->sequence++,
                'started_at_ms' => $startedAtMs,
                'duration_ms' => $durationMs,
                'metadata' => [
                    'sql' => $event->sql,
                    'connection' => $event->connectionName,
                    'bindings_count' => count($event->bindings),
                    'is_slow' => $event->time >= $slowThreshold,
                ],
            ];
        });
    }

    /**
     * Auto-close any open steps, return the full buffer, then reset.
     * Called by TrackRequest::terminate() (sync + async). The repository's
     * bulkInsert() handles the actual DB write.
     *
     * @return array<int, array>
     */
    public function drain(): array
    {
        foreach (array_keys($this->open) as $name) {
            $this->closeStep($name);
        }

        // Stop accepting new DB::listen events before we hand off the buffer.
        $this->requestStartNs = 0;

        $buffer = $this->buffer;
        $this->reset();

        return $buffer;
    }

    /**
     * Discard all buffered state without writing (e.g. for ignored paths).
     */
    public function reset(): void
    {
        $this->buffer = [];
        $this->open = [];
        $this->sequence = 0;
        $this->requestStartNs = 0;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /** Convert an absolute hrtime value to an ms offset from request start. */
    private function nsOffset(int $ns): int
    {
        return (int) round(($ns - $this->requestStartNs) / 1_000_000);
    }

    /** Truncate long SQL strings so that the step name stays readable. */
    private function abbreviate(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        return mb_strlen($sql) > 120
            ? mb_substr($sql, 0, 117).'…'
            : $sql;
    }
}
