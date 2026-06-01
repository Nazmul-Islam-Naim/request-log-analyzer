<?php

namespace NIN\RequestLogAnalyzer\Contracts;

use Illuminate\Support\Collection;
use NIN\RequestLogAnalyzer\Models\Query;

/**
 * QueryRepositoryInterface
 *
 * Owns all persistence and retrieval for the `queries` table.
 * Mirrors ErrorRepositoryInterface in shape (bulk-write / selective-read).
 */
interface QueryRepositoryInterface
{
    /**
     * Bulk-insert a batch of query records linked to a request.
     * Slow-query detection (time_ms >= threshold) is applied here.
     *
     * @param  array<int, array{
     *   sql: string,
     *   bindings: array,
     *   time_ms: float,
     *   connection: string,
     * }>  $rows  Drained from QueryCollector
     */
    public function bulkInsert(int $requestId, array $rows, string $timestamp): void;

    /**
     * Slowest queries across all requests, with parent request eager-loaded.
     *
     * @return Collection<int, Query>
     */
    public function slowWithRequest(int $limit = 5): Collection;
}
