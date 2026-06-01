<?php

namespace NIN\RequestLogAnalyzer\Repositories;

use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\StepRepositoryInterface;

/**
 * StepRepository — query-builder implementation.
 *
 * Write-only from the repository's perspective; reads happen via the
 * Request Eloquent model's `steps()` HasMany relationship.
 */
class StepRepository implements StepRepositoryInterface
{
    public function bulkInsert(int $requestId, array $rows, string $timestamp): void
    {
        if (empty($rows)) {
            return;
        }

        $insert = array_map(fn (array $step) => [
            'request_id' => $requestId,
            'name' => $step['name'],
            'type' => $step['type'],
            'sequence' => $step['sequence'],
            'started_at_ms' => $step['started_at_ms'],
            'duration_ms' => $step['duration_ms'],
            'metadata' => $step['metadata']
                ? json_encode($step['metadata'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $rows);

        DB::table('rla_request_steps')->insert($insert);
    }
}
