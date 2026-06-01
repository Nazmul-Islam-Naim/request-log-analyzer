<?php

namespace NIN\RequestLogAnalyzer\Services;

/**
 * SensitiveDataMasker — centralised sensitive-data masking service.
 *
 * Responsibilities (SRP):
 *  - Mask field values in associative arrays (request body, context).
 *  - Mask sensitive header values while preserving the header key.
 *  - Mask sensitive parameter values in query strings.
 *  - Apply regex scrub patterns to arbitrary strings (exception messages,
 *    stack traces, URL fragments, etc.).
 *
 * Configuration (all keys under `request-log-analyzer.masking`):
 *
 *  enabled      — master switch; when false all methods are no-ops.
 *  mask_value   — replacement string (default "[REDACTED]").
 *  fields       — body / context keys whose values are replaced.
 *  headers      — header names whose values are replaced (not removed).
 *  query_params — query-string parameter names whose values are replaced.
 *  patterns     — array of regex patterns applied to raw strings.
 *
 * The service also transparently inherits the legacy `hidden_fields`,
 * `hidden_headers`, and `scrub_patterns` config keys so that existing
 * published configs continue to work without any changes.
 *
 * Singleton — config values are loaded and cached lazily on first access.
 */
class SensitiveDataMasker
{
    private ?string $maskValue = null;

    /** @var string[]|null */
    private ?array $fields = null;

    /** @var string[]|null */
    private ?array $headerMask = null;

    /** @var string[]|null */
    private ?array $headerStrip = null;

    /** @var string[]|null */
    private ?array $queryParams = null;

    /** @var string[]|null */
    private ?array $patterns = null;

    // ── Public API ────────────────────────────────────────────────────────────

    public function isEnabled(): bool
    {
        return (bool) config('request-log-analyzer.masking.enabled', true);
    }

    /**
     * Recursively mask sensitive field values in an associative array.
     *
     * - Keys whose lowercased name is in masking.fields (or legacy hidden_fields)
     *   have their value replaced with the mask value.
     * - All other string values are scrubbed with masking.patterns.
     * - Arrays are recursed.
     */
    public function maskFields(array $data): array
    {
        if (! $this->isEnabled()) {
            return $data;
        }

        $fields = $this->fields();

        foreach ($data as $key => &$value) {
            if (in_array(strtolower((string) $key), $fields, true)) {
                $value = $this->maskValue();
                continue;
            }

            if (is_array($value)) {
                $value = $this->maskFields($value);
            } elseif (is_string($value)) {
                $value = $this->maskString($value);
            }
        }
        unset($value);

        return $data;
    }

    /**
     * Mask sensitive header values and strip headers that should be removed.
     *
     * Headers in masking.headers  → value replaced with [mask_value].
     * Headers in hidden_headers (legacy, and NOT in masking.headers) → removed.
     *
     * Symfony's HeaderBag stores header values as string arrays; the masked
     * value is stored as a single-element array to maintain that structure.
     *
     * @param  array<string, mixed>  $headers  e.g. ['authorization' => ['Bearer …']]
     * @return array<string, mixed>
     */
    public function maskHeaders(array $headers): array
    {
        if (! $this->isEnabled()) {
            return $headers;
        }

        $toMask  = $this->headerMask();
        $toStrip = $this->headerStrip();

        $result = [];
        foreach ($headers as $name => $value) {
            $lower = strtolower((string) $name);

            if (in_array($lower, $toMask, true)) {
                // Keep the header; replace its value.
                $result[$name] = [$this->maskValue()];
            } elseif (! in_array($lower, $toStrip, true)) {
                // Neither masked nor stripped — pass through unchanged.
                $result[$name] = $value;
            }
            // else: in $toStrip only → omit entirely.
        }

        return $result;
    }

    /**
     * Mask sensitive parameter values inside a URL query string.
     *
     * Parameters whose lowercased name is in masking.query_params have their
     * value replaced before the string is rebuilt with http_build_query().
     */
    public function maskQueryString(string $qs): string
    {
        if (! $this->isEnabled() || $qs === '') {
            return $qs;
        }

        $sensitive = $this->queryParams();

        if (empty($sensitive)) {
            return $qs;
        }

        parse_str($qs, $parsed);
        $parsed = $this->maskQueryArray($parsed, $sensitive);

        return http_build_query($parsed);
    }

    /**
     * Apply all configured regex patterns to a plain string.
     *
     * Used for exception messages, stack-trace lines, and any other raw
     * string values that might embed credential material.
     */
    public function maskString(string $text): string
    {
        if (! $this->isEnabled()) {
            return $text;
        }

        foreach ($this->patterns() as $pattern) {
            $text = (string) preg_replace($pattern, $this->maskValue(), $text);
        }

        return $text;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function maskQueryArray(array $params, array $sensitiveKeys): array
    {
        foreach ($params as $key => &$value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $value = $this->maskValue();
                continue;
            }

            if (is_array($value)) {
                $value = $this->maskQueryArray($value, $sensitiveKeys);
            }
        }
        unset($value);

        return $params;
    }

    private function maskValue(): string
    {
        if ($this->maskValue === null) {
            $this->maskValue = (string) config(
                'request-log-analyzer.masking.mask_value',
                '[REDACTED]'
            );
        }

        return $this->maskValue;
    }

    /** @return string[] Lowercase field names to mask. */
    private function fields(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $new    = (array) config('request-log-analyzer.masking.fields', []);
        $legacy = (array) config('request-log-analyzer.hidden_fields', []);

        return $this->fields = array_unique(
            array_map('strtolower', array_merge($new, $legacy))
        );
    }

    /**
     * Lowercase header names whose values should be masked (not removed).
     *
     * @return string[]
     */
    private function headerMask(): array
    {
        if ($this->headerMask !== null) {
            return $this->headerMask;
        }

        return $this->headerMask = array_map(
            'strtolower',
            (array) config('request-log-analyzer.masking.headers', [])
        );
    }

    /**
     * Lowercase header names that should be stripped entirely.
     * Headers that appear in both lists are masked (not stripped).
     *
     * @return string[]
     */
    private function headerStrip(): array
    {
        if ($this->headerStrip !== null) {
            return $this->headerStrip;
        }

        $legacy = array_map(
            'strtolower',
            (array) config('request-log-analyzer.hidden_headers', [])
        );

        // Any header covered by masking.headers is masked, not stripped.
        $toMask = $this->headerMask();

        return $this->headerStrip = array_values(
            array_diff($legacy, $toMask)
        );
    }

    /** @return string[] Lowercase query-param names to mask. */
    private function queryParams(): array
    {
        if ($this->queryParams !== null) {
            return $this->queryParams;
        }

        return $this->queryParams = array_map(
            'strtolower',
            (array) config('request-log-analyzer.masking.query_params', [])
        );
    }

    /**
     * Validated regex patterns from masking.patterns + legacy scrub_patterns.
     *
     * @return string[]
     */
    private function patterns(): array
    {
        if ($this->patterns !== null) {
            return $this->patterns;
        }

        $this->patterns = [];

        $all = array_unique(array_merge(
            (array) config('request-log-analyzer.masking.patterns', []),
            (array) config('request-log-analyzer.scrub_patterns', []),
        ));

        foreach ($all as $pattern) {
            // Skip invalid regex to prevent crashes at runtime.
            if (@preg_match($pattern, '') !== false) {
                $this->patterns[] = $pattern;
            }
        }

        return $this->patterns;
    }
}
