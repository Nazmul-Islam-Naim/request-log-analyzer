<?php

namespace NIN\RequestLogAnalyzer\Services;

use NIN\RequestLogAnalyzer\Contracts\RateUsageRepositoryInterface;

/**
 * RateLimiterService — Checks and enforces rate limits.
 *
 * Compares current request counts against configured thresholds
 * and triggers incidents when limits are exceeded.
 */
class RateLimiterService
{
    public function __construct(
        private readonly RateUsageRepositoryInterface $rateUsageRepo,
    ) {}

    /**
     * Check if a user has exceeded their rate limit.
     *
     * Returns alert data if limit exceeded, null otherwise.
     */
    public function checkUserRateLimit(?int $userId, string $periodType = 'minute'): ?array
    {
        if ($userId === null) {
            return null; // Only track authenticated users
        }

        $config = config('request-log-analyzer.rate_limits.users', []);

        if (!($config['enabled'] ?? false)) {
            return null;
        }

        $threshold = $config['threshold'] ?? 100;
        $usage = $this->rateUsageRepo->getUserRateUsage($userId, $periodType);

        if ($usage['count'] >= $threshold) {
            return [
                'type' => 'user_rate_limit',
                'user_id' => $userId,
                'count' => $usage['count'],
                'threshold' => $threshold,
                'period_type' => $periodType,
                'message' => "User {$userId} exceeded rate limit: {$usage['count']} requests in {$periodType} (limit: {$threshold})",
            ];
        }

        return null;
    }

    /**
     * Check if an IP has exceeded its rate limit.
     */
    public function checkIpRateLimit(string $ip, string $periodType = 'minute'): ?array
    {
        $config = config('request-log-analyzer.rate_limits.ips', []);

        if (!($config['enabled'] ?? false)) {
            return null;
        }

        $threshold = $config['threshold'] ?? 500;
        $usage = $this->rateUsageRepo->getIpRateUsage($ip, $periodType);

        if ($usage['count'] >= $threshold) {
            return [
                'type' => 'ip_rate_limit',
                'ip' => $ip,
                'count' => $usage['count'],
                'threshold' => $threshold,
                'period_type' => $periodType,
                'message' => "IP {$ip} exceeded rate limit: {$usage['count']} requests in {$periodType} (limit: {$threshold})",
            ];
        }

        return null;
    }

    /**
     * Check both user and IP rate limits in a single call.
     *
     * Returns array of alerts for all exceeded limits.
     */
    public function checkAllLimits(?int $userId, string $ip, string $periodType = 'minute'): array
    {
        $alerts = [];

        if ($userAlert = $this->checkUserRateLimit($userId, $periodType)) {
            $alerts[] = $userAlert;
            $this->rateUsageRepo->createIncident(
                $userId,
                $ip,
                null,
                $userAlert['count'],
                $userAlert['threshold'],
                'user_limit'
            );
        }

        if ($ipAlert = $this->checkIpRateLimit($ip, $periodType)) {
            $alerts[] = $ipAlert;
            $this->rateUsageRepo->createIncident(
                $userId,
                $ip,
                null,
                $ipAlert['count'],
                $ipAlert['threshold'],
                'ip_limit'
            );
        }

        return $alerts;
    }

    /**
     * Get rate limit status summary.
     */
    public function getStatus(?int $userId, string $ip, string $periodType = 'minute'): array
    {
        $userUsage = $userId ? $this->rateUsageRepo->getUserRateUsage($userId, $periodType) : null;
        $ipUsage = $this->rateUsageRepo->getIpRateUsage($ip, $periodType);

        $userConfig = config('request-log-analyzer.rate_limits.users', []);
        $ipConfig = config('request-log-analyzer.rate_limits.ips', []);

        $userThreshold = $userConfig['threshold'] ?? 100;
        $ipThreshold = $ipConfig['threshold'] ?? 500;

        return [
            'user' => [
                'requests' => $userUsage['count'] ?? 0,
                'limit' => $userThreshold,
                'percentage' => $userUsage ? round(($userUsage['count'] / $userThreshold) * 100, 2) : 0,
                'exceeded' => $userUsage['exceeded'] ?? false,
            ],
            'ip' => [
                'requests' => $ipUsage['count'],
                'limit' => $ipThreshold,
                'percentage' => round(($ipUsage['count'] / $ipThreshold) * 100, 2),
                'exceeded' => $ipUsage['exceeded'],
            ],
            'period' => $periodType,
        ];
    }
}
