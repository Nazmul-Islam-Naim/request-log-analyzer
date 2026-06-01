# Request Log Analyzer - Quick Reference

## Installation (30 seconds)

```bash
composer require nintis/request-log-analyzer
php artisan analyzer:install
```

Then add to `bootstrap/app.php`:
```php
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(TrackRequest::class);
})
```

**Dashboard:** `http://your-app.test/request-log-analyzer`

---

## Publishing Assets

| Command | Publishes |
|---------|-----------|
| `vendor:publish --tag=request-log-analyzer` | ⭐ All assets (recommended) |
| `--tag=request-log-analyzer-config` | Configuration file |
| `--tag=request-log-analyzer-views` | Blade views |
| `--tag=request-log-analyzer-migrations` | Database migrations |
| `--tag=request-log-analyzer-assets` | CSS/JS files |
| `--tag=request-log-analyzer-routes` | Route definitions |

---

## Available Commands

```bash
# Install the package
php artisan analyzer:install

# Clear request logs
php artisan analyzer:clear

# Generate system report
php artisan analyzer:report

# Clean up old data
php artisan analyzer:cleanup

# Test alert system
php artisan analyzer:test-alert

# Generate API token
php artisan analyzer:generate-token
```

---

## Configuration

Edit `config/request-log-analyzer.php`:

```php
// Enable/disable
'enabled' => true,

// What to track
'track_errors' => true,
'track_queries' => true,
'track_steps' => true,
'track_login_history' => true,

// Alerts (log, email, discord)
'alerts' => [
    'enabled' => true,
    'channels' => ['log', 'email', 'discord'],
    'error_alerts' => [
        'threshold' => 10,    // Alert after 10 errors
        'window' => 5,        // Within 5 minutes
        'cooldown' => 10,     // Wait 10 min before next alert
    ],
],

// Rate limiting
'rate_limits' => [
    'enabled' => true,
    'user_requests_per_minute' => 100,
    'ip_requests_per_minute' => 500,
],
```

---

## Features

### 📊 Dashboard
- Real-time request statistics
- Error tracking and alerts
- Performance analysis
- User activity monitoring

### 🚨 Alert System
- Automatic error spike detection
- Slow request detection
- Multi-channel delivery (Log, Email, Discord)
- Configurable thresholds and cooldowns

### ⏱️ Rate Limiting
- Track requests per user/IP
- Incident tracking and reporting
- Automatic detection of limit violations

### 🧠 Intelligent Insights
- Route performance analysis
- Error trend detection
- Usage pattern anomalies
- API usage insights

### 🔄 Request Replay
- Store and review requests
- Automatic sensitive data masking
- Manual replay execution
- Execution history tracking

### 📈 Analytics
- Route hit statistics
- Geographic analytics
- User activity reports
- Login history tracking

---

## Routes

| Route | Purpose |
|-------|---------|
| `/request-log-analyzer` | Dashboard |
| `/request-log-analyzer/requests` | Request logs |
| `/request-log-analyzer/slow-requests` | Slow requests |
| `/request-log-analyzer/api-insights` | API insights |
| `/request-log-analyzer/replay` | Request replay |
| `/request-log-analyzer/analytics` | Analytics |
| `/request-log-analyzer/geo` | Geo analytics |
| `/request-log-analyzer/tools` | Tools |

---

## API Endpoints

```bash
# Get recent requests
GET /api/request-log-analyzer/requests?limit=20

# Get error statistics
GET /api/request-log-analyzer/errors/stats

# Create test replay
POST /api/request-log-analyzer/replays

# Execute replay
POST /api/request-log-analyzer/replays/{id}/execute

# Get rate limits
GET /api/request-log-analyzer/rate-limits

# Get insights
GET /api/request-log-analyzer/insights
```

---

## Troubleshooting

**Package not discovered?**
```bash
php artisan package:discover
php artisan config:clear
```

**Migrations not running?**
```bash
php artisan migrate
```

**Dashboard not working?**
- Add middleware to `bootstrap/app.php`
- Check `request-log-analyzer.enabled` is `true`
- Clear route cache: `php artisan route:clear`

**Assets not loading?**
```bash
php artisan vendor:publish --tag=request-log-analyzer-assets
```

---

## Database Tables

- `rla_requests` - HTTP requests
- `rla_errors` - Exceptions/errors
- `rla_queries` - Database queries
- `rla_steps` - Request steps
- `rla_logins` - User logins
- `rla_tags` - Request tags
- `rla_api_rate_usage` - Rate usage
- `rla_rate_limit_incidents` - Rate limit violations
- `rla_request_replays` - Stored requests
- `rla_replay_executions` - Replay history

---

## Security Features

✅ Sensitive data masking (passwords, tokens, API keys)  
✅ Safe header extraction (removes Authorization, Cookies)  
✅ Method validation (only GET/POST for replays)  
✅ Database indexes for query performance  
✅ Configurable data retention

---

## Performance

- ✅ Optimized database indexes
- ✅ Query collectors use singletons (memory-efficient)
- ✅ Configurable data retention
- ✅ Automatic cleanup scheduling
- ✅ No impact on request performance

---

## Support

- 📖 [Full Documentation](./INSTALLATION.md)
- 🧪 [Test Results](../INSTALLATION_TEST_RESULTS.md)
- 📝 [Feature Guides](./ALERTS.md)
- 💬 [GitHub Issues](https://github.com/nintis/request-log-analyzer)

---

**Version:** 1.0.0 | **Laravel:** 10-13 | **PHP:** 8.1+
