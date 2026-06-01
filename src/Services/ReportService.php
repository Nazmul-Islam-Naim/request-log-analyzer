<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\ReportServiceInterface;

/**
 * ReportService — data-gathering logic for the CLI report.
 *
 * Extracted from ReportCommand so that:
 *  - The command remains a thin presentation layer (SRP).
 *  - The aggregate queries are independently testable.
 *  - Other consumers (webhooks, scheduled jobs) can reuse the same data.
 */
class ReportService implements ReportServiceInterface
{
    public function generate(?int $days = null): array
    {
        $since = $days ? now()->subDays($days)->startOfDay() : null;
        $label = $since ? "last {$days} day(s)" : 'all time';

        $reqQ = DB::table('rla_requests');
        $qryQ = DB::table('rla_queries');
        $errQ = DB::table('rla_errors');

        if ($since) {
            $reqQ->where('created_at', '>=', $since);
            $qryQ->where('created_at', '>=', $since);
            $errQ->where('created_at', '>=', $since);
        }

        $totalRequests = (clone $reqQ)->count();
        $errorRequests = (clone $reqQ)->where('status_code', '>=', 400)->count();
        $avgResponseMs = round((float) (clone $reqQ)->avg('response_time_ms'), 2);
        $maxResponseMs = (clone $reqQ)->max('response_time_ms') ?? 0;
        $p95Ms = $this->percentile(clone $reqQ, 'response_time_ms', 95);
        $totalQueries = (clone $qryQ)->count();
        $slowQueries = (clone $qryQ)->where('is_slow', true)->count();
        $totalErrors = (clone $errQ)->count();
        $errorRate = $totalRequests > 0
            ? round(($errorRequests / $totalRequests) * 100, 2)
            : 0;

        $topRoutes = (clone $reqQ)
            ->selectRaw(
                'uri, COUNT(*) as hits, '.
                'ROUND(AVG(response_time_ms), 1) as avg_ms, '.
                'MAX(response_time_ms) as max_ms'
            )
            ->groupBy('uri')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();

        $slowestRoutes = (clone $reqQ)
            ->selectRaw('uri, COUNT(*) as hits, ROUND(AVG(response_time_ms), 1) as avg_ms')
            ->whereNotNull('response_time_ms')
            ->groupBy('uri')
            ->orderByDesc('avg_ms')
            ->limit(5)
            ->get();

        $recentErrors = (clone $errQ)
            ->select('severity', 'exception_class', 'message', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $countries = (clone $reqQ)
            ->selectRaw('country, COUNT(*) as hits')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();

        return [
            'period' => $label,
            'generated_at' => now()->toIso8601String(),
            'summary' => compact(
                'totalRequests', 'errorRequests', 'errorRate',
                'avgResponseMs', 'maxResponseMs', 'p95Ms',
                'totalQueries', 'slowQueries', 'totalErrors'
            ),
            'top_routes' => $topRoutes,
            'slowest_routes' => $slowestRoutes,
            'recent_errors' => $recentErrors,
            'countries' => $countries,
        ];
    }

    /**
     * Approximate the Nth percentile of a column via LIMIT/OFFSET.
     * Fast enough for dashboards; accurate to ±1 row.
     */
    private function percentile(Builder $query, string $column, int $pct): ?int
    {
        $total = (clone $query)->count();

        if ($total === 0) {
            return null;
        }

        $offset = max(0, (int) ceil(($pct / 100) * $total) - 1);
        $value = (clone $query)->orderBy($column)->offset($offset)->limit(1)->value($column);

        return $value !== null ? (int) $value : null;
    }
}
