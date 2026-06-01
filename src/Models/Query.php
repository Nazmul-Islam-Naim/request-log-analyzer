<?php

namespace NIN\RequestLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Query extends Model
{
    protected $table = 'rla_queries';

    protected $fillable = [
        'request_id',
        'connection',
        'sql',
        'bindings',
        'time_ms',
        'is_slow',
    ];

    protected $casts = [
        'bindings' => 'array',
        'time_ms' => 'float',
        'is_slow' => 'boolean',
        'request_id' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Interpolate bindings into the SQL for display (read-only, not for execution).
     */
    public function interpolatedSql(): string
    {
        if (empty($this->bindings)) {
            return $this->sql;
        }

        $sql = $this->sql;
        $bindings = $this->bindings;

        return preg_replace_callback('/\?/', function () use (&$bindings) {
            $value = array_shift($bindings);
            if (is_null($value)) {
                return 'NULL';
            }
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            if (is_numeric($value)) {
                return (string) $value;
            }

            return "'".addslashes((string) $value)."'";
        }, $sql);
    }
}
