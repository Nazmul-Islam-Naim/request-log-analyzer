<?php

namespace NIN\RequestLogAnalyzer\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use NIN\RequestLogAnalyzer\Contracts\ClearServiceInterface;
use NIN\RequestLogAnalyzer\Contracts\ErrorRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\QueryRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\RequestRepositoryInterface;
use NIN\RequestLogAnalyzer\Exports\ErrorsExport;
use NIN\RequestLogAnalyzer\Exports\QueriesExport;
use NIN\RequestLogAnalyzer\Exports\RequestsExport;
use NIN\RequestLogAnalyzer\Models\UserLoginHistory;
use Maatwebsite\Excel\Facades\Excel;

class RequestLogController extends Controller
{
    public function __construct(
        private readonly RequestRepositoryInterface $requests,
        private readonly ErrorRepositoryInterface $errors,
        private readonly QueryRepositoryInterface $queries,
        private readonly ClearServiceInterface $clearService,
    ) {}

    // ── Dashboard overview ─────────────────────────────────────────────────

    public function index()
    {
        $logs = $this->requests->recent(10);
        $stats = $this->requests->stats();
        $recentErrors = $this->errors->recentWithRequest(5);
        $slowQueries = $this->queries->slowWithRequest(5);

        // ── Chart data: requests per day for last 7 days ───────────────────
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));
        $rawDays = $this->requests->groupedByDay(7);

        $chartDays = $days->values();
        $chartTotals = $days->map(fn ($d) => (int) ($rawDays[$d]->total ?? 0))->values();
        $chartErrors = $days->map(fn ($d) => (int) ($rawDays[$d]->errors ?? 0))->values();

        $topRoutes = $this->requests->topRoutes(8);
        $countryStats = $this->requests->countryStats(10);

        // ── Advanced metric charts ─────────────────────────────────────────
        $rawAvgResponse = $this->requests->avgResponseByDay(7);
        $chartAvgResponse = $days->map(fn ($d) => (int) ($rawAvgResponse[$d]->avg_ms ?? 0))->values();

        $rawHourlyErrors = $this->requests->hourlyErrors(24);
        $hours24 = collect(range(23, 0))->map(fn ($i) => now()->subHours($i)->startOfHour());
        $chartHourLabels = $hours24->map(fn ($h) => $h->format('H:i'))->values();
        $chartHourlyErrors = $hours24->map(
            fn ($h) => (int) ($rawHourlyErrors[$h->format('Y-m-d H:00:00')]->cnt ?? 0)
        )->values();

        $requestsPerMinute = round(
            DB::table('rla_requests')->where('created_at', '>=', now()->subMinutes(60))->count() / 60,
            1
        );

        // ── Summary cards ──────────────────────────────────────────────────

        $windowMinutes = (int) config('request-log-analyzer.active_users_window_minutes', 5);

        // whereBetween uses the created_at index; whereDate() would wrap the
        // column in DATE() and make the index unusable.
        $todayRequests = Cache::remember('rla.today_requests', 60, fn () => DB::table('rla_requests')
            ->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])
            ->count()
        );

        // Short TTL (30 s) — this is displayed as "live" on the dashboard.
        $activeUsersNow = Cache::remember('rla.active_users_now', 30, fn () => DB::table('rla_requests')
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subMinutes(
                (int) config('request-log-analyzer.active_users_window_minutes', 5)
            ))
            ->distinct('user_id')
            ->count('user_id')
        );

        // Re-use the already-fetched $topRoutes collection — zero extra query.
        $topRoute = $topRoutes->first();

        $totalLoggedUsers = Cache::remember('rla.total_logged_users', 300, fn () => DB::table('rla_requests')
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id')
        );

        return view('request-log-analyzer::dashboard', compact(
            'logs', 'stats', 'recentErrors', 'slowQueries',
            'chartDays', 'chartTotals', 'chartErrors', 'topRoutes',
            'countryStats',
            'todayRequests', 'activeUsersNow', 'topRoute', 'totalLoggedUsers', 'windowMinutes',
            'chartAvgResponse', 'chartHourLabels', 'chartHourlyErrors', 'requestsPerMinute'
        ));
    }

    // ── Slow requests list ─────────────────────────────────────────────────

    public function slowRequests()
    {
        $threshold = (int) config('request-log-analyzer.slow_request_threshold_ms', 500);
        $logs = $this->requests->slowRequestsPaginate(50);

        return view('request-log-analyzer::slow-requests', compact('logs', 'threshold'));
    }

    // ── Paginated request list ─────────────────────────────────────────────

    public function requests(Request $request)
    {
        $filters = $request->only([
            'method', 'status', 'status_code',
            'uri', 'tag',
            'date_from', 'date_to',
            'rt_min', 'rt_max',
            'search',
        ]);

        // Normalise: discard blank strings so downstream code can use isset().
        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

        $logs = $this->requests->paginate($filters);

        return view('request-log-analyzer::requests.index', compact('logs', 'filters'));
    }

    // ── Single request detail ──────────────────────────────────────────────

    public function show(int $id)
    {
        $log = $this->requests->findWithRelations($id);

        return view('request-log-analyzer::requests.show', compact('log'));
    }

    // ── Route analytics ────────────────────────────────────────────────────

    public function analytics()
    {
        $byCount = $this->requests->analyticsTopByCount(15);
        $byAvg = $this->requests->analyticsTopByAvgMs(15);
        $uniqueRoutes = $this->requests->uniqueRouteCount();
        $totalCovered = $byCount->sum('hit_count');
        $slowest = $byAvg->first();
        $fastest = $this->requests->fastestRoute();

        return view('request-log-analyzer::analytics', compact(
            'byCount', 'byAvg', 'uniqueRoutes', 'totalCovered', 'slowest', 'fastest'
        ));
    }

    // ── API Insights ────────────────────────────────────────────────────────

    public function apiInsights()
    {
        // Get rate usage data
        $rateUsageRepo = app(\NIN\RequestLogAnalyzer\Contracts\RateUsageRepositoryInterface::class);
        
        $topUsers = $rateUsageRepo->topUsersByRequests('minute', 10);
        $suspiciousIps = $rateUsageRepo->suspiciousIps('minute', 10);
        
        // Get incidents
        $incidents = \NIN\RequestLogAnalyzer\Models\RateLimitIncident::unresolved()
            ->recent()
            ->orderByDesc('detected_at')
            ->limit(20)
            ->get();

        $resolvedIncidents = \NIN\RequestLogAnalyzer\Models\RateLimitIncident::resolved()
            ->recent()
            ->limit(5)
            ->orderByDesc('cleared_at')
            ->get();

        // Overall stats
        $totalApiRequests = DB::table('rla_api_rate_usage')->count();
        $totalIncidents = \NIN\RequestLogAnalyzer\Models\RateLimitIncident::count();
        $activeUsers = DB::table('rla_api_rate_usage')
            ->where('rate_limit_exceeded', false)
            ->distinct('user_id')
            ->count('user_id');
        $suspiciousCount = DB::table('rla_api_rate_usage')
            ->where('rate_limit_exceeded', true)
            ->count();

        // Chart data: requests per hour for last 24 hours
        $hours = collect(range(23, 0))->map(fn ($i) => now()->subHours($i)->format('H:00'));
        $hourlyData = DB::table('rla_api_rate_usage')
            ->selectRaw("DATE_FORMAT(last_request_at, '%H:00') as hour, COUNT(*) as count")
            ->where('last_request_at', '>=', now()->subHours(24))
            ->groupByRaw("DATE_FORMAT(last_request_at, '%H:00')")
            ->pluck('count', 'hour');

        $chartHours = $hours->values();
        $chartCounts = $hours->map(fn ($h) => (int) ($hourlyData[$h] ?? 0))->values();

        // Generate intelligent insights
        $insightsGenerator = app(\NIN\RequestLogAnalyzer\Services\InsightsGeneratorService::class);
        $insights = $insightsGenerator->generateInsights();

        return view('request-log-analyzer::api-insights', compact(
            'topUsers', 'suspiciousIps', 'incidents', 'resolvedIncidents',
            'totalApiRequests', 'totalIncidents', 'activeUsers', 'suspiciousCount',
            'chartHours', 'chartCounts', 'insights'
        ));
    }

    // ── Geo analytics ──────────────────────────────────────────────────────

    public function geo(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $countryStats = $this->requests->countryStatsAll($from, $to);
        $totalRequests = $countryStats->sum('count');
        $totalCountries = $countryStats->count();
        $topCountry = $countryStats->first();

        return view('request-log-analyzer::geo', compact(
            'countryStats', 'totalRequests', 'totalCountries', 'topCountry', 'from', 'to'
        ));
    }

    // ── Lifecycle timeline ─────────────────────────────────────────────────

    public function timeline(int $id)
    {
        $log = $this->requests->findWithRelations($id);

        return view('request-log-analyzer::timeline', compact('log'));
    }

    // ── Active users (last 5 minutes) ─────────────────────────────────────

    public function activeUsers()
    {
        $windowMinutes = (int) config('request-log-analyzer.active_users_window_minutes', 5);
        $since = now()->subMinutes($windowMinutes);

        // Per user_id: latest created_at and the uri of that latest row
        $active = \DB::table('rla_requests as r')
            ->joinSub(
                \DB::table('rla_requests')
                    ->select('user_id', \DB::raw('MAX(created_at) as last_activity'))
                    ->whereNotNull('user_id')
                    ->where('created_at', '>=', $since)
                    ->groupBy('user_id'),
                'latest',
                fn ($join) => $join
                    ->on('r.user_id', '=', 'latest.user_id')
                    ->on('r.created_at', '=', 'latest.last_activity')
            )
            ->select('r.user_id', 'latest.last_activity', 'r.uri as current_route', 'r.method', 'r.status_code')
            ->orderByDesc('latest.last_activity')
            ->get();

        $userModel = config('auth.providers.users.model', User::class);
        $userIds = $active->pluck('user_id')->all();
        $users = $userModel::whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $active = $active->map(function ($row) use ($users) {
            $row->user = $users->get($row->user_id);

            return $row;
        });

        return view('request-log-analyzer::active-users', compact('active', 'windowMinutes'));
    }

    // ── User route hits report ─────────────────────────────────────────────

    public function userRouteHits(Request $request)
    {
        $userModel = config('auth.providers.users.model', User::class);

        $sort = in_array($request->input('sort'), ['asc', 'desc'], true)
            ? $request->input('sort')
            : 'desc';

        $query = \DB::table('rla_requests')
            ->select(
                'user_id',
                'uri',
                \DB::raw('COUNT(*) as hit_count')
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'uri')
            ->orderBy('hit_count', $sort);

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $hits = $query->paginate(50)->withQueryString();
        $userIds = collect($hits->items())->pluck('user_id')->unique()->all();
        $users = $userModel::whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $allUsers = $userModel::orderBy('name')->get(['id', 'name']);

        return view('request-log-analyzer::user-route-hits', compact('hits', 'users', 'allUsers', 'sort'));
    }

    // ── User login history ─────────────────────────────────────────────────

    public function loginHistory(Request $request)
    {
        $userModel = config('auth.providers.users.model', User::class);

        $query = UserLoginHistory::with('user')->latest('login_at');

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('login_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('login_at', '<=', $to);
        }

        $histories = $query->paginate(50)->withQueryString();
        $users = $userModel::orderBy('name')->get(['id', 'name']);

        return view('request-log-analyzer::login-history', compact('histories', 'users'));
    }

    // ── Tools page (Export + Cleanup) ────────────────────────────────────

    public function tools()
    {
        return view('request-log-analyzer::tools');
    }

    // ── Export ─────────────────────────────────────────────────────────────

    public function export(Request $request, string $type)
    {
        $format = $request->query('format', 'csv');

        $exportMap = [
            'requests' => [RequestsExport::class, 'requests'],
            'errors'   => [ErrorsExport::class,   'errors'],
            'queries'  => [QueriesExport::class,   'queries'],
        ];

        abort_unless(array_key_exists($type, $exportMap), 404);

        [$exportClass, $baseName] = $exportMap[$type];

        $timestamp = now()->format('Y-m-d_His');

        if ($format === 'excel') {
            return Excel::download(
                new $exportClass(),
                "{$baseName}_{$timestamp}.xlsx",
                \Maatwebsite\Excel\Excel::XLSX
            );
        }

        return Excel::download(
            new $exportClass(),
            "{$baseName}_{$timestamp}.csv",
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    // ── Manual cleanup ─────────────────────────────────────────────────────

    public function cleanup(Request $request)
    {
        $days = $request->input('days');

        if ($days !== null && (! is_numeric($days) || (int) $days < 1)) {
            return redirect()->route('request-log-analyzer.tools')
                ->with('rla_cleanup_error', 'Retention period must be a positive integer.');
        }

        $retentionDays = $days !== null
            ? (int) $days
            : (int) config('request-log-analyzer.cleanup.retention_days', 90);

        $isDryRun = (bool) $request->input('dry_run');

        $totals = $isDryRun
            ? $this->clearService->count($retentionDays)
            : $this->clearService->clear($retentionDays);

        $totalRows = array_sum($totals);

        return redirect()->route('request-log-analyzer.tools')->with([
            'rla_cleanup_done'     => true,
            'rla_cleanup_dry_run'  => $isDryRun,
            'rla_cleanup_days'     => $retentionDays,
            'rla_cleanup_totals'   => $totals,
            'rla_cleanup_total'    => $totalRows,
        ]);
    }
}
