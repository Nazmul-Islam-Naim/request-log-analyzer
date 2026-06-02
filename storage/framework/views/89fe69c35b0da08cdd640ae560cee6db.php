<?php $__env->startSection('page-title', 'Overview'); ?>

<?php $__env->startPush('head'); ?>
<style>
/* ══════════════════════════════════════════════════════════════
   Dashboard — scoped styles
══════════════════════════════════════════════════════════════ */

/* ── Hero summary cards ───────────────────────────────────────────────── */
.hero-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.1rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 1000px) { .hero-cards { grid-template-columns: repeat(2,1fr); } }
@media (max-width:  540px) { .hero-cards { grid-template-columns: 1fr; } }

.hc {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.4rem 1.5rem 1.25rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 4px 14px rgba(0,0,0,.04);
    transition: transform .15s, box-shadow .15s;
}
.hc:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,.1); }

/* Coloured top accent bar */
.hc::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 1rem 1rem 0 0;
}
.hc.hc-blue::before   { background: linear-gradient(90deg,#3b82f6,#6366f1); }
.hc.hc-green::before  { background: linear-gradient(90deg,#10b981,#34d399); }
.hc.hc-purple::before { background: linear-gradient(90deg,#8b5cf6,#a78bfa); }
.hc.hc-amber::before  { background: linear-gradient(90deg,#f59e0b,#fbbf24); }

.hc-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: .9rem;
}
.hc-icon {
    width: 42px; height: 42px;
    border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.hc-icon svg { width: 20px; height: 20px; }
.hc-blue   .hc-icon { background: #eff6ff; color: #2563eb; }
.hc-green  .hc-icon { background: #ecfdf5; color: #059669; }
.hc-purple .hc-icon { background: #f5f3ff; color: #7c3aed; }
.hc-amber  .hc-icon { background: #fffbeb; color: #d97706; }

.hc-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .63rem; font-weight: 700;
    padding: 3px 9px; border-radius: 9999px;
}
.hc-badge.live { background: #dcfce7; color: #15803d; }
.hc-badge.live::before {
    content:''; width:6px; height:6px; border-radius:50%;
    background:#22c55e; display:inline-block;
    animation: blink 1.6s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }

.hc-value {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -.03em;
    line-height: 1;
    color: #0f172a;
    margin-bottom: .3rem;
}
.hc-label {
    font-size: .77rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: .2rem;
}
.hc-sub {
    font-size: .69rem;
    color: #9ca3af;
}
.hc-route {
    font-family: 'Menlo','Consolas',monospace;
    font-size: .78rem;
    font-weight: 700;
    color: #7c3aed;
    background: #f5f3ff;
    padding: .2rem .5rem;
    border-radius: .35rem;
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-bottom: .35rem;
}

/* ── Mini metrics strip ───────────────────────────────────────────────── */
.metrics-strip {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: .875rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 540px) { .metrics-strip { grid-template-columns: repeat(2,1fr); } }

.mc {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .75rem;
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: .25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.mc-label {
    font-size: .62rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 700;
}
.mc-value {
    font-size: 1.45rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -.02em;
}
.mc-sub { font-size: .67rem; color: #9ca3af; }

/* ── Charts ───────────────────────────────────────────────────────────── */
.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.1rem;
    margin-bottom: 1.25rem;
}
@media (max-width: 960px) { .charts-row { grid-template-columns: 1fr; } }

/* ── Section header ───────────────────────────────────────────────────── */
.section-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .9rem 1.4rem;
    border-bottom: 1px solid #f1f5f9;
}
.section-head h2 { font-size: .84rem; font-weight: 700; flex: 1; color: #111827; }
.section-head-badge {
    font-size: .65rem; font-weight: 600;
    padding: 2px 10px; border-radius: 9999px;
    background: #f1f5f9; color: #6b7280;
}
.section-head-link { font-size: .74rem; color: #3b82f6; text-decoration: none; font-weight: 500; }
.section-head-link:hover { text-decoration: underline; }

/* ── Requests table ───────────────────────────────────────────────────── */
.req-table { width: 100%; border-collapse: collapse; }
.req-table thead th {
    background: #f8fafc;
    padding: .55rem 1.1rem;
    text-align: left;
    font-size: .63rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
    border-bottom: 1px solid #f1f5f9;
    white-space: nowrap;
}
.req-table tbody td {
    padding: .7rem 1.1rem;
    font-size: .8rem;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    color: #374151;
}
.req-table tbody tr:last-child td { border-bottom: none; }
.req-table tbody tr { transition: background .1s; }
.req-table tbody tr:hover td { background: #fafbff; }
.req-table td.uri-cell {
    max-width: 280px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    font-weight: 500; color: #1e293b;
}
.req-table td.time-cell { font-variant-numeric: tabular-nums; }
.req-table td.mono-cell { font-family: 'Menlo','Consolas',monospace; font-size: .73rem; color: #475569; }
.time-good  { color: #16a34a; font-weight: 600; }
.time-ok    { color: #d97706; font-weight: 600; }
.time-slow  { color: #dc2626; font-weight: 600; }

.action-links { display: flex; gap: .5rem; }
.action-link {
    font-size: .7rem; font-weight: 500;
    color: #6b7280; text-decoration: none;
    padding: 2px 8px; border-radius: 5px;
    border: 1px solid #e5e7eb;
    transition: all .1s;
    white-space: nowrap;
}
.action-link:hover { background: #f1f5f9; border-color: #d1d5db; color: #374151; }
.action-link.primary { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
.action-link.primary:hover { background: #dbeafe; }

/* ── Two-col bottom ───────────────────────────────────────────────────── */
.bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.1rem;
    margin-bottom: 1.25rem;
}
@media (max-width: 960px) { .bottom-row { grid-template-columns: 1fr; } }

/* ── Error / slow query rows ──────────────────────────────────────────── */
.err-row {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    padding: .75rem 1.4rem;
    border-bottom: 1px solid #f8fafc;
}
.err-row:last-child { border-bottom: none; }
.err-row:hover { background: #fafbff; }
.err-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ef4444; flex-shrink: 0; margin-top: .35rem;
}
.err-body { flex: 1; min-width: 0; }
.err-class { font-size: .73rem; font-weight: 600; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.err-msg   { font-size: .7rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .05rem; }
.err-meta  { font-size: .65rem; color: #9ca3af; margin-top: .15rem; display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }

.slow-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .65rem 1.4rem;
    border-bottom: 1px solid #f8fafc;
}
.slow-row:last-child { border-bottom: none; }
.slow-row:hover { background: #fafbff; }
.slow-ms {
    font-size: .75rem; font-weight: 700; color: #dc2626;
    min-width: 56px; text-align: right; flex-shrink: 0;
}
.slow-sql {
    flex: 1; min-width: 0;
    font-family: 'Menlo','Consolas',monospace;
    font-size: .7rem; color: #475569;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ── Country table ────────────────────────────────────────────────────── */
.country-bar-wrap { display:flex; align-items:center; gap:.6rem; }
.country-bar { flex: 1; height: 5px; background: #f1f5f9; border-radius: 9999px; overflow: hidden; }
.country-bar-fill { height: 100%; border-radius: 9999px; background: linear-gradient(90deg,#3b82f6,#6366f1); }
.country-pct { font-size: .68rem; color: #9ca3af; white-space: nowrap; min-width: 32px; text-align: right; }

/* ── Empty state ──────────────────────────────────────────────────────── */
.ds-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2.5rem 1.5rem; gap: .5rem; }
.ds-empty svg { width: 36px; height: 36px; color: #d1d5db; }
.ds-empty p { font-size: .8rem; color: #9ca3af; text-align: center; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<?php
    $sc = fn(string $s): string => match(true) {
        str_starts_with($s, '2') => 'status-2xx',
        str_starts_with($s, '3') => 'status-3xx',
        str_starts_with($s, '4') => 'status-4xx',
        default                  => 'status-5xx',
    };
    $slowThreshold = (int) config('request-log-analyzer.slow_request_threshold_ms', 500);
    $timeClass = fn($ms): string => match(true) {
        $ms === null              => '',
        $ms < 200                 => 'time-good',
        $ms < $slowThreshold      => 'time-ok',
        default                   => 'time-slow',
    };
?>


<div class="hero-cards">

    
    <div class="hc hc-blue">
        <div class="hc-top">
            <div class="hc-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div style="text-align:right;">
                <div style="font-size:.65rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Today</div>
                <div style="font-size:.75rem;color:#374151;font-weight:600;"><?php echo e(now()->format('d M')); ?></div>
            </div>
        </div>
        <div class="hc-value c-blue"><?php echo e(number_format($todayRequests)); ?></div>
        <div class="hc-label">Requests Today</div>
        <div class="hc-sub">since midnight · <?php echo e(now()->format('T')); ?></div>
    </div>

    
    <div class="hc hc-green">
        <div class="hc-top">
            <div class="hc-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <span class="hc-badge live">Live</span>
        </div>
        <div class="hc-value c-green"><?php echo e(number_format($activeUsersNow)); ?></div>
        <div class="hc-label">Active Users Now</div>
        <div class="hc-sub">within last <?php echo e($windowMinutes); ?> min window</div>
    </div>

    
    <div class="hc hc-purple">
        <div class="hc-top">
            <div class="hc-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <?php if($topRoute): ?>
                <div style="font-size:.65rem;color:#9ca3af;font-weight:600;"><?php echo e(number_format($topRoute->count)); ?> hits</div>
            <?php endif; ?>
        </div>
        <?php if($topRoute): ?>
            <div class="hc-route" title="<?php echo e($topRoute->uri); ?>"><?php echo e($topRoute->uri); ?></div>
        <?php else: ?>
            <div class="hc-value" style="font-size:1.4rem;color:#d1d5db;">—</div>
        <?php endif; ?>
        <div class="hc-label">Top Route</div>
        <div class="hc-sub"><?php echo e($topRoute ? 'most requested endpoint' : 'no data yet'); ?></div>
    </div>

    
    <div class="hc hc-amber">
        <div class="hc-top">
            <div class="hc-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
        <div class="hc-value c-amber"><?php echo e(number_format($totalLoggedUsers)); ?></div>
        <div class="hc-label">Total Logged Users</div>
        <div class="hc-sub">distinct authenticated users</div>
    </div>

</div>


<div class="metrics-strip">
    <div class="mc">
        <div class="mc-label">Total Requests</div>
        <div class="mc-value c-blue"><?php echo e(number_format($stats['total_requests'])); ?></div>
        <div class="mc-sub">all time</div>
    </div>
    <div class="mc">
        <div class="mc-label">Avg Response</div>
        <div class="mc-value c-green"><?php echo e($stats['avg_response_ms']); ?><span style="font-size:.9rem;font-weight:400;color:#9ca3af;"> ms</span></div>
        <div class="mc-sub">mean latency</div>
    </div>
    <div class="mc">
        <div class="mc-label">Error Rate</div>
        <div class="mc-value <?php echo e($stats['error_rate_percent'] > 5 ? 'c-red' : 'c-amber'); ?>"><?php echo e($stats['error_rate_percent']); ?><span style="font-size:.9rem;font-weight:400;color:#9ca3af;">%</span></div>
        <div class="mc-sub"><?php echo e(number_format($stats['error_requests'])); ?> 4xx/5xx</div>
    </div>
    <div class="mc">
        <div class="mc-label">Exceptions</div>
        <div class="mc-value c-red"><?php echo e(number_format($stats['total_errors'])); ?></div>
        <div class="mc-sub">captured</div>
    </div>
    <div class="mc">
        <div class="mc-label">DB Queries</div>
        <div class="mc-value c-purple"><?php echo e(number_format($stats['total_queries'])); ?></div>
        <div class="mc-sub"><?php echo e(number_format($stats['slow_queries'])); ?> slow</div>
    </div>
    <div class="mc">
        <div class="mc-label">Slow Requests</div>
        <div class="mc-value c-red"><?php echo e(number_format($stats['slow_requests'])); ?></div>
        <div class="mc-sub">&ge; <?php echo e(number_format($slowThreshold)); ?> ms</div>
    </div>
    <div class="mc">
        <div class="mc-label">Req / min</div>
        <div class="mc-value c-blue"><?php echo e($requestsPerMinute); ?><span style="font-size:.9rem;font-weight:400;color:#9ca3af;"> /m</span></div>
        <div class="mc-sub">avg over last 60 min</div>
    </div>
</div>


<div class="charts-row">
    <div class="card">
        <div class="section-head">
            <h2>Requests over time</h2>
            <span class="section-head-badge">Last 7 days</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:220px;"><canvas id="chartTime"></canvas></div>
        </div>
    </div>
    <div class="card">
        <div class="section-head">
            <h2>Top Routes</h2>
            <a href="<?php echo e(route('request-log-analyzer.analytics')); ?>" class="section-head-link">Analytics →</a>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:220px;"><canvas id="chartRoutes"></canvas></div>
        </div>
    </div>
</div>


<div class="charts-row" style="margin-bottom:1.25rem;">
    <div class="card">
        <div class="section-head">
            <h2>Avg Response Time</h2>
            <span class="section-head-badge">Last 7 days &middot; ms</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:220px;"><canvas id="chartAvgResponse"></canvas></div>
        </div>
    </div>
    <div class="card">
        <div class="section-head">
            <h2>Error Trend</h2>
            <span class="section-head-badge">Last 24 hours</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:220px;"><canvas id="chartErrorTrend"></canvas></div>
        </div>
    </div>
</div>


<div class="card" style="margin-bottom:1.25rem;">
    <div class="section-head">
        <svg style="width:15px;height:15px;color:#3b82f6;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <h2>Recent Requests</h2>
        <span class="section-head-badge">Last 10</span>
        <a href="<?php echo e(route('request-log-analyzer.requests')); ?>" class="section-head-link">View all →</a>
    </div>

    <?php if($logs->isEmpty()): ?>
        <div class="ds-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p>No requests logged yet.<br>Register the <code>TrackRequest</code> middleware to start capturing traffic.</p>
        </div>
    <?php else: ?>
        <div class="tbl-wrap">
            <table class="req-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th style="width:72px;">Method</th>
                        <th>URI</th>
                        <th style="width:64px;">Status</th>
                        <th style="width:88px;">Time</th>
                        <th style="width:64px;">Queries</th>
                        <th style="width:56px;">Errors</th>
                        <th style="width:110px;">When</th>
                        <th style="width:130px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $mc = in_array($log->method, ['GET','POST','PUT','PATCH','DELETE']) ? 'badge-'.$log->method : 'badge-other';
                        $scCode = (string) $log->status_code;
                        $tc = $timeClass($log->response_time_ms);
                    ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:.72rem;font-variant-numeric:tabular-nums;"><?php echo e($log->id); ?></td>
                        <td><span class="badge <?php echo e($mc); ?>"><?php echo e($log->method); ?></span></td>
                        <td class="uri-cell" title="<?php echo e($log->url); ?>"><?php echo e($log->uri); ?></td>
                        <td class="<?php echo e($log->statusBadgeClass()); ?>" style="font-weight:600;font-variant-numeric:tabular-nums;"><?php echo e($log->status_code); ?></td>
                        <td class="time-cell <?php echo e($tc); ?>">
                            <?php echo e($log->response_time_ms !== null ? $log->response_time_ms.' ms' : '—'); ?>

                            <?php if($log->isSlow()): ?><span class="slow-marker">SLOW</span><?php endif; ?>
                        </td>
                        <td style="color:#6b7280;text-align:center;"><?php echo e($log->queries_count); ?></td>
                        <td style="text-align:center;">
                            <?php if($log->errors_count > 0): ?>
                                <span class="badge badge-error"><?php echo e($log->errors_count); ?></span>
                            <?php else: ?>
                                <span style="color:#d1d5db;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#9ca3af;font-size:.72rem;white-space:nowrap;"><?php echo e($log->created_at->diffForHumans()); ?></td>
                        <td>
                            <div class="action-links">
                                <a href="<?php echo e(route('request-log-analyzer.show', $log->id)); ?>" class="action-link primary">Detail</a>
                                <a href="<?php echo e(route('request-log-analyzer.timeline', $log->id)); ?>" class="action-link">Timeline</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>


<div class="bottom-row">

    
    <div class="card">
        <div class="section-head">
            <svg style="width:15px;height:15px;color:#ef4444;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <h2>Recent Errors</h2>
            <?php if($recentErrors->isNotEmpty()): ?>
                <span class="section-head-badge" style="background:#fef2f2;color:#dc2626;"><?php echo e($recentErrors->count()); ?></span>
            <?php endif; ?>
        </div>

        <?php if($recentErrors->isEmpty()): ?>
            <div class="ds-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <p>No errors recorded. Your app is running clean!</p>
            </div>
        <?php else: ?>
            <?php $__currentLoopData = $recentErrors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="err-row">
                <div class="err-dot" style="<?php echo e(in_array($err->severity, ['critical','alert','emergency']) ? 'background:#9d174d' : ''); ?>"></div>
                <div class="err-body">
                    <div class="err-class"><?php echo e($err->shortClass()); ?></div>
                    <div class="err-msg"><?php echo e($err->message); ?></div>
                    <div class="err-meta">
                        <span class="badge badge-<?php echo e($err->severity); ?>"><?php echo e($err->severity); ?></span>
                        <span><?php echo e($err->created_at->diffForHumans()); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>

    
    <div class="card">
        <div class="section-head">
            <svg style="width:15px;height:15px;color:#f59e0b;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <h2>Slowest Queries</h2>
            <?php if($slowQueries->isNotEmpty()): ?>
                <span class="section-head-badge" style="background:#fef3c7;color:#d97706;"><?php echo e($slowQueries->count()); ?></span>
            <?php endif; ?>
        </div>

        <?php if($slowQueries->isEmpty()): ?>
            <div class="ds-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <p>No slow queries detected. Your queries are fast!</p>
            </div>
        <?php else: ?>
            <?php $__currentLoopData = $slowQueries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="slow-row">
                <div class="slow-ms"><?php echo e(number_format($q->time_ms, 1)); ?> ms</div>
                <div class="slow-sql" title="<?php echo e($q->sql); ?>"><?php echo e($q->sql); ?></div>
                <span class="badge badge-other" style="flex-shrink:0;font-size:.63rem;"><?php echo e($q->connection); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>

</div>


<?php if($countryStats->isNotEmpty()): ?>
<div class="bottom-row">
    <div class="card">
        <div class="section-head">
            <svg style="width:15px;height:15px;color:#6366f1;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <h2>Geo Distribution</h2>
            <a href="<?php echo e(route('request-log-analyzer.geo')); ?>" class="section-head-link" style="margin-left:auto;">View World Map →</a>
        </div>
        <div class="card-body" style="padding:0;">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"/>
            <div id="dash-mini-map" style="height:240px;width:100%;background:#f0f4f8;"></div>
        </div>
    </div>

    <div class="card">
        <div class="section-head">
            <h2>Top Countries</h2>
            <span class="section-head-badge">by request count</span>
        </div>
        <?php $totalGeoHits = $countryStats->sum('count'); ?>
        <div class="tbl-wrap">
            <table class="req-table">
                <thead><tr><th>Country</th><th>Requests</th><th style="width:140px;">Share</th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $countryStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $sharePct = $totalGeoHits > 0 ? round($cs->count / $totalGeoHits * 100, 1) : 0; ?>
                    <tr>
                        <td style="font-weight:600;color:#1e293b;"><?php echo e($cs->country); ?></td>
                        <td style="font-variant-numeric:tabular-nums;color:#374151;"><?php echo e(number_format($cs->count)); ?></td>
                        <td>
                            <div class="country-bar-wrap">
                                <div class="country-bar"><div class="country-bar-fill" style="width:<?php echo e($sharePct); ?>%;"></div></div>
                                <span class="country-pct"><?php echo e($sharePct); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const days    = <?php echo json_encode($chartDays, 15, 512) ?>;
    const totals  = <?php echo json_encode($chartTotals, 15, 512) ?>;
    const errors  = <?php echo json_encode($chartErrors, 15, 512) ?>;
    const routes  = <?php echo json_encode($topRoutes->pluck('uri'), 15, 512) ?>;
    const rcounts = <?php echo json_encode($topRoutes->pluck('count'), 15, 512) ?>;

    Chart.defaults.font.family = "system-ui,-apple-system,'Segoe UI',sans-serif";
    Chart.defaults.font.size   = 11;
    Chart.defaults.color       = '#9ca3af';

    /* ── Requests over time ────────────────────────────────────────────── */
    new Chart(document.getElementById('chartTime'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [
                {
                    label: 'Requests',
                    data: totals,
                    borderColor: '#3b82f6',
                    backgroundColor: (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(0,0,0,200);
                        g.addColorStop(0, 'rgba(59,130,246,.18)');
                        g.addColorStop(1, 'rgba(59,130,246,0)');
                        return g;
                    },
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: true,
                },
                {
                    label: 'Errors (4xx/5xx)',
                    data: errors,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,.06)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#ef4444',
                    tension: 0.4,
                    fill: true,
                    borderDash: [4,3],
                },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 16, usePointStyle: true } },
                tooltip: { padding: 12, bodySpacing: 5, cornerRadius: 8 },
            },
            scales: {
                x: { grid: { color: '#f3f4f6' }, ticks: { maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { precision: 0 } },
            },
        },
    });

    /* ── Top routes ────────────────────────────────────────────────────── */
    const palette = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#f97316','#84cc16'];

    new Chart(document.getElementById('chartRoutes'), {
        type: 'bar',
        data: {
            labels: routes,
            datasets: [{
                label: 'Requests',
                data: rcounts,
                backgroundColor: palette.slice(0, rcounts.length),
                borderRadius: 5,
                borderSkipped: false,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { padding: 10, cornerRadius: 8 },
            },
            scales: {
                x: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { precision: 0 } },
                y: { grid: { display: false }, ticks: {
                    callback: v => v.length > 30 ? v.slice(0,27)+'…' : v,
                }},
            },
        },
    });

    /* ── Avg response time (7-day) ─────────────────────────────────────── */
    const avgResponse = <?php echo json_encode($chartAvgResponse, 15, 512) ?>;
    new Chart(document.getElementById('chartAvgResponse'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'Avg Response (ms)',
                data: avgResponse,
                borderColor: '#10b981',
                backgroundColor: (ctx) => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    g.addColorStop(0, 'rgba(16,185,129,.15)');
                    g.addColorStop(1, 'rgba(16,185,129,0)');
                    return g;
                },
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#10b981',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 12, cornerRadius: 8,
                    callbacks: { label: ctx => ` ${ctx.formattedValue} ms` },
                },
            },
            scales: {
                x: { grid: { color: '#f3f4f6' }, ticks: { maxRotation: 0 } },
                y: {
                    beginAtZero: true, grid: { color: '#f3f4f6' },
                    ticks: { precision: 0, callback: v => v + ' ms' },
                },
            },
        },
    });

    /* ── Error trend (24 h, hourly) ─────────────────────────────────────── */
    const hourLabels   = <?php echo json_encode($chartHourLabels, 15, 512) ?>;
    const hourlyErrors = <?php echo json_encode($chartHourlyErrors, 15, 512) ?>;
    new Chart(document.getElementById('chartErrorTrend'), {
        type: 'bar',
        data: {
            labels: hourLabels,
            datasets: [{
                label: '4xx/5xx Errors',
                data: hourlyErrors,
                backgroundColor: 'rgba(239,68,68,.7)',
                borderColor: '#ef4444',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { padding: 10, cornerRadius: 8 },
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { precision: 0 } },
            },
        },
    });

    /* ── Dashboard mini geo map ──────────────────────────────────────── */
    const miniMapEl = document.getElementById('dash-mini-map');
    if (miniMapEl) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js';
        script.onload = function () {
            const miniMap = L.map('dash-mini-map', {
                center: [20, 10], zoom: 1, zoomControl: false,
                attributionControl: false, dragging: false,
                scrollWheelZoom: false, doubleClickZoom: false,
            });
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd', maxZoom: 4,
            }).addTo(miniMap);

            const miniData = <?php echo json_encode($countryStats->mapWithKeys(fn($c) => [strtolower($c->country) => (int)$c->count]), 15, 512) ?>;
            const miniMax  = Math.max(...Object.values(miniData), 1);
            const idToName = {4:'Afghanistan',8:'Albania',12:'Algeria',24:'Angola',32:'Argentina',36:'Australia',40:'Austria',31:'Azerbaijan',50:'Bangladesh',56:'Belgium',76:'Brazil',100:'Bulgaria',104:'Myanmar',116:'Cambodia',120:'Cameroon',124:'Canada',152:'Chile',156:'China',170:'Colombia',188:'Costa Rica',191:'Croatia',192:'Cuba',203:'Czechia',208:'Denmark',218:'Ecuador',818:'Egypt',231:'Ethiopia',246:'Finland',250:'France',268:'Georgia',276:'Germany',288:'Ghana',300:'Greece',320:'Guatemala',340:'Honduras',348:'Hungary',356:'India',360:'Indonesia',364:'Iran',368:'Iraq',372:'Ireland',376:'Israel',380:'Italy',392:'Japan',400:'Jordan',398:'Kazakhstan',404:'Kenya',410:'South Korea',458:'Malaysia',484:'Mexico',504:'Morocco',528:'Netherlands',554:'New Zealand',566:'Nigeria',578:'Norway',586:'Pakistan',604:'Peru',608:'Philippines',616:'Poland',620:'Portugal',634:'Qatar',642:'Romania',643:'Russia',682:'Saudi Arabia',703:'Slovakia',705:'Slovenia',710:'South Africa',724:'Spain',144:'Sri Lanka',752:'Sweden',756:'Switzerland',760:'Syria',764:'Thailand',788:'Tunisia',792:'Turkey',804:'Ukraine',784:'United Arab Emirates',826:'United Kingdom',840:'United States',858:'Uruguay',704:'Vietnam',887:'Yemen',716:'Zimbabwe'};
            function getColor(c) {
                if (!c) return '#f0f4f8';
                const t = Math.log1p(c)/Math.log1p(miniMax);
                if (t<0.15) return '#dbeafe'; if (t<0.35) return '#93c5fd';
                if (t<0.55) return '#3b82f6'; if (t<0.75) return '#1d4ed8';
                return '#1e3a8a';
            }

            function topoFeature(topo, obj) {
                const sc=topo.transform?.scale??[1,1],tr=topo.transform?.translate??[0,0];
                function dec(arc){let x=0,y=0;return arc.map(([dx,dy])=>{x+=dx;y+=dy;return[x*sc[0]+tr[0],y*sc[1]+tr[1]];});}
                function res(arcs){return arcs.map(ring=>ring.map(i=>{const a=topo.arcs[i<0?~i:i];const p=dec(a);return i<0?p.reverse():p;}).flat());}
                return {type:'FeatureCollection',features:obj.geometries.map(g=>({type:'Feature',id:g.id,properties:{},geometry:g.type==='Polygon'?{type:'Polygon',coordinates:res(g.arcs)}:g.type==='MultiPolygon'?{type:'MultiPolygon',coordinates:g.arcs.map(a=>res(a))}:null}))};
            }

            fetch('https://cdn.jsdelivr.net/npm/world-atlas@2/countries-110m.json')
                .then(r=>r.json()).then(world=>{
                    L.geoJSON(topoFeature(world,world.objects.countries),{
                        style: f=>({fillColor:getColor(miniData[(idToName[parseInt(f.id)]??'').toLowerCase()]??0),weight:.5,color:'#fff',fillOpacity:.85}),
                        onEachFeature: (f,layer)=>{
                            const name=idToName[parseInt(f.id)];
                            const cnt=miniData[(name??'').toLowerCase()]??0;
                            if(cnt>0) layer.bindTooltip(`<strong>${name}</strong><br>${cnt.toLocaleString()} requests`,{sticky:true,className:'leaflet-tooltip'});
                        },
                    }).addTo(miniMap);
                });
        };
        document.head.appendChild(script);
    }
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/dashboard.blade.php ENDPATH**/ ?>