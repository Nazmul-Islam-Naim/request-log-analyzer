<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Request Log Analyzer')</title>
    @stack('head')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── Sidebar ──────────────────────────────────────────────────────── */
        .sidebar {
            width: 220px;
            flex-shrink: 0;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            z-index: 10;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: 1.1rem 1.25rem;
            color: #f8fafc;
            font-size: .875rem;
            font-weight: 700;
            letter-spacing: .01em;
            border-bottom: 1px solid #1e293b;
            text-decoration: none;
        }
        .sidebar-brand-icon {
            width: 28px; height: 28px;
            background: #2563eb;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-brand-icon svg { width: 15px; height: 15px; color: #fff; }

        .nav-section {
            padding: 1.25rem 1.25rem .35rem;
            font-size: .65rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 600;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: .625rem;
            padding: .525rem 1rem;
            margin: 1px .625rem;
            border-radius: 6px;
            color: #94a3b8;
            font-size: .8rem;
            text-decoration: none;
            transition: background .12s, color .12s;
        }
        .nav-link svg { width: 14px; height: 14px; flex-shrink: 0; }
        .nav-link:hover { background: #1e293b; color: #e2e8f0; }
        .nav-link.active { background: #1e3a8a; color: #bfdbfe; }
        .nav-link.active svg { color: #60a5fa; }

        /* Live pulse dot (Active Users) */
        .nav-live-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #22c55e; margin-left: auto; flex-shrink: 0;
            animation: nav-blink 2s ease-in-out infinite;
        }
        @keyframes nav-blink { 0%,100%{opacity:1} 50%{opacity:.2} }

        .sidebar-footer {
            margin-top: auto;
            padding: 1rem 1.25rem;
            font-size: .68rem;
            color: #334155;
            border-top: 1px solid #1e293b;
        }

        /* ── Main ─────────────────────────────────────────────────────────── */
        .main { margin-left: 220px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .875rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky; top: 0; z-index: 5;
        }
        .topbar-title { font-size: .9rem; font-weight: 600; color: #0f172a; }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: .75rem; }
        .topbar-badge {
            font-size: .68rem;
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 500;
        }

        .page { padding: 1.75rem 2rem; flex: 1; }

        /* ── Breadcrumb ───────────────────────────────────────────────────── */
        .breadcrumb { display: flex; align-items: center; gap: .4rem; font-size: .75rem; color: #94a3b8; margin-bottom: .1rem; }
        .breadcrumb a { color: #64748b; text-decoration: none; }
        .breadcrumb a:hover { color: #2563eb; }
        .breadcrumb-sep { color: #cbd5e1; }

        /* ── Stats grid ───────────────────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .875rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            padding: 1.1rem 1.25rem;
        }
        .stat-label { font-size: .65rem; color: #64748b; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
        .stat-value { font-size: 1.65rem; font-weight: 700; margin-top: .2rem; line-height: 1.1; }
        .stat-sub { font-size: .7rem; color: #94a3b8; margin-top: .3rem; }
        .c-blue   { color: #2563eb; }
        .c-green  { color: #16a34a; }
        .c-red    { color: #dc2626; }
        .c-amber  { color: #d97706; }
        .c-purple { color: #7c3aed; }
        .c-slate  { color: #475569; }

        /* ── Card ────────────────────────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .card-head {
            padding: .875rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .card-head h2 { font-size: .82rem; font-weight: 600; flex: 1; }
        .card-head-action { font-size: .75rem; color: #3b82f6; text-decoration: none; white-space: nowrap; }
        .card-head-action:hover { text-decoration: underline; }
        .card-body { padding: 1.25rem; }

        /* ── Two-column ──────────────────────────────────────────────────── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 960px) { .two-col { grid-template-columns: 1fr; } }

        /* ── Table ───────────────────────────────────────────────────────── */
        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #f8fafc;
            padding: .55rem 1.25rem;
            text-align: left;
            font-size: .65rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
        }
        tbody td { padding: .65rem 1.25rem; font-size: .8rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fafafa; }
        td.trunc { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        td.monosp { font-family: 'Menlo', 'Consolas', monospace; font-size: .75rem; }

        /* ── Badges ──────────────────────────────────────────────────────── */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: .67rem; font-weight: 600; white-space: nowrap; }
        .badge-GET    { background: #dbeafe; color: #1d4ed8; }
        .badge-POST   { background: #dcfce7; color: #15803d; }
        .badge-PUT    { background: #fef9c3; color: #854d0e; }
        .badge-PATCH  { background: #fef9c3; color: #854d0e; }
        .badge-DELETE { background: #fee2e2; color: #b91c1c; }
        .badge-HEAD   { background: #f1f5f9; color: #475569; }
        .badge-other  { background: #f1f5f9; color: #475569; }
        /* severity */
        .badge-debug     { background: #f1f5f9; color: #475569; }
        .badge-info      { background: #dbeafe; color: #1d4ed8; }
        .badge-notice    { background: #e0f2fe; color: #0369a1; }
        .badge-warning   { background: #fef9c3; color: #854d0e; }
        .badge-error     { background: #fee2e2; color: #b91c1c; }
        .badge-critical  { background: #fce7f3; color: #9d174d; }
        .badge-alert     { background: #fdf2f8; color: #86198f; }
        .badge-emergency { background: #0f172a; color: #f8fafc; }

        .status-2xx { color: #16a34a; font-weight: 600; }
        .status-3xx { color: #2563eb; font-weight: 600; }
        .status-4xx { color: #ea580c; font-weight: 600; }
        .status-5xx { color: #dc2626; font-weight: 600; }

        /* ── Buttons ─────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .425rem .875rem;
            border-radius: 6px;
            font-size: .78rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .12s;
        }
        .btn svg { width: 13px; height: 13px; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-ghost { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-ghost:hover { background: #e2e8f0; }
        .btn-danger { background: #fff; color: #dc2626; border: 1px solid #fecaca; }
        .btn-danger:hover { background: #fef2f2; }

        /* ── Filter bar ──────────────────────────────────────────────────── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: .625rem;
            padding: .75rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            background: #fafafa;
            flex-wrap: wrap;
        }
        .filter-bar select,
        .filter-bar input[type="text"] {
            padding: .35rem .7rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: .78rem;
            color: #0f172a;
            background: #fff;
            outline: none;
            height: 32px;
        }
        .filter-bar select:focus,
        .filter-bar input:focus { border-color: #3b82f6; }
        .filter-label { font-size: .72rem; color: #64748b; white-space: nowrap; }
        .filter-spacer { flex: 1; }

        /* ── Pagination ──────────────────────────────────────────────────── */
        .pagination {
            display: flex;
            align-items: center;
            gap: .35rem;
            padding: .875rem 1.25rem;
            border-top: 1px solid #f1f5f9;
        }
        .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 .5rem;
            border-radius: 6px;
            font-size: .78rem;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            color: #475569;
            background: #fff;
            transition: background .1s;
        }
        .page-btn:hover { background: #f1f5f9; }
        .page-btn.current { background: #2563eb; color: #fff; border-color: #2563eb; font-weight: 600; }
        .page-btn.disabled { color: #cbd5e1; pointer-events: none; }
        .pagination-info { margin-left: auto; font-size: .72rem; color: #94a3b8; }

        /* ── Detail grid ─────────────────────────────────────────────────── */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 2rem;
        }
        @media (max-width: 700px) { .detail-grid { grid-template-columns: 1fr; } }
        .detail-field { display: flex; flex-direction: column; gap: .2rem; }
        .detail-field .df-label { font-size: .65rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
        .detail-field .df-value { font-size: .82rem; color: #0f172a; word-break: break-all; }
        .detail-field .df-value.mono { font-family: 'Menlo', 'Consolas', monospace; font-size: .75rem; }

        /* ── Chart container ─────────────────────────────────────────────── */
        .chart-wrap { position: relative; width: 100%; height: 200px; }

        /* ── Misc ────────────────────────────────────────────────────────── */
        .text-muted { color: #94a3b8; }
        .text-mono  { font-family: 'Menlo', 'Consolas', monospace; font-size: .75rem; }
        .empty-state { text-align: center; padding: 2.5rem 1.5rem; color: #94a3b8; font-size: .82rem; }
        .slow-marker { display: inline-block; color: #dc2626; font-size: .7rem; font-weight: 700; margin-left: .25rem; }
        .tag-link { font-size: .72rem; color: #3b82f6; text-decoration: none; }
        .tag-link:hover { text-decoration: underline; }
        code { font-family: 'Menlo', 'Consolas', monospace; font-size: .8em; background: #f1f5f9; padding: 1px 5px; border-radius: 3px; }

        footer { text-align: center; padding: 1.5rem; font-size: .7rem; color: #94a3b8; border-top: 1px solid #e2e8f0; background: #fff; }
    </style>
</head>
<body>

{{-- ── Sidebar ────────────────────────────────────────────────────────── --}}
<nav class="sidebar">

    {{-- Brand --}}
    <a class="sidebar-brand" href="{{ route('request-log-analyzer.dashboard') }}">
        <div class="sidebar-brand-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div>
            <div style="font-size:.8rem;font-weight:700;line-height:1.1;">Request Log</div>
            <div style="font-size:.6rem;color:#64748b;font-weight:500;letter-spacing:.04em;">ANALYZER</div>
        </div>
    </a>

    {{-- ── Main navigation ─────────────────────────────────────────── --}}
    <div class="nav-section">Main</div>

    {{-- 1. Dashboard --}}
    <a href="{{ route('request-log-analyzer.dashboard') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.dashboard') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
        Dashboard
    </a>

    {{-- 2. Request Logs --}}
    <a href="{{ route('request-log-analyzer.requests') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.requests') || request()->routeIs('request-log-analyzer.show') || request()->routeIs('request-log-analyzer.timeline') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        Request Logs
    </a>

    {{-- 3. Slow Requests --}}
    <a href="{{ route('request-log-analyzer.slow-requests') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.slow-requests') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Slow Requests
    </a>

    {{-- ── Reports section ──────────────────────────────────────────── --}}
    <div class="nav-section">Reports</div>

    {{-- 3. User Route Report --}}
    <a href="{{ route('request-log-analyzer.user-route-hits') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.user-route-hits') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        User Route Report
    </a>

    {{-- 4. Active Users --}}
    <a href="{{ route('request-log-analyzer.active-users') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.active-users') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Active Users
        <span class="nav-live-dot"></span>
    </a>

    {{-- 5. Login History --}}
    <a href="{{ route('request-log-analyzer.login-history') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.login-history') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Login History
    </a>

    {{-- Analytics (always visible) --}}
    <div class="nav-section">Insights</div>

    <a href="{{ route('request-log-analyzer.api-insights') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.api-insights') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21v-4a6 6 0 0 1 6-6h0a6 6 0 0 1 6 6v4"/>
            <circle cx="9" cy="9" r="4"/>
            <path d="M16 8.94a4 4 0 0 1 0 7.07"/>
            <path d="M9 14c1.5 0 3 .5 3 2v4"/>
        </svg>
        API Insights
    </a>

    <a href="{{ route('request-log-analyzer.analytics') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.analytics') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/>
            <line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6"  y1="20" x2="6"  y2="14"/>
        </svg>
        Analytics
    </a>

    <a href="{{ route('request-log-analyzer.geo') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.geo') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
        </svg>
        Geo Analytics
    </a>

    {{-- Request Replay --}}
    <div class="nav-section">Tools & Replay</div>

    <a href="{{ route('request-log-analyzer.replay.index') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.replay.*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 2l4 4m-4-4v3m0-3H9a2 2 0 00-2 2v11a2 2 0 002 2h9a2 2 0 002-2V7"/>
            <path d="M9 9h6m-6 4h6m-6 4h2"/>
        </svg>
        Request Replay
    </a>

    {{-- Tools --}}
    <a href="{{ route('request-log-analyzer.tools') }}"
       class="nav-link {{ request()->routeIs('request-log-analyzer.tools') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
        Tools
    </a>

    {{-- Footer --}}
    <div class="sidebar-footer">
        <div style="font-size:.65rem;color:#475569;">nin/request-log-analyzer</div>
    </div>
</nav>

{{-- ── Main ────────────────────────────────────────────────────────────── --}}
<div class="main">
    <div class="topbar">
        <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
        <div class="topbar-right">
            <span class="topbar-badge">{{ config('request-log-analyzer.route_prefix', 'request-log-analyzer') }}</span>
        </div>
    </div>

    <div class="page">
        @yield('content')
    </div>

    <footer>Request Log Analyzer &mdash; nin/request-log-analyzer</footer>
</div>

@stack('scripts')
</body>
</html>
