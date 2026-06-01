<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/**
 * QueryCollector — pure in-memory event buffer.
 *
 * Responsibilities (SRP):
 *  - Register the DB::listen hook once via listen().
 *  - Buffer every QueryExecuted event into an in-process array.
 *  - Expose the buffer via getBuffer() and drain().
 *
 * Persistence is intentionally NOT this class's concern; it is handled
 * by QueryRepository::bulkInsert() so that the collector can be unit-tested
 * without a database connection.
 */
class QueryCollector
{
    /** @var array<int, array{sql: string, bindings: array, time_ms: float, connection: string}> */
    private array $buffer = [];

    private bool $listening = false;

    /**
     * Register the DB::listen hook.
     * Called once from the ServiceProvider during boot.
     */
    public function listen(): void
    {
        if ($this->listening) {
            return;
        }

        $this->listening = true;

        DB::listen(function (QueryExecuted $event): void {
            $this->buffer[] = [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'time_ms' => $event->time,      // already in ms (float)
                'connection' => $event->connectionName,
            ];
        });
    }

    /**
     * Return all buffered rows and atomically reset the buffer.
     * Called by TrackRequest::terminate() (sync) or by the async path before
     * dispatching PersistRequestLog. The repository's bulkInsert() handles
     * the actual DB write.
     *
     * @return array<int, array{sql: string, bindings: array, time_ms: float, connection: string}>
     */
    public function drain(): array
    {
        $buffer = $this->buffer;
        $this->buffer = [];

        return $buffer;
    }

    /**
     * Discard the buffer without returning it (e.g. for ignored paths).
     */
    public function reset(): void
    {
        $this->buffer = [];
    }

    /**
     * Number of queries collected so far in this request.
     */
    public function count(): int
    {
        return count($this->buffer);
    }

    /**
     * Expose the raw buffer (useful for testing/debugging).
     *
     * @return array<int, array{sql: string, bindings: array, time_ms: float, connection: string}>
     */
    public function getBuffer(): array
    {
        return $this->buffer;
    }
}
