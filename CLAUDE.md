# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

`nintis/request-log-analyzer` is a Laravel package (not a standalone app) that captures HTTP requests, database queries, exceptions, and user activity into its own database tables and exposes an interactive dashboard at `/request-log-analyzer`. It supports Laravel 10–13 and PHP 8.1+.

## Development Commands

This is a Composer package — there is no `composer install` or test suite in the package root itself. Development happens inside a host Laravel app that requires this package.

**Install into a host app:**
```bash
composer require nintis/request-log-analyzer
php artisan analyzer:install          # publish config + migrations, run migrate
```

**Artisan commands provided by the package:**
```bash
php artisan analyzer:install          # first-time setup
php artisan analyzer:install --force  # overwrite published files
php artisan analyzer:install --no-migrate  # skip running migrations
php artisan analyzer:cleanup          # delete records older than retention_days
php artisan analyzer:clear --older-than=7 --force  # manual data clear
php artisan analyzer:token            # generate a Bearer token for the JSON API
php artisan analyzer:report           # generate a text report
php artisan analyzer:test-alert       # fire a test alert notification
```

**Publish assets individually:**
```bash
php artisan vendor:publish --tag=request-log-analyzer-config
php artisan vendor:publish --tag=request-log-analyzer-migrations
php artisan vendor:publish --tag=request-log-analyzer-views
php artisan vendor:publish --tag=request-log-analyzer-assets
```

**Seed test data (for development):**
```bash
php artisan db:seed --class="NIN\\RequestLogAnalyzer\\Database\\Seeders\\RequestLogAnalyzerTestDataSeeder"
```

## Architecture Overview

### Request lifecycle

1. `TrackRequest` middleware (`src/Http/Middleware/TrackRequest.php`) is a **singleton** (critical — registered as singleton so `handle()` and `terminate()` share state). It runs in two phases:
   - `handle()`: captures a lightweight snapshot of the incoming request; optionally starts step tracking.
   - `terminate()` (post-response): applies sampling logic, sampling overrides (always capture errors/slow), then either dispatches `PersistRequestLog` to a queue (async mode) or writes synchronously via repository calls.

2. **Collectors** (`Services/QueryCollector`, `Services/ExceptionCollector`, `Services/StepCollector`) are also singletons that act as per-request in-memory buffers. `drain()` atomically returns and resets each buffer. The ServiceProvider registers DB listeners and exception hooks once at boot.

3. **Repositories** (`Repositories/`) abstract all DB writes behind interfaces (`Contracts/`). `bulkInsert()` is the main write path — never direct `DB::table()` calls in collectors or middleware.

4. **Async path**: when `async_logging = true`, `TrackRequest::terminate()` calls `drain()` on all collectors and dispatches `PersistRequestLog` (a queued job). The GeoIP lookup moves entirely to the queue worker — zero impact on web response time.

5. **Sync path**: GeoIP lookup and all DB inserts happen in `terminate()`, which runs after the response is sent, so user-perceived latency is unaffected.

### Key design decisions

- `TrackRequest` must be a **singleton** in the container (registered in `ServiceProvider::register()`). Laravel resolves middleware twice — once for `handle()` and once for `terminate()`. Without a singleton, `terminate()` gets a fresh instance with empty state and writes nothing.
- Collectors use `drain()` (not `reset()`) in write paths to ensure atomicity — drain returns the buffer and clears it in one call.
- All repository implementations are bound behind interfaces so they are swappable and testable without a real DB.
- The `TrackRequest` middleware self-skips for static assets (extension check before any snapshot), ignored path patterns (`fnmatch`), and named route patterns. The `ignore_routes` check runs in `terminate()` because the route may not be resolved yet in `handle()` when the middleware is global.
- Sampling overrides (`always_capture_errors`, `always_capture_slow_ms`) bypass the sample rate gate but still use the snapshot captured for every non-ignored request.

### Directory structure

```
src/
  RequestLogAnalyzer.php             # Public entry point / tagging API
  RequestLogAnalyzerServiceProvider.php  # Registers + boots everything
  helpers.php                        # logAnalyzer() global helper
  Contracts/                         # All interfaces (DIP)
  Http/
    Controllers/RequestLogController.php   # Dashboard pages
    Controllers/Api/AnalyzerApiController.php  # JSON API
    Controllers/RequestReplayController.php
    Middleware/TrackRequest.php       # Core capture middleware
    Middleware/AnalyzerApiAuth.php    # Bearer token auth for JSON API
  Services/
    QueryCollector.php               # DB event buffer
    ExceptionCollector.php           # Exception buffer (with masking)
    StepCollector.php                # Lifecycle step buffer
    SensitiveDataMasker.php          # Masks fields/headers/query params/patterns
    GeoIpResolver.php                # ip-api.com lookup
    AlertChecker.php / AlertNotifier.php  # Threshold alerting
    RateLimiterService.php
    InsightsGeneratorService.php
    RequestReplayService.php
  Repositories/                      # DB write implementations
  Jobs/PersistRequestLog.php         # Async queue job
  Console/                           # Artisan commands
  Models/                            # Eloquent models for all tables
  Listeners/TrackUserLogin.php       # Login/logout event listener
config/request-log-analyzer.php     # All config with env() defaults
database/migrations/                 # 11 migration files
routes/web.php                       # Dashboard + replay routes
routes/api.php                       # JSON API routes (/api/analyzer/*)
resources/views/                     # Blade dashboard pages
resources/js / css/                  # Frontend assets
```

### Database tables (all prefixed `rla_` by convention)

- `rla_requests` — one row per tracked HTTP request
- `rla_request_steps` — per-step timing (routing, controller, query)
- `rla_errors` — exceptions linked to requests
- `rla_queries` — SQL queries linked to requests
- `rla_user_login_histories` — login/logout events
- `rla_api_rate_usages` / `rla_rate_limit_incidents` — rate tracking
- `rla_request_replays` / `rla_replay_executions` — replay feature

### Public API (application code)

Tag the current request from anywhere in app code:
```php
logAnalyzer()->tag('payment');
logAnalyzer()->tag(['payment', 'checkout']);
\RequestLogAnalyzer::tag('admin');
```

Tags are flushed once by `TrackRequest::terminate()` just before the INSERT.

### Dashboard routes

All under `/{route_prefix}` (default `request-log-analyzer`). Protected by `middleware` config (default `['web']` — add `'auth'` for production). JSON API routes under `/api/analyzer/*`, protected by Bearer token (`REQUEST_LOG_ANALYZER_API_TOKEN`).

## Adding New Features

- Add new tracked data → add a Collector (singleton, `drain()`/`reset()` pattern), a Repository interface + implementation, and wire up in the ServiceProvider.
- Add a dashboard page → add a Blade view, a controller method in `RequestLogController`, and a route in `routes/web.php`.
- Add a new artisan command → create in `src/Console/`, register in `ServiceProvider::boot()` inside the `runningInConsole()` guard.
- New config option → add to `config/request-log-analyzer.php` with an `env()` default; document the env key.
