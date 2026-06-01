# Migration Guide: v1 to v2

Upgrading Request Log Analyzer from v1 to v2.

---

## Overview

**v2 includes**:
- New database schema (more efficient)
- Enhanced dashboard UI
- Additional features (GeoIP, masking, async logging)
- Better performance
- API support

**Breaking changes**: Table structures changed, requires migration.

---

## Before You Start

### Backup Your Data

```bash
# Backup database
mysqldump -u root -p your_database > backup_$(date +%Y%m%d).sql

# Or using Laravel
php artisan db:show  # Verify connection
```

### Check Current Version

```bash
composer show nin/request-log-analyzer
# Look for: version
```

### Estimated Time

- Small app (<1M requests): 5-10 minutes
- Medium app (1-10M requests): 15-30 minutes
- Large app (10M+ requests): 1-2 hours

---

## Step 1: Update Composer

```bash
# Update package
composer require "nin/request-log-analyzer:^2.0"

# Wait for installation
# May take 1-5 minutes
```

**If it fails**:
```bash
# Clear composer cache and retry
composer clear-cache
composer require "nin/request-log-analyzer:^2.0"
```

---

## Step 2: Publish New Config

```bash
php artisan vendor:publish --tag=request-log-analyzer-config --force
```

**What changed in config**:
- New options: `async_logging`, `sampling`
- Renamed: `track_request_body` → `track_request_body`
- New features: GeoIP, masking, API

**Review the config**:
```bash
cat config/request-log-analyzer.php
```

**Transfer your old settings** (if .env based):
```env
# Old v1 settings to new v2 names (if changed):
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100  # New in v2
```

---

## Step 3: Backup Old Tables

The v2 migration will rename old tables (not delete).

```bash
# If you want to keep the old data for reference:
mysql -u root -p -e "
USE your_database;
RENAME TABLE request_logs TO request_logs_v1;
RENAME TABLE slow_requests TO slow_requests_v1;
"

# Or via Tinker:
php artisan tinker
> DB::statement('RENAME TABLE request_logs TO request_logs_v1')
> DB::statement('RENAME TABLE slow_requests TO slow_requests_v1')
```

---

## Step 4: Run Migrations

```bash
# This creates new v2 tables and migrates data if possible
php artisan migrate
```

**Output should show**:
```
Migrating: 2024_01_01_000001_create_request_log_analyzer_tables.php
Migrated: 2024_01_01_000001_create_request_log_analyzer_tables.php
```

**If migration fails**:
```bash
# Rollback and fix
php artisan migrate:rollback

# Check logs
tail -f storage/logs/laravel.log

# Try force flag
php artisan migrate --force
```

---

## Step 5: Clear Cache

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Step 6: Update Middleware (Laravel 10)

If using Laravel 10, verify middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest::class,
];
```

**For Laravel 11+**, it should be in `bootstrap/app.php` already.

---

## Step 7: Update Middleware (Laravel 11+)

If using Laravel 11, verify middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest::class);
})
```

---

## Step 8: Test the Dashboard

```bash
# Start your app
php artisan serve

# Visit dashboard
# http://localhost:8000/request-log-analyzer

# Make a test request
curl http://localhost:8000/api/test
```

**You should see**:
- ✅ Dashboard loads
- ✅ New request appears
- ✅ No errors in logs

---

## Migrating Data from v1 to v2

### Option A: Keep Old Data (Recommended)

If you have valuable v1 data, you can keep it:

```bash
# The migration script automatically:
# 1. Creates new v2 tables
# 2. Keeps old v1 tables (renamed)
# 3. You can manually migrate important records later

# To export v1 data:
php artisan tinker
> $old = DB::table('request_logs_v1')->get()
> // Process and insert into new tables if needed
```

### Option B: Start Fresh (Simplest)

New v2 schema will be empty. Fresh start:

```bash
# Just proceed to Step 9, v1 data is archived
# New requests will go to v2 tables
```

### Option C: Delete Old Data (Destructive)

```bash
# WARNING: This deletes v1 data permanently
php artisan tinker
> DB::statement('DROP TABLE request_logs_v1')
> DB::statement('DROP TABLE slow_requests_v1')
```

---

## Step 9: Verify Installation

```bash
# Check new tables exist
php artisan tinker
> DB::table('rla_requests')->count()
> DB::table('rla_database_queries')->count()
> DB::table('rla_errors')->count()

# Should show 0 or more (depending on if you've made requests)

# Check config loaded
> config('request-log-analyzer.enabled')
# Should return: true
```

---

## Step 10: Re-publish Views (Optional)

If you customized v1 dashboard views:

```bash
# Publish new v2 views
php artisan vendor:publish --tag=request-log-analyzer-views --force

# This overwrites custom changes!
# If you have customizations, keep backup first:
cp -r resources/views/request-log-analyzer resources/views/request-log-analyzer-backup
```

---

## Configuration Migration

### New Features to Consider Enabling

**Async Logging** (recommended for performance):
```env
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
```

**Data Masking** (recommended for security):
```env
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
```

**GeoIP Tracking** (optional):
```env
REQUEST_LOG_ANALYZER_TRACK_GEO=true
REQUEST_LOG_ANALYZER_GEO_API=ip-api.com
```

**Sampling** (recommended for production):
```env
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10  # Log 10% of requests
```

---

## API Token Setup (New in v2)

Generate API token for new API endpoints:

```bash
php artisan analyzer:token
```

Save output to `.env`:
```env
REQUEST_LOG_ANALYZER_API_TOKEN=generated-token-here
```

Then test:
```bash
curl -H "Authorization: Bearer generated-token-here" \
    http://localhost:8000/api/analyzer/requests
```

---

## Troubleshooting Migration

### Issue: "Migration file not found"

```bash
# Clear cache and retry
php artisan cache:clear
composer dump-autoload
php artisan migrate
```

### Issue: "Table already exists"

```bash
# Means v2 tables already exist
# This is fine, just verify data:
php artisan tinker
> DB::table('rla_requests')->count()
```

### Issue: "FOREIGN KEY constraint fails"

```bash
# May happen with large datasets
# Temporarily disable checks:
php artisan tinker
> DB::statement('SET FOREIGN_KEY_CHECKS=0')
> // Run migrate
> DB::statement('SET FOREIGN_KEY_CHECKS=1')
```

### Issue: "Out of memory" during migration

For large datasets:

```bash
# Increase PHP memory
# In .env or php.ini:
memory_limit = 512M

# Or split migration:
php artisan migrate --step

# Run one migration at a time
```

---

## Rollback to v1 (If Needed)

If v2 doesn't work and you need to go back:

```bash
# 1. Restore database backup
mysql -u root -p your_database < backup_20240101.sql

# 2. Rollback to v1
composer require "nin/request-log-analyzer:^1.0"

# 3. Clear cache
composer dump-autoload
php artisan cache:clear

# 4. Rollback migrations
php artisan migrate:rollback

# 5. Re-publish
php artisan vendor:publish --tag=request-log-analyzer-config --force
```

---

## After Migration

### Check Everything Works

```bash
# 1. Dashboard loads
# http://your-app.test/request-log-analyzer

# 2. Make test request
curl http://your-app.test/api/test

# 3. See it in dashboard
# Refresh page, should see new request

# 4. Check logs for errors
tail -f storage/logs/laravel.log
```

### Run Tests

```bash
php artisan test

# Should all pass
```

### Performance Check

Monitor app performance:

```bash
# If slow with default settings:
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10  # Log less
REQUEST_LOG_ANALYZER_ASYNC=true      # Async logging
```

---

## New Features to Explore

After successful migration, check out:

1. **[API Reference](12_api_reference.md)** - New JSON API
2. **[Data Protection](09_features_data_protection.md)** - New masking features
3. **[Best Practices](11_best_practices.md)** - Production recommendations
4. **[GeoIP Tracking](07_features_geoip.md)** - Geographic insights

---

## Frequently Asked During Migration

### Q: Will my old data be deleted?

**A**: No, old tables are preserved. You can manually migrate if needed.

### Q: How long will migration take?

**A**: Usually < 5 minutes. Large datasets (10M+ records) may take longer.

### Q: Can I rollback if something goes wrong?

**A**: Yes, restore your database backup and rollback to v1.

### Q: Is there downtime?

**A**: Minimal. Migration typically takes seconds. App stays online.

### Q: Should I backup before migrating?

**A**: Absolutely yes. Always backup before major upgrades.

### Q: Can I skip v2 and stay on v1?

**A**: Yes, but v1 won't receive updates. Migration is recommended.

---

## Success Indicators

After migration, you should see:

✅ Dashboard loads at `/request-log-analyzer`
✅ New requests appear in dashboard within seconds
✅ No errors in `storage/logs/laravel.log`
✅ Database contains data in `rla_requests`, `rla_database_queries`, etc.
✅ API works: `curl -H "Authorization: Bearer TOKEN" http://app/api/analyzer/requests`
✅ Config file exists at `config/request-log-analyzer.php`
✅ Tests pass: `php artisan test`

---

## Need Help?

1. **Check [13_troubleshooting.md](13_troubleshooting.md)** for common issues
2. **See [14_faq.md](14_faq.md)** for Q&A
3. **Review [02_configuration.md](02_configuration.md)** for new options
4. **Check GitHub issues**: [GitHub Issues](https://github.com/nin-company/request-log-analyzer/issues)

---

**Congratulations!** 🎉 You've successfully migrated to v2!

