# API Rate Usage Tracking

Track and monitor API usage patterns per user and per IP address. Detect rate limit violations and receive automated alerts.

## Features

- **Per-User Tracking**: Monitor requests from authenticated users
- **Per-IP Tracking**: Monitor requests from IP addresses  
- **Configurable Thresholds**: Set different limits for users and IPs
- **Incident Recording**: Automatic detection and logging of limit violations
- **Alert Integration**: Send alerts via log, email, or Discord when limits exceeded
- **Dashboard Widgets**: Display top users, suspicious IPs, and active incidents
- **Cleanup Jobs**: Automatic removal of old data for storage optimization

## Installation

The rate usage system is automatically enabled when the package is installed. Run migrations to create tables:

```bash
php artisan migrate
```

This creates:
- `rla_api_rate_usage` - tracks user/IP request counts
- `rla_rate_limit_incidents` - records limit violations

## Configuration

All settings in `.env`:

```env
# Master switch
REQUEST_LOG_ANALYZER_RATE_LIMITS_ENABLED=true

# Per-user limits
REQUEST_LOG_ANALYZER_RATE_LIMITS_USER_ENABLED=true
REQUEST_LOG_ANALYZER_RATE_LIMITS_USER_THRESHOLD=100

# Per-IP limits
REQUEST_LOG_ANALYZER_RATE_LIMITS_IP_ENABLED=true
REQUEST_LOG_ANALYZER_RATE_LIMITS_IP_THRESHOLD=500

# Alert channels for rate limit violations
REQUEST_LOG_ANALYZER_RATE_LIMITS_CHANNELS=log,email,discord

# Data retention (days)
REQUEST_LOG_ANALYZER_RATE_LIMITS_RETENTION=30
```

Configure in `config/request-log-analyzer.php`:

```php
'rate_limits' => [
    'enabled' => env('REQUEST_LOG_ANALYZER_RATE_LIMITS_ENABLED', true),
    'channels' => explode(',', env('REQUEST_LOG_ANALYZER_RATE_LIMITS_CHANNELS', 'log')),

    'users' => [
        'enabled' => env('REQUEST_LOG_ANALYZER_RATE_LIMITS_USER_ENABLED', true),
        'threshold' => (int) env('REQUEST_LOG_ANALYZER_RATE_LIMITS_USER_THRESHOLD', 100),
    ],

    'ips' => [
        'enabled' => env('REQUEST_LOG_ANALYZER_RATE_LIMITS_IP_ENABLED', true),
        'threshold' => (int) env('REQUEST_LOG_ANALYZER_RATE_LIMITS_IP_THRESHOLD', 500),
    ],

    'retention_days' => (int) env('REQUEST_LOG_ANALYZER_RATE_LIMITS_RETENTION', 30),
],
```

## How It Works

### Request Tracking

When a request is processed, the middleware automatically records it:

```
Request → TrackRequest Middleware → recordRequest()
          → Rate Usage Recorded
          → Thresholds Checked
          → Alerts Sent (if exceeded)
```

### Threshold Detection

The system checks thresholds using a **sliding window**:

```
Per-User:  Last 60 seconds = 100+ requests → Alert
Per-IP:    Last 60 seconds = 500+ requests → Alert
```

Configurable via `.env`:

```env
REQUEST_LOG_ANALYZER_RATE_LIMITS_USER_THRESHOLD=100
REQUEST_LOG_ANALYZER_RATE_LIMITS_IP_THRESHOLD=500
```

### Incident Recording

When a limit is exceeded, an incident is automatically created:

```php
$incident = [
    'user_id' => $userId,
    'ip' => $ip,
    'endpoint' => '/api/endpoint',
    'request_count' => 150,
    'limit_threshold' => 100,
    'incident_type' => 'user',  // or 'ip'
    'detected_at' => now(),
    'resolved' => false,
];
```

### Alerts

Rate limit violations can trigger alerts via:

- **Log**: Written to `storage/logs/laravel.log`
- **Email**: Sent to configured recipients
- **Discord**: Posted to webhook

Configure channels:

```env
# Single channel
REQUEST_LOG_ANALYZER_RATE_LIMITS_CHANNELS=log

# Multiple channels (comma-separated)
REQUEST_LOG_ANALYZER_RATE_LIMITS_CHANNELS=log,email,discord
```

## Dashboard Integration

Query rate usage data for dashboard widgets:

### Top Users by Request Count

```php
use NIN\RequestLogAnalyzer\Repositories\RateUsageRepository;

$repo = app(RateUsageRepository::class);

$topUsers = $repo->topUsersByRequests(10, 'minute');

foreach ($topUsers as $record) {
    echo $record->user_id . ": " . $record->request_count . " requests\n";
}
```

### Suspicious IPs

```php
$suspiciousIps = $repo->suspiciousIps(10, 'minute');

foreach ($suspiciousIps as $record) {
    echo $record->ip . ": " . $record->request_count . " requests\n";
    echo "Exceeded: " . ($record->rate_limit_exceeded ? 'Yes' : 'No') . "\n";
}
```

### Active Incidents

```php
use NIN\RequestLogAnalyzer\Models\RateLimitIncident;

// Unresolved incidents (last 7 days)
$incidents = RateLimitIncident::unresolved()
    ->recent()
    ->orderByDesc('detected_at')
    ->limit(20)
    ->get();

foreach ($incidents as $incident) {
    echo "Type: " . $incident->incident_type . "\n";
    echo "Detected: " . $incident->detected_at->diffForHumans() . "\n";
    echo "Requests: " . $incident->request_count . " / " . $incident->limit_threshold . "\n";
}
```

### Current Usage vs Limits

```php
$limiter = app(\NIN\RequestLogAnalyzer\Services\RateLimiterService::class);

$status = $limiter->getStatus(auth()->id(), request()->ip());

echo "User Rate: " . $status['user_percentage'] . "%\n";
echo "IP Rate: " . $status['ip_percentage'] . "%\n";
```

## Models

### ApiRateUsage

Track requests per user and per IP:

```php
use NIN\RequestLogAnalyzer\Models\ApiRateUsage;

// Recent usage
$usage = ApiRateUsage::recent()->first();

// Get duration and rate
$seconds = $usage->getDurationInSeconds();
$rate = $usage->getRequestsPerSecond();

// Check if recently active
if ($usage->isRecentlyActive()) {
    // Handle active user
}
```

### RateLimitIncident

View and manage incidents:

```php
use NIN\RequestLogAnalyzer\Models\RateLimitIncident;

// Unresolved incidents
$incidents = RateLimitIncident::unresolved()->get();

// Mark as resolved
$incident->markResolved();

// Calculate stats
$excess = $incident->getExcessPercentage();  // 150% of limit
$duration = $incident->getDurationInMinutes();
```

## Advanced Configuration

### Custom Period Types

The system supports multiple period types:

```php
// Track per minute
$repo->recordRequest($userId, $ip, $endpoint, 'minute');

// Track per hour
$repo->recordRequest($userId, $ip, $endpoint, 'hour');

// Track per day
$repo->recordRequest($userId, $ip, $endpoint, 'day');
```

### Endpoint-Specific Limits

To track specific endpoints:

```php
$limiter->checkEndpointLimit('/api/auth/login', $ip, $threshold);
```

### Cleanup

Automatically clean old data:

```bash
php artisan analyzer:cleanup
```

Retains data older than `retention_days` setting:

```env
REQUEST_LOG_ANALYZER_RATE_LIMITS_RETENTION=30
```

## Troubleshooting

### Rate Limits Not Triggering

**Check configuration:**
```bash
php artisan config:show request-log-analyzer
```

**Verify tables exist:**
```bash
php artisan tinker
>>> DB::table('rla_api_rate_usage')->count()
```

**Enable debug logging:**
```env
APP_DEBUG=true
```

### High Memory Usage

Lower retention and increase cleanup frequency:

```env
REQUEST_LOG_ANALYZER_RATE_LIMITS_RETENTION=7
```

### Incidents Not Recording

Ensure migration ran:

```bash
php artisan migrate --path=packages/NIN/RequestLogAnalyzer/database/migrations
```

## API Reference

### RateUsageRepository

```php
interface RateUsageRepositoryInterface {
    // Record a request
    public function recordRequest(?int $userId, string $ip, string $endpoint, string $periodType = 'minute'): void;
    
    // Get usage stats
    public function getUserRateUsage(?int $userId, string $periodType = 'minute'): ?ApiRateUsage;
    public function getIpRateUsage(string $ip, string $periodType = 'minute'): ?ApiRateUsage;
    
    // Dashboard data
    public function topUsersByRequests(int $limit = 10, string $periodType = 'minute'): Collection;
    public function suspiciousIps(int $limit = 10, string $periodType = 'minute'): Collection;
    
    // Incidents
    public function createIncident(array $data): RateLimitIncident;
    public function getUnresolvedIncidents(?int $limit = null): Collection;
    public function getRecentIncidents(?int $limit = null): Collection;
    
    // Cleanup
    public function cleanup(int $retentionDays = 30): int;
}
```

### RateLimiterService

```php
class RateLimiterService {
    public function checkUserRateLimit(?int $userId, string $periodType = 'minute'): ?array;
    public function checkIpRateLimit(string $ip, string $periodType = 'minute'): ?array;
    public function checkAllLimits(?int $userId, string $ip, string $periodType = 'minute'): array;
    public function getStatus(?int $userId, string $ip): array;
}
```

## Examples

### Example 1: Monitor API Endpoint Abuse

```php
// In dashboard controller
public function rateLimitStats()
{
    $repo = app(\NIN\RequestLogAnalyzer\Repositories\RateUsageRepository::class);
    
    return [
        'top_users' => $repo->topUsersByRequests(5),
        'suspicious_ips' => $repo->suspiciousIps(5),
        'incidents' => RateLimitIncident::unresolved()
            ->recent()
            ->with('user')
            ->orderByDesc('detected_at')
            ->limit(10)
            ->get(),
    ];
}
```

### Example 2: Stricter Limits for Development

```env
REQUEST_LOG_ANALYZER_RATE_LIMITS_USER_THRESHOLD=50
REQUEST_LOG_ANALYZER_RATE_LIMITS_IP_THRESHOLD=250
REQUEST_LOG_ANALYZER_RATE_LIMITS_CHANNELS=log,discord
```

### Example 3: Manual Incident Resolution

```php
$incident = RateLimitIncident::findOrFail($id);

// Investigate...

// Mark resolved
$incident->update([
    'resolved' => true,
    'cleared_at' => now(),
    'notes' => 'Legitimate traffic spike during campaign',
]);
```

## Performance Considerations

- **Queries per Request**: 2-3 additional queries (configurable via caching)
- **Storage**: ~50-100 bytes per tracked request
- **Cleanup**: Recommended weekly for 30-day retention
- **Memory**: Minimal; uses database for storage

Optimize with:

```env
REQUEST_LOG_ANALYZER_RATE_LIMITS_RETENTION=7  # Shorter retention = less data
REQUEST_LOG_ANALYZER_RATE_LIMITS_ENABLED=false  # Disable if not needed
```
