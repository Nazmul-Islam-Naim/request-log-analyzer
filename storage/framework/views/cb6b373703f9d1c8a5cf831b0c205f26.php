


<?php $__env->startSection('title', 'Timeline — ' . $log->method . ' ' . $log->uri); ?>

<?php $__env->startPush('head'); ?>
<style>
    /* ── Step type colour tokens ─────────────────────────────────────── */
    :root {
        --clr-middleware : #3b82f6;
        --clr-controller : #10b981;
        --clr-view       : #8b5cf6;
        --clr-event      : #ec4899;
        --clr-job        : #f59e0b;
        --clr-other      : #64748b;
    }

    /* ── Request meta strip ─────────────────────────────────────────── */
    .req-meta {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
        padding: .75rem 1.25rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        font-size: .78rem;
    }
    .req-meta .uri  { font-family: 'Menlo','Consolas',monospace; font-size: .78rem; color: #0f172a; word-break: break-all; flex: 1; min-width: 0; }
    .req-meta .sep  { color: #cbd5e1; }

    /* ── Vertical timeline ──────────────────────────────────────────── */
    .tl-wrap { padding: 1rem 1.25rem; }

    .tl-item {
        display: grid;
        grid-template-columns: 36px 1fr;
        column-gap: .75rem;
        position: relative;
        padding-bottom: 1rem;
    }
    .tl-item:last-child { padding-bottom: 0; }

    .tl-item:not(:last-child) .tl-dot::after {
        content: '';
        position: absolute;
        left: 17px;
        top: 36px;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }

    .tl-dot {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        z-index: 1;
    }
    .tl-dot svg { width: 14px; height: 14px; }

    .tl-dot.type-middleware { background: #dbeafe; border-color: var(--clr-middleware); color: var(--clr-middleware); }
    .tl-dot.type-controller { background: #d1fae5; border-color: var(--clr-controller); color: var(--clr-controller); }
    .tl-dot.type-view       { background: #ede9fe; border-color: var(--clr-view);       color: var(--clr-view);       }
    .tl-dot.type-event      { background: #fce7f3; border-color: var(--clr-event);      color: var(--clr-event);      }
    .tl-dot.type-job        { background: #fef3c7; border-color: var(--clr-job);        color: var(--clr-job);        }
    .tl-dot.type-other      { background: #f1f5f9; border-color: var(--clr-other);      color: var(--clr-other);      }

    .tl-body {
        background: #fafafa;
        border: 1px solid #e2e8f0;
        border-radius: .6rem;
        padding: .65rem 1rem;
        min-width: 0;
    }
    .tl-top {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: .3rem;
    }
    .tl-name {
        font-size: .82rem;
        font-weight: 600;
        color: #0f172a;
        word-break: break-all;
        flex: 1;
        min-width: 0;
    }
    .tl-type-badge {
        font-size: .64rem;
        font-weight: 700;
        padding: 1px 7px;
        border-radius: 9999px;
        white-space: nowrap;
    }
    .tl-type-badge.type-middleware { background: #dbeafe; color: #1d4ed8; }
    .tl-type-badge.type-controller { background: #d1fae5; color: #065f46; }
    .tl-type-badge.type-view       { background: #ede9fe; color: #5b21b6; }
    .tl-type-badge.type-event      { background: #fce7f3; color: #9d174d; }
    .tl-type-badge.type-job        { background: #fef3c7; color: #78350f; }
    .tl-type-badge.type-other      { background: #f1f5f9; color: #475569; }

    .tl-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: .72rem;
        color: #94a3b8;
        flex-wrap: wrap;
    }
    .tl-meta .hi  { font-weight: 600; color: #475569; }

    .tl-bar-wrap {
        height: 4px;
        background: #e2e8f0;
        border-radius: 2px;
        margin-top: .55rem;
        overflow: hidden;
    }
    .tl-bar-fill {
        height: 100%;
        border-radius: 2px;
        min-width: 2px;
    }

    /* ── Gantt chart ────────────────────────────────────────────────── */
    .gantt-outer { overflow-x: auto; padding: 1rem 1.25rem 1.25rem; }
    .gantt-inner { min-width: 500px; }

    .gantt-legend {
        display: flex;
        gap: .6rem 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .legend-dot  { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }
    .legend-item { display: flex; align-items: center; gap: .35rem; font-size: .72rem; color: #475569; }

    .gantt-ruler-wrap { display: flex; margin-left: 160px; margin-bottom: .25rem; }
    .gantt-ruler {
        flex: 1;
        position: relative;
        height: 20px;
        overflow: visible;
    }
    .ruler-label {
        position: absolute;
        font-size: .62rem;
        color: #94a3b8;
        transform: translateX(-50%);
        white-space: nowrap;
        bottom: 0;
    }
    .ruler-label:first-child { transform: none; left: 0 !important; }
    .ruler-label:last-child  { transform: translateX(-100%); }

    .gantt-rows { display: flex; flex-direction: column; gap: 5px; }
    .gantt-row  { display: flex; align-items: center; gap: 8px; min-height: 26px; }

    .gantt-row-label {
        width: 160px;
        flex-shrink: 0;
        font-size: .72rem;
        color: #475569;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding-right: 6px;
    }
    .gantt-track {
        flex: 1;
        height: 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 3px;
        position: relative;
        overflow: hidden;
    }
    .gantt-bar {
        position: absolute;
        top: 0;
        height: 100%;
        border-radius: 2px;
        min-width: 3px;
        cursor: pointer;
        transition: opacity .12s;
    }
    .gantt-bar:hover { opacity: .75; }
    .gantt-bar.type-middleware { background: var(--clr-middleware); }
    .gantt-bar.type-controller { background: var(--clr-controller); }
    .gantt-bar.type-view       { background: var(--clr-view); }
    .gantt-bar.type-event      { background: var(--clr-event); }
    .gantt-bar.type-job        { background: var(--clr-job); }
    .gantt-bar.type-other      { background: var(--clr-other); }
    .gantt-bar.total-bar       { background: #e2e8f0; }
    .gantt-bar.query-bar       { background: #f59e0b; }
    .gantt-bar.query-bar.slow  { background: #ef4444; }
    .gantt-bar-text {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        left: 5px;
        font-size: .6rem;
        color: #fff;
        white-space: nowrap;
        pointer-events: none;
        overflow: hidden;
    }
    .gantt-sep { height: 1px; background: #f1f5f9; margin: 3px 0 3px 168px; }

    /* ── Tooltip ─────────────────────────────────────────────────────── */
    .rla-tip {
        display: none;
        position: fixed;
        z-index: 9999;
        background: #1e293b;
        color: #f8fafc;
        font-size: .73rem;
        padding: .55rem .8rem;
        border-radius: 7px;
        max-width: 340px;
        line-height: 1.55;
        pointer-events: none;
        box-shadow: 0 4px 20px rgba(0,0,0,.25);
    }
    .rla-tip code   { font-size: .7rem; color: #93c5fd; word-break: break-all; }
    .rla-tip strong { color: #fde68a; }

    /* ── DB Queries ──────────────────────────────────────────────────── */
    .query-row { background: #fafafa; border-radius: .5rem; padding: .65rem 1rem; border: 1px solid #f1f5f9; }
    .query-row + .query-row { margin-top: .5rem; }
    .query-sql  { font-family: 'Menlo','Consolas',monospace; font-size: .72rem; color: #334155; word-break: break-all; line-height: 1.5; }
    .query-row.is-slow { border-color: #fecaca; background: #fff5f5; }
    .query-row.is-slow .query-sql { color: #b91c1c; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('page-title', 'Timeline'); ?>

<?php $__env->startSection('content'); ?>

<?php
    $totalMs = max((int) ($log->response_time_ms ?? 1), 1);
    $steps   = $log->steps->sortBy('sequence');
    $queries = $log->queries->sortBy('started_at_ms');
    $sc      = $log->status_code;
    $pct     = fn(int $ms): float => round(min(max($ms / $totalMs, 0), 1) * 100, 4);

    $typeColors = [
        'middleware' => '#3b82f6',
        'controller' => '#10b981',
        'view'       => '#8b5cf6',
        'event'      => '#ec4899',
        'job'        => '#f59e0b',
        'other'      => '#64748b',
    ];
?>


<div class="breadcrumb" style="margin-bottom:.75rem;">
    <a href="<?php echo e(route('request-log-analyzer.requests')); ?>">Requests</a>
    <span class="breadcrumb-sep">/</span>
    <a href="<?php echo e(route('request-log-analyzer.show', $log->id)); ?>">#<?php echo e($log->id); ?></a>
    <span class="breadcrumb-sep">/</span>
    <span>Timeline</span>
</div>


<div class="req-meta">
    <span class="badge badge-<?php echo e($log->method); ?>"><?php echo e($log->method); ?></span>
    <span class="uri"><?php echo e($log->uri); ?></span>
    <span class="sep">|</span>
    <?php $scClass = $sc >= 500 ? 'badge-error' : ($sc >= 400 ? 'badge-warning' : 'badge-info'); ?>
    <span class="badge <?php echo e($scClass); ?>"><?php echo e($log->status_code); ?></span>
    <span class="sep">|</span>
    <span class="text-muted"><?php echo e($log->created_at->format('Y-m-d H:i:s')); ?></span>
    <?php if($log->ip): ?>
        <span class="sep">|</span>
        <code><?php echo e($log->ip); ?></code>
    <?php endif; ?>
</div>


<div class="stats-grid" style="margin-bottom:1.25rem;">
    <div class="stat-card">
        <div class="stat-label">Total Time</div>
        <div class="stat-value c-blue"><?php echo e($log->response_time_ms ?? 0); ?><span style="font-size:.85rem;font-weight:400;color:#94a3b8;"> ms</span></div>
        <div class="stat-sub">end-to-end</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lifecycle Steps</div>
        <div class="stat-value c-green"><?php echo e($steps->count()); ?></div>
        <div class="stat-sub">middleware · controller · view</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">DB Queries</div>
        <div class="stat-value c-amber"><?php echo e($queries->count()); ?></div>
        <div class="stat-sub"><?php echo e($queries->sum('duration_ms')); ?> ms total</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Slow Queries</div>
        <div class="stat-value c-red"><?php echo e($queries->where('is_slow', true)->count()); ?></div>
        <div class="stat-sub">above threshold</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Memory</div>
        <div class="stat-value c-purple" style="font-size:1.3rem;">
            <?php if($log->memory_usage_bytes): ?>
                <?php echo e(number_format($log->memory_usage_bytes / 1048576, 1)); ?><span style="font-size:.85rem;font-weight:400;color:#94a3b8;"> MB</span>
            <?php else: ?>
                —
            <?php endif; ?>
        </div>
        <div class="stat-sub">peak usage</div>
    </div>
</div>


<div class="card">
    <div class="card-head">
        <h2>Gantt Chart</h2>
        <div class="gantt-legend">
            <?php $__currentLoopData = $typeColors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="legend-item">
                    <div class="legend-dot" style="background:<?php echo e($color); ?>;"></div>
                    <?php echo e(ucfirst($type)); ?>

                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div class="legend-item"><div class="legend-dot" style="background:#f59e0b;"></div> Query</div>
            <div class="legend-item"><div class="legend-dot" style="background:#ef4444;"></div> Slow Query</div>
        </div>
    </div>

    <div class="gantt-outer">
        <?php if($steps->isEmpty() && $queries->isEmpty()): ?>
            <div class="empty-state">No lifecycle steps recorded for this request.</div>
        <?php else: ?>
        <div class="gantt-inner">
            
            <div class="gantt-ruler-wrap">
                <div class="gantt-ruler">
                    <?php for($i = 0; $i <= 6; $i++): ?>
                        <?php $ms = ($totalMs / 6) * $i; ?>
                        <span class="ruler-label" style="left:<?php echo e($pct((int)$ms)); ?>%;"><?php echo e(number_format($ms, 0)); ?>ms</span>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="gantt-rows">
                
                <div class="gantt-row">
                    <div class="gantt-row-label" style="color:#94a3b8;font-style:italic;">total request</div>
                    <div class="gantt-track">
                        <div class="gantt-bar total-bar" style="left:0%;width:100%;background:#cbd5e1;"></div>
                    </div>
                </div>

                
                <?php if($steps->isNotEmpty()): ?>
                    <div class="gantt-sep"></div>
                    <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $startMs  = (int) ($step->started_at_ms ?? 0);
                            $durMs    = (int) ($step->duration_ms   ?? 0);
                            $leftPct  = $pct($startMs);
                            $widthPct = min(max($pct($durMs), 0.3), 100 - $leftPct);
                        ?>
                        <div class="gantt-row">
                            <div class="gantt-row-label" title="<?php echo e($step->name); ?>"><?php echo e($step->name); ?></div>
                            <div class="gantt-track">
                                <div class="gantt-bar type-<?php echo e($step->type); ?>"
                                     style="left:<?php echo e($leftPct); ?>%;width:<?php echo e($widthPct); ?>%;"
                                     onmouseenter="rlaTip.show(event,this)"
                                     onmouseleave="rlaTip.hide()"
                                     data-tip="<strong><?php echo e(e(ucfirst($step->type))); ?>: <?php echo e(e($step->name)); ?></strong><br>Start: <?php echo e($startMs); ?> ms &nbsp;·&nbsp; Duration: <?php echo e($durMs); ?> ms">
                                    <?php if($widthPct > 8): ?>
                                        <span class="gantt-bar-text"><?php echo e($durMs); ?>ms</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>

                
                <?php if($queries->isNotEmpty()): ?>
                    <div class="gantt-sep"></div>
                    <?php $__currentLoopData = $queries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $qStart   = (int) ($q->started_at_ms ?? 0);
                            $qDur     = (int) ($q->duration_ms   ?? 0);
                            $qLeft    = $pct($qStart);
                            $qWidth   = min(max($pct($qDur), 0.3), 100 - $qLeft);
                            $isSlow   = (bool) ($q->is_slow ?? false);
                            $shortSql = preg_replace('/\s+/', ' ', (string) ($q->sql ?? ''));
                            $shortSql = mb_strlen($shortSql) > 50 ? mb_substr($shortSql, 0, 48) . '…' : $shortSql;
                            $tipHtml  = '<strong>Query</strong>' . ($isSlow ? ' <span style="color:#fca5a5;">⚠ slow</span>' : '')
                                . '<br><code>' . e($q->sql ?? '') . '</code>'
                                . '<br>Connection: ' . e($q->connection_name ?? 'default')
                                . ' &nbsp;·&nbsp; Duration: ' . $qDur . ' ms';
                        ?>
                        <div class="gantt-row">
                            <div class="gantt-row-label" title="<?php echo e($q->sql ?? ''); ?>"
                                 style="font-family:monospace;font-size:.65rem;"><?php echo e($shortSql); ?></div>
                            <div class="gantt-track">
                                <div class="gantt-bar query-bar <?php echo e($isSlow ? 'slow' : ''); ?>"
                                     style="left:<?php echo e($qLeft); ?>%;width:<?php echo e($qWidth); ?>%;"
                                     onmouseenter="rlaTip.show(event,this)"
                                     onmouseleave="rlaTip.hide()"
                                     data-tip="<?php echo e(e($tipHtml)); ?>">
                                    <?php if($qWidth > 8): ?>
                                        <span class="gantt-bar-text"><?php echo e($qDur); ?>ms</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>


<?php if($steps->isNotEmpty()): ?>
<div class="card">
    <div class="card-head">
        <h2>Lifecycle Steps</h2>
        <span class="text-muted" style="font-size:.72rem;"><?php echo e($steps->count()); ?> steps</span>
    </div>
    <div class="tl-wrap">
        <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $startMs = (int) ($step->started_at_ms ?? 0);
                $durMs   = (int) ($step->duration_ms   ?? 0);
                $barPct  = $totalMs > 0 ? min(round($durMs / $totalMs * 100, 1), 100) : 0;
                $type    = $step->type;
                $color   = $typeColors[$type] ?? '#64748b';
            ?>
            <div class="tl-item">
                <div class="tl-dot type-<?php echo e($type); ?>">
                    <?php switch($type):
                        case ('middleware'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <?php break; ?>
                        <?php case ('controller'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            <?php break; ?>
                        <?php case ('view'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <?php break; ?>
                        <?php case ('event'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            <?php break; ?>
                        <?php case ('job'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                            <?php break; ?>
                        <?php default: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
                    <?php endswitch; ?>
                </div>

                <div class="tl-body">
                    <div class="tl-top">
                        <span class="tl-name"><?php echo e($step->name); ?></span>
                        <span class="tl-type-badge type-<?php echo e($type); ?>"><?php echo e($type); ?></span>
                        <span class="badge" style="background:#f1f5f9;color:#64748b;font-size:.62rem;">#<?php echo e($step->sequence); ?></span>
                    </div>
                    <div class="tl-meta">
                        <span>Start: <span class="hi"><?php echo e($startMs); ?> ms</span></span>
                        <span>Duration: <span class="hi" style="color:<?php echo e($color); ?>;"><?php echo e($durMs); ?> ms</span></span>
                        <span>End: <span class="hi"><?php echo e($startMs + $durMs); ?> ms</span></span>
                    </div>
                    <div class="tl-bar-wrap">
                        <div class="tl-bar-fill" style="width:<?php echo e(max($barPct, 0.5)); ?>%;background:<?php echo e($color); ?>;"></div>
                    </div>
                    <?php if(!empty($step->metadata)): ?>
                        <details style="margin-top:.5rem;">
                            <summary style="font-size:.7rem;color:#94a3b8;cursor:pointer;user-select:none;">metadata</summary>
                            <pre style="font-size:.68rem;color:#475569;margin-top:.35rem;background:#f1f5f9;padding:.5rem .75rem;border-radius:4px;overflow-x:auto;white-space:pre-wrap;line-height:1.5;"><?php echo e(json_encode($step->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php endif; ?>


<?php if($queries->isNotEmpty()): ?>
<div class="card">
    <div class="card-head">
        <h2>DB Queries</h2>
        <span class="text-muted" style="font-size:.72rem;">
            <?php echo e($queries->count()); ?> &nbsp;·&nbsp; <?php echo e($queries->sum('duration_ms')); ?> ms total
            <?php if($queries->where('is_slow', true)->count()): ?>
                &nbsp;·&nbsp; <span style="color:#dc2626;font-weight:600;"><?php echo e($queries->where('is_slow', true)->count()); ?> slow</span>
            <?php endif; ?>
        </span>
    </div>
    <div class="card-body" style="padding:.75rem 1.25rem;">
        <?php $__currentLoopData = $queries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $isSlow = (bool) ($q->is_slow ?? false); ?>
            <div class="query-row <?php echo e($isSlow ? 'is-slow' : ''); ?>">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.35rem;">
                    <span class="badge" style="<?php echo e($isSlow ? 'background:#fee2e2;color:#b91c1c;' : 'background:#fef3c7;color:#78350f;'); ?>">
                        <?php echo e($q->duration_ms ?? 0); ?> ms
                    </span>
                    <?php if($isSlow): ?>
                        <span class="badge badge-error" style="font-size:.62rem;">⚠ slow</span>
                    <?php endif; ?>
                    <span class="text-muted" style="font-size:.7rem;margin-left:auto;">
                        <code><?php echo e($q->connection_name ?? 'default'); ?></code>
                    </span>
                </div>
                <div class="query-sql"><?php echo e($q->sql); ?></div>
                <?php if(!empty($q->bindings)): ?>
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:.3rem;">
                        bindings: <code><?php echo e(is_string($q->bindings) ? $q->bindings : json_encode($q->bindings)); ?></code>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php endif; ?>


<div class="rla-tip" id="rla-tip"></div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const rlaTip = {
        el: document.getElementById('rla-tip'),
        show(e, bar) {
            this.el.innerHTML = bar.dataset.tip;
            this.el.style.display = 'block';
            this.move(e);
        },
        hide() { this.el.style.display = 'none'; },
        move(e) {
            const w = this.el.offsetWidth;
            const h = this.el.offsetHeight;
            const x = e.clientX + 14;
            const y = e.clientY + 14;
            this.el.style.left = (x + w > window.innerWidth  ? x - w - 28 : x) + 'px';
            this.el.style.top  = (y + h > window.innerHeight ? y - h - 28 : y) + 'px';
        },
    };
    document.addEventListener('mousemove', e => {
        if (rlaTip.el.style.display !== 'none') rlaTip.move(e);
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/timeline.blade.php ENDPATH**/ ?>