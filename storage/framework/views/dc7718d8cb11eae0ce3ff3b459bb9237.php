

<?php $__env->startSection('title', 'API Insights'); ?>
<?php $__env->startSection('page-title', 'API Insights'); ?>

<?php $__env->startPush('head'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

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
    
    <?php if(!empty($insights) && count($insights) > 0): ?>
        <div class="insights-container">
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: #1f2937;">💡 Intelligent Insights</h2>
            <?php $__currentLoopData = $insights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $insight): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="insight-item <?php echo e($insight['severity']); ?>">
                    <div class="insight-icon"><?php echo e($insight['icon']); ?></div>
                    <div class="insight-content">
                        <div class="insight-title"><?php echo e($insight['title']); ?></div>
                        <div class="insight-message"><?php echo e($insight['message']); ?></div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    
    <div class="breadcrumb">
        <a href="<?php echo e(route('request-log-analyzer.dashboard')); ?>">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span>API Insights</span>
    </div>

    
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; font-weight: 700; margin-bottom: .5rem;">API Insights</h1>
        <p style="color: #64748b; font-size: .9rem;">Monitor API usage patterns and rate limit incidents</p>
    </div>

    
    <div class="insight-grid">
        <div class="insight-card">
            <div class="insight-label">Total API Requests</div>
            <div class="insight-value"><?php echo e(number_format($totalApiRequests)); ?></div>
        </div>
        <div class="insight-card">
            <div class="insight-label">Active Users</div>
            <div class="insight-value"><?php echo e(number_format($activeUsers)); ?></div>
        </div>
        <div class="insight-card">
            <div class="insight-label">Rate Limit Incidents</div>
            <div class="insight-value"><?php echo e(number_format($totalIncidents)); ?></div>
        </div>
        <div class="insight-card">
            <div class="insight-label">Suspicious IPs</div>
            <div class="insight-value"><?php echo e(number_format($suspiciousCount)); ?></div>
        </div>
    </div>

    
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

    
    <div class="two-col">
        
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
                        <?php $__empty_1 = true; $__currentLoopData = $topUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $threshold = config('request-log-analyzer.rate_limits.users.threshold', 100);
                                $percentage = min(100, round(($user->request_count / $threshold) * 100));
                            ?>
                            <tr>
                                <td><strong>User #<?php echo e($user->user_id ?? 'Guest'); ?></strong></td>
                                <td><code style="font-size:.7rem;"><?php echo e(substr($user->endpoint, 0, 20)); ?><?php echo e(strlen($user->endpoint) > 20 ? '...' : ''); ?></code></td>
                                <td><strong><?php echo e(number_format($user->request_count)); ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: .5rem;">
                                        <div style="flex: 1; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
                                            <div style="height: 100%; background: <?php if($percentage > 90): ?> #dc2626 <?php elseif($percentage > 70): ?> #d97706 <?php else: ?> #16a34a <?php endif; ?>; width: <?php echo e($percentage); ?>%;"></div>
                                        </div>
                                        <span style="font-size: .7rem; color: #64748b;"><?php echo e($percentage); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                    No API usage data yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
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
                        <?php $__empty_1 = true; $__currentLoopData = $suspiciousIps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $threshold = config('request-log-analyzer.rate_limits.ips.threshold', 500);
                                $percentage = min(100, round(($ip->request_count / $threshold) * 100));
                            ?>
                            <tr>
                                <td><code style="font-size:.75rem;"><?php echo e($ip->ip); ?></code></td>
                                <td><code style="font-size:.7rem;"><?php echo e(substr($ip->endpoint, 0, 20)); ?><?php echo e(strlen($ip->endpoint) > 20 ? '...' : ''); ?></code></td>
                                <td><strong><?php echo e(number_format($ip->request_count)); ?></strong></td>
                                <td>
                                    <?php if($ip->rate_limit_exceeded): ?>
                                        <span class="alert-badge badge-critical">⚠ Exceeded</span>
                                    <?php else: ?>
                                        <span class="alert-badge badge-warning">⚡ Elevated</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                    No suspicious IP activity
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
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
                    <?php $__empty_1 = true; $__currentLoopData = $incidents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $incident): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $excess = round((($incident->request_count - $incident->limit_threshold) / $incident->limit_threshold) * 100);
                        ?>
                        <tr>
                            <td>
                                <span class="badge <?php if($incident->incident_type === 'user'): ?> badge-info <?php else: ?> badge-warning <?php endif; ?>">
                                    <?php echo e(ucfirst($incident->incident_type)); ?>

                                </span>
                            </td>
                            <td>
                                <?php if($incident->incident_type === 'user'): ?>
                                    <code style="font-size:.75rem;">User #<?php echo e($incident->user_id); ?></code>
                                <?php else: ?>
                                    <code style="font-size:.75rem;"><?php echo e($incident->ip); ?></code>
                                <?php endif; ?>
                            </td>
                            <td><code style="font-size:.7rem;"><?php echo e(substr($incident->endpoint, 0, 25)); ?><?php echo e(strlen($incident->endpoint) > 25 ? '...' : ''); ?></code></td>
                            <td><strong><?php echo e(number_format($incident->request_count)); ?></strong></td>
                            <td><span style="color: #64748b;"><?php echo e(number_format($incident->limit_threshold)); ?></span></td>
                            <td>
                                <span style="font-size:.75rem; color: #64748b;">
                                    <?php echo e($incident->detected_at->diffForHumans()); ?>

                                </span>
                            </td>
                            <td>
                                <span class="alert-badge badge-critical"><?php echo e($excess); ?>% Over</span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                ✓ No active incidents
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <?php if($resolvedIncidents->count() > 0): ?>
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
                        <?php $__currentLoopData = $resolvedIncidents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $incident): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $duration = $incident->cleared_at->diffInMinutes($incident->detected_at);
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?php if($incident->incident_type === 'user'): ?> badge-info <?php else: ?> badge-warning <?php endif; ?>">
                                        <?php echo e(ucfirst($incident->incident_type)); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php if($incident->incident_type === 'user'): ?>
                                        <code style="font-size:.75rem;">User #<?php echo e($incident->user_id); ?></code>
                                    <?php else: ?>
                                        <code style="font-size:.75rem;"><?php echo e($incident->ip); ?></code>
                                    <?php endif; ?>
                                </td>
                                <td><span style="font-size:.75rem; color: #64748b;"><?php echo e($duration); ?>m</span></td>
                                <td>
                                    <span style="font-size:.75rem; color: #64748b;">
                                        <?php echo e($incident->cleared_at->diffForHumans()); ?>

                                    </span>
                                </td>
                                <td>
                                    <span style="font-size:.7rem; color: #64748b;"><?php echo e($incident->notes ?? '-'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Hourly activity chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartHours, 15, 512) ?>,
            datasets: [{
                label: 'Requests',
                data: <?php echo json_encode($chartCounts, 15, 512) ?>,
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/api-insights.blade.php ENDPATH**/ ?>