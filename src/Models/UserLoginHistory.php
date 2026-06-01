<?php

namespace NIN\RequestLogAnalyzer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginHistory extends Model
{
    protected $table = 'rla_user_login_histories';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', User::class));
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function sessionDuration(): ?string
    {
        if (! $this->login_at || ! $this->logout_at) {
            return null;
        }

        $seconds = $this->login_at->diffInSeconds($this->logout_at);

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return "{$h}h {$m}m {$s}s";
        }
        if ($m > 0) {
            return "{$m}m {$s}s";
        }

        return "{$s}s";
    }
}
