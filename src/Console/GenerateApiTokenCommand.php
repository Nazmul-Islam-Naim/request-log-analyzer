<?php

namespace NIN\RequestLogAnalyzer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiTokenCommand extends Command
{
    protected $signature   = 'analyzer:token {--show : Print the current token without generating a new one}';
    protected $description = 'Generate a secure API token for the Request Log Analyzer API';

    public function handle(): int
    {
        if ($this->option('show')) {
            $current = config('request-log-analyzer.api.token', '');

            if (empty($current)) {
                $this->warn('No API token is set. Run without --show to generate one.');

                return self::FAILURE;
            }

            $this->line('<comment>Current token:</comment> '.$current);

            return self::SUCCESS;
        }

        $token = Str::random(64);

        $this->line('');
        $this->info('New API token generated:');
        $this->line('');
        $this->line("  <comment>{$token}</comment>");
        $this->line('');
        $this->info('Add this to your .env file:');
        $this->line('');
        $this->line("  REQUEST_LOG_ANALYZER_API_TOKEN={$token}");
        $this->line('');
        $this->warn('Keep this token secret — it grants read access to all log data.');

        return self::SUCCESS;
    }
}
