<?php

namespace NIN\RequestLogAnalyzer\Console;

use Illuminate\Console\Command;
use NIN\RequestLogAnalyzer\Contracts\ClearServiceInterface;

/**
 * analyzer:cleanup
 *
 * Deletes analyzer records older than the configured (or supplied) retention
 * period. Designed for unattended, scheduled execution — no interactive
 * prompts by default.
 *
 * Options:
 *   --days=N    Override the retention_days value from config for this run.
 *   --dry-run   Preview the row counts that would be deleted without actually
 *               deleting anything.
 */
class CleanupCommand extends Command
{
    protected $signature = 'analyzer:cleanup
                            {--days=    : Retention period in days (overrides config)}
                            {--dry-run  : Show what would be deleted without deleting}';

    protected $description = 'Delete Request Log Analyzer records older than the configured retention period';

    public function __construct(private readonly ClearServiceInterface $clearService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = $this->resolveRetentionDays();

        if ($days === null) {
            $this->components->error(
                'No retention period configured. Set REQUEST_LOG_ANALYZER_RETENTION_DAYS '.
                'or pass --days=N.'
            );

            return self::FAILURE;
        }

        if ($days < 1) {
            $this->components->error('Retention period must be a positive integer (minimum 1 day).');

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $cutoff   = now()->subDays($days)->toDateTimeString();

        if ($isDryRun) {
            $this->components->warn("[DRY RUN] No data will be deleted.");
            $this->newLine();
        }

        $this->components->info(
            sprintf(
                '%s analyzer records older than %d day(s) (before %s).',
                $isDryRun ? 'Would delete' : 'Deleting',
                $days,
                $cutoff
            )
        );

        $totals = [];

        $this->components->task(
            $isDryRun ? 'Counting records' : 'Deleting records',
            function () use ($days, $isDryRun, &$totals): void {
                $totals = $isDryRun
                    ? $this->clearService->count($days)
                    : $this->clearService->clear($days);
            }
        );

        $this->newLine();

        $totalRows = array_sum($totals);

        if ($totalRows === 0) {
            $this->components->info('No records found outside the retention window. Nothing to do.');

            return self::SUCCESS;
        }

        $label = $isDryRun ? 'Records that would be deleted:' : 'Cleanup complete. Records deleted:';
        $this->components->success($label);

        $rows = collect($totals)
            ->map(fn ($count, $table) => [$table, number_format($count)])
            ->values()
            ->toArray();

        $this->table(['Table', $isDryRun ? 'Would Delete' : 'Deleted'], $rows);
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Resolve the effective retention period in days.
     *
     * Priority: --days CLI option > config cleanup.retention_days
     */
    private function resolveRetentionDays(): ?int
    {
        $option = $this->option('days');

        if ($option !== null) {
            return is_numeric($option) ? (int) $option : null;
        }

        $configured = config('request-log-analyzer.cleanup.retention_days');

        return is_numeric($configured) && $configured > 0 ? (int) $configured : null;
    }
}
