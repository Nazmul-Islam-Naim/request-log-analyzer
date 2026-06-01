<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Error extends Model
{
    protected $table = 'rla_errors';

    protected $fillable = [
        'request_id',
        'exception_class',
        'message',
        'file',
        'line',
        'trace',
        'context',
        'severity',
    ];

    protected $casts = [
        'line' => 'integer',
        'context' => 'array',
        'request_id' => 'integer',
    ];

    // PSR-3 severity levels
    const SEVERITY_DEBUG = 'debug';

    const SEVERITY_INFO = 'info';

    const SEVERITY_NOTICE = 'notice';

    const SEVERITY_WARNING = 'warning';

    const SEVERITY_ERROR = 'error';

    const SEVERITY_CRITICAL = 'critical';

    const SEVERITY_ALERT = 'alert';

    const SEVERITY_EMERGENCY = 'emergency';

    // ── Relationships ─────────────────────────────────────────────────────────

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function shortClass(): string
    {
        return class_basename($this->exception_class);
    }

    public function isCritical(): bool
    {
        return in_array($this->severity, [self::SEVERITY_CRITICAL, self::SEVERITY_ALERT, self::SEVERITY_EMERGENCY]);
    }
}
