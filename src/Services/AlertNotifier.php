<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use NIN\RequestLogAnalyzer\Contracts\AlertRepositoryInterface;

/**
 * AlertNotifier — sends alerts via configured channels.
 *
 * Supports:
 *   - Log file (default)
 *   - Email (optional)
 *
 * SOLID: Single responsibility (sending alerts); delegates state tracking
 * to AlertRepository.
 */
class AlertNotifier
{
    public function __construct(
        private readonly AlertRepositoryInterface $alertRepository,
    ) {}

    /**
     * Send error alert via all configured channels.
     */
    public function sendErrorAlert(array $alertData): void
    {
        $channels = config('request-log-analyzer.alerts.channels', ['log']);

        foreach ($channels as $channel) {
            match (strtolower(trim($channel))) {
                'log' => $this->sendViaLog($alertData),
                'email' => $this->sendViaEmail($alertData),
                'discord' => $this->sendViaDiscord($alertData),
                default => null,
            };
        }

        $this->alertRepository->recordErrorAlert();
    }

    /**
     * Send a slow request alert via all configured channels.
     */
    public function sendSlowAlert(array $alertData): void
    {
        $channels = config('request-log-analyzer.alerts.channels', ['log']);

        foreach ($channels as $channel) {
            match (strtolower(trim($channel))) {
                'log' => $this->sendViaLog($alertData),
                'email' => $this->sendViaEmail($alertData),
                'discord' => $this->sendViaDiscord($alertData),
                default => null,
            };
        }

        $this->alertRepository->recordSlowAlert();
    }

    /**
     * Send alert to log file.
     */
    private function sendViaLog(array $alertData): void
    {
        $type = ucfirst(str_replace('_', ' ', $alertData['type']));
        $message = $alertData['message'];

        Log::warning("RLA Alert: {$type}", $alertData);
    }

    /**
     * Send alert to Discord channel via webhook.
     */
    private function sendViaDiscord(array $alertData): void
    {
        $config = config('request-log-analyzer.alerts.discord_config', []);
        $webhookUrl = $config['webhook_url'] ?? '';

        if (empty($webhookUrl)) {
            Log::warning('RLA Discord Alert skipped: webhook URL not configured', $alertData);
            return;
        }

        try {
            $type = ucfirst(str_replace('_', ' ', $alertData['type']));
            $color = $alertData['type'] === 'errors'
                ? (int) ($config['color_errors'] ?? '15158332')
                : (int) ($config['color_slow'] ?? '16776960');

            $payload = $this->buildDiscordPayload($type, $alertData, $color, $config);

            $response = $this->sendDiscordWebhook($webhookUrl, $payload);

            if ($response) {
                Log::info('RLA Discord Alert sent', [
                    'type' => $alertData['type'],
                    'webhook_domain' => parse_url($webhookUrl, PHP_URL_HOST),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RLA Discord Alert failed: ' . $e->getMessage(), [
                'alert_type' => $alertData['type'],
                'exception' => get_class($e),
            ]);
        }
    }

    /**
     * Send alert via email.
     */
    private function sendViaEmail(array $alertData): void
    {
        $emailConfig = config('request-log-analyzer.alerts.email_config', []);
        $recipients = $emailConfig['to'] ?? [];

        // Skip if no recipients configured
        if (empty($recipients)) {
            Log::warning('RLA Email Alert skipped: no recipients configured', $alertData);
            return;
        }

        // Ensure recipients is an array
        if (is_string($recipients)) {
            $recipients = explode(',', $recipients);
        }

        // Filter empty email addresses
        $recipients = array_filter(array_map('trim', $recipients));

        if (empty($recipients)) {
            Log::warning('RLA Email Alert skipped: all recipients invalid', $alertData);
            return;
        }

        try {
            $type = ucfirst(str_replace('_', ' ', $alertData['type']));
            $from = $emailConfig['from'] ?? config('mail.from.address');
            $subject = str_replace('{type}', $type, $emailConfig['subject'] ?? 'Request Log Alert: {type}');

            $htmlBody = $this->buildEmailBody($alertData);

            Mail::html($htmlBody, function ($message) use ($recipients, $from, $subject) {
                $message->subject($subject)
                    ->from($from)
                    ->to($recipients);
            });

            Log::info('RLA Email Alert sent', [
                'type' => $alertData['type'],
                'recipients' => count($recipients),
            ]);
        } catch (\Exception $e) {
            Log::error('RLA Email Alert failed: '.$e->getMessage(), [
                'alert_type' => $alertData['type'],
                'exception' => get_class($e),
            ]);
        }
    }

    /**
     * Build HTML email body for alert.
     */
    private function buildEmailBody(array $alertData): string
    {
        $type = ucfirst(str_replace('_', ' ', $alertData['type']));
        $appName = config('app.name', 'Laravel');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f44336; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 5px; }
                .detail { margin: 10px 0; }
                .label { font-weight: bold; color: #555; }
                .footer { margin-top: 20px; font-size: 12px; color: #999; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚨 {$type} Alert</h1>
                </div>
                <div class="content">
                    <p>{$alertData['message']}</p>
                    <div class="detail">
                        <span class="label">Alert Type:</span> {$type}
                    </div>
                    <div class="detail">
                        <span class="label">Count:</span> {$alertData['count']}
                    </div>
                    <div class="detail">
                        <span class="label">Threshold:</span> {$alertData['threshold']}
                    </div>
                    <div class="detail">
                        <span class="label">Time Window:</span> {$alertData['time_window']} minute(s)
                    </div>
                    <div class="detail">
                        <span class="label">Timestamp:</span> {$this->getFormattedTime()}
                    </div>
                </div>
                <div class="footer">
                    <p>Sent by Request Log Analyzer for {$appName}</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get current timestamp formatted for email.
     */
    private function getFormattedTime(): string
    {
        return now()->format('Y-m-d H:i:s T');
    }

    /**
     * Build Discord webhook payload with embeds.
     */
    private function buildDiscordPayload(string $type, array $alertData, int $color, array $config): array
    {
        $appName = config('app.name', 'Laravel');
        $timestamp = now()->toIso8601String();

        $fields = [
            [
                'name' => 'Alert Type',
                'value' => $type,
                'inline' => true,
            ],
            [
                'name' => 'Count',
                'value' => (string) $alertData['count'],
                'inline' => true,
            ],
            [
                'name' => 'Threshold',
                'value' => (string) $alertData['threshold'],
                'inline' => true,
            ],
            [
                'name' => 'Time Window',
                'value' => $alertData['time_window'] . ' minute(s)',
                'inline' => true,
            ],
        ];

        // Add slow threshold info if it's a slow request alert
        if ($alertData['type'] === 'slow_requests' && isset($alertData['slow_threshold_ms'])) {
            $fields[] = [
                'name' => 'Slow Threshold',
                'value' => $alertData['slow_threshold_ms'] . ' ms',
                'inline' => true,
            ];
        }

        return [
            'username' => $config['username'] ?? 'Request Log Analyzer',
            'avatar_url' => $config['avatar_url'] ?? '',
            'embeds' => [
                [
                    'title' => '🚨 ' . $type . ' Alert',
                    'description' => $alertData['message'],
                    'color' => $color,
                    'fields' => $fields,
                    'footer' => [
                        'text' => "Request Log Analyzer - {$appName}",
                    ],
                    'timestamp' => $timestamp,
                ],
            ],
        ];
    }

    /**
     * Send Discord webhook request.
     */
    private function sendDiscordWebhook(string $webhookUrl, array $payload): bool
    {
        try {
            $ch = curl_init($webhookUrl);

            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL error: {$error}");
            }

            if ($httpCode >= 400) {
                throw new \Exception("Discord webhook returned HTTP {$httpCode}: {$response}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error('RLA Discord webhook failed: ' . $e->getMessage());
            return false;
        }
    }
}
