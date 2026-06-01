{{-- ============================================================
     login-history.blade.php  –  User login / logout history
     ============================================================ --}}
@extends('request-log-analyzer::_layout')

@section('title', 'Login History — Request Log Analyzer')
@section('page-title', 'Login History')

@section('content')

{{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:1.25rem;">
    <form method="GET" action="{{ route('request-log-analyzer.login-history') }}" style="display:contents;">
        <div class="filter-bar">

            <span class="filter-label">User</span>
            <select name="user_id" onchange="this.form.submit()">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>

            <span class="filter-label">From</span>
            <input type="date" name="from" value="{{ request('from') }}">

            <span class="filter-label">To</span>
            <input type="date" name="to" value="{{ request('to') }}">

            <button type="submit" class="btn btn-primary" style="height:32px;">Filter</button>

            @if(request()->hasAny(['user_id','from','to']))
                <a href="{{ route('request-log-analyzer.login-history') }}" class="btn btn-ghost" style="height:32px;">Clear</a>
            @endif

            <div class="filter-spacer"></div>
            <span class="text-muted" style="font-size:.72rem;">{{ number_format($histories->total()) }} records</span>
        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────────────────── --}}
    @if($histories->isEmpty())
        <div class="empty-state">No login records found.</div>
    @else
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Duration</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $row)
                        <tr>
                            <td class="text-muted">{{ $row->id }}</td>

                            <td>
                                @if($row->user)
                                    <span style="font-weight:600;">{{ $row->user->name }}</span>
                                    <br>
                                    <span class="text-muted" style="font-size:.7rem;">{{ $row->user->email ?? '' }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                {{ $row->login_at?->format('Y-m-d H:i:s') ?? '—' }}
                            </td>

                            <td>
                                @if($row->logout_at)
                                    {{ $row->logout_at->format('Y-m-d H:i:s') }}
                                @else
                                    <span class="badge badge-green">Active</span>
                                @endif
                            </td>

                            <td>
                                @if($row->sessionDuration())
                                    {{ $row->sessionDuration() }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                <code style="font-size:.72rem;">{{ $row->ip_address ?? '—' }}</code>
                            </td>

                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="{{ $row->user_agent }}">
                                <span class="text-muted" style="font-size:.72rem;">{{ $row->user_agent ?? '—' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ──────────────────────────────────────────────── --}}
        @if($histories->hasPages())
            <div class="pagination-wrap">
                {{ $histories->links('request-log-analyzer::_pagination') }}
            </div>
        @endif
    @endif
</div>

@endsection
