<?php

namespace NIN\RequestLogAnalyzer\Console;

use Illuminate\Console\Command;
use NIN\RequestLogAnalyzer\Services\AlertNotifier;

class TestAlertCommand extends Command
{
    protected $signature = 'analyzer:test-alert {type=error : Type of alert to test (error or slow)}';

    protected $description = 'Test alert system by sending a sample alert';

    public function handle(): int
    {
        $type = strtolower($this->argument('type'));

        if (! in_array($type, ['error', 'slow'])) {
            $this->error('Type must be "error" or "slow"');
            return 1;
        }

        $notifier = app(AlertNotifier::class);

        $this->info("Testing {$type} alert...");
        $this->newLine();

        if ($type === 'error') {
            $alertData = [
                'type' => 'errors',
                'count' => 12,
                'threshold' => 10,
                'time_window' => 5,
                'message' => 'Alert: 12 errors detected in the last 5 minutes (threshold: 10)',
            ];
            $notifier->sendErrorAlert($alertData);
        } else {
            $alertData = [
                'type' => 'slow_requests',
                'count' => 8,
                'threshold' => 5,
                'time_window' => 5,
                'slow_threshold_ms' => 500,
                'message' => 'Alert: 8 slow requests (>500ms) detected in the last 5 minutes (threshold: 5)',
            ];
            $notifier->sendSlowAlert($alertData);
        }

        $channels = config('request-log-analyzer.alerts.channels', ['log']);

        $this->info('Alert sent via: ' . implode(', ', $channels));
        $this->newLine();

        if (in_array('email', $channels)) {
            $emailTo = config('request-log-analyzer.alerts.email_config.to', []);
            $this->line('<fg=cyan>Email recipients:</> ' . implode(', ', $emailTo));
        }

        if (in_array('discord', $channels)) {
            $webhook = config('request-log-analyzer.alerts.discord_config.webhook_url', '');
            if ($webhook) {
                $this->line('<fg=cyan>Discord webhook:</> ' . substr($webhook, 0, 50) . '...');
            } else {
                $this->line('<fg=yellow>Discord webhook not configured</>');
            }
        }

        $this->newLine();
        $this->info('✓ Test alert completed');

        return 0;
    }
}
