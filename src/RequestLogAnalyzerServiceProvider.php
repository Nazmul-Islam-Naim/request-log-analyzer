<?php

namespace NIN\RequestLogAnalyzer;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;
use NIN\RequestLogAnalyzer\Console\CleanupCommand;
use NIN\RequestLogAnalyzer\Console\ClearCommand;
use NIN\RequestLogAnalyzer\Console\GenerateApiTokenCommand;
use NIN\RequestLogAnalyzer\Console\InstallCommand;
use NIN\RequestLogAnalyzer\Console\ReportCommand;
use NIN\RequestLogAnalyzer\Console\TestAlertCommand;
use NIN\RequestLogAnalyzer\Contracts\AlertRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\ClearServiceInterface;
use NIN\RequestLogAnalyzer\Contracts\ErrorRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\GeoIpResolverInterface;
use NIN\RequestLogAnalyzer\Contracts\QueryRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\RateUsageRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\ReportServiceInterface;
use NIN\RequestLogAnalyzer\Contracts\RequestRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\StepRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\TagManagerInterface;
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;
use NIN\RequestLogAnalyzer\Listeners\TrackUserLogin;
use NIN\RequestLogAnalyzer\Repositories\ErrorRepository;
use NIN\RequestLogAnalyzer\Repositories\AlertRepository;
use NIN\RequestLogAnalyzer\Repositories\QueryRepository;
use NIN\RequestLogAnalyzer\Repositories\RateUsageRepository;
use NIN\RequestLogAnalyzer\Repositories\RequestRepository;
use NIN\RequestLogAnalyzer\Repositories\StepRepository;
use NIN\RequestLogAnalyzer\Services\AlertChecker;
use NIN\RequestLogAnalyzer\Services\AlertNotifier;
use NIN\RequestLogAnalyzer\Services\ClearService;
use NIN\RequestLogAnalyzer\Services\ExceptionCollector;
use NIN\RequestLogAnalyzer\Services\GeoIpResolver;
use NIN\RequestLogAnalyzer\Services\InsightsGeneratorService;
use NIN\RequestLogAnalyzer\Services\QueryCollector;
use NIN\RequestLogAnalyzer\Services\RateLimiterService;
use NIN\RequestLogAnalyzer\Services\ReportService;
use NIN\RequestLogAnalyzer\Services\RequestReplayService;
use NIN\RequestLogAnalyzer\Services\SensitiveDataMasker;
use NIN\RequestLogAnalyzer\Services\StepCollector;
use NIN\RequestLogAnalyzer\Support\TagManager;

class RequestLogAnalyzerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! function_exists('logAnalyzer')) {
            require_once __DIR__.'/helpers.php';
        }

        $this->mergeConfigFrom(
            __DIR__.'/../config/request-log-analyzer.php',
            'request-log-analyzer'
        );

        // ── Interface → Concrete bindings (DIP) ──────────────────────────────

        $this->app->singleton(TagManagerInterface::class, TagManager::class);
        $this->app->singleton(GeoIpResolverInterface::class, GeoIpResolver::class);

        $this->app->singleton(RequestRepositoryInterface::class, RequestRepository::class);
        $this->app->singleton(ErrorRepositoryInterface::class, ErrorRepository::class);
        $this->app->singleton(QueryRepositoryInterface::class, QueryRepository::class);
        $this->app->singleton(StepRepositoryInterface::class, StepRepository::class);
        $this->app->singleton(RateUsageRepositoryInterface::class, RateUsageRepository::class);

        $this->app->bind(ReportServiceInterface::class, ReportService::class);
        $this->app->bind(ClearServiceInterface::class, ClearService::class);

        // ── Package entry point ───────────────────────────────────────────────
        // The singleton receives the TagManager via its TagManagerInterface binding.
        $this->app->singleton('request-log-analyzer', function ($app) {
            return new RequestLogAnalyzer($app->make(TagManagerInterface::class));
        });

        $this->app->alias('request-log-analyzer', RequestLogAnalyzer::class);

        // ── Collector singletons ──────────────────────────────────────────────
        // Each collector is a stateful per-request buffer; singletons ensure
        // the same instance is shared across middleware and job dispatch.
        $this->app->singleton(QueryCollector::class, fn () => new QueryCollector);
        $this->app->singleton(SensitiveDataMasker::class);
        $this->app->singleton(ExceptionCollector::class, function ($app) {
            return new ExceptionCollector($app->make(SensitiveDataMasker::class));
        });
        $this->app->singleton(StepCollector::class, fn () => new StepCollector);

        // ── Alert system singletons ───────────────────────────────────────────
        // Alert repository and services for monitoring error/slow request thresholds.
        $this->app->singleton(AlertRepositoryInterface::class, AlertRepository::class);
        $this->app->singleton(AlertChecker::class);
        $this->app->singleton(AlertNotifier::class);

        // ── Rate usage system singletons ──────────────────────────────────────
        // Rate usage repository and limiter service for tracking and alerting on
        // per-user and per-IP request rates.
        $this->app->singleton(RateLimiterService::class);

        // ── Insights generator singleton ───────────────────────────────────────
        // Generates intelligent insights based on request data analysis.
        $this->app->singleton(InsightsGeneratorService::class);

        // ── Request replay singleton ───────────────────────────────────────────
        // Handles request storage, sensitive data masking, and replay execution.
        $this->app->singleton(RequestReplayService::class);

        // ── TrackRequest singleton ────────────────────────────────────────────
        // Laravel's kernel resolves the middleware twice: once inside the
        // pipeline (handle) and once in terminateMiddleware() (terminate).
        // Without a singleton, the second call gets a fresh instance whose
        // $snapshot / $shouldTrack / $hardIgnored are all empty, so terminate()
        // returns immediately without writing any data to the database.
        // Registering it as a singleton guarantees both phases share the same
        // instance and its in-request state.
        $this->app->singleton(TrackRequest::class);
    }

    public function boot(): void
    {
        if (! config('request-log-analyzer.enabled', true)) {
            return;
        }

        // ── Publishing configuration ─────────────────────────────────────
        $this->publishes([
            __DIR__.'/../config/request-log-analyzer.php' => config_path('request-log-analyzer.php'),
        ], 'request-log-analyzer-config');

        // ── Publishing database migrations ───────────────────────────────
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations/request-log-analyzer'),
        ], 'request-log-analyzer-migrations');

        // ── Publishing views ─────────────────────────────────────────────
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'request-log-analyzer');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/request-log-analyzer'),
        ], 'request-log-analyzer-views');

        // ── Publishing routes (optional) ─────────────────────────────────
        // Users can optionally customize the routes
        $this->publishes([
            __DIR__.'/../routes/web.php' => base_path('routes/vendor/request-log-analyzer-web.php'),
            __DIR__.'/../routes/api.php' => base_path('routes/vendor/request-log-analyzer-api.php'),
        ], 'request-log-analyzer-routes');

        // ── Publishing CSS/JS assets ────────────────────────────────────
        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/request-log-analyzer/css'),
            __DIR__.'/../resources/js' => public_path('vendor/request-log-analyzer/js'),
        ], 'request-log-analyzer-assets');

        // ── Publishing all assets at once ────────────────────────────────
        $this->publishes([
            __DIR__.'/../config/request-log-analyzer.php' => config_path('request-log-analyzer.php'),
            __DIR__.'/../database/migrations/' => database_path('migrations'),
            __DIR__.'/../resources/views' => resource_path('views/vendor/request-log-analyzer'),
            __DIR__.'/../resources/css' => public_path('vendor/request-log-analyzer/css'),
            __DIR__.'/../resources/js' => public_path('vendor/request-log-analyzer/js'),
        ], 'request-log-analyzer');

        // ── Load routes ──────────────────────────────────────────────────
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if (config('request-log-analyzer.api.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        if (config('request-log-analyzer.track_errors', true)) {
            $this->registerExceptionReporting();
        }

        if (config('request-log-analyzer.track_queries', true)) {
            $this->app->make(QueryCollector::class)->listen();
        }

        if (config('request-log-analyzer.track_steps', true)) {
            /** @var StepCollector $stepCollector */
            $stepCollector = $this->app->make(StepCollector::class);
            $stepCollector->listenToQueries();

            $this->app['events']->listen(
                RouteMatched::class,
                static function () use ($stepCollector): void {
                    $stepCollector->closeStep('routing');
                    $stepCollector->openStep('controller', 'controller');
                }
            );
        }

        // ── User login / logout tracking ──────────────────────────────────
        if (config('request-log-analyzer.track_login_history', true)) {
            $this->app->singleton(TrackUserLogin::class);
            $listener = $this->app->make(TrackUserLogin::class);
            $this->app['events']->listen(Login::class, [$listener, 'handleLogin']);
            $this->app['events']->listen(Logout::class, [$listener, 'handleLogout']);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ClearCommand::class,
                ReportCommand::class,
                CleanupCommand::class,
                GenerateApiTokenCommand::class,
                TestAlertCommand::class,
            ]);
        }

        $this->registerCleanupSchedule();
    }

    /**
     * Schedule the automatic cleanup command when enabled in config.
     *
     * Uses callAfterResolving so the Schedule instance is available both in
     * Laravel 10 (where it is resolved during Console\Kernel::schedule) and
     * in Laravel 11+ (where it is resolved via the scheduler service provider).
     * This is the canonical pattern for package-defined schedules.
     */
    private function registerCleanupSchedule(): void
    {
        if (! config('request-log-analyzer.cleanup.enabled', false)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $cron = config('request-log-analyzer.cleanup.schedule', '0 2 * * *');

            $schedule->command('analyzer:cleanup')
                ->cron($cron)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/analyzer-cleanup.log'));
        });
    }

    /**
     * Register a reporting callback with the application's exception handler.
     *  - Laravel 10: ExceptionHandler::reportable() is available on the
     *    concrete Handler class that is always bound in the container.
     *  - Laravel 11+: The same reportable() method is still available via
     *    the container, even when exceptions are configured via withExceptions()
     *    in bootstrap/app.php.
     *
     * The callback returns void (no return value) so that Laravel's default
     * reporting chain (log drivers, Sentry, Bugsnag, etc.) continues normally.
     */
    private function registerExceptionReporting(): void
    {
        try {
            /** @var ExceptionHandler $handler */
            $handler = $this->app->make(ExceptionHandler::class);

            if (method_exists($handler, 'reportable')) {
                $handler->reportable(function (\Throwable $e): void {
                    try {
                        $this->app->make(ExceptionCollector::class)->collect($e);
                    } catch (\Throwable) {
                        // Never let the collector crash the application.
                    }
                });
            }
        } catch (\Throwable) {
            // Silently skip if the exception handler is unavailable
            // (e.g. during unit tests with a minimal container).
        }
    }
}
