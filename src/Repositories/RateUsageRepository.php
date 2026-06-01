<?php

namespace NIN\RequestLogAnalyzer\Repositories;

use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\RateUsageRepositoryInterface;
use NIN\RequestLogAnalyzer\Models\ApiRateUsage;
use NIN\RequestLogAnalyzer\Models\RateLimitIncident;

/**
 * RateUsageRepository — Eloquent implementation of rate usage tracking.
 *
 * Provides efficient queries for rate limit analysis and incident tracking.
 */
class RateUsageRepository implements RateUsageRepositoryInterface
{
    public function recordRequest(?int $userId, string $ip, string $endpoint, string $periodType): void
    {
        // Find or create rate usage record
        $record = ApiRateUsage::query()
            ->where('user_id', $userId)
            ->where('ip', $ip)
            ->where('endpoint', $endpoint)
            ->where('period_type', $periodType)
            ->first();

        if ($record) {
            $record->update([
                'request_count' => $record->request_count + 1,
                'last_request_at' => now(),
            ]);
        } else {
            ApiRateUsage::create([
                'user_id' => $userId,
                'ip' => $ip,
                'endpoint' => $endpoint,
                'request_count' => 1,
                'first_request_at' => now(),
                'last_request_at' => now(),
                'rate_limit_exceeded' => false,
                'period_type' => $periodType,
            ]);
        }
    }

    public function getUserRateUsage(?int $userId, string $periodType): array
    {
        $record = ApiRateUsage::query()
            ->where('user_id', $userId)
            ->where('period_type', $periodType)
            ->first();

        if (!$record) {
            return [
                'count' => 0,
                'exceeded' => false,
                'first_at' => null,
                'last_at' => null,
            ];
        }

        return [
            'count' => $record->request_count,
            'exceeded' => $record->rate_limit_exceeded,
            'first_at' => $record->first_request_at,
            'last_at' => $record->last_request_at,
        ];
    }

    public function getIpRateUsage(string $ip, string $periodType): array
    {
        $result = DB::table('rla_api_rate_usage')
            ->where('ip', $ip)
            ->where('period_type', $periodType)
            ->selectRaw('SUM(request_count) as total_count, MAX(rate_limit_exceeded) as any_exceeded, MIN(first_request_at) as first_at, MAX(last_request_at) as last_at')
            ->first();

        if (!$result || $result->total_count === null) {
            return [
                'count' => 0,
                'exceeded' => false,
                'first_at' => null,
                'last_at' => null,
            ];
        }

        return [
            'count' => (int) $result->total_count,
            'exceeded' => (bool) $result->any_exceeded,
            'first_at' => $result->first_at,
            'last_at' => $result->last_at,
        ];
    }

    public function topUsersByRequests(string $periodType, int $limit = 20)
    {
        return ApiRateUsage::query()
            ->whereNotNull('user_id')
            ->where('period_type', $periodType)
            ->selectRaw('user_id, SUM(request_count) as total_requests, COUNT(*) as unique_endpoints, MAX(last_request_at) as last_request')
            ->groupBy('user_id')
            ->orderByDesc('total_requests')
            ->limit($limit)
            ->get();
    }

    public function suspiciousIps(string $periodType, int $limit = 20)
    {
        return DB::table('rla_api_rate_usage')
            ->where('period_type', $periodType)
            ->selectRaw('ip, SUM(request_count) as total_requests, COUNT(*) as unique_endpoints, MAX(rate_limit_exceeded) as rate_limited, MAX(last_request_at) as last_request')
            ->where('rate_limit_exceeded', true)
            ->groupBy('ip')
            ->orderByDesc('total_requests')
            ->limit($limit)
            ->get();
    }

    public function createIncident(
        ?int $userId,
        string $ip,
        ?string $endpoint,
        int $requestCount,
        int $threshold,
        string $type
    ): void
    {
        // Check if an unresolved incident exists for this user/IP/endpoint
        $existing = RateLimitIncident::query()
            ->where('user_id', $userId)
            ->where('ip', $ip)
            ->where('endpoint', $endpoint)
            ->where('incident_type', $type)
            ->unresolved()
            ->first();

        if ($existing) {
            // Update the existing incident
            $existing->update([
                'request_count' => $requestCount,
                'limit_threshold' => $threshold,
            ]);
        } else {
            // Create new incident
            RateLimitIncident::create([
                'user_id' => $userId,
                'ip' => $ip,
                'endpoint' => $endpoint,
                'request_count' => $requestCount,
                'limit_threshold' => $threshold,
                'incident_type' => $type,
                'detected_at' => now(),
                'resolved' => false,
            ]);
        }

        // Update rate_limit_exceeded flag
        ApiRateUsage::query()
            ->where('user_id', $userId)
            ->where('ip', $ip)
            ->where('endpoint', $endpoint)
            ->update(['rate_limit_exceeded' => true]);
    }

    public function getUnresolvedIncidents(int $limit = 50)
    {
        return RateLimitIncident::unresolved()
            ->orderBy('detected_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentIncidents(int $days = 7, int $limit = 50)
    {
        return RateLimitIncident::recent($days)
            ->orderBy('detected_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function cleanup(int $retentionDays = 30): int
    {
        $before = now()->subDays($retentionDays);

        return DB::table('rla_api_rate_usage')
            ->where('last_request_at', '<', $before)
            ->delete();
    }
}
