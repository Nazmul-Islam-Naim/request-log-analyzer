{{-- ============================================================
     tools.blade.php  –  Export & Data Cleanup
     ============================================================ --}}
@extends('request-log-analyzer::_layout')

@section('page-title', 'Tools')

@push('head')
<style>
/* ── Export ───────────────────────────────────────────────────────────── */
.export-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding: 1.25rem 1.4rem;
}
@media (max-width: 760px) { .export-grid { grid-template-columns: 1fr; } }

.export-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: .75rem;
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: .65rem;
}
.export-item-label {
    font-size: .8rem;
    font-weight: 700;
    color: #374151;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.export-item-label svg { flex-shrink: 0; }
.export-btns {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}
.btn-export {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .38rem .8rem;
    border-radius: .45rem;
    font-size: .73rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid transparent;
    transition: background .15s, border-color .15s;
    cursor: pointer;
}
.btn-export-csv {
    background: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
}
.btn-export-csv:hover { background: #dcfce7; border-color: #86efac; }
.btn-export-xlsx {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.btn-export-xlsx:hover { background: #dbeafe; border-color: #93c5fd; }

/* ── Cleanup ──────────────────────────────────────────────────────────── */
.cleanup-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.1rem;
    padding: 1.25rem 1.4rem;
}
@media (max-width: 760px) { .cleanup-grid { grid-template-columns: 1fr; } }

.cleanup-info-row {
    display: flex;
    flex-direction: column;
    gap: .55rem;
}
.cleanup-stat {
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .8rem;
    color: #374151;
}
.cleanup-stat-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.cleanup-stat strong { color: #111827; }

.cleanup-form { display: flex; flex-direction: column; gap: .75rem; }
.cleanup-form label {
    font-size: .75rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: .15rem;
    display: block;
}
.cleanup-input-row { display: flex; gap: .6rem; align-items: flex-end; }
.cleanup-input {
    flex: 1;
    padding: .45rem .75rem;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    font-size: .82rem;
    color: #111827;
    outline: none;
    transition: border-color .15s;
    background: #fff;
}
.cleanup-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }

.cleanup-dry-row {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .78rem;
    color: #6b7280;
}
.cleanup-dry-row input[type=checkbox] { accent-color: #3b82f6; cursor: pointer; }
.cleanup-dry-row label { cursor: pointer; margin-bottom: 0; font-weight: 400; }

.btn-cleanup {
    padding: .45rem 1.1rem;
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: .5rem;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, opacity .15s;
    white-space: nowrap;
}
.btn-cleanup:hover { background: #dc2626; }
.btn-cleanup:disabled { opacity: .55; cursor: not-allowed; }

.cleanup-result {
    margin: 0 1.4rem 1.1rem;
    border-radius: .65rem;
    padding: .85rem 1.1rem;
    font-size: .8rem;
}
.cleanup-result.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.cleanup-result.dry     { background: #eef2ff; border: 1px solid #c7d2fe; color: #3730a3; }
.cleanup-result.error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.cleanup-result strong { font-weight: 700; }
.cleanup-result-table {
    margin-top: .6rem;
    width: 100%;
    border-collapse: collapse;
    font-size: .76rem;
}
.cleanup-result-table th,
.cleanup-result-table td {
    padding: .3rem .6rem;
    text-align: left;
    border-bottom: 1px solid rgba(0,0,0,.06);
}
.cleanup-result-table th { font-weight: 700; opacity: .7; }
.cleanup-result-table tr:last-child td { border-bottom: none; }

/* ── Shared section head ──────────────────────────────────────────────── */
.section-head {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .875rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}
.section-head h2 { font-size: .82rem; font-weight: 700; color: #0f172a; }
.section-head-badge {
    margin-left: auto;
    font-size: .65rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #64748b;
    padding: 2px 8px;
    border-radius: 9999px;
}
</style>
@endpush

@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div style="margin-bottom:1.5rem;">
    <div class="breadcrumb">
        <a href="{{ route('request-log-analyzer.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span>Tools</span>
    </div>
    <h1 style="font-size:1.15rem;font-weight:700;color:#0f172a;margin-top:.3rem;">Tools</h1>
    <p style="font-size:.8rem;color:#64748b;margin-top:.25rem;">Export log data and manage database retention.</p>
</div>

{{-- ════════════════════════════════════════════════════════════════
     SECTION 1 — Export Logs
════════════════════════════════════════════════════════════════ --}}
<div class="card" style="margin-bottom:1.25rem;">
    <div class="section-head">
        <svg style="width:15px;height:15px;color:#10b981;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        <h2>Export Logs</h2>
        <span class="section-head-badge">CSV &amp; Excel</span>
    </div>

    <div class="export-grid">

        {{-- Requests --}}
        <div class="export-item">
            <div class="export-item-label">
                <svg style="width:14px;height:14px;color:#3b82f6;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Requests
            </div>
            <div class="export-btns">
                <a href="{{ route('request-log-analyzer.export', ['type' => 'requests', 'format' => 'csv']) }}"
                   class="btn-export btn-export-csv">
                    <svg style="width:11px;height:11px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                    CSV
                </a>
                <a href="{{ route('request-log-analyzer.export', ['type' => 'requests', 'format' => 'excel']) }}"
                   class="btn-export btn-export-xlsx">
                    <svg style="width:11px;height:11px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                    Excel
                </a>
            </div>
        </div>

        {{-- Errors --}}
        <div class="export-item">
            <div class="export-item-label">
                <svg style="width:14px;height:14px;color:#ef4444;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Errors
            </div>
            <div class="export-btns">
                <a href="{{ route('request-log-analyzer.export', ['type' => 'errors', 'format' => 'csv']) }}"
                   class="btn-export btn-export-csv">
                    <svg style="width:11px;height:11px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                    CSV
                </a>
                <a href="{{ route('request-log-analyzer.export', ['type' => 'errors', 'format' => 'excel']) }}"
                   class="btn-export btn-export-xlsx">
                    <svg style="width:11px;height:11px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                    Excel
                </a>
            </div>
        </div>

        {{-- Queries --}}
        <div class="export-item">
            <div class="export-item-label">
                <svg style="width:14px;height:14px;color:#f59e0b;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                Queries
            </div>
            <div class="export-btns">
                <a href="{{ route('request-log-analyzer.export', ['type' => 'queries', 'format' => 'csv']) }}"
                   class="btn-export btn-export-csv">
                    <svg style="width:11px;height:11px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                    CSV
                </a>
                <a href="{{ route('request-log-analyzer.export', ['type' => 'queries', 'format' => 'excel']) }}"
                   class="btn-export btn-export-xlsx">
                    <svg style="width:11px;height:11px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                    Excel
                </a>
            </div>
        </div>

    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     SECTION 2 — Data Cleanup
════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="section-head">
        <svg style="width:15px;height:15px;color:#ef4444;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6"/><path d="M14 11v6"/>
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
        </svg>
        <h2>Data Cleanup</h2>
        <span class="section-head-badge">Retention management</span>
    </div>

    {{-- Flash: error --}}
    @if(session('rla_cleanup_error'))
        <div class="cleanup-result error">
            <strong>Error:</strong> {{ session('rla_cleanup_error') }}
        </div>
    @endif

    {{-- Flash: result --}}
    @if(session('rla_cleanup_done'))
        @php
            $isDry  = session('rla_cleanup_dry_run');
            $totals = session('rla_cleanup_totals', []);
            $total  = session('rla_cleanup_total', 0);
            $days   = session('rla_cleanup_days');
        @endphp
        <div class="cleanup-result {{ $isDry ? 'dry' : 'success' }}">
            @if($isDry)
                <strong>Dry run &mdash;</strong>
                {{ $total > 0
                    ? number_format($total).' row(s) would be deleted (older than '.$days.' day(s)).'
                    : 'No records found outside the '.$days.'-day retention window.' }}
            @else
                <strong>Cleanup complete &mdash;</strong>
                {{ $total > 0
                    ? number_format($total).' row(s) deleted (older than '.$days.' day(s)).'
                    : 'No records found outside the '.$days.'-day retention window.' }}
            @endif

            @if($total > 0)
            <table class="cleanup-result-table">
                <thead><tr><th>Table</th><th>{{ $isDry ? 'Would delete' : 'Deleted' }}</th></tr></thead>
                <tbody>
                    @foreach($totals as $table => $count)
                        <tr>
                            <td><code>{{ $table }}</code></td>
                            <td>{{ number_format($count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    @endif

    <div class="cleanup-grid">

        {{-- Left: current config info --}}
        <div class="cleanup-info-row">
            @php
                $cleanupEnabled  = config('request-log-analyzer.cleanup.enabled', false);
                $retentionDays   = (int) config('request-log-analyzer.cleanup.retention_days', 90);
                $cleanupSchedule = config('request-log-analyzer.cleanup.schedule', '0 2 * * *');
            @endphp

            <div style="font-size:.78rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">
                Current Configuration
            </div>

            <div class="cleanup-stat">
                <span class="cleanup-stat-dot" style="background:{{ $cleanupEnabled ? '#22c55e' : '#9ca3af' }};"></span>
                <span>Auto-schedule:
                    <strong>{{ $cleanupEnabled ? 'Enabled' : 'Disabled' }}</strong>
                    @if($cleanupEnabled)
                        &mdash; <code style="font-size:.74rem;">{{ $cleanupSchedule }}</code>
                    @endif
                </span>
            </div>

            <div class="cleanup-stat">
                <span class="cleanup-stat-dot" style="background:#3b82f6;"></span>
                <span>Retention period: <strong>{{ $retentionDays }} days</strong></span>
            </div>

            <div style="font-size:.74rem;color:#9ca3af;margin-top:.25rem;line-height:1.5;">
                Records older than the retention period are eligible for deletion.<br>
                Set <code style="font-size:.72rem;">REQUEST_LOG_ANALYZER_CLEANUP_ENABLED=true</code> to enable automatic scheduling.
            </div>
        </div>

        {{-- Right: manual run form --}}
        <form method="POST"
              action="{{ route('request-log-analyzer.cleanup') }}"
              class="cleanup-form"
              onsubmit="return confirmCleanup(this)">
            @csrf

            <div style="font-size:.78rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">
                Run Manual Cleanup
            </div>

            <div>
                <label for="cleanup-days">Retention period (days)</label>
                <div class="cleanup-input-row">
                    <input id="cleanup-days"
                           class="cleanup-input"
                           type="number"
                           name="days"
                           min="1"
                           placeholder="{{ $retentionDays }}"
                           value="">
                </div>
                <div style="font-size:.72rem;color:#9ca3af;margin-top:.3rem;">
                    Leave blank to use the configured default ({{ $retentionDays }} days).
                </div>
            </div>

            <div class="cleanup-dry-row">
                <input type="checkbox" id="cleanup-dry-run" name="dry_run" value="1">
                <label for="cleanup-dry-run">Dry run — preview rows without deleting</label>
            </div>

            <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                <button type="submit" class="btn-cleanup" id="cleanup-submit-btn">
                    <svg style="width:13px;height:13px;display:inline;vertical-align:-.15em;margin-right:.3rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                    Run Cleanup
                </button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
function confirmCleanup(form) {
    const isDry = form.querySelector('#cleanup-dry-run').checked;
    if (isDry) return true;
    const days = form.querySelector('#cleanup-days').value;
    const label = days ? days + ' days' : '{{ $retentionDays }} days (default)';
    if (! confirm('Delete all analyzer records older than ' + label + '?\n\nThis cannot be undone.')) {
        return false;
    }
    form.querySelector('#cleanup-submit-btn').disabled = true;
    form.querySelector('#cleanup-submit-btn').textContent = 'Running…';
    return true;
}
</script>
@endpush

@endsection
