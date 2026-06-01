<?php

namespace NIN\RequestLogAnalyzer\Contracts;

/**
 * RateUsageRepositoryInterface — API rate usage tracking and queries.
 *
 * Manages rate usage statistics per user and per IP, with detection
 * of rate limit violations.
 */
interface RateUsageRepositoryInterface
{
    /**
     * Record or update a request in rate usage tracking.
     *
     * @param int|null $userId User ID (null for unauthenticated)
     * @param string $ip Client IP address
     * @param string $endpoint Request endpoint/URI
     * @param string $periodType Time period (minute, hour, day)
     */
    public function recordRequest(?int $userId, string $ip, string $endpoint, string $periodType): void;

    /**
     * Get rate usage for a user in a time period.
     *
     * @return array{count: int, exceeded: bool, first_at: datetime, last_at: datetime}
     */
    public function getUserRateUsage(?int $userId, string $periodType): array;

    /**
     * Get rate usage for an IP in a time period.
     */
    public function getIpRateUsage(string $ip, string $periodType): array;

    /**
     * Get top users by request count in a period.
     *
     * @return \Illuminate\Support\Collection
     */
    public function topUsersByRequests(string $periodType, int $limit = 20);

    /**
     * Get suspicious IPs (high request count).
     */
    public function suspiciousIps(string $periodType, int $limit = 20);

    /**
     * Create a rate limit incident record.
     */
    public function createIncident(
        ?int $userId,
        string $ip,
        ?string $endpoint,
        int $requestCount,
        int $threshold,
        string $type
    ): void;

    /**
     * Get unresolved incidents.
     */
    public function getUnresolvedIncidents(int $limit = 50);

    /**
     * Get recent incidents.
     */
    public function getRecentIncidents(int $days = 7, int $limit = 50);

    /**
     * Clean up old rate usage records (older than retention period).
     */
    public function cleanup(int $retentionDays = 30): int;
}
