<?php

namespace NIN\RequestLogAnalyzer\Contracts;

use Illuminate\Support\Collection;
use NIN\RequestLogAnalyzer\Models\Error;

/**
 * ErrorRepositoryInterface
 *
 * Owns all persistence and retrieval for the `errors` table.
 * Bulk-insert is the primary write path (called from middleware or jobs);
 * recentWithRequest is the primary read path (dashboard).
 */
interface ErrorRepositoryInterface
{
    /**
     * Bulk-insert a batch of exception records linked to a request.
     *
     * @param  array<int, array{
     *   exception_class: string,
     *   message: string,
     *   file: string,
     *   line: int,
     *   trace: string,
     *   context: array|null,
     *   severity: string,
     * }>  $rows  Drained from ExceptionCollector
     */
    public function bulkInsert(int $requestId, array $rows, string $timestamp): void;

    /**
     * Most recent errors with their parent request loaded.
     *
     * @return Collection<int, Error>
     */
    public function recentWithRequest(int $limit = 5): Collection;
}
