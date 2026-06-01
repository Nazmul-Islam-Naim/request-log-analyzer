# Configuration Reference - Request Log Analyzer

Complete guide to all configuration options with examples and explanations.

---

## Environment Variables Quick Reference

### Master Control

```env
# Enable/disable entire package
REQUEST_LOG_ANALYZER_ENABLED=true
```

### Features Toggle

```env
# Track database queries
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true

# Track exceptions/errors
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true

# Resolve GeoIP location
REQUEST_LOG_ANALYZER_TRACK_GEO=true

# Track login/logout events
REQUEST_LOG_ANALYZER_TRACK_LOGIN_HISTORY=true
```

### Performance & Sampling

```env
# Percentage of requests to log (0-100)
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100

# Time in ms for "slow" requests
REQUEST_LOG_ANALYZER_SLOW_MS=500

# Always capture errors regardless of sample rate
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true

# Always capture requests slower than X ms
REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS=1000

# Skip static files (.css, .js, etc)
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
```

### Data Protection

```env
# Mask sensitive fields
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true

# What character to use for masking
REQUEST_LOG_ANALYZER_MASK_VALUE=[REDACTED]
```

### Async Logging

```env
# Log asynchronously (background)
REQUEST_LOG_ANALYZER_ASYNC=false

# Queue driver to use
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis

# Queue name
REQUEST_LOG_ANALYZER_QUEUE_NAME=default
```

### GeoIP Settings

```env
# API URL for geolocation
REQUEST_LOG_ANALYZER_GEO_API_URL=http://ip-api.com/json/{ip}

# Timeout for GeoIP lookup
REQUEST_LOG_ANALYZER_GEO_TIMEOUT=2
```

### Dashboard Settings

```env
# URL prefix for dashboard
REQUEST_LOG_ANALYZER_PREFIX=request-log-analyzer

# Active users time window
REQUEST_LOG_ANALYZER_ACTIVE_WINDOW_MINUTES=5
```

---

## Configuration Examples

### Development Environment

Best for learning and debugging:

```env
# .env.local
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100              # Log everything
REQUEST_LOG_ANALYZER_ASYNC=false                  # Immediate DB
REQUEST_LOG_ANALYZER_MASKING_ENABLED=false        # See raw data
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true
REQUEST_LOG_ANALYZER_TRACK_GEO=false              # Skip GeoIP (slow)
```

### Staging Environment

Testing with realistic data:

```env
# .env.staging
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=50               # 50% of traffic
REQUEST_LOG_ANALYZER_ASYNC=true                   # Background processing
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true
REQUEST_LOG_ANALYZER_SLOW_MS=300
```

### Production Environment

Optimized for scale and reliability:

```env
# .env.production
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10               # 10% of traffic
REQUEST_LOG_ANALYZER_ASYNC=true                   # Always async
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_QUEUE_NAME=analytics        # Separate queue
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true         # Never miss errors
REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS=500
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
REQUEST_LOG_ANALYZER_TRACK_GEO=true
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true
REQUEST_LOG_ANALYZER_TRACK_LOGIN_HISTORY=true
```

---

## Configuration in PHP

Edit `config/request-log-analyzer.php`:

### Enabled Setting

```php
'enabled' => env('REQUEST_LOG_ANALYZER_ENABLED', true),
```

When false, package does NOTHING - no overhead, no logging.

### Sample Rate

```php
'sample_rate' => (int) env('REQUEST_LOG_ANALYZER_SAMPLE_RATE', 100),
```

| Value | Meaning | Use Case |
|-------|---------|----------|
| 100 | Log all requests | Development |
| 50 | Log 50% | Staging |
| 10 | Log 10% (1 in 10) | High-traffic production |
| 1 | Log 1% (1 in 100) | Very high traffic |

### Slow Request Threshold

```php
'slow_request_threshold_ms' => (int) env('REQUEST_LOG_ANALYZER_SLOW_MS', 500),
```

Requests taking longer than this are marked "slow" in dashboard.

| Value | Meaning |
|-------|---------|
| 100 | Very strict (useful for APIs) |
| 500 | Default (reasonable for most apps) |
| 1000 | Lenient (1 second) |
| 0 | Disabled |

### Feature Tracking

```php
// Track database queries
'track_queries' => env('REQUEST_LOG_ANALYZER_TRACK_QUERIES', true),

// Track exceptions
'track_errors' => env('REQUEST_LOG_ANALYZER_TRACK_ERRORS', true),

// Track geolocation
'track_geo' => env('REQUEST_LOG_ANALYZER_TRACK_GEO', true),

// Track login events
'track_login_history' => env('REQUEST_LOG_ANALYZER_TRACK_LOGIN_HISTORY', true),

// Track lifecycle steps (for timeline)
'track_steps' => env('REQUEST_LOG_ANALYZER_TRACK_STEPS', true),
```

### Async Logging

```php
'async_logging' => env('REQUEST_LOG_ANALYZER_ASYNC', false),
'queue_connection' => env('REQUEST_LOG_ANALYZER_QUEUE_CONNECTION', null),
'queue_name' => env('REQUEST_LOG_ANALYZER_QUEUE_NAME', 'default'),
```

Enable async for production! Means:
- Requests log to queue instead of directly to DB
- Your app responds faster
- Background worker processes logs
- Requires queue worker running

### Sensitive Data Masking

```php
'masking' => [
    'enabled' => env('REQUEST_LOG_ANALYZER_MASKING_ENABLED', true),
    'mask_value' => env('REQUEST_LOG_ANALYZER_MASK_VALUE', '[REDACTED]'),
    
    'fields' => [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'credit_card',
        'ssn',
        // Add custom fields here
    ],
    
    'headers' => [
        'authorization',
        'x-api-key',
        'cookie',
        // Add custom headers
    ],
    
    'query_params' => [
        'token',
        'api_key',
        'password',
        // Add custom params
    ],
    
    'patterns' => [
        '/Bearer\s+[\w\-\.]+/i',
        // Add regex patterns for your data
    ],
],
```

### Dashboard Access

```php
// URL prefix
'route_prefix' => env('REQUEST_LOG_ANALYZER_PREFIX', 'request-log-analyzer'),
// Access at: /request-log-analyzer

// Middleware (who can access)
'middleware' => ['web'],
// Add 'auth' to require login
// Add 'admin' for admin only
```

### Static Files

```php
'ignore_static_assets' => env('REQUEST_LOG_ANALYZER_IGNORE_STATIC', true),
```

When true, skips .css, .js, .png, .jpg, .gif, .svg, .woff, .eot files.

---

## Recommended Settings by Scenario

### Scenario 1: Local Development

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_ASYNC=false
REQUEST_LOG_ANALYZER_MASKING_ENABLED=false
REQUEST_LOG_ANALYZER_TRACK_GEO=false
```

**Why**:
- See all data while developing
- Immediate results (easier debugging)
- No external services (GeoIP)
- No data masking (see real values)

### Scenario 2: Learning/Tutorial

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_ASYNC=false
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true
```

**Why**:
- Capture all requests to learn
- Practice with safe masking
- See queries for optimization
- Track errors for debugging

### Scenario 3: Testing Suite

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_ASYNC=false
REQUEST_LOG_ANALYZER_TRACK_GEO=false
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
```

**Why**:
- Capture all test requests
- Synchronous (easier to debug)
- Skip GeoIP (test env restriction)
- Mask sensitive data

### Scenario 4: Small Production Site

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=50
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=database
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true
```

**Why**:
- 50% sampling reduces disk usage
- Async keeps responses fast
- Database queue (no Redis needed)
- Always capture errors

### Scenario 5: High-Traffic Site

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=5
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_QUEUE_NAME=analytics
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true
REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS=500
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
```

**Why**:
- 5% sampling (1 in 20 requests)
- Async with Redis (fast & scalable)
- Dedicated queue (doesn't block other jobs)
- Always catch errors and slow requests

---

## Performance Impact

### With Default Settings

| Operation | Impact | Notes |
|-----------|--------|-------|
| Per request | +5-15ms | Depends on GeoIP, sample rate |
| Memory | +2-5MB per request | Buffered in middleware |
| Database | ~1KB per request | Per table query |
| Disk | ~10MB per 1000 requests | Configurable retention |

### How to Reduce Impact

1. **Lower sample rate**: `SAMPLE_RATE=10` → 90% less DB writes
2. **Disable features**: `TRACK_QUERIES=false` → Skip query logging
3. **Use async**: `ASYNC=true` → Faster response times
4. **Skip GeoIP**: `TRACK_GEO=false` → Save API call
5. **Skip static**: `IGNORE_STATIC=true` → Skip .js/.css files

### Best for Performance

```env
REQUEST_LOG_ANALYZER_SAMPLE_RATE=5
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_TRACK_GEO=false
REQUEST_LOG_ANALYZER_TRACK_QUERIES=false
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
```

**Overhead**: < 1ms per request

---

## Security Considerations

### Passwords & Tokens

Automatically masked by default. Check `masking.fields` in config.

### API Access

```php
'api' => [
    'enabled' => true,
    'prefix' => 'api/analyzer',
    'token' => env('REQUEST_LOG_ANALYZER_API_TOKEN'),
],
```

If `token` is empty, API is disabled. Set token:

```env
REQUEST_LOG_ANALYZER_API_TOKEN=your-secret-token-here
```

### Dashboard Access

Protect with middleware:

```php
'middleware' => ['web', 'auth', 'admin'],
```

Or with gates:

```php
Gate::define('viewLogs', function ($user) {
    return $user->isAdmin();
});

'middleware' => ['web', 'can:viewLogs'],
```

---

## Troubleshooting Configuration

### "Nothing is being logged"

Check:
```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100  # Not 0
```

### "Disk is filling too fast"

Try:
```env
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10        # Reduce from 100
REQUEST_LOG_ANALYZER_TRACK_QUERIES=false   # Disable queries
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true    # Skip static files
```

### "App is slow"

Try:
```env
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_TRACK_GEO=false
```

### "Want to see more data"

Try:
```env
REQUEST_LOG_ANALYZER_MASKING_ENABLED=false  # See passwords etc
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true
```

---

## Next Steps

1. **Set up for your environment** - Use recommended config above
2. **Test it** - Make some requests, check dashboard
3. **Fine-tune** - Adjust sample rate, features as needed
4. **Monitor** - Watch for performance impact
5. **Optimize** - Disable features you don't use

