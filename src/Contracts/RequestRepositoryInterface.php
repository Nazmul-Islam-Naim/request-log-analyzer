<?php

namespace NIN\RequestLogAnalyzer\Contracts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use NIN\RequestLogAnalyzer\Models\Request as RequestLog;

/**
 * RequestRepositoryInterface
 *
 * Provides all persistence and retrieval operations for the `requests` table.
 * Keeps DB coupling out of middleware, controllers, and commands so that each
 * can be unit-tested against a fake/in-memory implementation.
 *
 * SRP: owns the requests table exclusively.
 * DIP: callers depend on this interface; the concrete class may use Eloquent,
 *      raw PDO, or an in-memory array for tests.
 */
interface RequestRepositoryInterface
{
    /**
     * Insert a new request row and return its auto-increment ID.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): int;

    /**
     * Retrieve recent requests with error and query counts eager-loaded.
     *
     * @return Collection<int, RequestLog>
     */
    public function recent(int $limit = 20): Collection;

    /**
     * Aggregate stats across all captured requests.
     *
     * @return array{
     *   total_requests: int,
     *   error_requests: int,
     *   error_rate_percent: float,
     *   avg_response_ms: float,
     *   total_queries: int,
     *   slow_queries: int,
     *   total_errors: int,
     *   slow_requests: int,
     * }
     */
    public function stats(): array;

    /**
     * Find a single request with its steps, queries, and errors loaded.
     *
     * @throws ModelNotFoundException
     */
    public function findWithRelations(int $id): RequestLog;

    /**
     * Return a paginated, filtered list of requests.
     *
     * Supported filter keys: method, status (2xx/3xx/4xx/5xx), uri, tag,
     * date_from, date_to, rt_min, rt_max, search (full-text across uri,
     * error messages and SQL queries).
     *
     * @param  array<string, string>  $filters
     */
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator;

    /**
     * Request counts (and error counts) per day for the last N days.
     * Used to drive the dashboard line chart.
     *
     * @return Collection<string, object{day: string, total: int, errors: int}> keyed by date string
     */
    public function groupedByDay(int $days = 7): Collection;

    /**
     * Top N URIs by hit count.
     * Used for the dashboard bar chart.
     *
     * @return Collection<int, object{uri: string, count: int}>
     */
    public function topRoutes(int $limit = 8): Collection;

    /**
     * Top N countries by request count (excludes null countries).
     *
     * @return Collection<int, object{country: string, count: int}>
     */
    public function countryStats(int $limit = 10): Collection;

    /**
     * All countries with request counts, optionally filtered by date range.
     *
     * @return Collection<int, object{country: string, count: int}>
     */
    public function countryStatsAll(?string $from = null, ?string $to = null): Collection;

    /**
     * Top N routes by hit count with avg/min/max response times.
     * Used for the analytics page.
     *
     * @return Collection<int, object{uri: string, hit_count: int, avg_ms: float, min_ms: int, max_ms: int}>
     */
    public function analyticsTopByCount(int $limit = 15): Collection;

    /**
     * Top N routes sorted by average response time (slowest first).
     * Used for the analytics page.
     *
     * @return Collection<int, object{uri: string, hit_count: int, avg_ms: float}>
     */
    public function analyticsTopByAvgMs(int $limit = 15): Collection;

    /**
     * Count of unique URIs ever recorded.
     */
    public function uniqueRouteCount(): int;

    /**
     * Fastest route by average response time.
     *
     * @return object{uri: string, avg_ms: float}|null
     */
    public function fastestRoute(): ?object;

    /**
     * Paginated list of slow requests (response_time_ms >= threshold).
     * Sorted by response_time_ms descending.
     */
    public function slowRequestsPaginate(int $perPage = 50): LengthAwarePaginator;

    /**
     * Count of requests exceeding the slow-request threshold.
     */
    public function slowRequestsCount(): int;

    /**
     * Average response time (ms) per day for the last N days.
     * Used for the dashboard avg-response line chart.
     *
     * @return Collection<string, object{day: string, avg_ms: int}> keyed by date string
     */
    public function avgResponseByDay(int $days = 7): Collection;

    /**
     * Error (4xx/5xx) count per hour for the last N hours.
     * Used for the dashboard error-trend bar chart.
     *
     * @return Collection<string, object{slot: string, cnt: int}> keyed by 'Y-m-d H:00:00' string
     */
    public function hourlyErrors(int $hours = 24): Collection;
}
