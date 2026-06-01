<?php

namespace NIN\RequestLogAnalyzer\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\QueryRepositoryInterface;
use NIN\RequestLogAnalyzer\Models\Query;

/**
 * QueryRepository — Eloquent + query-builder implementation.
 *
 * Slow-query detection (time_ms >= threshold) lives here — the threshold
 * is read from config once per bulkInsert call rather than per-row.
 */
class QueryRepository implements QueryRepositoryInterface
{
    public function bulkInsert(int $requestId, array $rows, string $timestamp): void
    {
        if (empty($rows)) {
            return;
        }

        $slowThreshold = (float) config('request-log-analyzer.slow_query_threshold_ms', 200);

        $insert = array_map(fn (array $q) => [
            'request_id' => $requestId,
            'connection' => $q['connection'],
            'sql' => $q['sql'],
            'bindings' => empty($q['bindings'])
                ? null
                : json_encode($q['bindings'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'time_ms' => $q['time_ms'],
            'is_slow' => $q['time_ms'] >= $slowThreshold ? 1 : 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $rows);

        DB::table('rla_queries')->insert($insert);
    }

    public function slowWithRequest(int $limit = 5): Collection
    {
        return Query::with('request')
            ->where('is_slow', true)
            ->orderByDesc('time_ms')
            ->limit($limit)
            ->get();
    }
}
