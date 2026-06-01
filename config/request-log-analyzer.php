<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Analyzer
    |--------------------------------------------------------------------------
    | Master switch. When false, no listening, no DB writes, no overhead.
    */
    'enabled' => env('REQUEST_LOG_ANALYZER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Async Logging (Queue)
    |--------------------------------------------------------------------------
    | When true, all DB writes and the GeoIP lookup are dispatched to a
    | background queue worker via PersistRequestLog. The PHP-FPM worker is
    | freed immediately after terminate() returns — no blocking post-response.
    |
    | Requirements:
    |   - A configured queue driver (database, Redis, SQS, …)
    |   - `php artisan queue:work` running as a background process
    |
    | queue_connection  — null falls back to the app default (QUEUE_CONNECTION).
    | queue_name        — override the target queue (useful for prioritisation).
    |
    | Env: REQUEST_LOG_ANALYZER_ASYNC
    */
    'async_logging'    => env('REQUEST_LOG_ANALYZER_ASYNC', false),
    'queue_connection' => env('REQUEST_LOG_ANALYZER_QUEUE_CONNECTION', null),
    'queue_name'       => env('REQUEST_LOG_ANALYZER_QUEUE_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Ignore Static Assets
    |--------------------------------------------------------------------------
    | When true, requests whose URI ends with a known static file extension
    | (CSS, JS, images, fonts, archives, …) are skipped before any snapshot
    | is taken. This is the cheapest possible skip — no DB writes, no GeoIP,
    | no memory buffering — and is strongly recommended for production apps
    | that serve their own assets.
    |
    | Env: REQUEST_LOG_ANALYZER_IGNORE_STATIC
    */
    'ignore_static_assets' => env('REQUEST_LOG_ANALYZER_IGNORE_STATIC', true),

    /*
    |--------------------------------------------------------------------------
    | Sampling Overrides
    |--------------------------------------------------------------------------
    | These override the sample_rate gate for high-value events so they are
    | never missed, even on heavily sampled production traffic.
    |
    | always_capture_errors  — always log requests that return a 5xx response
    |                          (default true — server errors are always worth
    |                          capturing).
    |
    | always_capture_slow_ms — always log requests whose total response time
    |                          exceeds this value in milliseconds.
    |                          0 = disabled (do not override sampling for slow
    |                          requests).
    |
    | Env: REQUEST_LOG_ANALYZER_CAPTURE_ERRORS
    |      REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS
    */
    'always_capture_errors'  => env('REQUEST_LOG_ANALYZER_CAPTURE_ERRORS', true),
    'always_capture_slow_ms' => (int) env('REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS', 0),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    | Percentage (0–100) of incoming requests to actually record.
    | 100 = record every request (default, development safe).
    | 10  = record 1 in 10 requests (useful on high-traffic production).
    | 0   = record nothing (equivalent to disabling the analyzer).
    |
    | The decision is made per-request using mt_rand(), so the long-run
    | ratio converges to this value without any external coordination.
    |
    | Env: REQUEST_LOG_ANALYZER_SAMPLE_RATE
    */
    'sample_rate' => (int) env('REQUEST_LOG_ANALYZER_SAMPLE_RATE', 100),

    /*
    |--------------------------------------------------------------------------
    | Slow Request Threshold
    |--------------------------------------------------------------------------
    | Requests whose response time exceeds this value (in milliseconds) are
    | considered "slow". They are flagged with a badge in the dashboard and
    | the request list, and appear on the dedicated Slow Requests page.
    |
    | Set to 0 to disable slow-request detection entirely.
    |
    | Env: REQUEST_LOG_ANALYZER_SLOW_THRESHOLD_MS
    */
    'slow_request_threshold_ms' => (int) env('REQUEST_LOG_ANALYZER_SLOW_THRESHOLD_MS', 500),

    /*
    |--------------------------------------------------------------------------
    | Track Queries
    |--------------------------------------------------------------------------
    | When true, every DB query executed during a tracked request is captured
    | via DB::listen and stored in the queries table.
    | Disable in high-throughput environments where query volume is too large.
    */
    'track_queries' => env('REQUEST_LOG_ANALYZER_TRACK_QUERIES', true),

    /*
    |--------------------------------------------------------------------------
    | Track Exceptions
    |--------------------------------------------------------------------------
    | When true, all exceptions reported via ->withExceptions() are captured
    | and stored in the errors table, linked to the current request.
    */
    'track_errors' => env('REQUEST_LOG_ANALYZER_TRACK_ERRORS', true),

    /*
    |--------------------------------------------------------------------------
    | Track Lifecycle Steps
    |--------------------------------------------------------------------------
    | When true, request lifecycle steps (routing, controller, DB queries) are
    | recorded with millisecond-precision timestamps in the request_steps table.
    | These power the per-request timeline visualisation on the dashboard.
    | Disable in extremely high-throughput environments to save overhead.
    */
    'track_steps' => env('REQUEST_LOG_ANALYZER_TRACK_STEPS', true),

    /*
    |--------------------------------------------------------------------------
    | Track Login History
    |--------------------------------------------------------------------------
    | When true, user login and logout events are recorded in the
    | rla_user_login_histories table (login time, logout time, IP, user agent).
    | Requires the migration to have been run.
    |
    | Env: REQUEST_LOG_ANALYZER_TRACK_LOGIN_HISTORY
    */
    'track_login_history' => env('REQUEST_LOG_ANALYZER_TRACK_LOGIN_HISTORY', true),

    /*
    |--------------------------------------------------------------------------
    | Active Users Window
    |--------------------------------------------------------------------------
    | The number of minutes a user is considered "active" after their last
    | request. Used on the Active Users dashboard page.
    |
    | Env: REQUEST_LOG_ANALYZER_ACTIVE_WINDOW_MINUTES
    */
    'active_users_window_minutes' => (int) env('REQUEST_LOG_ANALYZER_ACTIVE_WINDOW_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | GeoIP Tracking
    |--------------------------------------------------------------------------
    | When true, the country and city are resolved from the client IP using
    | the ip-api.com free JSON API (no key required; 1 000 req/min limit).
    | Private/reserved IP ranges (127.x, 10.x, 192.168.x, etc.) are skipped
    | automatically and stored as NULL.
    |
    | geo_api_url          - Endpoint with {ip} placeholder. Swap for a paid
    |                        service or self-hosted MaxMind instance if needed.
    | geo_timeout_seconds  - Max wait time per lookup; 0 = disabled.
    |
    | Env: REQUEST_LOG_ANALYZER_TRACK_GEO
    */
    'track_geo'           => env('REQUEST_LOG_ANALYZER_TRACK_GEO', true),
    'geo_api_url'         => env('REQUEST_LOG_ANALYZER_GEO_API_URL', 'http://ip-api.com/json/{ip}'),
    'geo_timeout_seconds' => (int) env('REQUEST_LOG_ANALYZER_GEO_TIMEOUT', 2),

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Masking
    |--------------------------------------------------------------------------
    | Controls how sensitive data is masked before any value is written to
    | the database.  Masking is applied to:
    |   - Request body / context field values  (masking.fields)
    |   - Request and response header values   (masking.headers)
    |   - Query-string parameter values        (masking.query_params)
    |   - Raw strings (exception messages, stack traces) (masking.patterns)
    |
    | enabled      — master switch; false disables ALL masking.
    | mask_value   — the replacement string (default "[REDACTED]").
    | fields       — body / context keys whose values are masked.
    |                Merged with the legacy `hidden_fields` list.
    | headers      — header names whose values are masked (key is kept).
    |                Takes precedence over `hidden_headers` (legacy removal).
    | query_params — query-string parameter names whose values are masked.
    | patterns     — regex patterns applied to raw strings.
    |                Merged with the legacy `scrub_patterns` list.
    |
    | Env: REQUEST_LOG_ANALYZER_MASKING_ENABLED
    |      REQUEST_LOG_ANALYZER_MASK_VALUE
    */
    'masking' => [
        'enabled'    => env('REQUEST_LOG_ANALYZER_MASKING_ENABLED', true),
        'mask_value' => env('REQUEST_LOG_ANALYZER_MASK_VALUE', '[REDACTED]'),

        'fields' => [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
            '_token',
            'access_token',
            'refresh_token',
            'private_key',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
        ],

        'headers' => [
            'authorization',
            'x-api-key',
            'x-auth-token',
            'x-access-token',
            'cookie',
            'php-auth-pw',
        ],

        'query_params' => [
            'token',
            'api_key',
            'apikey',
            'password',
            'secret',
            'access_token',
            'auth',
            'key',
        ],

        'patterns' => [
            '/Bearer\s+[\w\-\.]+/i',
            '/password["\']?\s*[:=]\s*["\']?[^\s,&"\'>]+/i',
            '/token["\']?\s*[:=]\s*["\']?[\w\-\.]{10,}/i',
            '/api[_\-]?key["\']?\s*[:=]\s*["\']?[\w\-]{10,}/i',
            '/secret["\']?\s*[:=]\s*["\']?[^\s,&"\'>]+/i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scrub Patterns (sensitive data removal)
    |--------------------------------------------------------------------------
    | Regex patterns matched (case-insensitively) against exception message,
    | stack-trace lines, and context values. Matching text is replaced with
    | "[REDACTED]" before the error is stored.
    |
    | Add any app-specific secrets, tokens, or PII patterns here.
    | These are merged into masking.patterns automatically.
    */
    'scrub_patterns' => [
        '/Bearer\s+[\w\-\.]+/i',           // Bearer tokens
        '/password["\']?\s*[:=]\s*["\']?[^\s,&"\'>]+/i', // password=...
        '/token["\']?\s*[:=]\s*["\']?[\w\-\.]{10,}/i',   // token=...
        '/api[_\-]?key["\']?\s*[:=]\s*["\']?[\w\-]{10,}/i', // api_key=...
        '/secret["\']?\s*[:=]\s*["\']?[^\s,&"\'>]+/i',    // secret=...
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    | The URI prefix for the request log analyzer dashboard.
    | Access it at: /request-log-analyzer
    */
    'route_prefix' => env('REQUEST_LOG_ANALYZER_PREFIX', 'request-log-analyzer'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    | Middleware applied to the dashboard routes. Add 'auth' or a custom guard
    | to restrict access in production.
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | API Access
    |--------------------------------------------------------------------------
    | Enables the read-only JSON API for external dashboard integration.
    |
    | enabled  — set false to disable all API routes entirely.
    | prefix   — URI prefix for all API endpoints.
    |            Default: api/analyzer  → /api/analyzer/requests, …
    | token    — Bearer token required on every API request.
    |            Generate one with:  php artisan analyzer:token
    |            Set REQUEST_LOG_ANALYZER_API_TOKEN in your .env file.
    |            Leave empty to keep the API unreachable (503 response).
    |
    | Env: REQUEST_LOG_ANALYZER_API_ENABLED
    |      REQUEST_LOG_ANALYZER_API_PREFIX
    |      REQUEST_LOG_ANALYZER_API_TOKEN
    */
    'api' => [
        'enabled' => env('REQUEST_LOG_ANALYZER_API_ENABLED', true),
        'prefix'  => env('REQUEST_LOG_ANALYZER_API_PREFIX', 'api/analyzer'),
        'token'   => env('REQUEST_LOG_ANALYZER_API_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Track Request / Response Headers
    |--------------------------------------------------------------------------
    | When true, headers are stored as JSON in the database.
    | Sensitive header names listed in `hidden_headers` are always stripped
    | before storing (case-insensitive).
    |
    | Env: REQUEST_LOG_ANALYZER_TRACK_REQ_HEADERS
    |      REQUEST_LOG_ANALYZER_TRACK_RES_HEADERS
    */
    'track_request_headers'  => env('REQUEST_LOG_ANALYZER_TRACK_REQ_HEADERS', false),
    'track_response_headers' => env('REQUEST_LOG_ANALYZER_TRACK_RES_HEADERS', false),

    'hidden_headers' => [
        'authorization',
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
        'proxy-authorization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Fields
    |--------------------------------------------------------------------------
    | These request payload keys will be stripped before storing logs.
    */
    'hidden_fields' => [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        '_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Paths
    |--------------------------------------------------------------------------
    | URI patterns that should not be logged (supports wildcards via fnmatch).
    */
    'ignored_paths' => [
        'request-log-analyzer*',
        '_debugbar*',
        'telescope*',
        'horizon*',
        'livewire*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Routes
    |--------------------------------------------------------------------------
    | Named route patterns to exclude from logging. Supports the same wildcard
    | syntax accepted by $request->routeIs() — i.e. fnmatch-style patterns.
    |
    | Examples:
    |   'api.healthcheck'        — exact named route
    |   'api.internal.*'        — all routes whose name starts with api.internal.
    |   'horizon.*'             — all Horizon dashboard routes
    |
    | Note: checked in terminate() so it works for both global middleware and
    | route-level middleware.
    */
    'ignore_routes' => [
        // 'api.healthcheck',
        // 'debugbar.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slow Query Threshold
    |--------------------------------------------------------------------------
    | Queries taking longer than this value (in milliseconds) are flagged as
    | slow in the queries table.
    */
    'slow_query_threshold_ms' => env('REQUEST_LOG_ANALYZER_SLOW_QUERY_MS', 200),

    /*
    |--------------------------------------------------------------------------
    | Max Records
    |--------------------------------------------------------------------------
    | Maximum number of records to keep. Older records are pruned automatically.
    | Set to null to keep all records.
    */
    'max_records' => env('REQUEST_LOG_ANALYZER_MAX_RECORDS', 10000),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    | The database connection to use for request logs. Defaults to the app's
    | default connection.
    */
    'db_connection' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Data Cleanup
    |--------------------------------------------------------------------------
    | Controls the scheduled `analyzer:cleanup` command that automatically
    | removes old analyzer data to keep the database size manageable.
    |
    | enabled         — Register the automatic schedule. When false the command
    |                   can still be run manually but will not be scheduled.
    |
    | retention_days  — Number of days to retain records. Rows whose
    |                   created_at is older than this value are deleted.
    |                   Must be a positive integer (minimum 1).
    |
    | schedule        — Cron expression that controls when the cleanup job
    |                   runs automatically (only used when enabled = true).
    |                   Standard cron syntax: minute hour day month weekday.
    |                   Default: daily at 02:00 AM server time.
    |
    | Env: REQUEST_LOG_ANALYZER_CLEANUP_ENABLED
    |      REQUEST_LOG_ANALYZER_RETENTION_DAYS
    |      REQUEST_LOG_ANALYZER_CLEANUP_SCHEDULE
    */
    'cleanup' => [
        'enabled'        => env('REQUEST_LOG_ANALYZER_CLEANUP_ENABLED', false),
        'retention_days' => (int) env('REQUEST_LOG_ANALYZER_RETENTION_DAYS', 90),
        'schedule'       => env('REQUEST_LOG_ANALYZER_CLEANUP_SCHEDULE', '0 2 * * *'),
    ],

];
