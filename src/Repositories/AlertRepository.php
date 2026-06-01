<?php

namespace NIN\RequestLogAnalyzer\Repositories;

use Illuminate\Support\Facades\Cache;
use NIN\RequestLogAnalyzer\Contracts\AlertRepositoryInterface;

/**
 * AlertRepository — tracks alert state using Laravel's cache.
 *
 * Uses cache to store the last time each alert type was sent, allowing
 * efficient cooldown checking without database writes.
 *
 * Cache keys:
 *   rla_alert_error_last    — last time error alert was sent (Unix timestamp)
 *   rla_alert_slow_last     — last time slow request alert was sent (Unix timestamp)
 */
class AlertRepository implements AlertRepositoryInterface
{
    private const CACHE_ERROR_KEY = 'rla_alert_error_last';
    private const CACHE_SLOW_KEY = 'rla_alert_slow_last';

    public function getLastErrorAlertTime(): ?int
    {
        return Cache::get(self::CACHE_ERROR_KEY);
    }

    public function getLastSlowAlertTime(): ?int
    {
        return Cache::get(self::CACHE_SLOW_KEY);
    }

    public function recordErrorAlert(): void
    {
        Cache::put(self::CACHE_ERROR_KEY, time(), now()->addHours(24));
    }

    public function recordSlowAlert(): void
    {
        Cache::put(self::CACHE_SLOW_KEY, time(), now()->addHours(24));
    }
}
