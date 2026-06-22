<?php

namespace NIN\RequestLogAnalyzer\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'analyzer:install
                            {--force : Overwrite existing config, migration, and view files}
                            {--no-migrate : Skip running migrations}';

    protected $description = 'Install the Request Log Analyzer: publish config, migrations and run migrations';

    public function handle(): int
    {
        $this->components->info('Installing Request Log Analyzer…');

        // ── 1. Publish config ─────────────────────────────────────────────
        $this->components->task('Publishing config file', function () {
            $args = [
                '--tag'      => 'request-log-analyzer-config',
                '--provider' => 'NIN\\RequestLogAnalyzer\\RequestLogAnalyzerServiceProvider',
            ];

            if ($this->option('force')) {
                $args['--force'] = true;
            }

            $this->call('vendor:publish', $args);
        });

        // ── 2. Publish migrations ─────────────────────────────────────────
        $this->components->task('Publishing migrations', function () {
            $args = [
                '--tag'      => 'request-log-analyzer-migrations',
                '--provider' => 'NIN\\RequestLogAnalyzer\\RequestLogAnalyzerServiceProvider',
            ];

            if ($this->option('force')) {
                $args['--force'] = true;
            }

            $this->call('vendor:publish', $args);
        });

        // ── 3. Publish views ──────────────────────────────────────────────
        // Always force-publish views so that stale files from a previous install
        // (or older package version) do not shadow new view files in the package.
        $this->components->task('Publishing views', function () {
            $this->call('vendor:publish', [
                '--tag'      => 'request-log-analyzer-views',
                '--provider' => 'NIN\\RequestLogAnalyzer\\RequestLogAnalyzerServiceProvider',
                '--force'    => true,
            ]);
        });

        // ── 4. Publish assets (CSS/JS) ────────────────────────────────────
        $this->components->task('Publishing assets', function () {
            $this->call('vendor:publish', [
                '--tag'      => 'request-log-analyzer-assets',
                '--provider' => 'NIN\\RequestLogAnalyzer\\RequestLogAnalyzerServiceProvider',
                '--force'    => true,
            ]);
        });

        // ── 5. Run migrations ─────────────────────────────────────────────
        if (! $this->option('no-migrate')) {
            $this->components->task('Running package migrations', function () {
                $this->call('migrate', [
                    '--force' => true,
                    '--path'  => 'database/migrations/request-log-analyzer',
                ]);
            });
        } else {
            $this->components->warn('Skipped migrations (--no-migrate flag set).');
        }

        $this->newLine();
        $this->components->success('Request Log Analyzer installed successfully.');
        $this->line('  <fg=gray>➜ Add <fg=white>\\NIN\\RequestLogAnalyzer\\Http\\Middleware\\TrackRequest</> to your middleware stack.</>');
        $this->line('  <fg=gray>➜ Dashboard available at: <fg=white>/'.config('request-log-analyzer.route_prefix', 'request-log-analyzer').'</></>');
        $this->newLine();

        return self::SUCCESS;
    }
}