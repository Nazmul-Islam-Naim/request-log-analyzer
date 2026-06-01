# Frequently Asked Questions (FAQ)

Common questions about Request Log Analyzer.

---

## General Questions

### What is Request Log Analyzer?

A Laravel package that captures, analyzes, and displays HTTP requests, database queries, errors, and performance metrics through an interactive dashboard.

**Key benefits**:
- Real-time request monitoring
- Database query analysis
- Error tracking
- Performance insights
- GeoIP tracking
- Security monitoring
- Data privacy & masking

### Which Laravel versions are supported?

- Laravel 10, 11, 12, 13
- PHP 8.1+

Check [02_configuration.md](02_configuration.md) for specific version matrix.

### Is this package free?

Yes! Request Log Analyzer is open source and free to use.

### Can I use this in production?

Yes, with proper configuration:

1. **Enable async logging**: Prevents request delays
   ```env
   REQUEST_LOG_ANALYZER_ASYNC=true
   ```

2. **Set sample rate**: Don't log every request
   ```env
   REQUEST_LOG_ANALYZER_SAMPLE_RATE=10  # Log 10% of requests
   ```

3. **Disable expensive features**: If not needed
   ```env
   REQUEST_LOG_ANALYZER_TRACK_QUERIES=false  # If too slow
   REQUEST_LOG_ANALYZER_TRACK_GEO=false       # If not needed
   ```

4. **Set retention**: Clean old data automatically
   ```php
   // config/request-log-analyzer.php
   'retention_days' => 30,  // Delete older records
   ```

---

## Installation Questions

### Do I need Redis?

**No**, but it's recommended for async logging.

- **Without Redis**: Use database queue or sync logging
- **With Redis**: Better performance and reliability

### What about Docker?

Works fine in Docker! Just ensure:
```bash
# 1. Database connection works
# 2. If using Redis, Redis container running
# 3. PHP extensions available (see 01_installation.md)
```

### Can I install multiple versions?

Not recommended. Keep one version installed.

To update:
```bash
composer update nin/request-log-analyzer
php artisan migrate
```

### Where are files installed?

- Config: `config/request-log-analyzer.php`
- Migrations: `database/migrations/`
- Views: `resources/views/request-log-analyzer/` (published)
- Assets: `public/vendor/request-log-analyzer/` (published)

---

## Configuration Questions

### What do all the settings mean?

See [02_configuration.md](02_configuration.md) for detailed explanation of all options.

**Quick summary**:
- `enabled`: Turn tracking on/off
- `sample_rate`: % of requests to log
- `track_*`: Which features to enable
- `async_logging`: Use queue instead of synchronous
- `masking`: Hide sensitive data

### Can I change config at runtime?

Partially:
```php
// In code:
config(['request-log-analyzer.enabled' => false]);  // Won't persist

// Better: Use .env
REQUEST_LOG_ANALYZER_ENABLED=false
php artisan config:clear  // Clear cache
```

### Should I commit config to Git?

**Not recommended** if it contains secrets.

Instead:
1. Commit `config/request-log-analyzer.php` (the template)
2. Add secrets to `.env` (not committed)
3. Use environment variables for production

### What environment variables are available?

```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_ASYNC=false
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_SLOW_REQUEST_MS=1000
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true
REQUEST_LOG_ANALYZER_TRACK_STEPS=true
REQUEST_LOG_ANALYZER_TRACK_LOGIN=true
REQUEST_LOG_ANALYZER_ACTIVE_USERS_MINUTES=15
REQUEST_LOG_ANALYZER_TRACK_GEO=true
REQUEST_LOG_ANALYZER_GEO_API=ip-api.com
REQUEST_LOG_ANALYZER_GEO_TIMEOUT=5
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
REQUEST_LOG_ANALYZER_API_ENABLED=true
REQUEST_LOG_ANALYZER_API_TOKEN=your-token
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
```

---

## Usage Questions

### How do I access the dashboard?

Default URL:
```
http://your-app.test/request-log-analyzer
```

**If different**:
```php
// Check config:
'route_prefix' => 'analyzer'  // Would be /analyzer instead

// Or use config setting:
REQUEST_LOG_ANALYZER_ROUTE_PREFIX=custom-path
```

### Who can access the dashboard?

By default, anyone can access. To restrict:

See [08_features_security.md](08_features_security.md) for authentication setup.

### What data is being collected?

- **Requests**: Method, URL, status, duration, user
- **Queries**: SQL, execution time, results
- **Errors**: Type, message, stack trace
- **Performance**: Response times, memory usage
- **Security**: Login attempts, user activity
- **Geo**: IP, country, city (if enabled)

See [03-10_*.md](03_features_request_tracking.md) for details.

### Can I delete records?

Via database:
```bash
php artisan tinker
> DB::table('rla_requests')->where('id', 123)->delete()

# Or delete all:
> DB::table('rla_requests')->truncate()
```

**Better**: Use retention settings to auto-cleanup.

### How do I export data?

Via API:
```bash
# Get all requests
curl -H "Authorization: Bearer YOUR_TOKEN" \
    http://your-app.test/api/analyzer/requests

# Response: JSON array
# Save to file
curl -H "Authorization: Bearer YOUR_TOKEN" \
    http://your-app.test/api/analyzer/requests > requests.json
```

See [12_api_reference.md](12_api_reference.md) for API endpoints.

---

## Performance Questions

### Why is my app slower after installing?

**Causes**:
1. Synchronous logging (blocks requests)
2. Sampling everything (100% sample rate)
3. Tracking expensive features (queries, geo)

**Solutions**:
1. Enable async: `REQUEST_LOG_ANALYZER_ASYNC=true`
2. Lower sample rate: `REQUEST_LOG_ANALYZER_SAMPLE_RATE=10`
3. Disable unneeded features:
   ```env
   REQUEST_LOG_ANALYZER_TRACK_QUERIES=false
   REQUEST_LOG_ANALYZER_TRACK_GEO=false
   ```

### How much disk space does this use?

**Depends on**:
- Number of requests per day
- What data you track
- Sample rate
- Retention period

**Estimate**:
- 1 request ≈ 1-5 KB
- 1000 requests ≈ 1-5 MB
- 1M requests/month ≈ 30-150 MB

**To manage**:
- Set `retention_days` to delete old data
- Lower `sample_rate`
- Disable `track_queries`

### Should I use async or sync?

| Setting | Pro | Con |
|---------|-----|-----|
| **Async (Recommended)** | Non-blocking, fast | Requires queue, more complex |
| **Sync** | Simple, fewer moving parts | Blocks requests, slower |

**For production**: Always use async.

**For development**: Sync is fine.

---

## Data Privacy Questions

### Does this store sensitive data?

Yes, by default. Enable masking:

```env
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
```

This automatically hides:
- Passwords
- API keys/tokens
- Credit card data
- Authentication headers

See [09_features_data_protection.md](09_features_data_protection.md) for details.

### Can I mask custom fields?

Yes:
```php
// config/request-log-analyzer.php
'masking' => [
    'fields' => [
        'password', 'api_key',
        // Add your custom fields:
        'ssn', 'phone', 'custom_token',
    ],
]
```

### Who can see the data?

Anyone who can access `/request-log-analyzer` URL.

To restrict:
```php
// config/request-log-analyzer.php
'middleware' => ['web', 'auth', 'admin'],  // Require auth + admin role
```

### Does this comply with GDPR/privacy laws?

Depends on your configuration:

✅ **If you**:
- Enable masking
- Set retention to delete old data
- Restrict access to admins only
- Document data collection

❌ **If you don't**:
- Disable masking
- Keep data forever
- Allow public access
- Don't document

**Best practice**: Enable masking, set retention, restrict access.

### How do I delete a user's data?

```bash
php artisan tinker
# Get user requests
> $requests = DB::table('rla_requests')->where('user_id', 123)->get()

# Delete them
> DB::table('rla_requests')->where('user_id', 123)->delete()

# Verify
> DB::table('rla_requests')->where('user_id', 123)->count()
# Should return 0
```

---

## Troubleshooting Questions

### Nothing is showing in the dashboard

**Check list**:
1. Is it enabled? `REQUEST_LOG_ANALYZER_ENABLED=true`
2. Is middleware registered? (See [01_installation.md](01_installation.md))
3. Is sample rate > 0? `REQUEST_LOG_ANALYZER_SAMPLE_RATE` must be > 0
4. Have you made requests? (Check with curl/browser)
5. Check database for data:
   ```bash
   php artisan tinker
   > DB::table('rla_requests')->count()
   ```

See [13_troubleshooting.md](13_troubleshooting.md) for more issues.

### Dashboard shows 404

**Cause**: Route not registered

**Fix**:
```bash
# 1. Verify middleware installed
php artisan route:list | grep analyzer

# 2. If not showing, check middleware registration
# Laravel 11: bootstrap/app.php
# Laravel 10: app/Http/Kernel.php

# 3. Clear cache
php artisan route:cache
php artisan route:clear
```

### "Table doesn't exist" error

**Cause**: Migrations not run

**Fix**:
```bash
php artisan migrate
```

### How do I see error details?

Enable Laravel debug:
```env
APP_DEBUG=true
```

Then check:
- Browser network tab (F12 → Network)
- `storage/logs/laravel.log`

### How do I report a bug?

1. Check [13_troubleshooting.md](13_troubleshooting.md) first
2. Collect info:
   ```bash
   php artisan --version
   php -v
   php artisan config:show REQUEST_LOG_ANALYZER
   ```
3. Check GitHub issues: [nin/request-log-analyzer](https://github.com/nin-company/request-log-analyzer)

---

## Integration Questions

### Can I use this with [tool X]?

**Supported integrations**:
- ✅ Telescope (can coexist)
- ✅ Debugbar (can coexist)
- ✅ Sentry (for errors)
- ✅ New Relic (for monitoring)
- ✅ DataDog (for metrics)

**Coexistence notes**:
- They collect similar data
- No conflicts expected
- May slightly increase overhead
- Can disable overlapping features

### Can I customize the dashboard?

Currently: Limited customization

**Possible**:
- Change route prefix
- Change middleware
- Publish views and edit

**Future**: Custom widgets, themes

### Can I send data to external services?

Via API:
```bash
curl -H "Authorization: Bearer TOKEN" \
    http://your-app.test/api/analyzer/requests | \
    jq . | POST to http://external-service.com/logs
```

Or manually query and export.

---

## API Questions

### How do I get an API token?

```bash
php artisan analyzer:token
```

Copy the output to `.env`:
```env
REQUEST_LOG_ANALYZER_API_TOKEN=generated-token-here
```

### What's the API rate limit?

No built-in rate limit. Add your own:
```php
// app/Http/Middleware/RateLimitAnalyzer.php
// Custom middleware to rate limit API
```

### Can I use the API from external apps?

Yes! The API is designed for external access.

**Requirements**:
- Enable API: `REQUEST_LOG_ANALYZER_API_ENABLED=true`
- Include token: `Authorization: Bearer YOUR_TOKEN`

See [12_api_reference.md](12_api_reference.md) for endpoints.

### What API endpoints are available?

- `GET /api/analyzer/requests` - List requests
- `GET /api/analyzer/requests/{id}` - Get single request
- `GET /api/analyzer/stats` - Get statistics
- `GET /api/analyzer/queries` - List queries
- `GET /api/analyzer/errors` - List errors

See [12_api_reference.md](12_api_reference.md) for full reference.

---

## Development Questions

### Can I contribute?

Yes! The project is open source.

**How to contribute**:
1. Fork on GitHub
2. Create feature branch
3. Make changes
4. Submit pull request
5. Wait for review

### Can I modify the package for my needs?

Yes:
1. Publish views: `php artisan vendor:publish --tag=request-log-analyzer-views`
2. Publish config: `php artisan vendor:publish --tag=request-log-analyzer-config`
3. Edit as needed
4. Changes persist through updates

### How do I run tests?

```bash
php artisan test

# Or with coverage
php artisan test --coverage
```

See [TESTING.md](#) for full testing guide.

### Where's the source code?

- GitHub: [nin-company/request-log-analyzer](https://github.com/nin-company/request-log-analyzer)
- Vendor: `vendor/nin/request-log-analyzer/`

---

## Still have questions?

1. **Check documentation**: Read the [INDEX.md](INDEX.md) for all topics
2. **Search GitHub issues**: [GitHub Issues](https://github.com/nin-company/request-log-analyzer/issues)
3. **Enable debug mode**: Set `APP_DEBUG=true` for more details
4. **Check logs**: Look in `storage/logs/laravel.log`

Happy logging! 📊

