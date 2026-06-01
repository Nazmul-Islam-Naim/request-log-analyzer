<?php

namespace NIN\RequestLogAnalyzer\Console;

use Illuminate\Console\Command;
use NIN\RequestLogAnalyzer\Contracts\ClearServiceInterface;

/**
 * analyzer:clear
 *
 * Delegates all data deletion to ClearService (SRP: this command is UI only).
 *
 * Options:
 *   --older-than=N  Only delete records older than N days
 *   --force         Skip confirmation prompt
 */
class ClearCommand extends Command
{
    protected $signature = 'analyzer:clear
                            {--older-than= : Only clear records older than this many days}
                            {--force       : Skip confirmation prompt}';

    protected $description = 'Clear all (or aged) Request Log Analyzer data from the database';

    public function __construct(private readonly ClearServiceInterface $clearService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $olderThan = $this->option('older-than');

        if ($olderThan !== null && (! is_numeric($olderThan) || (int) $olderThan < 1)) {
            $this->components->error('--older-than must be a positive integer (number of days).');

            return self::FAILURE;
        }

        $scope = $olderThan
            ? "records older than {$olderThan} day(s)"
            : 'ALL records';

        if (! $this->option('force') && ! $this->confirm("This will permanently delete {$scope} from all analyzer tables. Continue?")) {
            $this->components->warn('Aborted. No data was deleted.');

            return self::SUCCESS;
        }

        $days = $olderThan !== null ? (int) $olderThan : null;
        $totals = [];

        $this->components->task('Clearing analyzer tables', function () use ($days, &$totals): void {
            $totals = $this->clearService->clear($days);
        });

        $this->newLine();
        $this->components->success('Clear complete. Records deleted:');

        $rows = collect($totals)
            ->map(fn ($count, $table) => [$table, number_format($count)])
            ->values()
            ->toArray();

        $this->table(['Table', 'Deleted'], $rows);
        $this->newLine();

        return self::SUCCESS;
    }
}
