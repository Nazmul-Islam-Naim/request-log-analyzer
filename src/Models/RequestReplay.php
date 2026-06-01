<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestReplay extends Model
{
    protected $table = 'rla_request_replays';

    protected $fillable = [
        'original_request_id',
        'user_id',
        'method',
        'url',
        'uri',
        'query_string',
        'payload',
        'headers',
        'status',
        'last_error',
        'replayed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'replayed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function originalRequest()
    {
        return $this->belongsTo(Request::class, 'original_request_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ReplayExecution::class, 'replay_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReplayed($query)
    {
        return $query->where('status', 'replayed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeForUser($query, ?int $userId)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    // ── Helper Methods ────────────────────────────────────────────────────

    /**
     * Check if this replay is executable (only GET and POST allowed)
     */
    public function isExecutable(): bool
    {
        return in_array(strtoupper($this->method), ['GET', 'POST']);
    }

    /**
     * Get the latest execution for this replay
     */
    public function getLatestExecution(): ?ReplayExecution
    {
        return $this->executions()->latest('executed_at')->first();
    }

    /**
     * Mark replay as successfully replayed
     */
    public function markAsReplayed(): void
    {
        $this->update([
            'status' => 'replayed',
            'replayed_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Mark replay as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
        ]);
    }

    /**
     * Get safe headers for display (no sensitive values)
     */
    public function getSafeHeaders(): array
    {
        $headers = $this->headers;
        if (is_string($headers)) {
            $headers = json_decode($headers, true) ?? [];
        }
        if (!is_array($headers)) {
            return [];
        }

        // Use the service to extract safe headers
        return app('NIN\RequestLogAnalyzer\Services\RequestReplayService')
            ->extractSafeHeaders($headers);
    }

    /**
     * Get safe payload for display (no sensitive values)
     */
    public function getSafePayload(): array
    {
        $payload = $this->payload;
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?? [];
        }
        if (!is_array($payload)) {
            return [];
        }

        // Use the service to mask sensitive data
        return app('NIN\RequestLogAnalyzer\Services\RequestReplayService')
            ->maskSensitiveData($payload);
    }

    /**
     * Get execution history
     */
    public function getExecutionHistory()
    {
        return $this->executions()
            ->orderByDesc('executed_at')
            ->limit(10)
            ->get();
    }
}
