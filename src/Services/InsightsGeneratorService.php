<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\RequestRepositoryInterface;

class InsightsGeneratorService
{
    public function __construct(
        private readonly RequestRepositoryInterface $requests,
    ) {}

    /**
     * Generate intelligent insights based on request data
     */
    public function generateInsights(): array
    {
        $insights = [];

        // Analyze route performance
        $insights = array_merge($insights, $this->analyzeRoutePerformance());

        // Analyze error trends
        $insights = array_merge($insights, $this->analyzeErrorTrends());

        // Analyze slow requests
        $insights = array_merge($insights, $this->analyzeSlowRequests());

        // Analyze API usage patterns
        $insights = array_merge($insights, $this->analyzeApiUsagePatterns());

        // Sort by severity (warning > info)
        usort($insights, fn ($a, $b) => $b['severity_order'] <=> $a['severity_order']);

        return $insights;
    }

    /**
     * Analyze route performance vs average
     */
    private function analyzeRoutePerformance(): array
    {
        $insights = [];

        // Get average response time across all routes
        $avgTime = (int) DB::table('rla_requests')
            ->where('created_at', '>=', now()->subHours(24))
            ->avg('response_time_ms') ?? 0;

        if (!$avgTime) {
            return $insights;
        }

        // Find routes slower than average
        $slowRoutes = DB::table('rla_requests')
            ->selectRaw('uri, COUNT(*) as count, AVG(response_time_ms) as avg_ms')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('uri')
            ->havingRaw('AVG(response_time_ms) > ?', [$avgTime * 1.3]) // 30% slower
            ->orderByDesc('avg_ms')
            ->limit(3)
            ->get();

        foreach ($slowRoutes as $route) {
            $percent = round((($route->avg_ms - $avgTime) / $avgTime) * 100, 1);
            $insights[] = [
                'type' => 'performance',
                'title' => "Route {$route->uri} is slower than average",
                'message' => "Average response time: {$route->avg_ms}ms (${percent}% slower than {$avgTime}ms average)",
                'severity' => 'warning',
                'severity_order' => 2,
                'icon' => '⚠️',
                'color' => '#f59e0b',
            ];
        }

        return $insights;
    }

    /**
     * Analyze error rate trends
     */
    private function analyzeErrorTrends(): array
    {
        $insights = [];

        // Get error count for last 24 hours and last 24 hours before that
        $recentErrors = DB::table('rla_requests')
            ->where('created_at', '>=', now()->subHours(24))
            ->where('status_code', '>=', 400)
            ->count();

        $previousErrors = DB::table('rla_requests')
            ->whereBetween('created_at', [now()->subHours(48), now()->subHours(24)])
            ->where('status_code', '>=', 400)
            ->count();

        // Get total requests to calculate error rate
        $recentTotal = DB::table('rla_requests')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $previousTotal = DB::table('rla_requests')
            ->whereBetween('created_at', [now()->subHours(48), now()->subHours(24)])
            ->count();

        if ($recentTotal > 0 && $previousTotal > 0) {
            $recentErrorRate = ($recentErrors / $recentTotal) * 100;
            $previousErrorRate = ($previousErrors / $previousTotal) * 100;
            $increase = $recentErrorRate - $previousErrorRate;

            if ($increase > 5) {
                $insights[] = [
                    'type' => 'errors',
                    'title' => 'Error rate increased',
                    'message' => "Error rate increased from {$previousErrorRate}% to {$recentErrorRate}% (+{$increase}%)",
                    'severity' => 'warning',
                    'severity_order' => 2,
                    'icon' => '🚨',
                    'color' => '#ef4444',
                ];
            } elseif ($increase < -5) {
                $insights[] = [
                    'type' => 'errors',
                    'title' => 'Error rate improved',
                    'message' => "Error rate decreased from {$previousErrorRate}% to {$recentErrorRate}% ({$increase}%)",
                    'severity' => 'info',
                    'severity_order' => 1,
                    'icon' => '✅',
                    'color' => '#10b981',
                ];
            }
        }

        // Check for specific high error codes
        $statusCodes = DB::table('rla_requests')
            ->selectRaw('status_code, COUNT(*) as count')
            ->where('created_at', '>=', now()->subHours(24))
            ->where('status_code', '>=', 400)
            ->groupBy('status_code')
            ->orderByDesc('count')
            ->limit(1)
            ->first();

        if ($statusCodes && $statusCodes->count > 10) {
            $errorName = match ($statusCodes->status_code) {
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                429 => 'Too Many Requests',
                500 => 'Internal Server Error',
                503 => 'Service Unavailable',
                default => "HTTP {$statusCodes->status_code}",
            };

            $insights[] = [
                'type' => 'errors',
                'title' => "{$errorName} errors detected",
                'message' => "{$statusCodes->count} {$errorName} ({$statusCodes->status_code}) errors in last 24 hours",
                'severity' => $statusCodes->status_code >= 500 ? 'warning' : 'info',
                'severity_order' => $statusCodes->status_code >= 500 ? 2 : 1,
                'icon' => '❌',
                'color' => $statusCodes->status_code >= 500 ? '#ef4444' : '#f59e0b',
            ];
        }

        return $insights;
    }

    /**
     * Analyze slow request patterns
     */
    private function analyzeSlowRequests(): array
    {
        $insights = [];

        // Find HTTP methods with high response times
        $slowMethods = DB::table('rla_requests')
            ->selectRaw('method, COUNT(*) as count, AVG(response_time_ms) as avg_ms')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('method')
            ->havingRaw('AVG(response_time_ms) > 500')
            ->orderByDesc('avg_ms')
            ->first();

        if ($slowMethods) {
            $insights[] = [
                'type' => 'performance',
                'title' => "Slow {$slowMethods->method} requests detected",
                'message' => "{$slowMethods->method} requests averaging {$slowMethods->avg_ms}ms ({$slowMethods->count} requests)",
                'severity' => 'info',
                'severity_order' => 1,
                'icon' => '🐢',
                'color' => '#f59e0b',
            ];
        }

        return $insights;
    }

    /**
     * Analyze API usage patterns
     */
    private function analyzeApiUsagePatterns(): array
    {
        $insights = [];

        // Check for unusual spike in requests
        $currentHourRequests = DB::table('rla_requests')
            ->where('created_at', '>=', now()->startOfHour())
            ->count();

        $avgHourlyRequests = (int) DB::table('rla_requests')
            ->where('created_at', '>=', now()->subHours(24))
            ->count() / 24;

        if ($avgHourlyRequests > 0 && $currentHourRequests > $avgHourlyRequests * 1.5) {
            $increase = round((($currentHourRequests - $avgHourlyRequests) / $avgHourlyRequests) * 100, 1);
            $insights[] = [
                'type' => 'usage',
                'title' => 'High request volume detected',
                'message' => "Current hour: $currentHourRequests requests (${increase}% above average of {$avgHourlyRequests})",
                'severity' => 'info',
                'severity_order' => 1,
                'icon' => '📈',
                'color' => '#3b82f6',
            ];
        }

        // Check for unusually low requests
        if ($avgHourlyRequests > 0 && $currentHourRequests < $avgHourlyRequests * 0.5) {
            $decrease = round(((1 - ($currentHourRequests / $avgHourlyRequests)) * 100), 1);
            $insights[] = [
                'type' => 'usage',
                'title' => 'Low request volume detected',
                'message' => "Current hour: $currentHourRequests requests (${decrease}% below average of {$avgHourlyRequests})",
                'severity' => 'info',
                'severity_order' => 1,
                'icon' => '📉',
                'color' => '#6366f1',
            ];
        }

        return $insights;
    }
}
