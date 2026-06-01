<?php

namespace NIN\RequestLogAnalyzer\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use NIN\RequestLogAnalyzer\Models\RequestReplay;
use NIN\RequestLogAnalyzer\Models\ReplayExecution;

class RequestReplayService
{
    /**
     * Sensitive fields/patterns that should be masked
     */
    private const SENSITIVE_PATTERNS = [
        'password', 'passwd', 'pwd',
        'token', 'api_key', 'apikey', 'secret',
        'authorization', 'auth', 'bearer',
        'credit_card', 'cc_number', 'cvv', 'cvc',
        'ssn', 'social_security',
        'session', 'sessionid', 'cookie',
        'pin', 'otp', 'mfa',
    ];

    /**
     * Mask sensitive data in array
     */
    public function maskSensitiveData(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $masked[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Check if a key is sensitive
     */
    private function isSensitiveKey(string $key): bool
    {
        $keyLower = strtolower($key);

        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (stripos($keyLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask a sensitive value
     */
    private function maskValue($value): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '***REDACTED***';
        }

        $str = (string) $value;

        if (strlen($str) === 0) {
            return '***EMPTY***';
        }

        if (strlen($str) <= 3) {
            return '***';
        }

        $visible = ceil(strlen($str) / 4);
        $masked = substr($str, 0, $visible) . '*' . substr($str, -1);

        return $masked;
    }

    /**
     * Extract and mask safe headers from request
     */
    public function extractSafeHeaders(array $headers): array
    {
        $safeHeaders = [];

        // Define allowed header prefixes that are safe
        $allowedPrefixes = [
            'accept',
            'accept-',
            'user-agent',
            'content-type',
            'content-length',
            'referer',
            'origin',
            'host',
            'x-requested-with',
        ];

        foreach ($headers as $key => $value) {
            $keyLower = strtolower($key);

            // Skip sensitive headers
            if ($this->isSensitiveKey($key)) {
                continue;
            }

            // Only include whitelisted safe headers
            $isSafe = false;
            foreach ($allowedPrefixes as $prefix) {
                if (stripos($keyLower, $prefix) === 0) {
                    $isSafe = true;
                    break;
                }
            }

            if ($isSafe) {
                // Get first value if it's an array
                $headerValue = is_array($value) ? $value[0] ?? null : $value;
                if ($headerValue) {
                    $safeHeaders[$key] = $headerValue;
                }
            }
        }

        return $safeHeaders;
    }

    /**
     * Check if a request method is safe for replay
     */
    public function isSafeMethod(string $method): bool
    {
        $method = strtoupper($method);
        return in_array($method, ['GET', 'POST']);
    }

    /**
     * Create a replay record from an existing request
     */
    public function createReplayFromRequest(array $requestData): RequestReplay
    {
        // Extract and mask payload
        $payload = $requestData['body'] ?? null;
        if ($payload && is_string($payload)) {
            // Try to parse as JSON
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $payload = $this->maskSensitiveData($decoded);
            }
        } elseif (is_array($payload)) {
            $payload = $this->maskSensitiveData($payload);
        }

        // Extract safe headers
        $headers = $this->extractSafeHeaders($requestData['headers'] ?? []);

        // Create replay record
        return RequestReplay::create([
            'original_request_id' => $requestData['request_id'] ?? null,
            'user_id' => $requestData['user_id'] ?? null,
            'method' => strtoupper($requestData['method'] ?? 'GET'),
            'url' => $requestData['url'] ?? '',
            'uri' => $requestData['uri'] ?? '',
            'query_string' => $requestData['query_string'] ?? null,
            'payload' => $payload ? json_encode($payload) : null,
            'headers' => $headers ? json_encode($headers) : null,
            'status' => 'pending',
        ]);
    }

    /**
     * Execute a replay and record the result
     */
    public function executeReplay(RequestReplay $replay): ReplayExecution
    {
        $execution = new ReplayExecution([
            'replay_id' => $replay->id,
            'executed_at' => now(),
        ]);

        try {
            // Validate method
            if (!$this->isSafeMethod($replay->method)) {
                throw new \Exception("Method {$replay->method} is not allowed for replay. Only GET and POST are supported.");
            }

            // Prepare request
            $headers = $replay->getSafeHeaders();
            $payload = $replay->getSafePayload();

            // Build full URL
            $url = $replay->url;
            if ($replay->query_string && !str_contains($url, '?')) {
                $url .= '?' . $replay->query_string;
            }

            // Execute request based on method
            $response = match (strtoupper($replay->method)) {
                'GET' => Http::withHeaders($headers)->get($url),
                'POST' => Http::withHeaders($headers)->post($url, $payload),
            };

            // Record execution
            $execution->status_code = $response->status();
            $execution->response_time_ms = $response->transferTime() ? (int) ($response->transferTime() * 1000) : null;
            $execution->response_body = substr($response->body(), 0, 5000); // Limit response storage

            // Mark replay as replayed
            $replay->markAsReplayed();
        } catch (\Throwable $e) {
            $execution->error_message = $e->getMessage();
            $replay->markAsFailed($e->getMessage());
        }

        $execution->save();

        return $execution;
    }

    /**
     * Archive old replays
     */
    public function archiveOldReplays(int $daysOld = 30): int
    {
        return RequestReplay::where('created_at', '<', now()->subDays($daysOld))
            ->where('status', '!=', 'archived')
            ->update(['status' => 'archived']);
    }

    /**
     * Delete archived replays
     */
    public function deleteArchivedReplays(int $olderThanDays = 90): int
    {
        return RequestReplay::where('created_at', '<', now()->subDays($olderThanDays))
            ->where('status', 'archived')
            ->delete();
    }
}
