<?php $__env->startSection('title', 'Active Users — Request Log Analyzer'); ?>
<?php $__env->startSection('page-title', 'Active Users'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
    <div style="display:flex;align-items:center;gap:.6rem;">
        <span class="badge badge-green" style="font-size:.8rem;padding:.3rem .7rem;">
            <?php echo e($active->count()); ?> online
        </span>
        <span class="text-muted" style="font-size:.78rem;">
            active within the last <?php echo e($windowMinutes); ?> minutes
        </span>
    </div>
    <span id="refresh-countdown" class="text-muted" style="font-size:.72rem;">
        refreshing in <span id="countdown">10</span>s
    </span>
</div>


<div class="card">
    <?php if($active->isEmpty()): ?>
        <div class="empty-state">
            No users have been active in the last <?php echo e($windowMinutes); ?> minutes.
        </div>
    <?php else: ?>
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
                    <?php $__currentLoopData = $active; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $lastActivity = \Carbon\Carbon::parse($row->last_activity);
                            $secsAgo      = $lastActivity->diffInSeconds(now());
                            $statusClass  = match(true) {
                                $row->status_code >= 500 => 'badge-red',
                                $row->status_code >= 400 => 'badge-amber',
                                $row->status_code >= 300 => 'badge-blue',
                                default                  => 'badge-green',
                            };
                        ?>
                        <tr>
                            
                            <td>
                                <?php if($row->user): ?>
                                    <div style="display:flex;align-items:center;gap:.55rem;">
                                        <span class="avatar" aria-hidden="true">
                                            <?php echo e(strtoupper(substr($row->user->name, 0, 1))); ?>

                                        </span>
                                        <div>
                                            <div style="font-weight:600;line-height:1.2;"><?php echo e($row->user->name); ?></div>
                                            <div class="text-muted" style="font-size:.7rem;"><?php echo e($row->user->email ?? ''); ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">User #<?php echo e($row->user_id); ?></span>
                                <?php endif; ?>
                            </td>

                            
                            <td><?php echo e($lastActivity->format('H:i:s')); ?></td>

                            
                            <td>
                                <span class="text-muted" style="font-size:.8rem;">
                                    <?php if($secsAgo < 60): ?>
                                        <?php echo e($secsAgo); ?>s ago
                                    <?php else: ?>
                                        <?php echo e(floor($secsAgo / 60)); ?>m <?php echo e($secsAgo % 60); ?>s ago
                                    <?php endif; ?>
                                </span>
                            </td>

                            
                            <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="<?php echo e($row->current_route); ?>">
                                <code style="font-size:.72rem;"><?php echo e($row->current_route); ?></code>
                            </td>

                            
                            <td>
                                <span class="badge badge-blue" style="font-size:.65rem;"><?php echo e($row->method); ?></span>
                            </td>

                            
                            <td>
                                <span class="badge <?php echo e($statusClass); ?>" style="font-size:.65rem;">
                                    <?php echo e($row->status_code); ?>

                                </span>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/active-users.blade.php ENDPATH**/ ?>