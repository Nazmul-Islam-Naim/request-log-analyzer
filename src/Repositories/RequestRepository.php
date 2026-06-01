<?php

namespace NIN\RequestLogAnalyzer\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\RequestRepositoryInterface;
use NIN\RequestLogAnalyzer\Models\Query;
use NIN\RequestLogAnalyzer\Models\Request as RequestLog;

/**
 * RequestRepository — Eloquent + query-builder implementation.
 *
 * All raw DB::table() calls for the `requests` table live here, keeping
 * middleware, controllers, and console commands free of persistence details.
 */
class RequestRepository implements RequestRepositoryInterface
{
    public function create(array $data): int
    {
        return DB::table('rla_requests')->insertGetId($data);
    }

    public function recent(int $limit = 20): Collection
    {
        return RequestLog::withCount(['errors', 'queries'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function stats(): array
    {
        // Single aggregate pass on rla_requests (3 queries total, down from 6).
        $req = DB::table('rla_requests')->selectRaw(
            'COUNT(*) as total, '.
            'SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors, '.
            'ROUND(AVG(response_time_ms), 2) as avg_ms'
        )->first();

        // Single aggregate pass on rla_queries.
        $qry = DB::table('rla_queries')->selectRaw(
            'COUNT(*) as total, SUM(is_slow) as slow_count'
        )->first();

        $errCount = DB::table('rla_errors')->count();

        $total = (int) ($req->total ?? 0);
        $errors = (int) ($req->errors ?? 0);
        $avgTime = (float) ($req->avg_ms ?? 0);
        $totalQs = (int) ($qry->total ?? 0);
        $slowQs = (int) ($qry->slow_count ?? 0);

        $slowThreshold = (int) config('request-log-analyzer.slow_request_threshold_ms', 500);
        $slowRequests = $slowThreshold > 0
            ? (int) DB::table('rla_requests')->where('response_time_ms', '>=', $slowThreshold)->count()
            : 0;

        return [
            'total_requests' => $total,
            'error_requests' => $errors,
            'error_rate_percent' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
            'avg_response_ms' => $avgTime,
            'total_queries' => $totalQs,
            'slow_queries' => $slowQs,
            'total_errors' => $errCount,
            'slow_requests' => $slowRequests,
        ];
    }

    public function findWithRelations(int $id): RequestLog
    {
        return RequestLog::with(['queries', 'errors', 'steps'])->findOrFail($id);
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = RequestLog::withCount(['errors', 'queries'])->latest();

        // ── HTTP method ────────────────────────────────────────────────────
        if ($method = ($filters['method'] ?? null)) {
            $query->where('method', strtoupper($method));
        }

        // ── Status code: exact beats range ─────────────────────────────────
        // status_code index (scalar) is cheaper than BETWEEN; if both are
        // supplied the exact value is the tighter constraint.
        if (($sc = $filters['status_code'] ?? null) !== null && $sc !== '') {
            $query->where('status_code', (int) $sc);
        } elseif ($status = ($filters['status'] ?? null)) {
            match ($status) {
                '2xx' => $query->whereBetween('status_code', [200, 299]),
                '3xx' => $query->whereBetween('status_code', [300, 399]),
                '4xx' => $query->whereBetween('status_code', [400, 499]),
                '5xx' => $query->whereBetween('status_code', [500, 599]),
                default => null,
            };
        }

        // ── URI / route ────────────────────────────────────────────────────
        // Uses the uri(200) prefix index for moderate selectivity.
        if ($uri = ($filters['uri'] ?? null)) {
            $query->where('uri', 'like', '%'.$uri.'%');
        }

        // ── Tag (JSON column) ─────────────────────────────────────────────
        if ($tag = ($filters['tag'] ?? null)) {
            $driver = $query->getModel()->getConnection()->getDriverName();
            if ($driver === 'sqlite') {
                $query->where('tags', 'like', '%'.addslashes($tag).'%');
            } else {
                $query->whereRaw('JSON_CONTAINS(tags, ?)', [json_encode($tag)]);
            }
        }

        // ── Date range — uses created_at index ────────────────────────────
        if ($dateFrom = ($filters['date_from'] ?? null)) {
            // Accept YYYY-MM-DD; cast to start-of-day for the inclusive bound.
            $query->where('created_at', '>=', $dateFrom.' 00:00:00');
        }
        if ($dateTo = ($filters['date_to'] ?? null)) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        // ── Response time range — uses idx_requests_response_time ─────────
        if (($rtMin = $filters['rt_min'] ?? null) !== null && $rtMin !== '') {
            $query->where('response_time_ms', '>=', (int) $rtMin);
        }
        if (($rtMax = $filters['rt_max'] ?? null) !== null && $rtMax !== '') {
            $query->where('response_time_ms', '<=', (int) $rtMax);
        }

        // ── Full-text search: URI, error messages, SQL queries ─────────────
        // MySQL: MATCH…AGAINST in boolean mode with FULLTEXT indexes.
        // SQLite / other: LIKE fallback (no index, but correct results).
        if ($search = ($filters['search'] ?? null)) {
            $driver = $query->getModel()->getConnection()->getDriverName();

            $query->where(function ($q) use ($search, $driver): void {
                if ($driver === 'mysql') {
                    // Append * so short prefixes still match (boolean mode wildcard).
                    $term = trim($search).'*';

                    $q->whereRaw('MATCH(uri) AGAINST (? IN BOOLEAN MODE)', [$term])
                      ->orWhereExists(fn ($sub) => $sub
                          ->selectRaw('1')
                          ->from('rla_errors')
                          ->whereColumn('request_id', 'rla_requests.id')
                          ->whereRaw('MATCH(message) AGAINST (? IN BOOLEAN MODE)', [$term])
                      )
                      ->orWhereExists(fn ($sub) => $sub
                          ->selectRaw('1')
                          ->from('rla_queries')
                          ->whereColumn('request_id', 'rla_requests.id')
                          ->whereRaw('MATCH(`sql`) AGAINST (? IN BOOLEAN MODE)', [$term])
                      );
                } else {
                    $like = '%'.addcslashes($search, '\\%_').'%';

                    $q->where('uri', 'like', $like)
                      ->orWhereExists(fn ($sub) => $sub
                          ->selectRaw('1')
                          ->from('rla_errors')
                          ->whereColumn('request_id', 'rla_requests.id')
                          ->where('message', 'like', $like)
                      )
                      ->orWhereExists(fn ($sub) => $sub
                          ->selectRaw('1')
                          ->from('rla_queries')
                          ->whereColumn('request_id', 'rla_requests.id')
                          ->where('sql', 'like', $like)
                      );
                }
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function groupedByDay(int $days = 7): Collection
    {
        return DB::table('rla_requests')
            ->selectRaw(
                'DATE(created_at) as day, COUNT(*) as total, '.
                'SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors'
            )
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');
    }

    public function topRoutes(int $limit = 8): Collection
    {
        $cacheKey = "rla.top_routes.{$limit}";

        $rows = Cache::remember($cacheKey, 300, fn () => DB::table('rla_requests')
            ->selectRaw('uri, COUNT(*) as count')
            ->groupBy('uri')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->all()  // plain PHP array of stdClass — survives all cache drivers
        );

        // Guard against stale or corrupt cached values (e.g. a serialized
        // Collection from an older code version, or __PHP_Incomplete_Class
        // objects produced when the database cache driver deserializes data
        // whose class is no longer resolvable). Discard and re-fetch.
        if (! is_array($rows) || $this->hasStaleCacheRows($rows)) {
            Cache::forget($cacheKey);
            $rows = DB::table('rla_requests')
                ->selectRaw('uri, COUNT(*) as count')
                ->groupBy('uri')
                ->orderByDesc('count')
                ->limit($limit)
                ->get()
                ->all();
        }

        return collect($rows);
    }

    public function countryStats(int $limit = 10): Collection
    {
        $cacheKey = "rla.country_stats.{$limit}";

        $rows = Cache::remember($cacheKey, 300, fn () => DB::table('rla_requests')
            ->selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->all()  // plain PHP array of stdClass — survives all cache drivers
        );

        // Same stale-cache guard as topRoutes().
        if (! is_array($rows) || $this->hasStaleCacheRows($rows)) {
            Cache::forget($cacheKey);
            $rows = DB::table('rla_requests')
                ->selectRaw('country, COUNT(*) as count')
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->groupBy('country')
                ->orderByDesc('count')
                ->limit($limit)
                ->get()
                ->all();
        }

        return collect($rows);
    }

    public function countryStatsAll(?string $from = null, ?string $to = null): Collection
    {
        $query = DB::table('rla_requests')
            ->selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('count');

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->get();
    }

    /**
     * Return true if the first element of a cached rows array is not a plain,
     * fully-resolved stdClass object. This catches two failure modes:
     *
     *  1. An old Collection was stored before the ->all() call was added.
     *  2. The database cache driver deserialized the data into
     *     __PHP_Incomplete_Class objects (class not found at unserialize time).
     */
    private function hasStaleCacheRows(array $rows): bool
    {
        if (empty($rows)) {
            return false;
        }

        $first = reset($rows);

        // get_class() on __PHP_Incomplete_Class returns '__PHP_Incomplete_Class';
        // on a normal stdClass it returns 'stdClass'.
        return ! is_object($first) || get_class($first) === '__PHP_Incomplete_Class';
    }

    public function analyticsTopByCount(int $limit = 15): Collection
    {
        return DB::table('rla_requests')
            ->selectRaw(
                'uri, COUNT(*) as hit_count, '.
                'ROUND(AVG(response_time_ms), 1) as avg_ms, '.
                'MIN(response_time_ms) as min_ms, '.
                'MAX(response_time_ms) as max_ms'
            )
            ->whereNotNull('response_time_ms')
            ->groupBy('uri')
            ->orderByDesc('hit_count')
            ->limit($limit)
            ->get();
    }

    public function analyticsTopByAvgMs(int $limit = 15): Collection
    {
        return DB::table('rla_requests')
            ->selectRaw(
                'uri, COUNT(*) as hit_count, '.
                'ROUND(AVG(response_time_ms), 1) as avg_ms'
            )
            ->whereNotNull('response_time_ms')
            ->groupBy('uri')
            ->orderByDesc('avg_ms')
            ->limit($limit)
            ->get();
    }

    public function uniqueRouteCount(): int
    {
        return DB::table('rla_requests')->distinct()->count('uri');
    }

    public function fastestRoute(): ?object
    {
        return DB::table('rla_requests')
            ->selectRaw('uri, ROUND(AVG(response_time_ms), 1) as avg_ms')
            ->whereNotNull('response_time_ms')
            ->groupBy('uri')
            ->orderBy('avg_ms')
            ->first();
    }

    public function slowRequestsPaginate(int $perPage = 50): LengthAwarePaginator
    {
        $threshold = (int) config('request-log-analyzer.slow_request_threshold_ms', 500);

        return RequestLog::withCount(['errors', 'queries'])
            ->where('response_time_ms', '>=', max(1, $threshold))
            ->orderByDesc('response_time_ms')
            ->paginate($perPage);
    }

    public function slowRequestsCount(): int
    {
        $threshold = (int) config('request-log-analyzer.slow_request_threshold_ms', 500);

        if ($threshold <= 0) {
            return 0;
        }

        return DB::table('rla_requests')
            ->where('response_time_ms', '>=', $threshold)
            ->count();
    }

    public function avgResponseByDay(int $days = 7): Collection
    {
        return DB::table('rla_requests')
            ->selectRaw('DATE(created_at) as day, ROUND(AVG(response_time_ms)) as avg_ms')
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->whereNotNull('response_time_ms')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');
    }

    public function hourlyErrors(int $hours = 24): Collection
    {
        return DB::table('rla_requests')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as slot, COUNT(*) as cnt")
            ->where('created_at', '>=', now()->subHours($hours - 1)->startOfHour())
            ->where('status_code', '>=', 400)
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')")
            ->get()
            ->keyBy('slot');
    }
}
