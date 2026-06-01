<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Support\Facades\Http;
use NIN\RequestLogAnalyzer\Contracts\GeoIpResolverInterface;

/**
 * GeoIpResolver — ip-api.com implementation of GeoIpResolverInterface.
 *
 * Concrete driver: ip-api.com (free, no API key, 1 000 req/min).
 * Swap for a MaxMind-backed implementation by binding a different class
 * to GeoIpResolverInterface in the service container — no other changes
 * required (DIP).
 *
 * Private/reserved IPs are skipped immediately (no HTTP call).
 * Per-process in-memory cache avoids redundant lookups within a worker.
 * All errors are swallowed; geo data is optional/supplementary.
 */
class GeoIpResolver implements GeoIpResolverInterface
{
    /** Per-process IP → geo cache. */
    private array $cache = [];

    /**
     * Resolve geo data for the given IP.
     *
     * @return array{country: string|null, city: string|null}
     */
    public function lookup(string $ip): array
    {
        $blank = ['country' => null, 'city' => null];

        if (! config('request-log-analyzer.track_geo', true)) {
            return $blank;
        }

        // Skip private/loopback/reserved ranges — ip-api.com can't resolve them
        // and it would just waste an API call.
        if (! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        )) {
            return $blank;
        }

        if (array_key_exists($ip, $this->cache)) {
            return $this->cache[$ip];
        }

        try {
            $apiUrl = config(
                'request-log-analyzer.geo_api_url',
                'http://ip-api.com/json/{ip}'
            );
            $url = str_replace('{ip}', rawurlencode($ip), $apiUrl);

            $response = Http::timeout(
                (int) config('request-log-analyzer.geo_timeout_seconds', 2)
            )->get($url, ['fields' => 'status,country,city']);

            if ($response->ok() && $response->json('status') === 'success') {
                $result = [
                    'country' => $response->json('country') ?: null,
                    'city' => $response->json('city') ?: null,
                ];
            } else {
                $result = $blank;
            }
        } catch (\Throwable) {
            // Swallow all errors (timeouts, DNS failures, etc.) — geo data
            // is supplementary; a lookup failure must never break the logger.
            $result = $blank;
        }

        $this->cache[$ip] = $result;

        return $result;
    }

    /** Flush the in-process cache (useful in tests). */
    public function flush(): void
    {
        $this->cache = [];
    }
}
