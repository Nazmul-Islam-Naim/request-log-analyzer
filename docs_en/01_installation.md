# Installation Guide - Request Log Analyzer

## 📋 Quick Installation (60 seconds)

```bash
# 1. Install package
composer require nintis/request-log-analyzer

# 2. Run install command
php artisan analyzer:install

# 3. Access dashboard
# Go to: http://your-app.test/request-log-analyzer
```

**Done!** Data collection starts immediately.

---

## Prerequisites

| Requirement | Version | Check |
|-------------|---------|-------|
| PHP | 8.1+ | `php -v` |
| Laravel | 10, 11, 12, 13 | `php artisan --version` |
| Database | MySQL, PostgreSQL, SQLite | configured in `.env` |

---

## Installation Methods

### Method 1: Auto-Install (Recommended)

```bash
php artisan analyzer:install
```

**What it does**:
- ✅ Publishes config file
- ✅ Runs all migrations
- ✅ Sets up tables
- ✅ Ready to use

**With options**:
```bash
php artisan analyzer:install --force        # Overwrite existing config
php artisan analyzer:install --no-migrate   # Skip migrations
```

### Method 2: Composer + Manual

```bash
# Install
composer require nintis/request-log-analyzer

# Publish config
php artisan vendor:publish --tag=request-log-analyzer-config

# Run migrations
php artisan migrate
```

### Method 3: Development (Local path)

If package is in `packages/NIN/RequestLogAnalyzer/`:

```bash
# In composer.json, add:
"repositories": [
    {
        "type": "path",
        "url": "./packages/NIN/RequestLogAnalyzer"
    }
]

# Then:
composer require nintis/request-log-analyzer
php artisan analyzer:install
```

---

## Step 2: Register Middleware

Middleware is what captures requests. Without it, nothing is logged.

### For Laravel 11+ (bootstrap/app.php)

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(TrackRequest::class);  // Add this line
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
```

### For Laravel 10 (app/Http/Kernel.php)

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        // ... other middleware
        TrackRequest::class,  // Add here
    ];
    
    // ...
}
```

### For Specific Routes Only

If you want to track only certain routes:

```php
Route::middleware([TrackRequest::class])->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('/api/users', [UserController::class, 'store']);
    // Only these routes are tracked
});
```

---

## Step 3: Verify Installation

### Check 1: Configuration File

```bash
# Should exist now:
cat config/request-log-analyzer.php
# Shows all settings with defaults
```

### Check 2: Database Tables

```bash
# Run migrations
php artisan migrate

# Check tables were created:
php artisan db:show

# Should see: rla_requests, rla_queries, rla_errors, etc.
```

### Check 3: Dashboard Access

```bash
# Start Laravel server
php artisan serve

# Visit in browser:
# http://localhost:8000/request-log-analyzer
# Should see dashboard (empty initially, that's OK)
```

### Check 4: Trigger Data Collection

```bash
# Make a request to your app
curl http://localhost:8000/api/users

# Back in dashboard, refresh
# Should see the request you just made
```

---

## Configuration (Optional)

### Basic Configuration

Edit `.env` file:

```env
# Master switch
REQUEST_LOG_ANALYZER_ENABLED=true

# Which features to track
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true
REQUEST_LOG_ANALYZER_TRACK_GEO=true

# Performance settings
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100        # 100% in dev, 10-20% in prod
REQUEST_LOG_ANALYZER_SLOW_MS=500            # What counts as slow?

# Async logging (recommended for production)
REQUEST_LOG_ANALYZER_ASYNC=false            # true for async
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
```

### Full Reference

See `02_configuration.md` for all 50+ options.

---

## Common Installation Issues

### Issue: "Module not found" / "Class not found"

**Solution**:
```bash
composer dump-autoload
php artisan cache:clear
```

### Issue: Migration fails

**Solution**:
```bash
# Check if migrations exist
ls database/migrations/ | grep rla

# Force re-publish migrations
php artisan vendor:publish --tag=request-log-analyzer --force
php artisan migrate
```

### Issue: Dashboard shows 404

**Solutions**:
1. Check middleware is registered
2. Check route prefix in config
3. Clear cache: `php artisan cache:clear`

### Issue: No data appearing

**Checklist**:
- [ ] Middleware registered?
- [ ] Made a request? (check browser console)
- [ ] Enable feature? (`TRACK_QUERIES=true`)
- [ ] Sample rate not 0? (`SAMPLE_RATE=100`)
- [ ] Check `.env` overrides

---

## Environment Setup

### Development Machine

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_ASYNC=false
REQUEST_LOG_ANALYZER_MASKING_ENABLED=false    # See all data in dev
```

### Staging Server

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=50
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
```

### Production Server

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true     # Never miss errors
REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS=500     # Always capture slow
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true    # Protect data
```

---

## Queue Setup (For Async Logging)

If using `REQUEST_LOG_ANALYZER_ASYNC=true`, start queue worker:

### Redis Queue

```bash
# Terminal 1: Queue worker
php artisan queue:work redis

# Terminal 2: Your app
php artisan serve
```

### Database Queue

```bash
# Terminal 1: Queue worker
php artisan queue:work database

# Terminal 2: Your app
php artisan serve
```

### See Queue Status

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry
```

---

## Access Control

### Allow Everyone (Default)

```php
// In config/request-log-analyzer.php
'middleware' => ['web'],
```

### Require Authentication

```php
'middleware' => ['web', 'auth'],
```

### Admin Only

```php
'middleware' => ['web', 'auth', 'verified'],  // Must be verified
// OR
'middleware' => ['web', 'can:viewAnalytics'],  // Custom gate
```

### Setup Custom Authorization

```php
// In app/Providers/AuthServiceProvider.php
Gate::define('viewAnalytics', function ($user) {
    return $user->isAdmin();
});

// Then in config:
'middleware' => ['web', 'auth', 'can:viewAnalytics'],
```

---

## Verify Everything Works

Run this quick test:

```php
<?php
// Make a test file or use Tinker
// php artisan tinker

// Test 1: Can we access the package?
use NIN\RequestLogAnalyzer\RequestLogAnalyzer;
echo "✓ Package loaded";

// Test 2: Can we access the config?
$config = config('request-log-analyzer');
echo "✓ Config loaded: " . $config['enabled'];

// Test 3: Can we access the database?
use NIN\RequestLogAnalyzer\Models\RequestLog;
echo "✓ Table exists: " . RequestLog::count() . " records";

// Done!
echo "✓ Installation verified!";
```

---

## Next Steps

1. **Configure settings**: [02_configuration.md](02_configuration.md)
2. **Understand features**: Pick a feature guide (03-10)
3. **Use dashboard**: [11_dashboard.md](11_dashboard.md)
4. **Troubleshoot**: [13_troubleshooting.md](13_troubleshooting.md)

---

**Total Setup Time**: ~5 minutes  
**Difficulty**: Easy  
**Status**: ✅ Ready to use

