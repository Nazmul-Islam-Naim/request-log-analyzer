<?php

namespace NIN\RequestLogAnalyzer\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use NIN\RequestLogAnalyzer\Contracts\ReportServiceInterface;

/**
 * analyzer:report
 *
 * Delegates all data gathering to ReportService (SRP: this command is UI only).
 *
 * Options:
 *   --days=N  Limit stats to the last N days (default: all time)
 *   --json    Output raw JSON instead of tables
 */
class ReportCommand extends Command
{
    protected $signature = 'analyzer:report
                            {--days=   : Limit the report to the last N days}
                            {--json    : Output as JSON instead of formatted tables}';

    protected $description = 'Show a summary report of captured request log data';

    public function __construct(private readonly ReportServiceInterface $reportService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = $this->option('days');

        if ($days !== null && (! is_numeric($days) || (int) $days < 1)) {
            $this->components->error('--days must be a positive integer.');

            return self::FAILURE;
        }

        $data = $this->reportService->generate($days !== null ? (int) $days : null);

        $label = $data['period'];
        $summary = $data['summary'];
        $topRoutes = $data['top_routes'];
        $slowestRoutes = $data['slowest_routes'];
        $recentErrors = $data['recent_errors'];
        $countries = $data['countries'];

        $totalRequests = $summary['totalRequests'];
        $errorRequests = $summary['errorRequests'];
        $errorRate = $summary['errorRate'];
        $avgResponseMs = $summary['avgResponseMs'];
        $maxResponseMs = $summary['maxResponseMs'];
        $p95Ms = $summary['p95Ms'];
        $totalQueries = $summary['totalQueries'];
        $slowQueries = $summary['slowQueries'];
        $totalErrors = $summary['totalErrors'];

        // â”€â”€ JSON output path â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        // â”€â”€ Formatted console output â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $this->newLine();
        $this->line('  <fg=blue;options=bold>Request Log Analyzer â€” Report</>');
        $this->line("  <fg=gray>Period: {$label}</>");
        $this->line('  <fg=gray>Generated: '.now()->format('Y-m-d H:i:s').'</>');
        $this->newLine();

        $this->components->info('Summary');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests',    number_format($totalRequests)],
                ['Error Requests',    number_format($errorRequests)],
                ['Error Rate',        $errorRate.'%'],
                ['Avg Response Time', $avgResponseMs.' ms'],
                ['95th Percentile',   ($p95Ms !== null ? $p95Ms.' ms' : 'n/a')],
                ['Max Response Time', $maxResponseMs.' ms'],
                ['Total DB Queries',  number_format($totalQueries)],
                ['Slow DB Queries',   number_format($slowQueries)],
                ['Total Exceptions',  number_format($totalErrors)],
            ]
        );

        if ($topRoutes->isNotEmpty()) {
            $this->newLine();
            $this->components->info('Top 10 Routes by Hit Count');
            $this->table(
                ['#', 'URI', 'Hits', 'Avg ms', 'Max ms'],
                $topRoutes->map(fn ($r, $i) => [
                    $i + 1,
                    mb_strimwidth($r->uri, 0, 60, 'â€¦'),
                    number_format($r->hits),
                    number_format($r->avg_ms, 1),
                    number_format($r->max_ms),
                ])->toArray()
            );
        }

        if ($slowestRoutes->isNotEmpty()) {
            $this->newLine();
            $this->components->info('Top 5 Slowest Routes (by Avg Response Time)');
            $this->table(
                ['#', 'URI', 'Hits', 'Avg ms'],
                $slowestRoutes->map(fn ($r, $i) => [
                    $i + 1,
                    mb_strimwidth($r->uri, 0, 60, 'â€¦'),
                    number_format($r->hits),
                    number_format($r->avg_ms, 1),
                ])->toArray()
            );
        }

        if ($recentErrors->isNotEmpty()) {
            $this->newLine();
            $this->components->info('5 Most Recent Exceptions');
            $this->table(
                ['Severity', 'Class', 'Message', 'When'],
                $recentErrors->map(fn ($e) => [
                    strtoupper($e->severity),
                    class_basename($e->exception_class),
                    mb_strimwidth($e->message, 0, 50, 'â€¦'),
                    Carbon::parse($e->created_at)->diffForHumans(),
                ])->toArray()
            );
        }

        if ($countries->isNotEmpty()) {
            $this->newLine();
            $this->components->info('Country Distribution (top 10)');
            $maxHits = $countries->max('hits');
            $this->table(
                ['#', 'Country', 'Requests', 'Share', 'Bar'],
                $countries->map(fn ($c, $i) => [
                    $i + 1,
                    $c->country,
                    number_format($c->hits),
                    $totalRequests > 0 ? round($c->hits / $totalRequests * 100, 1).'%' : '0%',
                    $this->bar($c->hits, $maxHits, 20),
                ])->toArray()
            );
        }

        $this->newLine();
        $this->line('  <fg=gray>Tip: run <fg=white>php artisan analyzer:report --days=7</> for a weekly view, or <fg=white>--json</> for machine-readable output.</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /** Return a simple ASCII progress bar. */
    private function bar(int $value, int $max, int $width = 20): string
    {
        if ($max === 0) {
            return str_repeat('-', $width);
        }
        $filled = (int) round($value / $max * $width);

        return str_repeat('#', $filled).str_repeat('-', $width - $filled);
    }
}
