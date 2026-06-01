<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\AlertRepositoryInterface;

/**
 * AlertChecker — evaluates if alert thresholds have been exceeded.
 *
 * Responsibilities:
 *   - Count errors and slow requests within configurable time windows
 *   - Check if thresholds are exceeded
 *   - Respect cooldown periods to prevent alert spam
 *
 * SOLID: Single responsibility (threshold checking); delegates persistence
 * to AlertRepository and notification to AlertNotifier.
 */
class AlertChecker
{
    public function __construct(
        private readonly AlertRepositoryInterface $alertRepository,
    ) {}

    /**
     * Check if an error alert should be sent.
     *
     * Returns alert data if threshold exceeded AND cooldown has passed,
     * otherwise returns null.
     */
    public function checkErrorAlert(): ?array
    {
        $config = config('request-log-analyzer.alerts.error_alerts');

        if (! $config['enabled'] ?? false) {
            return null;
        }

        // Check cooldown period
        if (! $this->shouldSendAlert($this->alertRepository->getLastErrorAlertTime(), $config['cooldown_minutes'])) {
            return null;
        }

        // Count errors (5xx responses) in the time window
        $errorCount = $this->countErrors($config['time_window_minutes']);

        if ($errorCount >= $config['threshold']) {
            return [
                'type' => 'errors',
                'count' => $errorCount,
                'threshold' => $config['threshold'],
                'time_window' => $config['time_window_minutes'],
                'message' => "Alert: {$errorCount} errors detected in the last {$config['time_window_minutes']} minutes (threshold: {$config['threshold']})",
            ];
        }

        return null;
    }

    /**
     * Check if a slow request alert should be sent.
     *
     * Returns alert data if threshold exceeded AND cooldown has passed,
     * otherwise returns null.
     */
    public function checkSlowAlert(): ?array
    {
        $config = config('request-log-analyzer.alerts.slow_request_alerts');

        if (! $config['enabled'] ?? false) {
            return null;
        }

        // Check cooldown period
        if (! $this->shouldSendAlert($this->alertRepository->getLastSlowAlertTime(), $config['cooldown_minutes'])) {
            return null;
        }

        // Get slow request threshold from main config
        $slowThreshold = (int) config('request-log-analyzer.slow_request_threshold_ms', 500);

        // Count slow requests in the time window
        $slowCount = $this->countSlowRequests($config['time_window_minutes'], $slowThreshold);

        if ($slowCount >= $config['threshold']) {
            return [
                'type' => 'slow_requests',
                'count' => $slowCount,
                'threshold' => $config['threshold'],
                'time_window' => $config['time_window_minutes'],
                'slow_threshold_ms' => $slowThreshold,
                'message' => "Alert: {$slowCount} slow requests (>{$slowThreshold}ms) detected in the last {$config['time_window_minutes']} minutes (threshold: {$config['threshold']})",
            ];
        }

        return null;
    }

    /**
     * Check if enough time has passed since the last alert.
     */
    private function shouldSendAlert(?int $lastAlertTime, int $cooldownMinutes): bool
    {
        if ($lastAlertTime === null) {
            return true;
        }

        $cooldownSeconds = $cooldownMinutes * 60;
        return (time() - $lastAlertTime) >= $cooldownSeconds;
    }

    /**
     * Count error responses (5xx) in the last N minutes.
     */
    private function countErrors(int $windowMinutes): int
    {
        $since = now()->subMinutes($windowMinutes);

        return (int) DB::table('rla_requests')
            ->where('status_code', '>=', 500)
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Count slow requests in the last N minutes.
     */
    private function countSlowRequests(int $windowMinutes, int $slowThresholdMs): int
    {
        $since = now()->subMinutes($windowMinutes);

        return (int) DB::table('rla_requests')
            ->where('response_time_ms', '>=', $slowThresholdMs)
            ->where('created_at', '>=', $since)
            ->count();
    }
}
