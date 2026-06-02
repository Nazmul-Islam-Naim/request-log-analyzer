<?php $__env->startSection('title', 'User Route Hits — Request Log Analyzer'); ?>
<?php $__env->startSection('page-title', 'User Route Hits'); ?>

<?php $__env->startSection('content'); ?>


<div class="card" style="margin-bottom:1.25rem;">
    <form method="GET" action="<?php echo e(route('request-log-analyzer.user-route-hits')); ?>" style="display:contents;">
        <div class="filter-bar">

            <span class="filter-label">User</span>
            <select name="user_id" onchange="this.form.submit()">
                <option value="">All Users</option>
                <?php $__currentLoopData = $allUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($u->id); ?>" <?php echo e(request('user_id') == $u->id ? 'selected' : ''); ?>>
                        <?php echo e($u->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <span class="filter-label">From</span>
            <input type="date" name="from" value="<?php echo e(request('from')); ?>">

            <span class="filter-label">To</span>
            <input type="date" name="to" value="<?php echo e(request('to')); ?>">

            
            <span class="filter-label">Sort</span>
            <select name="sort" onchange="this.form.submit()">
                <option value="desc" <?php echo e($sort === 'desc' ? 'selected' : ''); ?>>Highest first</option>
                <option value="asc"  <?php echo e($sort === 'asc'  ? 'selected' : ''); ?>>Lowest first</option>
            </select>

            <button type="submit" class="btn btn-primary" style="height:32px;">Filter</button>

            <?php if(request()->hasAny(['user_id','from','to','sort'])): ?>
                <a href="<?php echo e(route('request-log-analyzer.user-route-hits')); ?>" class="btn btn-ghost" style="height:32px;">Clear</a>
            <?php endif; ?>

            <div class="filter-spacer"></div>
            <span class="text-muted" style="font-size:.72rem;">
                <?php echo e(number_format($hits->total())); ?> route–user combinations
            </span>
        </div>
    </form>

    
    <?php if($hits->isEmpty()): ?>
        <div class="empty-state">No data matches the current filters.</div>
    <?php else: ?>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Route</th>
                        <th style="text-align:right;">
                            Hit Count
                            <?php if($sort === 'desc'): ?>
                                <svg style="width:10px;height:10px;vertical-align:middle;color:#94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                            <?php else: ?>
                                <svg style="width:10px;height:10px;vertical-align:middle;color:#94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
                            <?php endif; ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $hits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $user = $users->get($row->user_id); ?>
                        <tr>
                            <td class="text-muted" style="width:3rem;">
                                <?php echo e(($hits->currentPage() - 1) * $hits->perPage() + $loop->iteration); ?>

                            </td>

                            <td>
                                <?php if($user): ?>
                                    <div style="font-weight:600;line-height:1.2;"><?php echo e($user->name); ?></div>
                                    <div class="text-muted" style="font-size:.7rem;"><?php echo e($user->email ?? ''); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">User #<?php echo e($row->user_id); ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <code style="font-size:.75rem;"><?php echo e($row->uri); ?></code>
                            </td>

                            <td style="text-align:right;">
                                <span class="stat-value c-blue" style="font-size:1rem;">
                                    <?php echo e(number_format($row->hit_count)); ?>

                                </span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        
        <?php if($hits->hasPages()): ?>
            <div class="pagination-wrap">
                <?php echo e($hits->links('request-log-analyzer::_pagination')); ?>

            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/user-route-hits.blade.php ENDPATH**/ ?>