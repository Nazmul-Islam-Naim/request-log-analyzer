# Request Log Analyzer - Installation Guide

## Overview

The **Request Log Analyzer** package is a comprehensive Laravel package for logging, analyzing, and monitoring HTTP requests. It includes alerting, rate limiting, intelligent insights, and request replay features.

## Quick Installation

### 1. Install via Composer

```bash
composer require nin/request-log-analyzer
```

### 2. Run the Installation Command

```bash
php artisan analyzer:install
```

This command will:
- Publish the package configuration to `config/request-log-analyzer.php`
- Run all package migrations to create the required database tables
- Verify package auto-discovery

### 3. Add Middleware

Add the `TrackRequest` middleware to your application's middleware stack in `bootstrap/app.php`:

```php
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;

->withMiddleware(function (Middleware $middleware) {
    $middleware->append(TrackRequest::class);
})
```

## Auto-Discovery

The package uses **Laravel auto-discovery**, so the service provider is automatically registered. You can verify this by running:

```bash
php artisan package:discover
```

You should see:
```
nin/request-log-analyzer .............................................. DONE
```

## Publishing Assets

The package supports granular asset publishing via `vendor:publish`:

### Publish All Assets (Recommended)
```bash
php artisan vendor:publish --tag=request-log-analyzer
```

This publishes:
- ✅ Configuration file
- ✅ Database migrations
- ✅ Blade views
- ✅ CSS/JavaScript assets

### Publish Specific Assets

**Config only:**
```bash
php artisan vendor:publish --tag=request-log-analyzer-config
```

**Migrations only:**
```bash
php artisan vendor:publish --tag=request-log-analyzer-migrations
```

**Views only:**
```bash
php artisan vendor:publish --tag=request-log-analyzer-views
```

**CSS/JS assets only:**
```bash
php artisan vendor:publish --tag=request-log-analyzer-assets
```

**Routes only (optional - for customization):**
```bash
php artisan vendor:publish --tag=request-log-analyzer-routes
```

## Database Setup

Run migrations to create the required tables:

```bash
php artisan migrate
```

This creates the following tables:
- `rla_requests` - HTTP request logs
- `rla_errors` - Exception/error logs
- `rla_queries` - Database query logs
- `rla_logins` - User login history
- `rla_api_rate_usage` - API rate usage tracking
- `rla_rate_limit_incidents` - Rate limit violation logs
- `rla_request_replays` - Stored requests for replay
- `rla_replay_executions` - Replay execution history
- Plus supporting tables for alerts, steps, and tags

## Configuration

Edit `config/request-log-analyzer.php` to customize:

### Core Settings
```php
'enabled' => true,  // Enable/disable package
'track_errors' => true,  // Track exceptions
'track_queries' => true,  // Track database queries
'track_steps' => true,  // Track request steps
'track_login_history' => true,  // Track user logins
```

### Alert Configuration
```php
'alerts' => [
    'enabled' => true,
    'channels' => ['log', 'email', 'discord'],  // Alert channels
    'error_alerts' => [
        'enabled' => true,
        'threshold' => 10,  // Alert after 10 errors
        'window' => 5,      // Within 5 minutes
        'cooldown' => 10,   // Wait 10 minutes before next alert
    ],
    'slow_request_alerts' => [
        'enabled' => true,
        'threshold' => 500,  // Alert for requests > 500ms
        'cooldown' => 5,     // Minutes between alerts
    ],
],
```

### Rate Limiting Configuration
```php
'rate_limits' => [
    'enabled' => true,
    'user_requests_per_minute' => 100,
    'ip_requests_per_minute' => 500,
    'retention_days' => 30,
],
```

## Accessing the Dashboard

Once installed and middleware is configured, access the dashboard at:

```
http://your-app.test/request-log-analyzer
```

### Available Routes

- **Dashboard**: `/request-log-analyzer`
- **Request Logs**: `/request-log-analyzer/requests`
- **Slow Requests**: `/request-log-analyzer/slow-requests`
- **API Insights**: `/request-log-analyzer/api-insights`
- **Request Replay**: `/request-log-analyzer/replay`
- **Analytics**: `/request-log-analyzer/analytics`

## Features

### ✅ Request Logging
- Log all HTTP requests with method, URI, status code, duration
- Track query counts and slow queries
- Monitor exception/error rates

### ✅ Alert System
- Automatic alerts for error spikes (configurable threshold)
- Slow request detection and alerting
- Multi-channel delivery: Log files, Email, Discord
- Configurable cooldown to prevent alert fatigue

### ✅ Rate Limiting
- Track requests per user and per IP
- Detect rate limit violations
- Dashboard overview with incident tracking

### ✅ Intelligent Insights
- Route performance analysis
- Error trend detection (24h comparison)
- Usage pattern anomalies
- Slow request clustering

### ✅ Request Replay
- Store request data for later analysis
- Automatic sensitive data masking (passwords, tokens, API keys)
- Safe header extraction (remove Authorization, Cookies)
- Manual replay execution with result tracking
- Only safe methods allowed (GET/POST)

### ✅ Analytics & Reports
- User activity reports
- Geographic analytics (IP to location)
- Route hit tracking
- Login history

## Testing Installation

To verify the package is working:

1. **Check auto-discovery:**
   ```bash
   php artisan package:discover
   ```

2. **Generate test data:**
   ```bash
   php artisan tinker
   >> $service = app(\NIN\RequestLogAnalyzer\Services\RateLimiterService::class)
   >> $service->checkUserRateLimit(1, 50)
   ```

3. **Run the install command:**
   ```bash
   php artisan analyzer:install
   ```

4. **Access the dashboard:**
   Navigate to `http://your-app.test/request-log-analyzer`

## Troubleshooting

### Package Not Discovered
```bash
php artisan package:discover
php artisan config:clear
php artisan cache:clear
```

### Migrations Not Running
```bash
# Check if migrations are publishable
php artisan vendor:publish --tag=request-log-analyzer-migrations

# Run migrations
php artisan migrate
```

### Dashboard Not Accessible
- Verify middleware is registered in `bootstrap/app.php`
- Check that `request-log-analyzer.enabled` is `true` in config
- Clear route cache: `php artisan route:clear`

### Assets Not Loading
```bash
php artisan vendor:publish --tag=request-log-analyzer-assets
```

## Configuration Reference

### Complete config/request-log-analyzer.php

```php
return [
    'enabled' => env('LOG_ANALYZER_ENABLED', true),
    
    // Tracking
    'track_errors' => true,
    'track_queries' => true,
    'track_steps' => true,
    'track_login_history' => true,
    
    // Database
    'database' => env('LOG_ANALYZER_DB', 'default'),
    
    // Route configuration
    'route_prefix' => env('LOG_ANALYZER_PREFIX', 'request-log-analyzer'),
    'route_middleware' => ['web', 'auth'],
    
    // Alerts
    'alerts' => [
        'enabled' => true,
        'channels' => ['log', 'email', 'discord'],
        'error_alerts' => [...],
        'slow_request_alerts' => [...],
        'email_config' => [...],
        'discord_config' => [...],
    ],
    
    // Rate limiting
    'rate_limits' => [
        'enabled' => true,
        'user_requests_per_minute' => 100,
        'ip_requests_per_minute' => 500,
        'retention_days' => 30,
    ],
    
    // Data retention
    'retention' => [
        'requests_days' => 90,
        'errors_days' => 30,
        'queries_days' => 7,
    ],
    
    // Cleanup
    'cleanup' => [
        'enabled' => false,
        'schedule' => '0 2 * * *',  // 2 AM daily
    ],
];
```

## Support

For issues or feature requests, please refer to the package documentation or GitHub repository.

---

**Version**: 1.0.0  
**Compatibility**: Laravel 10.x, 11.x, 12.x, 13.x  
**PHP**: 8.1+  
