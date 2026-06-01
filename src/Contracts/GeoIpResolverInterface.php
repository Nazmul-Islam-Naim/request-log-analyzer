<?php

namespace NIN\RequestLogAnalyzer\Contracts;

/**
 * GeoIpResolverInterface
 *
 * Abstracts the mechanism used to map an IP address to a geographic location.
 * Implementations may call an external API (ip-api.com), a local MaxMind DB,
 * a stub for testing, or simply return nulls when geo tracking is disabled.
 *
 * SRP: knows only how to resolve an IP to country + city.
 * ISP: one tightly-focused method — implementors are never forced to do more.
 * OCP/DIP: callers depend on this interface, not on a concrete HTTP client.
 */
interface GeoIpResolverInterface
{
    /**
     * Resolve the country and city for a given IP address.
     *
     * - Private / reserved / localhost IPs SHOULD return null values.
     * - Implementations MUST NOT throw; all lookup failures silently return
     *   null values so that a geo lookup can never break request logging.
     *
     * @return array{country: string|null, city: string|null}
     */
    public function lookup(string $ip): array;
}
