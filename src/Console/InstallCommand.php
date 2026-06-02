<?php

namespace NIN\RequestLogAnalyzer\Console;

use Illuminate\Console\Command;

/**
 * analyzer:install
 *
 * One-shot setup:
 *  1. Publishes the package config to config/request-log-analyzer.php
 *  2. Runs any pending migrations for the package tables
 */
class InstallCommand extends Command
{
    protected $signature = 'analyzer:install
                            {--force : Overwrite existing config file}
                            {--no-migrate : Skip running migrations}';

    protected $description = 'Install the Request Log Analyzer: publish config and run migrations';

    public function handle(): int
    {
        $this->components->info('Installing Request Log Analyzer…');
        $migrationsPath = __DIR__.'/../../database/migrations';

        // ── 1. Publish config ─────────────────────────────────────────────
        $this->components->task('Publishing config file', function () {
            $args = ['--tag' => 'request-log-analyzer-config', '--provider' => 'NIN\\RequestLogAnalyzer\\RequestLogAnalyzerServiceProvider'];

            if ($this->option('force')) {
                $args['--force'] = true;
            }

            $this->call('vendor:publish', $args);
        });

        // ── 2. Run migrations ─────────────────────────────────────────────
        if (! $this->option('no-migrate')) {
            $this->components->task('Running package migrations', function () use ($migrationsPath) {
                $this->call('migrate', [
                    '--force' => true,
                    '--path' => $migrationsPath,
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
