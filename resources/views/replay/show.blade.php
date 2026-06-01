@extends('request-log-analyzer::_layout')

@section('title', 'Replay Details')
@section('page-title', 'Replay Details')

@section('content')

<style>
    .detail-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .label-value {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .label-value label {
        font-weight: 600;
        color: #4b5563;
        font-size: 0.875rem;
    }
    .label-value .value {
        color: #1f2937;
        font-family: monospace;
        word-break: break-all;
        white-space: pre-wrap;
    }
    .code-block {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 1rem;
        overflow-x: auto;
        font-family: monospace;
        font-size: 0.875rem;
        line-height: 1.5;
    }
    .execution-row {
        display: grid;
        grid-template-columns: 120px 100px 100px 1fr 100px;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        align-items: center;
    }
    .execution-row:last-child {
        border-bottom: none;
    }
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
        text-align: center;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-danger { background: #fee2e2; color: #b91c1c; }
    .badge-secondary { background: #f3f4f6; color: #4b5563; }
</style>

<div class="page">
    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('request-log-analyzer.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <a href="{{ route('request-log-analyzer.replay.index') }}">Request Replay</a>
        <span class="breadcrumb-sep">/</span>
        <span>Details</span>
    </div>

    {{-- Header --}}
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                <span style="background: #dbeafe; color: #1d4ed8; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem;">{{ $replay->method }}</span>
                {{ $replay->uri }}
            </h1>
            <p style="color: #64748b; font-size: 0.9rem;">{{ $replay->url }}</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @if ($replay->isExecutable())
                <form method="POST" action="{{ route('request-log-analyzer.replay.execute', $replay) }}" style="display: inline;">
                    @csrf
                    <button type="submit" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600;">
                        Execute Replay
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('request-log-analyzer.replay.destroy', $replay) }}" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                @csrf
                @method('DELETE')
                <button type="submit" style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600;">
                    Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Status and Metadata --}}
    <div class="detail-section">
        <div class="section-title">📊 Status & Info</div>
        <div class="label-value">
            <label>Status</label>
            <div>
                <span style="background: 
                    @if ($replay->status === 'pending') #e0e7ff; color: #3730a3;
                    @elseif ($replay->status === 'replayed') #dcfce7; color: #166534;
                    @elseif ($replay->status === 'failed') #fee2e2; color: #b91c1c;
                    @else #f3f4f6; color: #4b5563; @endif
                    padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; display: inline-block;">
                    {{ ucfirst($replay->status) }}
                </span>
                @if ($replay->last_error)
                    <div style="color: #b91c1c; font-size: 0.875rem; margin-top: 0.5rem;">Error: {{ $replay->last_error }}</div>
                @endif
            </div>
        </div>
        <div class="label-value">
            <label>Created</label>
            <div class="value">{{ $replay->created_at->format('Y-m-d H:i:s') }}</div>
        </div>
        @if ($replay->replayed_at)
            <div class="label-value">
                <label>Last Replayed</label>
                <div class="value">{{ $replay->replayed_at->format('Y-m-d H:i:s') }}</div>
            </div>
        @endif
    </div>

    {{-- Request Details --}}
    <div class="detail-section">
        <div class="section-title">📝 Request Details</div>
        <div class="label-value">
            <label>URL</label>
            <div class="value">{{ $replay->url }}</div>
        </div>
        <div class="label-value">
            <label>Query String</label>
            <div class="value">{{ $replay->query_string ?: 'None' }}</div>
        </div>
    </div>

    {{-- Safe Headers --}}
    @if ($replay->getSafeHeaders())
        <div class="detail-section">
            <div class="section-title">🔐 Headers (Safe Only)</div>
            <div class="code-block">
                @foreach ($replay->getSafeHeaders() as $key => $value)
<strong>{{ $key }}:</strong> {{ $value }}
                @endforeach
            </div>
        </div>
    @endif

    {{-- Payload --}}
    @if ($replay->getSafePayload())
        <div class="detail-section">
            <div class="section-title">📦 Payload (Masked)</div>
            <div class="code-block">{{ json_encode($replay->getSafePayload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
        </div>
    @endif

    {{-- Execution History --}}
    <div class="detail-section">
        <div class="section-title">⏱️ Execution History</div>
        @if ($executions->count())
            <div style="margin-bottom: 1rem;">
                <div class="execution-row" style="font-weight: 600; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <div>Status</div>
                    <div>Time</div>
                    <div>Duration</div>
                    <div>Error</div>
                    <div>Executed</div>
                </div>
                @foreach ($executions as $execution)
                    <div class="execution-row">
                        <div>
                            @php
                                $badge = $execution->getStatusBadge();
                            @endphp
                            <span class="status-badge {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                        </div>
                        <div>{{ $execution->status_code ?? 'N/A' }}</div>
                        <div>{{ $execution->getDurationFormatted() }}</div>
                        <div style="font-size: 0.75rem;">
                            @if ($execution->error_message)
                                {{ substr($execution->error_message, 0, 50) }}{{ strlen($execution->error_message) > 50 ? '...' : '' }}
                            @else
                                —
                            @endif
                        </div>
                        <div style="font-size: 0.875rem;">{{ $execution->executed_at->diffForHumans() }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color: #64748b; text-align: center; padding: 2rem;">
                No executions yet. Execute the replay to see results here.
            </p>
        @endif
    </div>
</div>

@endsection
