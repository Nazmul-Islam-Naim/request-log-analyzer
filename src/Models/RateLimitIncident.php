<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;

class RateLimitIncident extends Model
{
    protected $table = 'rla_rate_limit_incidents';

    protected $fillable = [
        'user_id',
        'ip',
        'endpoint',
        'request_count',
        'limit_threshold',
        'incident_type',
        'detected_at',
        'cleared_at',
        'resolved',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'request_count' => 'integer',
        'limit_threshold' => 'integer',
        'detected_at' => 'datetime',
        'cleared_at' => 'datetime',
        'resolved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('incident_type', $type);
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

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getExcessPercentage(): float
    {
        if ($this->limit_threshold === 0) {
            return 0;
        }
        return round((($this->request_count - $this->limit_threshold) / $this->limit_threshold) * 100, 2);
    }

    public function getDurationInMinutes(): int
    {
        if ($this->cleared_at) {
            return (int) $this->detected_at->diffInMinutes($this->cleared_at);
        }
        return (int) $this->detected_at->diffInMinutes(now());
    }

    public function markResolved(?string $notes = null): void
    {
        $this->update([
            'resolved' => true,
            'cleared_at' => now(),
            'notes' => $notes,
        ]);
    }
}
