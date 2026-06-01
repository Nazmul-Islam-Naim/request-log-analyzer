<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\ClearServiceInterface;

/**
 * ClearService — data-deletion logic for the CLI clear command.
 *
 * Deletes in FK-safe order: child tables first, `requests` last.
 * Extracted so ClearCommand is reduced to user interaction only (SRP).
 */
class ClearService implements ClearServiceInterface
{
    /** Deletion order respects foreign-key constraints. */
    private const TABLES = [
        'rla_request_steps',
        'rla_queries',
        'rla_errors',
        'rla_requests',
    ];

    public function clear(?int $olderThanDays = null): array
    {
        $connection = config('request-log-analyzer.db_connection');
        $cutoff = $olderThanDays ? now()->subDays($olderThanDays) : null;
        $totals = [];

        foreach (self::TABLES as $table) {
            $qb = DB::connection($connection)->table($table);

            if ($cutoff) {
                // Each table has its own created_at; delete by that column
                // rather than joining back to requests — simpler and still
                // keeps the data consistent for the most common use-case.
                $qb->where('created_at', '<', $cutoff);
            }

            $totals[$table] = $qb->count();
            $qb->delete();
        }

        return $totals;
    }

    public function count(?int $olderThanDays = null): array
    {
        $connection = config('request-log-analyzer.db_connection');
        $cutoff = $olderThanDays ? now()->subDays($olderThanDays) : null;
        $totals = [];

        foreach (self::TABLES as $table) {
            $qb = DB::connection($connection)->table($table);

            if ($cutoff) {
                $qb->where('created_at', '<', $cutoff);
            }

            $totals[$table] = $qb->count();
        }

        return $totals;
    }
}
