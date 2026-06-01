<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStep extends Model
{
    protected $table = 'rla_request_steps';

    protected $fillable = [
        'request_id',
        'name',
        'type',
        'sequence',
        'started_at_ms',
        'duration_ms',
        'metadata',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'started_at_ms' => 'integer',
        'duration_ms' => 'integer',
        'metadata' => 'array',
    ];

    // Step type constants
    const TYPE_MIDDLEWARE = 'middleware';

    const TYPE_CONTROLLER = 'controller';

    const TYPE_VIEW = 'view';

    const TYPE_EVENT = 'event';

    const TYPE_JOB = 'job';

    const TYPE_OTHER = 'other';

    // ── Relationships ─────────────────────────────────────────────────────────

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
