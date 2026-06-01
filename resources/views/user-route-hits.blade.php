{{-- ============================================================
     user-route-hits.blade.php  –  Per-user route hit counts
     ============================================================ --}}
@extends('request-log-analyzer::_layout')

@section('title', 'User Route Hits — Request Log Analyzer')
@section('page-title', 'User Route Hits')

@section('content')

{{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:1.25rem;">
    <form method="GET" action="{{ route('request-log-analyzer.user-route-hits') }}" style="display:contents;">
        <div class="filter-bar">

            <span class="filter-label">User</span>
            <select name="user_id" onchange="this.form.submit()">
                <option value="">All Users</option>
                @foreach($allUsers as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>

            <span class="filter-label">From</span>
            <input type="date" name="from" value="{{ request('from') }}">

            <span class="filter-label">To</span>
            <input type="date" name="to" value="{{ request('to') }}">

            {{-- Sort toggle ──────────────────────────────────────────────── --}}
            <span class="filter-label">Sort</span>
            <select name="sort" onchange="this.form.submit()">
                <option value="desc" {{ $sort === 'desc' ? 'selected' : '' }}>Highest first</option>
                <option value="asc"  {{ $sort === 'asc'  ? 'selected' : '' }}>Lowest first</option>
            </select>

            <button type="submit" class="btn btn-primary" style="height:32px;">Filter</button>

            @if(request()->hasAny(['user_id','from','to','sort']))
                <a href="{{ route('request-log-analyzer.user-route-hits') }}" class="btn btn-ghost" style="height:32px;">Clear</a>
            @endif

            <div class="filter-spacer"></div>
            <span class="text-muted" style="font-size:.72rem;">
                {{ number_format($hits->total()) }} route–user combinations
            </span>
        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────────────────── --}}
    @if($hits->isEmpty())
        <div class="empty-state">No data matches the current filters.</div>
    @else
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Route</th>
                        <th style="text-align:right;">
                            Hit Count
                            @if($sort === 'desc')
                                <svg style="width:10px;height:10px;vertical-align:middle;color:#94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                            @else
                                <svg style="width:10px;height:10px;vertical-align:middle;color:#94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hits as $i => $row)
                        @php $user = $users->get($row->user_id); @endphp
                        <tr>
                            <td class="text-muted" style="width:3rem;">
                                {{ ($hits->currentPage() - 1) * $hits->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                @if($user)
                                    <div style="font-weight:600;line-height:1.2;">{{ $user->name }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">{{ $user->email ?? '' }}</div>
                                @else
                                    <span class="text-muted">User #{{ $row->user_id }}</span>
                                @endif
                            </td>

                            <td>
                                <code style="font-size:.75rem;">{{ $row->uri }}</code>
                            </td>

                            <td style="text-align:right;">
                                <span class="stat-value c-blue" style="font-size:1rem;">
                                    {{ number_format($row->hit_count) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ──────────────────────────────────────────────── --}}
        @if($hits->hasPages())
            <div class="pagination-wrap">
                {{ $hits->links('request-log-analyzer::_pagination') }}
            </div>
        @endif
    @endif
</div>

@endsection
