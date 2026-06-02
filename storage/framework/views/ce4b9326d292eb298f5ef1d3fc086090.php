<?php $__env->startSection('title', 'Login History — Request Log Analyzer'); ?>
<?php $__env->startSection('page-title', 'Login History'); ?>

<?php $__env->startSection('content'); ?>


<div class="card" style="margin-bottom:1.25rem;">
    <form method="GET" action="<?php echo e(route('request-log-analyzer.login-history')); ?>" style="display:contents;">
        <div class="filter-bar">

            <span class="filter-label">User</span>
            <select name="user_id" onchange="this.form.submit()">
                <option value="">All Users</option>
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($u->id); ?>" <?php echo e(request('user_id') == $u->id ? 'selected' : ''); ?>>
                        <?php echo e($u->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <span class="filter-label">From</span>
            <input type="date" name="from" value="<?php echo e(request('from')); ?>">

            <span class="filter-label">To</span>
            <input type="date" name="to" value="<?php echo e(request('to')); ?>">

            <button type="submit" class="btn btn-primary" style="height:32px;">Filter</button>

            <?php if(request()->hasAny(['user_id','from','to'])): ?>
                <a href="<?php echo e(route('request-log-analyzer.login-history')); ?>" class="btn btn-ghost" style="height:32px;">Clear</a>
            <?php endif; ?>

            <div class="filter-spacer"></div>
            <span class="text-muted" style="font-size:.72rem;"><?php echo e(number_format($histories->total())); ?> records</span>
        </div>
    </form>

    
    <?php if($histories->isEmpty()): ?>
        <div class="empty-state">No login records found.</div>
    <?php else: ?>
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
                    <?php $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="text-muted"><?php echo e($row->id); ?></td>

                            <td>
                                <?php if($row->user): ?>
                                    <span style="font-weight:600;"><?php echo e($row->user->name); ?></span>
                                    <br>
                                    <span class="text-muted" style="font-size:.7rem;"><?php echo e($row->user->email ?? ''); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo e($row->login_at?->format('Y-m-d H:i:s') ?? '—'); ?>

                            </td>

                            <td>
                                <?php if($row->logout_at): ?>
                                    <?php echo e($row->logout_at->format('Y-m-d H:i:s')); ?>

                                <?php else: ?>
                                    <span class="badge badge-green">Active</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if($row->sessionDuration()): ?>
                                    <?php echo e($row->sessionDuration()); ?>

                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <code style="font-size:.72rem;"><?php echo e($row->ip_address ?? '—'); ?></code>
                            </td>

                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="<?php echo e($row->user_agent); ?>">
                                <span class="text-muted" style="font-size:.72rem;"><?php echo e($row->user_agent ?? '—'); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        
        <?php if($histories->hasPages()): ?>
            <div class="pagination-wrap">
                <?php echo e($histories->links('request-log-analyzer::_pagination')); ?>

            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/login-history.blade.php ENDPATH**/ ?>