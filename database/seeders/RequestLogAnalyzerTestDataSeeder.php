<?php

namespace NIN\RequestLogAnalyzer\Database\Seeders;

use Illuminate\Database\Seeder;
use NIN\RequestLogAnalyzer\Models\ApiRateUsage;
use NIN\RequestLogAnalyzer\Models\RateLimitIncident;

class RequestLogAnalyzerTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        ApiRateUsage::truncate();
        RateLimitIncident::truncate();

        // Generate API rate usage data for different users/IPs over 24 hours
        $users = [1, 2, 3, 5, 8, 13];
        $ips = ['192.168.1.100', '192.168.1.101', '192.168.1.102', '10.0.0.50', '10.0.0.51'];
        $endpoints = ['/api/users', '/api/posts', '/api/comments', '/api/products', '/api/orders'];

        // Generate hourly data for past 24 hours
        for ($hour = 0; $hour < 24; $hour++) {
            $timestamp = now()->subHours(24 - $hour);

            // User-based rate usage
            foreach ($users as $userId) {
                ApiRateUsage::create([
                    'user_id' => $userId,
                    'ip' => $ips[array_rand($ips)],
                    'endpoint' => $endpoints[array_rand($endpoints)],
                    'request_count' => rand(15, 85),
                    'first_request_at' => $timestamp->clone()->startOfHour(),
                    'last_request_at' => $timestamp->clone()->endOfHour(),
                    'rate_limit_exceeded' => false,
                    'period_type' => 'hour',
                ]);
            }

            // IP-based rate usage (suspicious pattern)
            foreach ($ips as $ip) {
                $requestCount = rand(20, 150);
                ApiRateUsage::create([
                    'user_id' => null,
                    'ip' => $ip,
                    'endpoint' => $endpoints[array_rand($endpoints)],
                    'request_count' => $requestCount,
                    'first_request_at' => $timestamp->clone()->startOfHour(),
                    'last_request_at' => $timestamp->clone()->endOfHour(),
                    'rate_limit_exceeded' => $requestCount > 100,
                    'period_type' => 'hour',
                ]);

                // Create incident for suspicious IPs with high request counts
                if ($requestCount > 100) {
                    RateLimitIncident::create([
                        'ip' => $ip,
                        'user_id' => null,
                        'endpoint' => $endpoints[array_rand($endpoints)],
                        'request_count' => $requestCount,
                        'limit_threshold' => 100,
                        'incident_type' => 'ip_limit',
                        'detected_at' => $timestamp,
                        'cleared_at' => rand(0, 1) ? $timestamp->addHours(rand(1, 4)) : null,
                        'resolved' => rand(0, 1),
                    ]);
                }
            }

            // Create incidents for users exceeding limits
            if (rand(0, 1)) {
                $userId = $users[array_rand($users)];
                $requestCount = rand(80, 150);
                RateLimitIncident::create([
                    'user_id' => $userId,
                    'ip' => $ips[array_rand($ips)],
                    'endpoint' => $endpoints[array_rand($endpoints)],
                    'request_count' => $requestCount,
                    'limit_threshold' => 100,
                    'incident_type' => 'user_limit',
                    'detected_at' => $timestamp,
                    'cleared_at' => rand(0, 1) ? $timestamp->addHours(rand(1, 6)) : null,
                    'resolved' => rand(0, 1),
                ]);
            }
        }

        $this->command->info('✓ RequestLogAnalyzer test data seeded successfully!');
        $this->command->info('  • Created ' . ApiRateUsage::count() . ' API rate usage records');
        $this->command->info('  • Created ' . RateLimitIncident::count() . ' rate limit incidents');
    }
}

