# Troubleshooting Guide - Request Log Analyzer

Solutions to common problems and issues.

---

## Installation Issues

### Problem: "Class not found" or "Module not found"

**Error**: `Class 'NIN\RequestLogAnalyzer\...' not found`

**Solutions**:
```bash
# 1. Dump autoloader
composer dump-autoload

# 2. Clear cache
php artisan cache:clear
php artisan config:clear

# 3. Force re-publish
php artisan vendor:publish --tag=request-log-analyzer-config --force

# 4. If still failing:
composer require nintis/request-log-analyzer
php artisan analyzer:install
```

### Problem: Migration fails

**Error**: `SQLSTATE[42S01]: Table 'rla_requests' already exists`

**Solutions**:
```bash
# Option 1: Force migrate
php artisan migrate --force

# Option 2: Refresh database (WARNING: loses data!)
php artisan migrate:refresh

# Option 3: Check if tables exist
php artisan db:show | grep rla
```

### Problem: "Undefined index" error during install

**Error**: `Undefined index: REQUEST_LOG_ANALYZER_ENABLED`

**Solution**:
```bash
# Republish config
php artisan vendor:publish --tag=request-log-analyzer-config --force

# Clear config cache
php artisan config:clear
```

---

## Dashboard Not Loading

### Problem: 404 Error on Dashboard

**URL**: `http://your-app.test/request-log-analyzer` → 404

**Checklist**:
```bash
# 1. Check middleware is registered
grep -r "TrackRequest" app/Http/Kernel.php  # Laravel 10
grep -r "TrackRequest" bootstrap/app.php    # Laravel 11+

# 2. Check route exists
php artisan route:list | grep analyzer

# 3. Clear route cache
php artisan route:cache
php artisan route:clear

# 4. Verify config
cat config/request-log-analyzer.php | grep route_prefix
```

**Fix**: Make sure middleware is registered (see `01_installation.md`)

### Problem: Dashboard shows blank/white page

**Solutions**:
```bash
# 1. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 2. Check errors
tail -f storage/logs/laravel.log

# 3. Enable debug mode temporarily
# In .env:
APP_DEBUG=true

# 4. Check browser console for errors
# F12 → Console tab → Look for red errors
```

### Problem: CSS/JS not loading (styling broken)

**Solution**:
```bash
# Publish assets
php artisan vendor:publish --tag=request-log-analyzer-views --force
php artisan vendor:publish --tag=request-log-analyzer-assets --force

# Clear caches
php artisan cache:clear
php artisan view:clear
```

### Problem: "Unauthorized" or "Forbidden" on dashboard

**Reason**: Middleware is rejecting access

**Solution 1: Remove auth requirement** (for testing)
```php
// config/request-log-analyzer.php
'middleware' => ['web'],  // Remove 'auth'
```

**Solution 2: Add yourself to allowed users**
```php
// In AuthServiceProvider.php
Gate::define('viewAnalyzer', function ($user) {
    return $user->id === 1;  // Allow user ID 1
});

// Then in config:
'middleware' => ['web', 'can:viewAnalyzer'],
```

---

## No Data Being Captured

### Problem: Dashboard is empty (no requests shown)

**Checklist**:
```bash
# 1. Is middleware registered?
grep TrackRequest app/Http/Kernel.php
grep TrackRequest bootstrap/app.php

# 2. Make a request
curl http://your-app.test/

# 3. Check database directly
php artisan tinker
> \DB::table('rla_requests')->count()
# Should show > 0

# 4. Check if tracking is enabled
config('request-log-analyzer.enabled')

# 5. Check sample rate isn't 0
config('request-log-analyzer.sample_rate')
```

**Fix**: Register middleware in Kernel.php or bootstrap/app.php

### Problem: Only some requests are logged

**Reason**: Sample rate is low

**Example**: `SAMPLE_RATE=10` means only ~10% of requests logged

**Solution**:
```env
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100  # Log all requests
```

After changing, restart the server and test.

### Problem: Requests from static files showing

**Reason**: `IGNORE_STATIC_ASSETS` is disabled

**Solution**:
```env
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
```

---

## Performance Issues

### Problem: App is slow/sluggish

**Reason**: Synchronous logging is blocking requests

**Solution**: Enable async logging
```env
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
```

**Also try**:
```bash
# 1. Disable expensive features
REQUEST_LOG_ANALYZER_TRACK_GEO=false
REQUEST_LOG_ANALYZER_TRACK_QUERIES=false

# 2. Lower sample rate
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10

# 3. Skip static files
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
```

### Problem: Queue is stuck (jobs not processing)

**Solution**:
```bash
# 1. Check failed jobs
php artisan queue:failed

# 2. Start queue worker (if not running)
php artisan queue:work redis

# 3. Retry failed jobs
php artisan queue:retry all

# 4. Clear failed jobs
php artisan queue:flush
```

### Problem: Disk space filling quickly

**Reason**: Logging too much data

**Solutions**:
```bash
# 1. Lower sample rate
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10

# 2. Disable query logging
REQUEST_LOG_ANALYZER_TRACK_QUERIES=false

# 3. Delete old records
php artisan analyzer:prune --days=7  # Keep only 7 days

# 4. Check retention setting
# In config: 'retention_days' => 30
```

---

## Data Issues

### Problem: Sensitive data showing in logs

**Reason**: Masking is disabled

**Solution**:
```env
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
```

**Also check**:
```php
// In config/request-log-analyzer.php
'masking' => [
    'enabled' => true,
    'fields' => ['password', 'api_key', 'token', /* ... */],
    'headers' => ['authorization', 'cookie', /* ... */],
]
```

**To add custom fields**:
```php
'masking' => [
    'fields' => [
        // ... existing
        'your_custom_field',  // Add here
        'another_field',
    ],
]
```

### Problem: Can't see all request details

**Reason**: Too many queries/data

**Solution**:
```bash
# Increase PHP limits in php.ini or .env
# (if supported by hosting)

# Or reduce what's captured
REQUEST_LOG_ANALYZER_TRACK_QUERIES=false
```

---

## Dashboard Issues

### Problem: Charts not showing

**Solution**:
```bash
# 1. Clear cache
php artisan cache:clear

# 2. Ensure chart data exists
php artisan analyzer:analyze  # If command exists

# 3. Check browser console (F12)
# Look for JavaScript errors
```

### Problem: Filter not working

**Solution**:
```bash
# 1. Try different filter
?method=GET instead of ?method=get

# 2. URL encode spaces
?uri=/api/users%20list  # instead of spaces

# 3. Refresh page
```

### Problem: Pagination not working

**Solution**:
```bash
# Might be database issue
php artisan db:show

# Try:
php artisan tinker
> \DB::table('rla_requests')->count()
# If returns huge number, pagination might be slow
```

---

## Middleware Issues

### Problem: "Middleware not registering"

**Laravel 11+ (bootstrap/app.php)**:
```php
// Make sure this is added:
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;

->withMiddleware(function (Middleware $middleware) {
    $middleware->append(TrackRequest::class);
})
```

**Laravel 10 (app/Http/Kernel.php)**:
```php
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;

protected $middleware = [
    // ... other middleware
    TrackRequest::class,  // MUST be here
];
```

**Test**:
```bash
php artisan route:list | grep middleware
# TrackRequest should appear in output
```

### Problem: "Middleware appears twice"

**Solution**: Remove duplicate registration

```bash
grep -r "TrackRequest" app/  # Find all instances
grep -r "TrackRequest" bootstrap/  # Find all instances
```

Remove from one location only.

---

## Configuration Issues

### Problem: Changes not taking effect

**Solution**:
```bash
# 1. Clear config cache
php artisan config:clear

# 2. For .env changes, restart server
# Stop: Ctrl+C
# Start: php artisan serve

# 3. Ensure no .env.production overriding
cat .env.production  # Check if exists
```

### Problem: Can't find config file

**Solution**:
```bash
# Should be at:
ls config/request-log-analyzer.php

# If missing, publish it:
php artisan vendor:publish --tag=request-log-analyzer-config

# Then check:
ls config/request-log-analyzer.php
```

---

## GeoIP Issues

### Problem: GeoIP not working / returns null

**Solution**:
```bash
# 1. Check if enabled
REQUEST_LOG_ANALYZER_TRACK_GEO=true

# 2. Test API manually
curl http://ip-api.com/json/8.8.8.8
# Should return JSON with country, city, etc.

# 3. Check timeout
REQUEST_LOG_ANALYZER_GEO_TIMEOUT=5  # Increase timeout

# 4. If using VPN/proxy, might not work
# Try with real IP
```

### Problem: "Private IP" showing as null

**Expected behavior**: Private IPs (192.168.x, 127.x, 10.x) return null.

No GeoIP data for private networks (correct for security).

---

## Queue Issues

### Problem: Async logging not working

**Checklist**:
```bash
# 1. Is async enabled?
echo $REQUEST_LOG_ANALYZER_ASYNC
# Should be: true

# 2. Is queue worker running?
ps aux | grep "queue:work"
# Should see running process

# 3. Start queue worker:
php artisan queue:work redis

# 4. Check failed jobs
php artisan queue:failed
```

### Problem: "Unable to locate driver" or "Connection refused"

**Error**: Queue connection not found

**Solutions**:
```bash
# 1. Check QUEUE_CONNECTION in .env
cat .env | grep QUEUE_CONNECTION

# 2. For Redis, verify Redis is running
redis-cli ping
# Should return: PONG

# 3. For database queue, verify table exists
php artisan queue:table  # Create if needed
php artisan migrate

# 4. Test with database queue temporarily
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=database
```

---

## API Issues

### Problem: "API not found" or 403 Forbidden

**Solutions**:
```bash
# 1. Check if enabled
config('request-log-analyzer.api.enabled')  # Should be true

# 2. Check token
REQUEST_LOG_ANALYZER_API_TOKEN=your-token-here

# 3. Use token in request
curl -H "Authorization: Bearer your-token-here" \
    http://your-app.test/api/analyzer/requests
```

### Problem: "Invalid token"

**Solution**:
```bash
# 1. Generate token
php artisan analyzer:token

# 2. Copy output to .env
REQUEST_LOG_ANALYZER_API_TOKEN=generated-token-here

# 3. Clear cache
php artisan config:clear
```

---

## Getting Help

### If You're Still Stuck

1. **Check the logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Enable debug mode temporarily**:
   ```env
   APP_DEBUG=true
   ```

3. **Use Tinker to test**:
   ```bash
   php artisan tinker
   > config('request-log-analyzer.enabled')
   > DB::table('rla_requests')->count()
   ```

4. **Check documentation**:
   - Installation: `01_installation.md`
   - Configuration: `02_configuration.md`
   - Features: `03-11_*.md` files

5. **Review database**:
   ```bash
   php artisan db:show
   php artisan db:table rla_requests
   ```

---

## Common Error Messages

| Error | Cause | Fix |
|-------|-------|-----|
| "Class not found" | Autoloader issue | `composer dump-autoload` |
| "Table not found" | Migration not run | `php artisan migrate` |
| "404 Not Found" | Dashboard route missing | Register middleware |
| "Middleware rejected" | Auth middleware | Check `middleware` config |
| "No data showing" | Sample rate 0 or middleware missing | Check both |
| "App slow" | Sync logging | Enable `ASYNC=true` |
| "Disk full" | Too much logging | Lower `SAMPLE_RATE` |

---

## Quick Debug Checklist

```
□ Package enabled? REQUEST_LOG_ANALYZER_ENABLED=true
□ Middleware registered? (Check Kernel.php or bootstrap/app.php)
□ Migrations run? php artisan migrate
□ Making requests? (Check curl or browser)
□ Dashboard accessible? http://app/request-log-analyzer
□ Data appearing? (Check dashboard, not empty)
□ If not: Check REQUEST_LOG_ANALYZER_SAMPLE_RATE != 0
```

---

**Still stuck?** Review the full documentation files 01-12.

