<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplayExecution extends Model
{
    protected $table = 'rla_replay_executions';

    protected $fillable = [
        'replay_id',
        'status_code',
        'response_time_ms',
        'response_body',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function replay(): BelongsTo
    {
        return $this->belongsTo(RequestReplay::class, 'replay_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeSuccessful($query)
    {
        return $query->where('status_code', '<', 400);
    }

    public function scopeFailed($query)
    {
        return $query->where('status_code', '>=', 400)
            ->orWhereNotNull('error_message');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('executed_at');
    }

    // ── Helper Methods ────────────────────────────────────────────────────

    /**
     * Check if execution was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status_code && $this->status_code < 400 && !$this->error_message;
    }

    /**
     * Get execution duration formatted
     */
    public function getDurationFormatted(): string
    {
        if (!$this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms < 1000) {
            return "{$this->response_time_ms}ms";
        }

        $seconds = round($this->response_time_ms / 1000, 2);
        return "{$seconds}s";
    }

    /**
     * Get status badge for display
     */
    public function getStatusBadge(): array
    {
        if ($this->error_message) {
            return ['text' => 'Error', 'class' => 'badge-danger'];
        }

        if ($this->status_code) {
            if ($this->status_code < 300) {
                return ['text' => "Success {$this->status_code}", 'class' => 'badge-success'];
            } elseif ($this->status_code < 400) {
                return ['text' => "Redirect {$this->status_code}", 'class' => 'badge-info'];
            } elseif ($this->status_code < 500) {
                return ['text' => "Client Error {$this->status_code}", 'class' => 'badge-warning'];
            } else {
                return ['text' => "Server Error {$this->status_code}", 'class' => 'badge-danger'];
            }
        }

        return ['text' => 'Unknown', 'class' => 'badge-secondary'];
    }
}
