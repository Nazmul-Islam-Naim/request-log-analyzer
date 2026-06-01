<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiRateUsage extends Model
{
    protected $table = 'rla_api_rate_usage';

    protected $fillable = [
        'user_id',
        'ip',
        'endpoint',
        'request_count',
        'first_request_at',
        'last_request_at',
        'rate_limit_exceeded',
        'period_type',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'request_count' => 'integer',
        'rate_limit_exceeded' => 'boolean',
        'first_request_at' => 'datetime',
        'last_request_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function incidents(): HasMany
    {
        return $this->hasMany(RateLimitIncident::class, 'user_id', 'user_id')
            ->where('ip', $this->ip);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeExceeded($query)
    {
        return $query->where('rate_limit_exceeded', true);
    }

    public function scopeByUser($query, ?int $userId)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        return $query->whereNull('user_id');
    }

    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip', $ip);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period_type', $period);
    }

    public function scopeRecent($query, int $minutes = 5)
    {
        return $query->where('last_request_at', '>=', now()->subMinutes($minutes));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getDurationInSeconds(): int
    {
        if ($this->first_request_at && $this->last_request_at) {
            return (int) $this->last_request_at->diffInSeconds($this->first_request_at);
        }
        return 0;
    }

    public function getRequestsPerSecond(): float
    {
        $duration = $this->getDurationInSeconds();
        if ($duration === 0) {
            return 0;
        }
        return round($this->request_count / $duration, 2);
    }

    public function isRecentlyActive(int $minutes = 5): bool
    {
        return $this->last_request_at && $this->last_request_at->isAfter(now()->subMinutes($minutes));
    }
}
