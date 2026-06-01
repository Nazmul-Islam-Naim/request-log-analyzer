@extends('request-log-analyzer::_layout')

@section('title', 'Request Replay')
@section('page-title', 'Request Replay')

@section('content')

<style>
    .replay-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .replay-method {
        font-weight: 700;
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        display: inline-block;
        margin-right: 1rem;
    }
    .replay-method.GET {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .replay-method.POST {
        background: #fef3c7;
        color: #b45309;
    }
    .replay-status {
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .replay-status.pending {
        background: #e0e7ff;
        color: #3730a3;
    }
    .replay-status.replayed {
        background: #dcfce7;
        color: #166534;
    }
    .replay-status.failed {
        background: #fee2e2;
        color: #b91c1c;
    }
    .replay-status.archived {
        background: #f3f4f6;
        color: #4b5563;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
    }
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2563eb;
    }
    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        margin-top: 0.5rem;
    }
</style>

<div class="page">
    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('request-log-analyzer.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span>Request Replay</span>
    </div>

    {{-- Header --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; font-weight: 700; margin-bottom: 0.5rem;">Request Replay</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Store and replay requests manually from the dashboard</p>
    </div>

    {{-- Statistics --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Total Stored</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['replayed'] }}</div>
            <div class="stat-label">Replayed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['failed'] }}</div>
            <div class="stat-label">Failed</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="card" style="margin-bottom: 2rem;">
        <div class="card-body" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Status</label>
                <select name="status" style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;">
                    <option value="">All Status</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="replayed" {{ $status === 'replayed' ? 'selected' : '' }}>Replayed</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Method</label>
                <select name="method" style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;">
                    <option value="">All Methods</option>
                    <option value="GET" {{ $method === 'GET' ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ $method === 'POST' ? 'selected' : '' }}>POST</option>
                </select>
            </div>
            <div style="align-self: flex-end;">
                <button type="submit" style="padding: 0.5rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600;">
                    Filter
                </button>
            </div>
        </div>
    </form>

    {{-- Replay List --}}
    <div class="card">
        <div class="card-head">
            <h2>Stored Requests</h2>
        </div>
        <div class="card-body">
            @if ($replays->count())
                @foreach ($replays as $replay)
                    <div class="replay-card">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <span class="replay-method {{ $replay->method }}">{{ $replay->method }}</span>
                                <span style="font-weight: 600; font-family: monospace; word-break: break-all;">{{ $replay->uri }}</span>
                            </div>
                            <div style="font-size: 0.875rem; color: #64748b;">
                                {{ $replay->url }}
                            </div>
                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">
                                Created: {{ $replay->created_at->format('Y-m-d H:i:s') }}
                                @if ($replay->replayed_at)
                                    | Replayed: {{ $replay->replayed_at->format('Y-m-d H:i:s') }}
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-left: 2rem;">
                            <span class="replay-status {{ $replay->status }}">
                                {{ ucfirst($replay->status) }}
                            </span>
                            <a href="{{ route('request-log-analyzer.replay.show', $replay) }}" 
                               style="padding: 0.5rem 1rem; background: #e5e7eb; color: #1f2937; border: none; border-radius: 0.375rem; cursor: pointer; text-decoration: none; font-weight: 600;">
                                View
                            </a>
                            @if ($replay->isExecutable())
                                <form method="POST" action="{{ route('request-log-analyzer.replay.execute', $replay) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600;">
                                        Replay
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Pagination --}}
                <div style="display: flex; justify-content: center; margin-top: 2rem;">
                    {{ $replays->links() }}
                </div>
            @else
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <p>No replays found. Requests will be stored here when captured.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
