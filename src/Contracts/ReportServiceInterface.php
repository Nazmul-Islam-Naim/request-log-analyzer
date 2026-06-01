<?php

namespace NIN\RequestLogAnalyzer\Contracts;

use Illuminate\Support\Collection;

/**
 * ReportServiceInterface
 *
 * Encapsulates the data-gathering logic for the CLI report command.
 * Extracted into a service so that:
 *  - ReportCommand is reduced to presentation only (thin command, SRP).
 *  - The same data can be consumed by scheduled tasks, webhooks, or tests
 *    without instantiating a console command.
 *  - The aggregate queries can be unit-tested independently of Artisan.
 */
interface ReportServiceInterface
{
    /**
     * Compile the full report dataset.
     *
     * @param  int|null  $days  Limit data to the last N days; null = all time.
     * @return array{
     *   period: string,
     *   generated_at: string,
     *   summary: array{
     *     totalRequests: int,
     *     errorRequests: int,
     *     errorRate: float,
     *     avgResponseMs: float,
     *     maxResponseMs: int,
     *     p95Ms: int|null,
     *     totalQueries: int,
     *     slowQueries: int,
     *     totalErrors: int,
     *   },
     *   top_routes: Collection,
     *   slowest_routes: Collection,
     *   recent_errors: Collection,
     *   countries: Collection,
     * }
     */
    public function generate(?int $days = null): array;
}
