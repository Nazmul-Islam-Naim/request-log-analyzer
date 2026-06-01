{{-- ============================================================
     active-users.blade.php  –  Users active in the last N minutes
     ============================================================ --}}
@extends('request-log-analyzer::_layout')

@section('title', 'Active Users — Request Log Analyzer')
@section('page-title', 'Active Users')

@section('content')

{{-- ── Header bar ──────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
    <div style="display:flex;align-items:center;gap:.6rem;">
        <span class="badge badge-green" style="font-size:.8rem;padding:.3rem .7rem;">
            {{ $active->count() }} online
        </span>
        <span class="text-muted" style="font-size:.78rem;">
            active within the last {{ $windowMinutes }} minutes
        </span>
    </div>
    <span id="refresh-countdown" class="text-muted" style="font-size:.72rem;">
        refreshing in <span id="countdown">10</span>s
    </span>
</div>

{{-- ── Table ───────────────────────────────────────────────────────────── --}}
<div class="card">
    @if($active->isEmpty())
        <div class="empty-state">
            No users have been active in the last {{ $windowMinutes }} minutes.
        </div>
    @else
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Last Activity</th>
                        <th>Ago</th>
                        <th>Current Route</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($active as $row)
                        @php
                            $lastActivity = \Carbon\Carbon::parse($row->last_activity);
                            $secsAgo      = $lastActivity->diffInSeconds(now());
                            $statusClass  = match(true) {
                                $row->status_code >= 500 => 'badge-red',
                                $row->status_code >= 400 => 'badge-amber',
                                $row->status_code >= 300 => 'badge-blue',
                                default                  => 'badge-green',
                            };
                        @endphp
                        <tr>
                            {{-- User ──────────────────────────────────────── --}}
                            <td>
                                @if($row->user)
                                    <div style="display:flex;align-items:center;gap:.55rem;">
                                        <span class="avatar" aria-hidden="true">
                                            {{ strtoupper(substr($row->user->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <div style="font-weight:600;line-height:1.2;">{{ $row->user->name }}</div>
                                            <div class="text-muted" style="font-size:.7rem;">{{ $row->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">User #{{ $row->user_id }}</span>
                                @endif
                            </td>

                            {{-- Last activity ─────────────────────────────── --}}
                            <td>{{ $lastActivity->format('H:i:s') }}</td>

                            {{-- Ago ──────────────────────────────────────── --}}
                            <td>
                                <span class="text-muted" style="font-size:.8rem;">
                                    @if($secsAgo < 60)
                                        {{ $secsAgo }}s ago
                                    @else
                                        {{ floor($secsAgo / 60) }}m {{ $secsAgo % 60 }}s ago
                                    @endif
                                </span>
                            </td>

                            {{-- Current route ─────────────────────────────── --}}
                            <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="{{ $row->current_route }}">
                                <code style="font-size:.72rem;">{{ $row->current_route }}</code>
                            </td>

                            {{-- Method ────────────────────────────────────── --}}
                            <td>
                                <span class="badge badge-blue" style="font-size:.65rem;">{{ $row->method }}</span>
                            </td>

                            {{-- Status ────────────────────────────────────── --}}
                            <td>
                                <span class="badge {{ $statusClass }}" style="font-size:.65rem;">
                                    {{ $row->status_code }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<style>
    .avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #334155;
        color: #94a3b8;
        font-size: .75rem;
        font-weight: 700;
        flex-shrink: 0;
    }
</style>
<script>
    // ── Auto-refresh every 10 s ────────────────────────────────────────────
    (function () {
        let remaining = 10;
        const el = document.getElementById('countdown');

        const tick = setInterval(function () {
            remaining--;
            if (el) el.textContent = remaining;
            if (remaining <= 0) {
                clearInterval(tick);
                window.location.reload();
            }
        }, 1000);
    })();
</script>
@endpush
