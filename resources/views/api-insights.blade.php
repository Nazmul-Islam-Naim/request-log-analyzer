@extends('request-log-analyzer::_layout')

@section('title', 'API Insights')
@section('page-title', 'API Insights')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')

<style>
    .chart-container {
        position: relative;
        width: 100%;
        height: 300px;
        margin-bottom: 1.5rem;
    }
    .insight-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .insight-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        padding: 1.25rem;
        text-align: center;
    }
    .insight-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2563eb;
        margin: .5rem 0;
    }
    .insight-label {
        font-size: .75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 600;
    }
    .alert-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: .7rem;
        font-weight: 600;
    }
    .badge-critical { background: #fee2e2; color: #b91c1c; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-info { background: #dbeafe; color: #1d4ed8; }

    /* Intelligent insights styling */
    .insights-container {
        margin-bottom: 2rem;
    }
    .insight-item {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background: #fff;
        border-left: 4px solid;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        align-items: flex-start;
    }
    .insight-item.warning {
        border-left-color: #f59e0b;
        background: #fffbf0;
    }
    .insight-item.info {
        border-left-color: #3b82f6;
        background: #f0f9ff;
    }
    .insight-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .insight-content {
        flex: 1;
        text-align: left;
    }
    .insight-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }
    .insight-message {
        font-size: 0.875rem;
        color: #6b7280;
    }
</style>

<div class="page">
    {{-- Intelligent Insights --}}
    @if (!empty($insights) && count($insights) > 0)
        <div class="insights-container">
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: #1f2937;">💡 Intelligent Insights</h2>
            @foreach ($insights as $insight)
                <div class="insight-item {{ $insight['severity'] }}">
                    <div class="insight-icon">{{ $insight['icon'] }}</div>
                    <div class="insight-content">
                        <div class="insight-title">{{ $insight['title'] }}</div>
                        <div class="insight-message">{{ $insight['message'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('request-log-analyzer.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span>API Insights</span>
    </div>

    {{-- Page title --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; font-weight: 700; margin-bottom: .5rem;">API Insights</h1>
        <p style="color: #64748b; font-size: .9rem;">Monitor API usage patterns and rate limit incidents</p>
    </div>

    {{-- Key metrics --}}
    <div class="insight-grid">
        <div class="insight-card">
            <div class="insight-label">Total API Requests</div>
            <div class="insight-value">{{ number_format($totalApiRequests) }}</div>
        </div>
        <div class="insight-card">
            <div class="insight-label">Active Users</div>
            <div class="insight-value">{{ number_format($activeUsers) }}</div>
        </div>
        <div class="insight-card">
            <div class="insight-label">Rate Limit Incidents</div>
            <div class="insight-value">{{ number_format($totalIncidents) }}</div>
        </div>
        <div class="insight-card">
            <div class="insight-label">Suspicious IPs</div>
            <div class="insight-value">{{ number_format($suspiciousCount) }}</div>
        </div>
    </div>

    {{-- Hourly chart --}}
    <div class="card">
        <div class="card-head">
            <h2>API Request Activity (24h)</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top users and suspicious IPs --}}
    <div class="two-col">
        {{-- Top Users --}}
        <div class="card">
            <div class="card-head">
                <h2>Top API Users</h2>
                <a href="#" class="card-head-action">View all</a>
            </div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Endpoint</th>
                            <th>Requests</th>
                            <th>Usage %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topUsers as $user)
                            @php
                                $threshold = config('request-log-analyzer.rate_limits.users.threshold', 100);
                                $percentage = min(100, round(($user->request_count / $threshold) * 100));
                            @endphp
                            <tr>
                                <td><strong>User #{{ $user->user_id ?? 'Guest' }}</strong></td>
                                <td><code style="font-size:.7rem;">{{ substr($user->endpoint, 0, 20) }}{{ strlen($user->endpoint) > 20 ? '...' : '' }}</code></td>
                                <td><strong>{{ number_format($user->request_count) }}</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: .5rem;">
                                        <div style="flex: 1; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
                                            <div style="height: 100%; background: @if($percentage > 90) #dc2626 @elseif($percentage > 70) #d97706 @else #16a34a @endif; width: {{ $percentage }}%;"></div>
                                        </div>
                                        <span style="font-size: .7rem; color: #64748b;">{{ $percentage }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                    No API usage data yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Suspicious IPs --}}
        <div class="card">
            <div class="card-head">
                <h2>Suspicious IPs</h2>
                <a href="#" class="card-head-action">View all</a>
            </div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Endpoint</th>
                            <th>Requests</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suspiciousIps as $ip)
                            @php
                                $threshold = config('request-log-analyzer.rate_limits.ips.threshold', 500);
                                $percentage = min(100, round(($ip->request_count / $threshold) * 100));
                            @endphp
                            <tr>
                                <td><code style="font-size:.75rem;">{{ $ip->ip }}</code></td>
                                <td><code style="font-size:.7rem;">{{ substr($ip->endpoint, 0, 20) }}{{ strlen($ip->endpoint) > 20 ? '...' : '' }}</code></td>
                                <td><strong>{{ number_format($ip->request_count) }}</strong></td>
                                <td>
                                    @if($ip->rate_limit_exceeded)
                                        <span class="alert-badge badge-critical">⚠ Exceeded</span>
                                    @else
                                        <span class="alert-badge badge-warning">⚡ Elevated</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                    No suspicious IP activity
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Active incidents --}}
    <div class="card">
        <div class="card-head">
            <h2>Active Rate Limit Incidents</h2>
            <a href="#" class="card-head-action">View all</a>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>User/IP</th>
                        <th>Endpoint</th>
                        <th>Requests</th>
                        <th>Limit</th>
                        <th>Detected</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incidents as $incident)
                        @php
                            $excess = round((($incident->request_count - $incident->limit_threshold) / $incident->limit_threshold) * 100);
                        @endphp
                        <tr>
                            <td>
                                <span class="badge @if($incident->incident_type === 'user') badge-info @else badge-warning @endif">
                                    {{ ucfirst($incident->incident_type) }}
                                </span>
                            </td>
                            <td>
                                @if($incident->incident_type === 'user')
                                    <code style="font-size:.75rem;">User #{{ $incident->user_id }}</code>
                                @else
                                    <code style="font-size:.75rem;">{{ $incident->ip }}</code>
                                @endif
                            </td>
                            <td><code style="font-size:.7rem;">{{ substr($incident->endpoint, 0, 25) }}{{ strlen($incident->endpoint) > 25 ? '...' : '' }}</code></td>
                            <td><strong>{{ number_format($incident->request_count) }}</strong></td>
                            <td><span style="color: #64748b;">{{ number_format($incident->limit_threshold) }}</span></td>
                            <td>
                                <span style="font-size:.75rem; color: #64748b;">
                                    {{ $incident->detected_at->diffForHumans() }}
                                </span>
                            </td>
                            <td>
                                <span class="alert-badge badge-critical">{{ $excess }}% Over</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                ✓ No active incidents
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Resolved incidents --}}
    @if($resolvedIncidents->count() > 0)
        <div class="card">
            <div class="card-head">
                <h2>Recently Resolved Incidents</h2>
            </div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>User/IP</th>
                            <th>Duration</th>
                            <th>Cleared</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resolvedIncidents as $incident)
                            @php
                                $duration = $incident->cleared_at->diffInMinutes($incident->detected_at);
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge @if($incident->incident_type === 'user') badge-info @else badge-warning @endif">
                                        {{ ucfirst($incident->incident_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($incident->incident_type === 'user')
                                        <code style="font-size:.75rem;">User #{{ $incident->user_id }}</code>
                                    @else
                                        <code style="font-size:.75rem;">{{ $incident->ip }}</code>
                                    @endif
                                </td>
                                <td><span style="font-size:.75rem; color: #64748b;">{{ $duration }}m</span></td>
                                <td>
                                    <span style="font-size:.75rem; color: #64748b;">
                                        {{ $incident->cleared_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size:.7rem; color: #64748b;">{{ $incident->notes ?? '-' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
    // Hourly activity chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: @json($chartHours),
            datasets: [{
                label: 'Requests',
                data: @json($chartCounts),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 50,
                        font: { size: 11 },
                    },
                    grid: { color: '#f1f5f9' },
                },
                x: {
                    ticks: { font: { size: 10 } },
                    grid: { display: false },
                },
            },
        },
    });
</script>

@endsection
