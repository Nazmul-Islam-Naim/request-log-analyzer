<?php $__env->startSection('page-title', 'Slow Requests'); ?>

<?php $__env->startSection('content'); ?>


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
                Requests whose response time is <strong style="color:#dc2626;">&ge;&nbsp;<?php echo e(number_format($threshold)); ?>&nbsp;ms</strong>
                (threshold: <code>slow_request_threshold_ms</code>), sorted by slowest first.
                <?php echo e($logs->total() > 0 ? number_format($logs->total()).' request'.($logs->total() === 1 ? '' : 's').' found.' : 'No slow requests recorded yet.'); ?>

            </p>
        </div>
        <div style="margin-left:auto;display:flex;align-items:center;gap:.75rem;flex-shrink:0;">
            <a href="<?php echo e(route('request-log-analyzer.requests')); ?>" class="btn btn-ghost" style="height:32px;">All Requests</a>
            <a href="<?php echo e(route('request-log-analyzer.dashboard')); ?>" class="btn btn-ghost" style="height:32px;">Dashboard</a>
        </div>
    </div>
</div>


<div class="card">
    <?php if($logs->isEmpty()): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:#d1d5db;margin-bottom:.5rem;">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <p style="font-size:.85rem;color:#9ca3af;text-align:center;">
                No slow requests detected.<br>
                <span style="font-size:.75rem;">All recorded responses finished in under <?php echo e(number_format($threshold)); ?> ms.</span>
            </p>
        </div>
    <?php else: ?>
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
                    <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $mc = in_array($log->method, ['GET','POST','PUT','PATCH','DELETE','HEAD'])
                            ? 'badge-'.$log->method
                            : 'badge-other';
                    ?>
                    <tr>
                        <td class="text-muted" style="font-variant-numeric:tabular-nums;"><?php echo e($log->id); ?></td>
                        <td><span class="badge <?php echo e($mc); ?>"><?php echo e($log->method); ?></span></td>
                        <td class="trunc" title="<?php echo e($log->url); ?>"><?php echo e($log->uri); ?></td>
                        <td class="<?php echo e($log->statusBadgeClass()); ?>"><?php echo e($log->status_code); ?></td>
                        <td style="font-variant-numeric:tabular-nums;white-space:nowrap;color:#dc2626;font-weight:700;">
                            <?php echo e(number_format($log->response_time_ms)); ?> ms
                            <span class="slow-marker">SLOW</span>
                        </td>
                        <td><?php echo e($log->queries_count ?: '—'); ?></td>
                        <td>
                            <?php if($log->errors_count > 0): ?>
                                <span style="color:#dc2626;font-weight:600;"><?php echo e($log->errors_count); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?php echo e($log->ip ?? '—'); ?></td>
                        <td class="text-muted" style="white-space:nowrap;" title="<?php echo e($log->created_at); ?>">
                            <?php echo e($log->created_at->diffForHumans()); ?>

                        </td>
                        <td style="white-space:nowrap;">
                            <a href="<?php echo e(route('request-log-analyzer.show', $log->id)); ?>" class="tag-link">Detail</a>
                            &ensp;
                            <a href="<?php echo e(route('request-log-analyzer.timeline', $log->id)); ?>" class="tag-link">Timeline</a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        
        <?php if($logs->hasPages()): ?>
        <div class="pagination-wrap">
            <div class="page-meta">
                Page <?php echo e($logs->currentPage()); ?> of <?php echo e($logs->lastPage()); ?>

                &nbsp;·&nbsp; <?php echo e(number_format($logs->total())); ?> results
            </div>
            <div class="page-btns">
                
                <?php if($logs->onFirstPage()): ?>
                    <span class="page-btn disabled">‹</span>
                <?php else: ?>
                    <a href="<?php echo e($logs->previousPageUrl()); ?>" class="page-btn">‹</a>
                <?php endif; ?>

                
                <?php
                    $current  = $logs->currentPage();
                    $last     = $logs->lastPage();
                    $window   = 2;
                    $start    = max(1, $current - $window);
                    $end      = min($last, $current + $window);
                ?>

                <?php if($start > 1): ?>
                    <a href="<?php echo e($logs->url(1)); ?>" class="page-btn">1</a>
                    <?php if($start > 2): ?><span class="page-btn disabled" style="border:none;">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for($p = $start; $p <= $end; $p++): ?>
                    <?php if($p === $current): ?>
                        <span class="page-btn current"><?php echo e($p); ?></span>
                    <?php else: ?>
                        <a href="<?php echo e($logs->url($p)); ?>" class="page-btn"><?php echo e($p); ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if($end < $last): ?>
                    <?php if($end < $last - 1): ?><span class="page-btn disabled" style="border:none;">…</span><?php endif; ?>
                    <a href="<?php echo e($logs->url($last)); ?>" class="page-btn"><?php echo e($last); ?></a>
                <?php endif; ?>

                
                <?php if($logs->hasMorePages()): ?>
                    <a href="<?php echo e($logs->nextPageUrl()); ?>" class="page-btn">›</a>
                <?php else: ?>
                    <span class="page-btn disabled">›</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/slow-requests.blade.php ENDPATH**/ ?>