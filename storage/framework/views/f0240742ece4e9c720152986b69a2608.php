<?php $__env->startSection('title', 'Route Analytics — Request Log Analyzer'); ?>
<?php $__env->startSection('page-title', 'Route Analytics'); ?>

<?php $__env->startSection('content'); ?>


<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-label">Unique Routes</div>
        <div class="stat-value c-blue"><?php echo e(number_format($uniqueRoutes)); ?></div>
        <div class="stat-sub">distinct URIs captured</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Most Popular</div>
        <div class="stat-value c-purple" style="font-size:1rem;word-break:break-all;line-height:1.3;padding-top:.2rem;">
            <?php echo e($byCount->first()?->uri ?? '—'); ?>

        </div>
        <div class="stat-sub"><?php echo e(number_format($byCount->first()?->hit_count ?? 0)); ?> hits</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Slowest Avg</div>
        <div class="stat-value c-red"><?php echo e($slowest?->avg_ms ?? '—'); ?><span style="font-size:.8rem;font-weight:400;color:#94a3b8;"> ms</span></div>
        <div class="stat-sub" style="word-break:break-all;"><?php echo e($slowest?->uri ?? ''); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Fastest Avg</div>
        <div class="stat-value c-green"><?php echo e($fastest?->avg_ms ?? '—'); ?><span style="font-size:.8rem;font-weight:400;color:#94a3b8;"> ms</span></div>
        <div class="stat-sub" style="word-break:break-all;"><?php echo e($fastest?->uri ?? ''); ?></div>
    </div>
</div>


<div class="two-col">

    
    <div class="card">
        <div class="card-head">
            <h2>Most Used Routes</h2>
            <span class="text-muted" style="font-size:.72rem;">top <?php echo e($byCount->count()); ?> by hit count</span>
        </div>
        <div class="card-body">
            <?php if($byCount->isEmpty()): ?>
                <div class="empty-state">No route data available.</div>
            <?php else: ?>
                <div class="chart-wrap" style="height:<?php echo e(max(220, $byCount->count() * 32)); ?>px;">
                    <canvas id="chartHits"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="card">
        <div class="card-head">
            <h2>Avg Response Time per Route</h2>
            <span class="text-muted" style="font-size:.72rem;">top <?php echo e($byAvg->count()); ?> slowest (ms)</span>
        </div>
        <div class="card-body">
            <?php if($byAvg->isEmpty()): ?>
                <div class="empty-state">No timing data available.</div>
            <?php else: ?>
                <div class="chart-wrap" style="height:<?php echo e(max(220, $byAvg->count() * 32)); ?>px;">
                    <canvas id="chartAvg"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>


<div class="card">
    <div class="card-head">
        <h2>Route Breakdown</h2>
        <span class="text-muted" style="font-size:.72rem;">all routes ordered by hit count</span>
    </div>

    <?php if($byCount->isEmpty()): ?>
        <div class="empty-state">No requests have been captured yet.</div>
    <?php else: ?>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:32px;">#</th>
                        <th>Route (URI)</th>
                        <th style="width:90px;">Hits</th>
                        <th style="width:90px;">Avg ms</th>
                        <th style="width:90px;">Min ms</th>
                        <th style="width:90px;">Max ms</th>
                        <th style="width:120px;">Hit share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $maxHits = $byCount->max('hit_count'); ?>
                    <?php $__currentLoopData = $byCount; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $sharePct  = $maxHits > 0 ? round($row->hit_count / $maxHits * 100) : 0;
                        $avgColor  = match(true) {
                            $row->avg_ms >= 500 => '#dc2626',
                            $row->avg_ms >= 200 => '#d97706',
                            default             => '#16a34a',
                        };
                    ?>
                    <tr>
                        <td class="text-muted"><?php echo e($i + 1); ?></td>
                        <td style="font-family:'Menlo','Consolas',monospace;font-size:.75rem;word-break:break-all;"><?php echo e($row->uri); ?></td>
                        <td style="font-variant-numeric:tabular-nums;font-weight:600;"><?php echo e(number_format($row->hit_count)); ?></td>
                        <td style="font-variant-numeric:tabular-nums;color:<?php echo e($avgColor); ?>;font-weight:600;"><?php echo e(number_format($row->avg_ms, 1)); ?></td>
                        <td style="font-variant-numeric:tabular-nums;color:#64748b;"><?php echo e(number_format($row->min_ms)); ?></td>
                        <td style="font-variant-numeric:tabular-nums;color:#64748b;"><?php echo e(number_format($row->max_ms)); ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem;">
                                <div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                                    <div style="width:<?php echo e($sharePct); ?>%;height:100%;background:#3b82f6;border-radius:3px;"></div>
                                </div>
                                <span style="font-size:.68rem;color:#94a3b8;white-space:nowrap;"><?php echo e($sharePct); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    /* ── Shared Chart.js defaults ─────────────────────────────────────── */
    Chart.defaults.font.family = "system-ui, -apple-system, 'Segoe UI', sans-serif";
    Chart.defaults.font.size   = 11;
    Chart.defaults.color       = '#94a3b8';

    /* ── Data from server ─────────────────────────────────────────────── */
    const hitLabels  = <?php echo json_encode($byCount->pluck('uri'), 15, 512) ?>;
    const hitCounts  = <?php echo json_encode($byCount->pluck('hit_count'), 15, 512) ?>;
    const avgLabels  = <?php echo json_encode($byAvg->pluck('uri'), 15, 512) ?>;
    const avgValues  = <?php echo json_encode($byAvg->pluck('avg_ms'), 15, 512) ?>;

    /* Colour helpers */
    const blue   = (alpha) => `rgba(59,130,246,${alpha})`;
    const amber  = (alpha) => `rgba(217,119,6,${alpha})`;

    /* ── Shared horizontal bar options ────────────────────────────────── */
    function hbarOptions(xLabel, tooltipSuffix) {
        return {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 10,
                    callbacks: {
                        label: (ctx) => ` ${ctx.formattedValue}${tooltipSuffix}`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.04)' },
                    border: { display: false },
                    ticks: { padding: 6 },
                    title: { display: true, text: xLabel, color: '#64748b', font: { size: 10 } },
                },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        padding: 8,
                        callback: function (val) {
                            const lbl = this.getLabelForValue(val);
                            return lbl.length > 28 ? lbl.slice(0, 26) + '…' : lbl;
                        },
                    },
                },
            },
        };
    }

    /* ── Chart 1: Most used routes (hit count) ─────────────────────────── */
    const hitsCtx = document.getElementById('chartHits');
    if (hitsCtx && hitLabels.length) {
        new Chart(hitsCtx, {
            type: 'bar',
            data: {
                labels: hitLabels,
                datasets: [{
                    label: 'Requests',
                    data: hitCounts,
                    backgroundColor: hitCounts.map((_, i) =>
                        blue(i === 0 ? 0.85 : 0.55 - i * 0.025)
                    ),
                    borderColor: hitCounts.map((_, i) =>
                        blue(i === 0 ? 1 : 0.7)
                    ),
                    borderWidth: 1,
                    borderRadius: 4,
                    barThickness: 18,
                }],
            },
            options: hbarOptions('Requests', ' hits'),
        });
    }

    /* ── Chart 2: Avg response time per route ────────────────────────── */
    const avgCtx = document.getElementById('chartAvg');
    if (avgCtx && avgLabels.length) {
        /* Colour by threshold: green <200ms, amber 200-500ms, red >500ms */
        const avgBg = avgValues.map(v =>
            v >= 500 ? 'rgba(220,38,38,.65)' :
            v >= 200 ? 'rgba(217,119,6,.65)'  :
                       'rgba(22,163,74,.55)'
        );
        const avgBorder = avgValues.map(v =>
            v >= 500 ? 'rgba(220,38,38,1)' :
            v >= 200 ? 'rgba(217,119,6,1)'  :
                       'rgba(22,163,74,1)'
        );

        new Chart(avgCtx, {
            type: 'bar',
            data: {
                labels: avgLabels,
                datasets: [{
                    label: 'Avg Response',
                    data: avgValues,
                    backgroundColor: avgBg,
                    borderColor: avgBorder,
                    borderWidth: 1,
                    borderRadius: 4,
                    barThickness: 18,
                }],
            },
            options: hbarOptions('Avg Response Time (ms)', ' ms'),
        });
    }
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/analytics.blade.php ENDPATH**/ ?>