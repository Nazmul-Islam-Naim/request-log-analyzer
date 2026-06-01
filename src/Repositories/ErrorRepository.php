<?php

namespace NIN\RequestLogAnalyzer\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\ErrorRepositoryInterface;
use NIN\RequestLogAnalyzer\Models\Error;

/**
 * ErrorRepository — Eloquent + query-builder implementation.
 */
class ErrorRepository implements ErrorRepositoryInterface
{
    public function bulkInsert(int $requestId, array $rows, string $timestamp): void
    {
        if (empty($rows)) {
            return;
        }

        $insert = array_map(fn (array $item) => [
            'request_id' => $requestId,
            'exception_class' => $item['exception_class'],
            'message' => $item['message'],
            'file' => $item['file'],
            'line' => $item['line'],
            'trace' => $item['trace'],
            'context' => $item['context'] !== null
                ? json_encode($item['context'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : null,
            'severity' => $item['severity'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $rows);

        DB::table('rla_errors')->insert($insert);
    }

    public function recentWithRequest(int $limit = 5): Collection
    {
        return Error::with('request')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
