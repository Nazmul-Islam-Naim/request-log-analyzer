{{-- ============================================================
     requests/show.blade.php  –  Single request detail
     (request info · inline timeline · queries · errors)
     ============================================================ --}}
@extends('request-log-analyzer::_layout')

@section('title', 'Request #' . $log->id . ' — Request Log Analyzer')
@section('page-title', 'Request #' . $log->id)

@push('head')
<style>
/* ── Timeline ─────────────────────────────────────────────────────────── */
.tl-wrap      { padding: 1.1rem 1.25rem 1.5rem; }
.tl-legend    { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1.1rem; }
.tl-leg-item  { display: flex; align-items: center; gap: .35rem; font-size: .7rem; color: #475569; }
.tl-leg-dot   { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }

/* Ruler */
.tl-ruler-row { display: flex; align-items: flex-end; gap: 0; margin-bottom: 4px; }
.tl-ruler-spc { width: 190px; flex-shrink: 0; }
.tl-ruler     { flex: 1; position: relative; height: 20px; }
.tl-tick      { position: absolute; bottom: 0; display: flex; flex-direction: column; align-items: center; gap: 1px; }
.tl-tick-line { width: 1px; height: 6px; background: #cbd5e1; }
.tl-tick-lbl  { font-size: .6rem; color: #94a3b8; transform: translateX(-50%); white-space: nowrap; }

/* Rows */
.tl-rows      { display: flex; flex-direction: column; gap: 5px; }
.tl-row       { display: flex; align-items: center; gap: 0; min-height: 26px; }
.tl-row-label { width: 190px; flex-shrink: 0; font-size: .72rem; color: #334155;
                overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                text-align: right; padding-right: 10px; }
.tl-track     { flex: 1; position: relative; height: 20px; background: #f8fafc;
                border-radius: 4px; border: 1px solid #f1f5f9; }
/* Total request span */
.tl-total-bar { position: absolute; inset: 2px 0; background: #e2e8f0; border-radius: 3px; }

/* Step bars */
.tl-bar       { position: absolute; top: 2px; height: 16px; border-radius: 3px;
                min-width: 2px; cursor: default; transition: filter .12s; }
.tl-bar:hover { filter: brightness(.88); }
.tl-bar-lbl   { position: absolute; inset: 0; display: flex; align-items: center;
                padding: 0 4px; font-size: .6rem; color: #fff;
                overflow: hidden; white-space: nowrap; pointer-events: none; }

/* Type colours */
.tl-middleware { background: #3b82f6; }
.tl-controller { background: #10b981; }
.tl-query      { background: #f59e0b; }
.tl-query.slow { background: #ef4444; }
.tl-event      { background: #8b5cf6; }
.tl-view       { background: #06b6d4; }
.tl-other      { background: #64748b; }

/* Tooltip */
.tl-tip {
    display: none; position: fixed; z-index: 200;
    background: #0f172a; color: #f8fafc;
    font-size: .72rem; line-height: 1.55;
    padding: .55rem .8rem; border-radius: 7px;
    max-width: 320px; pointer-events: none;
    box-shadow: 0 4px 16px rgba(0,0,0,.25);
}
.tl-tip strong { color: #fde68a; }
.tl-tip code   { color: #93c5fd; font-size: .68rem; word-break: break-all; }
.tl-sep        { height: 1px; background: #f1f5f9; margin: 4px 0 6px; }
</style>
@endpush

@section('content')

@php
    $mc  = in_array($log->method, ['GET','POST','PUT','PATCH','DELETE','HEAD']) ? 'badge-'.$log->method : 'badge-other';
    $sc  = $log->status_code;
    $cls = 'status-2xx';
    if ($sc >= 500)      $cls = 'status-5xx';
    elseif ($sc >= 400)  $cls = 'status-4xx';
    elseif ($sc >= 300)  $cls = 'status-3xx';
@endphp

{{-- ── Breadcrumb ──────────────────────────────────────────────────────── --}}
<div class="breadcrumb" style="margin-bottom:1rem;">
    <a href="{{ route('request-log-analyzer.requests') }}">Requests</a>
    <span class="breadcrumb-sep">›</span>
    <span>#{{ $log->id }}</span>
</div>

{{-- ── Title / action bar ──────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:.625rem;margin-bottom:1.25rem;flex-wrap:wrap;">
    <span class="badge {{ $mc }}" style="font-size:.78rem;padding:4px 10px;">{{ $log->method }}</span>
    <span class="{{ $cls }}" style="font-size:.95rem;font-weight:700;">{{ $log->status_code }}</span>
    <span style="font-size:.875rem;color:#0f172a;word-break:break-all;flex:1;min-width:0;">{{ $log->url }}</span>
    <a href="{{ route('request-log-analyzer.requests') }}" class="btn btn-ghost" style="flex-shrink:0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back
    </a>
</div>

{{-- ── Quick-stat row ──────────────────────────────────────────────────── --}}
<div class="stats-grid" style="margin-bottom:1.25rem;">
    <div class="stat-card">
        <div class="stat-label">Response Time</div>
        <div class="stat-value c-blue">{{ $log->response_time_ms ?? '—' }}<span style="font-size:.8rem;font-weight:400;color:#94a3b8;"> ms</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Memory Peak</div>
        <div class="stat-value c-purple">{{ $log->memory_usage_bytes ? number_format($log->memory_usage_bytes/1048576, 2) : '—' }}<span style="font-size:.8rem;font-weight:400;color:#94a3b8;"> MB</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">DB Queries</div>
        <div class="stat-value c-amber">{{ $log->queries->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Slow Queries</div>
        <div class="stat-value {{ $log->queries->where('is_slow',true)->count() > 0 ? 'c-red' : 'c-green' }}">
            {{ $log->queries->where('is_slow', true)->count() }}
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Exceptions</div>
        <div class="stat-value {{ $log->errors->count() > 0 ? 'c-red' : 'c-green' }}">{{ $log->errors->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Steps</div>
        <div class="stat-value c-slate">{{ $log->steps->count() }}</div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     1. REQUEST INFO
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-head">
        <h2>Request Info</h2>
        <span class="text-muted" style="font-size:.72rem;">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-field">
                <span class="df-label">Method</span>
                <span class="df-value"><span class="badge {{ $mc }}">{{ $log->method }}</span></span>
            </div>
            <div class="detail-field">
                <span class="df-label">Status</span>
                <span class="df-value {{ $cls }}" style="font-weight:700;">{{ $log->status_code }} {{ $log->statusLabel() }}</span>
            </div>
            <div class="detail-field" style="grid-column:span 2;">
                <span class="df-label">Full URL</span>
                <span class="df-value mono" style="font-size:.73rem;word-break:break-all;">{{ $log->url }}</span>
            </div>
            <div class="detail-field">
                <span class="df-label">URI</span>
                <span class="df-value mono">{{ $log->uri }}</span>
            </div>
            <div class="detail-field">
                <span class="df-label">Query String</span>
                <span class="df-value mono">{{ $log->query_string ?: '—' }}</span>
            </div>
            <div class="detail-field">
                <span class="df-label">IP</span>
                <span class="df-value mono">{{ $log->ip ?? '—' }}</span>
            </div>
            <div class="detail-field">
                <span class="df-label">Location</span>
                <span class="df-value">
                    @if($log->city || $log->country)
                        {{ implode(', ', array_filter([$log->city, $log->country])) }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </span>
            </div>
            <div class="detail-field">
                <span class="df-label">User ID</span>
                <span class="df-value">{{ $log->user_id ?? 'Guest' }}</span>
            </div>
            <div class="detail-field" style="grid-column:span 2;">
                <span class="df-label">User Agent</span>
                <span class="df-value mono" style="font-size:.7rem;">{{ $log->user_agent ?? '—' }}</span>
            </div>
            <div class="detail-field">
                <span class="df-label">Session ID</span>
                <span class="df-value mono" style="font-size:.7rem;word-break:break-all;">{{ $log->session_id ?? '—' }}</span>
            </div>
            <div class="detail-field">
                <span class="df-label">ULID</span>
                <span class="df-value mono" style="font-size:.7rem;">{{ $log->ulid }}</span>
            </div>
        </div>

        {{-- ── Tags ────────────────────────────────────────────────────── --}}
        @if(! empty($log->tags))
        <div style="margin-top:1.1rem;padding-top:1.1rem;border-top:1px solid #f1f5f9;">
            <div class="df-label" style="margin-bottom:.5rem;">Tags</div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
                @foreach($log->tags as $tag)
                <a href="{{ route('request-log-analyzer.requests', ['tag' => $tag]) }}"
                   style="display:inline-flex;align-items:center;gap:.3rem;padding:3px 10px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:9999px;font-size:.72rem;font-weight:500;text-decoration:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    {{ $tag }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(! empty($log->request_headers))
        <div style="margin-top:1.1rem;padding-top:1.1rem;border-top:1px solid #f1f5f9;">
            <div class="df-label" style="margin-bottom:.5rem;">Request Headers</div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
                @foreach($log->request_headers as $name => $values)
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:5px;padding:.3rem .65rem;min-width:0;">
                    <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;font-weight:600;">{{ $name }}</div>
                    <div class="text-mono" style="font-size:.7rem;">{{ is_array($values) ? implode(', ', $values) : $values }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     2. TIMELINE
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-head">
        <h2>Lifecycle Timeline</h2>
        @if($log->steps->isNotEmpty())
        <span class="text-muted" style="font-size:.72rem;">{{ $log->steps->count() }} steps &mdash; {{ $log->response_time_ms ?? '?' }} ms total</span>
        @endif
    </div>

    @if($log->steps->isEmpty())
        <div class="empty-state">
            No lifecycle steps recorded.<br>
            Enable <code>track_steps</code> in config and register the <code>TrackRequest</code> middleware.
        </div>
    @else
    @php
        $totalMs = max((int)($log->response_time_ms ?? 1), 1);
        $steps   = $log->steps;   // already ordered by sequence
        $pct     = fn(float $ms): string => round(min(($ms / $totalMs) * 100, 100), 4) . '%';

        // Ruler: 8 evenly-spaced ticks
        $ticks = [];
        for ($i = 0; $i <= 8; $i++) {
            $ticks[] = round(($totalMs / 8) * $i);
        }

        $typeColors = [
            'middleware' => '#3b82f6',
            'controller' => '#10b981',
            'query'      => '#f59e0b',
            'event'      => '#8b5cf6',
            'view'       => '#06b6d4',
            'other'      => '#64748b',
        ];
    @endphp

    <div class="tl-wrap">

        {{-- Legend --}}
        <div class="tl-legend">
            @foreach($typeColors as $type => $color)
            <div class="tl-leg-item">
                <div class="tl-leg-dot" style="background:{{ $color }};"></div>
                {{ ucfirst($type) }}
            </div>
            @endforeach
            <div class="tl-leg-item">
                <div class="tl-leg-dot" style="background:#ef4444;"></div>
                Slow query
            </div>
        </div>

        {{-- Ruler --}}
        <div class="tl-ruler-row">
            <div class="tl-ruler-spc"></div>
            <div class="tl-ruler">
                @foreach($ticks as $tick)
                <div class="tl-tick" style="left:{{ $pct($tick) }};">
                    <span class="tl-tick-lbl">{{ $tick }}ms</span>
                    <span class="tl-tick-line"></span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Full-request span --}}
        <div class="tl-rows">
            <div class="tl-row">
                <div class="tl-row-label" style="color:#94a3b8;font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;">total</div>
                <div class="tl-track">
                    <div class="tl-total-bar"></div>
                </div>
            </div>

            <div class="tl-sep" style="margin-left:190px;"></div>

            @php $prevType = null; @endphp

            @foreach($steps as $step)
            @php
                // Insert section divider when step type changes
                $isSlow    = $step->type === 'query' && ($step->metadata['is_slow'] ?? false);
                $barColor  = $isSlow ? '#ef4444' : ($typeColors[$step->type] ?? '#64748b');
                $typeClass = 'tl-' . $step->type . ($isSlow ? ' slow' : '');

                $leftPct  = $pct(max(0, $step->started_at_ms));
                $widthPct = $pct(max(0, $step->duration_ms));
                // Force minimum visible width via inline style calc
                $widthPct = max((float)$step->duration_ms / $totalMs * 100, 0.15) . '%';

                // Tooltip content
                if ($step->type === 'query' && isset($step->metadata['sql'])) {
                    $tipLines  = '<strong>' . e($step->type) . '</strong> &mdash; ' . e(number_format($step->duration_ms)) . ' ms';
                    $tipLines .= '<br><br><code>' . e($step->metadata['sql']) . '</code>';
                    $tipLines .= '<br><br>Start: ' . $step->started_at_ms . ' ms &nbsp; End: ' . ($step->started_at_ms + $step->duration_ms) . ' ms';
                    $tipLines .= '<br>Connection: ' . e($step->metadata['connection'] ?? '?');
                    if ($isSlow) $tipLines .= '<br><span style="color:#fca5a5;">⚠ Slow query</span>';
                } else {
                    $tipLines  = '<strong>' . e(ucfirst($step->type)) . '</strong>: ' . e($step->name);
                    $tipLines .= '<br>Start: ' . $step->started_at_ms . ' ms &nbsp; Duration: ' . $step->duration_ms . ' ms';
                }

                // Truncate label shown next to the row
                $label = $step->name;
                if ($step->type === 'query' && isset($step->metadata['sql'])) {
                    $label = preg_replace('/\s+/', ' ', $step->metadata['sql']);
                    $label = mb_strlen($label) > 38 ? mb_substr($label, 0, 35).'…' : $label;
                }

                // Separator on type change (skip first row and separators between queries)
                $showSep = $prevType !== null && $prevType !== $step->type
                           && !($prevType === 'query' && $step->type === 'query');
                $prevType = $step->type;
            @endphp

            @if($showSep)
                <div class="tl-sep" style="margin-left:190px;"></div>
            @endif

            <div class="tl-row">
                <div class="tl-row-label" title="{{ $step->name }}">{{ $label }}</div>
                <div class="tl-track">
                    <div class="tl-bar {{ $typeClass }}"
                         style="left:{{ $leftPct }};width:{{ $widthPct }};background:{{ $barColor }};"
                         data-tip="{{ e($tipLines) }}"
                         onmouseenter="tlShowTip(event,this)"
                         onmouseleave="tlHideTip()">
                        @if((float)$step->duration_ms / $totalMs > 0.06)
                            <span class="tl-bar-lbl">{{ $step->duration_ms }}ms</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>{{-- .tl-rows --}}
    </div>{{-- .tl-wrap --}}
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     3. DATABASE QUERIES
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-head">
        <h2>Database Queries</h2>
        @if($log->queries->isNotEmpty())
        <span class="text-muted" style="font-size:.72rem;">
            {{ $log->queries->count() }} queries
            &mdash; {{ number_format($log->queries->sum('time_ms'), 2) }} ms total
            @if($log->queries->where('is_slow', true)->count() > 0)
                &mdash; <span style="color:#dc2626;font-weight:600;">{{ $log->queries->where('is_slow', true)->count() }} slow</span>
            @endif
        </span>
        @endif
    </div>

    @if($log->queries->isEmpty())
        <div class="empty-state">No queries recorded for this request.</div>
    @else
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th style="width:72px;">ms</th>
                        <th style="width:90px;">Connection</th>
                        <th>SQL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($log->queries as $i => $query)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td style="font-variant-numeric:tabular-nums;white-space:nowrap;">
                            @if($query->is_slow)
                                <span style="color:#dc2626;font-weight:700;">{{ number_format($query->time_ms, 2) }}</span>
                                <span style="color:#dc2626;font-size:.7rem;" title="Slow query">⚠</span>
                            @else
                                {{ number_format($query->time_ms, 2) }}
                            @endif
                        </td>
                        <td><span class="badge badge-other">{{ $query->connection }}</span></td>
                        <td style="font-family:'Menlo','Consolas',monospace;font-size:.72rem;">
                            <div style="max-width:640px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $query->interpolatedSql() }}">
                                {{ $query->sql }}
                            </div>
                            @if(! empty($query->bindings))
                                <div style="color:#94a3b8;font-size:.65rem;margin-top:2px;">
                                    {{ count($query->bindings) }} binding(s): {{ implode(', ', array_map(fn($b) => json_encode($b), array_slice($query->bindings, 0, 6))) }}{{ count($query->bindings) > 6 ? '…' : '' }}
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     4. EXCEPTIONS / ERRORS
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-head">
        <h2>Exceptions</h2>
        @if($log->errors->isNotEmpty())
            <span class="text-muted" style="font-size:.72rem;">{{ $log->errors->count() }} recorded</span>
        @endif
    </div>

    @if($log->errors->isEmpty())
        <div class="empty-state">No exceptions recorded for this request.</div>
    @else
        @foreach($log->errors as $error)
        <div style="padding:1rem 1.25rem;{{ ! $loop->last ? 'border-bottom:1px solid #f8fafc;' : '' }}">
            {{-- Error header --}}
            <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.65rem;flex-wrap:wrap;">
                <span class="badge badge-{{ $error->severity }}">{{ $error->severity }}</span>
                <span style="font-weight:600;font-size:.82rem;">{{ $error->shortClass() }}</span>
                <span style="font-size:.7rem;color:#94a3b8;font-family:'Menlo','Consolas',monospace;">
                    {{ class_basename($error->file) }}:{{ $error->line }}
                </span>
                <span class="text-muted" style="font-size:.7rem;margin-left:auto;">{{ $error->created_at->format('H:i:s') }}</span>
            </div>

            {{-- Message --}}
            <div style="font-size:.82rem;color:#1e293b;margin-bottom:.5rem;padding:.6rem .75rem;background:#fef2f2;border-left:3px solid #fca5a5;border-radius:0 4px 4px 0;">
                {{ $error->message }}
            </div>

            {{-- File reference --}}
            <div class="text-mono text-muted" style="font-size:.68rem;margin-bottom:.35rem;">
                {{ $error->file }}:{{ $error->line }}
            </div>

            {{-- Stack trace --}}
            @if($error->trace)
            <details>
                <summary style="font-size:.72rem;color:#64748b;cursor:pointer;user-select:none;margin-top:.35rem;">
                    Show stack trace
                </summary>
                <pre style="margin-top:.5rem;background:#0f172a;color:#e2e8f0;padding:.875rem 1rem;border-radius:6px;font-size:.67rem;line-height:1.65;overflow-x:auto;tab-size:4;">{{ $error->trace }}</pre>
            </details>
            @endif
        </div>
        @endforeach
    @endif
</div>

@endsection

@push('scripts')
<script>
(function () {
    const tip = document.createElement('div');
    tip.className = 'tl-tip';
    document.body.appendChild(tip);

    window.tlShowTip = function (e, el) {
        tip.innerHTML = el.dataset.tip;
        tip.style.display = 'block';
        tlMoveTip(e);
    };
    window.tlHideTip = function () {
        tip.style.display = 'none';
    };
    document.addEventListener('mousemove', function (e) {
        if (tip.style.display !== 'none') tlMoveTip(e);
    });
    function tlMoveTip(e) {
        const x = e.clientX + 16;
        const y = e.clientY + 14;
        tip.style.left = x + 'px';
        tip.style.top  = y + 'px';
        const r = tip.getBoundingClientRect();
        if (r.right  > window.innerWidth)  tip.style.left = (e.clientX - r.width  - 8) + 'px';
        if (r.bottom > window.innerHeight) tip.style.top  = (e.clientY - r.height - 8) + 'px';
    }
})();
</script>
@endpush

