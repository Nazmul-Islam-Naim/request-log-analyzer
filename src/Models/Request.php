<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    protected $table = 'rla_requests';

    protected $fillable = [
        'ulid',
        'method',
        'url',
        'uri',
        'query_string',
        'ip',
        'country',
        'city',
        'user_agent',
        'user_id',
        'session_id',
        'status_code',
        'response_time_ms',
        'memory_usage_bytes',
        'request_headers',
        'response_headers',
        'tags',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'memory_usage_bytes' => 'integer',
        'user_id' => 'integer',
        'request_headers' => 'array',
        'response_headers' => 'array',
        'tags' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function steps(): HasMany
    {
        return $this->hasMany(RequestStep::class, 'request_id')->orderBy('sequence');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(Error::class, 'request_id');
    }

    public function queries(): HasMany
    {
        return $this->hasMany(Query::class, 'request_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isSuccess(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function isError(): bool
    {
        return $this->status_code >= 400;
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->status_code >= 500 => 'Server Error',
            $this->status_code >= 400 => 'Client Error',
            $this->status_code >= 300 => 'Redirect',
            $this->status_code >= 200 => 'Success',
            default => 'Unknown',
        };
    }

    public function statusBadgeClass(): string
    {
        return match (true) {
            $this->status_code >= 500 => 'status-5xx',
            $this->status_code >= 400 => 'status-4xx',
            $this->status_code >= 300 => 'status-3xx',
            default => 'status-2xx',
        };
    }

    public function totalQueryTimeMs(): float
    {
        return (float) $this->queries()->sum('time_ms');
    }

    public function slowQueriesCount(): int
    {
        return $this->queries()->where('is_slow', true)->count();
    }

    /**
     * Return true when this request's response time exceeds the configured
     * slow_request_threshold_ms. Returns false when response_time_ms is null
     * or the threshold is set to 0 (disabled).
     */
    public function isSlow(): bool
    {
        $threshold = (int) config('request-log-analyzer.slow_request_threshold_ms', 500);

        if ($threshold <= 0 || $this->response_time_ms === null) {
            return false;
        }

        return $this->response_time_ms >= $threshold;
    }
}
