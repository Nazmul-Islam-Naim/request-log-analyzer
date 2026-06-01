<?php

namespace NIN\RequestLogAnalyzer\Contracts;

/**
 * StepRepositoryInterface
 *
 * Owns all persistence for the `request_steps` table.
 * Read access happens via the Request Eloquent relationship rather than
 * a dedicated method here (ISP — keep the interface minimal).
 */
interface StepRepositoryInterface
{
    /**
     * Bulk-insert a batch of lifecycle step records linked to a request.
     *
     * @param  array<int, array{
     *   name: string,
     *   type: string,
     *   sequence: int,
     *   started_at_ms: int,
     *   duration_ms: int,
     *   metadata: array|null,
     * }>  $rows  Drained from StepCollector
     */
    public function bulkInsert(int $requestId, array $rows, string $timestamp): void;
}
