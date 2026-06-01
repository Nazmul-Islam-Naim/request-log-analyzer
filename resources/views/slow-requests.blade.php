{{-- ============================================================
     slow-requests.blade.php  –  Slow request list page
     ============================================================ --}}
@extends('request-log-analyzer::_layout')

@section('page-title', 'Slow Requests')

@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:1.25rem;">
    <div style="padding:1.1rem 1.4rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <div style="display:flex;align-items:center;gap:.6rem;">
                <svg style="width:18px;height:18px;color:#dc2626;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                <h1 style="font-size:1rem;font-weight:700;color:#111827;margin:0;">Slow Requests</h1>
            </div>
            <p style="font-size:.78rem;color:#6b7280;margin:.25rem 0 0;">
                Requests whose response time is <strong style="color:#dc2626;">&ge;&nbsp;{{ number_format($threshold) }}&nbsp;ms</strong>
                (threshold: <code>slow_request_threshold_ms</code>), sorted by slowest first.
                {{ $logs->total() > 0 ? number_format($logs->total()).' request'.($logs->total() === 1 ? '' : 's').' found.' : 'No slow requests recorded yet.' }}
            </p>
        </div>
        <div style="margin-left:auto;display:flex;align-items:center;gap:.75rem;flex-shrink:0;">
            <a href="{{ route('request-log-analyzer.requests') }}" class="btn btn-ghost" style="height:32px;">All Requests</a>
            <a href="{{ route('request-log-analyzer.dashboard') }}" class="btn btn-ghost" style="height:32px;">Dashboard</a>
        </div>
    </div>
</div>

{{-- ── Table (or empty state) ───────────────────────────────────────────── --}}
<div class="card">
    @if($logs->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:#d1d5db;margin-bottom:.5rem;">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <p style="font-size:.85rem;color:#9ca3af;text-align:center;">
                No slow requests detected.<br>
                <span style="font-size:.75rem;">All recorded responses finished in under {{ number_format($threshold) }} ms.</span>
            </p>
        </div>
    @else
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Method</th>
                        <th>URI</th>
                        <th>Status</th>
                        <th>Time (ms)</th>
                        <th>Queries</th>
                        <th>Errors</th>
                        <th>IP</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php
                        $mc = in_array($log->method, ['GET','POST','PUT','PATCH','DELETE','HEAD'])
                            ? 'badge-'.$log->method
                            : 'badge-other';
                    @endphp
                    <tr>
                        <td class="text-muted" style="font-variant-numeric:tabular-nums;">{{ $log->id }}</td>
                        <td><span class="badge {{ $mc }}">{{ $log->method }}</span></td>
                        <td class="trunc" title="{{ $log->url }}">{{ $log->uri }}</td>
                        <td class="{{ $log->statusBadgeClass() }}">{{ $log->status_code }}</td>
                        <td style="font-variant-numeric:tabular-nums;white-space:nowrap;color:#dc2626;font-weight:700;">
                            {{ number_format($log->response_time_ms) }} ms
                            <span class="slow-marker">SLOW</span>
                        </td>
                        <td>{{ $log->queries_count ?: '—' }}</td>
                        <td>
                            @if($log->errors_count > 0)
                                <span style="color:#dc2626;font-weight:600;">{{ $log->errors_count }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $log->ip ?? '—' }}</td>
                        <td class="text-muted" style="white-space:nowrap;" title="{{ $log->created_at }}">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('request-log-analyzer.show', $log->id) }}" class="tag-link">Detail</a>
                            &ensp;
                            <a href="{{ route('request-log-analyzer.timeline', $log->id) }}" class="tag-link">Timeline</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ──────────────────────────────────────────────── --}}
        @if($logs->hasPages())
        <div class="pagination-wrap">
            <div class="page-meta">
                Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
                &nbsp;·&nbsp; {{ number_format($logs->total()) }} results
            </div>
            <div class="page-btns">
                {{-- Previous --}}
                @if($logs->onFirstPage())
                    <span class="page-btn disabled">‹</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="page-btn">‹</a>
                @endif

                {{-- Page window --}}
                @php
                    $current  = $logs->currentPage();
                    $last     = $logs->lastPage();
                    $window   = 2;
                    $start    = max(1, $current - $window);
                    $end      = min($last, $current + $window);
                @endphp

                @if($start > 1)
                    <a href="{{ $logs->url(1) }}" class="page-btn">1</a>
                    @if($start > 2)<span class="page-btn disabled" style="border:none;">…</span>@endif
                @endif

                @for($p = $start; $p <= $end; $p++)
                    @if($p === $current)
                        <span class="page-btn current">{{ $p }}</span>
                    @else
                        <a href="{{ $logs->url($p) }}" class="page-btn">{{ $p }}</a>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)<span class="page-btn disabled" style="border:none;">…</span>@endif
                    <a href="{{ $logs->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif

                {{-- Next --}}
                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="page-btn">›</a>
                @else
                    <span class="page-btn disabled">›</span>
                @endif
            </div>
        </div>
        @endif
    @endif
</div>

@endsection
