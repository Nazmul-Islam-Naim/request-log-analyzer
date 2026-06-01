# Alert System Documentation

## Overview

The RequestLogAnalyzer includes a configurable alert system that monitors your application for two critical conditions:

1. **Too many errors** — Alert when error count (5xx responses) exceeds a threshold within a time window
2. **Too many slow requests** — Alert when slow request count exceeds a threshold within a time window

Alerts are sent via configurable channels (log file and/or email) with built-in cooldown periods to prevent alert fatigue.

---

## Quick Start

### Enable Alerts

Add to your `.env` file:

```env
REQUEST_LOG_ANALYZER_ALERTS_ENABLED=true
REQUEST_LOG_ANALYZER_ALERTS_CHANNELS=log,email,discord
REQUEST_LOG_ANALYZER_ERROR_ALERTS_THRESHOLD=10
REQUEST_LOG_ANALYZER_ERROR_ALERTS_WINDOW=5
REQUEST_LOG_ANALYZER_SLOW_ALERTS_THRESHOLD=5
REQUEST_LOG_ANALYZER_SLOW_ALERTS_WINDOW=5
REQUEST_LOG_ANALYZER_ALERTS_EMAIL=admin@example.com
REQUEST_LOG_ANALYZER_DISCORD_WEBHOOK=https://discordapp.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
```

### Getting Your Discord Webhook URL

1. Open your **Discord Server**
2. Go to **Server Settings** → **Integrations** → **Webhooks**
3. Click **New Webhook** (or select existing)
4. Give it a name (e.g., "Request Log Alerts")
5. Select the target **Channel**
6. Click **Copy Webhook URL**
7. Paste into `.env` as `REQUEST_LOG_ANALYZER_DISCORD_WEBHOOK`

### Configuration Structure

```php
'alerts' => [
    'enabled' => env('REQUEST_LOG_ANALYZER_ALERTS_ENABLED', true),
    'channels' => explode(',', env('REQUEST_LOG_ANALYZER_ALERTS_CHANNELS', 'log')),

    'error_alerts' => [
        'enabled' => env('REQUEST_LOG_ANALYZER_ERROR_ALERTS_ENABLED', true),
        'threshold' => (int) env('REQUEST_LOG_ANALYZER_ERROR_ALERTS_THRESHOLD', 10),
        'time_window_minutes' => (int) env('REQUEST_LOG_ANALYZER_ERROR_ALERTS_WINDOW', 5),
        'cooldown_minutes' => (int) env('REQUEST_LOG_ANALYZER_ERROR_ALERTS_COOLDOWN', 10),
    ],

    'slow_request_alerts' => [
        'enabled' => env('REQUEST_LOG_ANALYZER_SLOW_ALERTS_ENABLED', true),
        'threshold' => (int) env('REQUEST_LOG_ANALYZER_SLOW_ALERTS_THRESHOLD', 5),
        'time_window_minutes' => (int) env('REQUEST_LOG_ANALYZER_SLOW_ALERTS_WINDOW', 5),
        'cooldown_minutes' => (int) env('REQUEST_LOG_ANALYZER_SLOW_ALERTS_COOLDOWN', 10),
    ],

    'email_config' => [
        'from' => env('REQUEST_LOG_ANALYZER_ALERTS_FROM', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
        'to' => explode(',', env('REQUEST_LOG_ANALYZER_ALERTS_TO', '')),
        'subject' => 'Request Log Alert: {type}',
    ],

        'discord_config' => [
            'webhook_url' => env('REQUEST_LOG_ANALYZER_DISCORD_WEBHOOK', ''),
            'username' => env('REQUEST_LOG_ANALYZER_DISCORD_USERNAME', 'Request Log Analyzer'),
            'avatar_url' => env('REQUEST_LOG_ANALYZER_DISCORD_AVATAR', ''),
            'color_errors' => env('REQUEST_LOG_ANALYZER_DISCORD_COLOR_ERRORS', '15158332'),
            'color_slow' => env('REQUEST_LOG_ANALYZER_DISCORD_COLOR_SLOW', '16776960'),
        ],
## Configuration Reference

### Master Alert Switch

**`alerts.enabled`** — Enable/disable the entire alert system

```env
REQUEST_LOG_ANALYZER_ALERTS_ENABLED=true
```

Default: `true`

### Alert Channels

**`alerts.channels`** — How alerts are delivered (comma-separated list)

```env
REQUEST_LOG_ANALYZER_ALERTS_CHANNELS=log,email,discord
```

Supported channels:
- **`log`** — Write alerts to Laravel's log file (default)
- **`email`** — Send alerts via email (requires email configuration)
- **`discord`** — Send alerts to Discord channel (requires webhook URL)

Default: `log`

---

## Error Alerts

Triggers when **5xx error responses** exceed the threshold within the time window.

### Configuration

**`error_alerts.enabled`** — Enable/disable error alerts

```env
REQUEST_LOG_ANALYZER_ERROR_ALERTS_ENABLED=true
```

Default: `true`

**`error_alerts.threshold`** — Number of errors to trigger an alert

```env
REQUEST_LOG_ANALYZER_ERROR_ALERTS_THRESHOLD=10
```

Example: With threshold of 10, an alert triggers when 10+ errors occur.

Default: `10`

**`error_alerts.time_window_minutes`** — Measure errors within this time window

```env
REQUEST_LOG_ANALYZER_ERROR_ALERTS_WINDOW=5
```

Example: With window of 5 minutes, the system counts errors from the last 5 minutes.

Default: `5` minutes

**`error_alerts.cooldown_minutes`** — Wait before sending another error alert

```env
REQUEST_LOG_ANALYZER_ERROR_ALERTS_COOLDOWN=10
```

Example: Once an error alert is sent, wait 10 minutes before sending another one.

Default: `10` minutes

### Example

With these settings:
- Threshold: 10 errors
- Window: 5 minutes
- Cooldown: 10 minutes

The system will:
1. Count errors (5xx) from the last 5 minutes
2. If count ≥ 10, send an alert
3. Wait 10 minutes before checking again

---

## Slow Request Alerts

Triggers when **slow requests** (response time > threshold) exceed the threshold within the time window.

### Configuration

**`slow_request_alerts.enabled`** — Enable/disable slow request alerts

```env
REQUEST_LOG_ANALYZER_SLOW_ALERTS_ENABLED=true
```

Default: `true`

**`slow_request_alerts.threshold`** — Number of slow requests to trigger an alert

```env
REQUEST_LOG_ANALYZER_SLOW_ALERTS_THRESHOLD=5
```

Example: With threshold of 5, an alert triggers when 5+ slow requests occur.

Default: `5`

**`slow_request_alerts.time_window_minutes`** — Measure slow requests within this time window

```env
REQUEST_LOG_ANALYZER_SLOW_ALERTS_WINDOW=5
```

Example: With window of 5 minutes, the system counts slow requests from the last 5 minutes.

Default: `5` minutes

**`slow_request_alerts.cooldown_minutes`** — Wait before sending another slow request alert

```env
REQUEST_LOG_ANALYZER_SLOW_ALERTS_COOLDOWN=10
```

Example: Once a slow request alert is sent, wait 10 minutes before sending another one.

Default: `10` minutes

### Relationship to `slow_request_threshold_ms`

The "slow request" threshold is derived from the main package configuration:

```php
'slow_request_threshold_ms' => (int) env('REQUEST_LOG_ANALYZER_SLOW_THRESHOLD_MS', 500),
```

Requests exceeding this response time are considered "slow" and counted in the alert check.

---

## Email Configuration

### Recipients

**`email_config.to`** — Email address(es) to receive alerts

```env
REQUEST_LOG_ANALYZER_ALERTS_TO=admin@example.com,ops@example.com
```

Multiple recipients: comma-separated list (no spaces)

Default: empty (email alerts disabled)

### Sender Address

**`email_config.from`** — Email address of the alert sender

```env
REQUEST_LOG_ANALYZER_ALERTS_FROM=alerts@example.com
```

Falls back to `MAIL_FROM_ADDRESS` if not set.

Default: `noreply@example.com`

### Email Subject

**`email_config.subject`** — Email subject template

```php
'subject' => 'Request Log Alert: {type}',
```

Template variables:
- `{type}` — Alert type (e.g., "Errors" or "Slow Requests")

Default: `Request Log Alert: {type}`

---

## Discord Configuration

### Webhook URL

**`discord_config.webhook_url`** — Discord webhook URL for sending alerts

```env
REQUEST_LOG_ANALYZER_DISCORD_WEBHOOK=https://discordapp.com/api/webhooks/CHANNEL_ID/WEBHOOK_TOKEN
```

How to get your webhook URL:
1. Open your Discord server
2. Go to **Server Settings** → **Webhooks** (or **Channel Settings** → **Integrations** → **Webhooks**)
3. Click **Create Webhook** or select existing webhook
4. Click **Copy Webhook URL**
5. Set the URL in your `.env` file

Default: empty (Discord alerts disabled)

### Webhook Username

**`discord_config.username`** — Name displayed for webhook messages

```env
REQUEST_LOG_ANALYZER_DISCORD_USERNAME=Request Log Analyzer
```

Default: `Request Log Analyzer`

### Avatar URL

**`discord_config.avatar_url`** — Optional avatar image URL for webhook

```env
REQUEST_LOG_ANALYZER_DISCORD_AVATAR=https://example.com/avatar.png
```

Default: empty (Discord default)

### Embed Colors

**`discord_config.color_errors`** — Color for error alerts (decimal RGB value)

```env
REQUEST_LOG_ANALYZER_DISCORD_COLOR_ERRORS=15158332
```

This is red (`0xFF6B6B` or `15158332` in decimal). Discord uses decimal RGB format.

Common colors:
- Red (errors): `15158332` or `0xFF6B6B`
- Yellow (slow): `16776960` or `0xFFFF00`
- Green: `3066993` or `0x2ECC71`
- Blue: `3447003` or `0x3498DB`

Default: `15158332` (red)

**`discord_config.color_slow`** — Color for slow request alerts

```env
REQUEST_LOG_ANALYZER_DISCORD_COLOR_SLOW=16776960
```

Default: `16776960` (yellow)

---

## How It Works

### Alert Flow

1. **Request is logged** — After every tracked request, the middleware captures the data
2. **Thresholds are checked** — `AlertChecker` queries the database for errors/slow requests in the time window
3. **Cooldown verified** — System checks if enough time has passed since the last alert
4. **Conditions met?** — If threshold exceeded AND cooldown passed, alert is triggered
5. **Notifications sent** — `AlertNotifier` sends via all configured channels
6. **State updated** — Alert timestamp is cached to respect cooldown periods

### Cooldown Mechanism

The system uses Laravel's cache to track the last time each alert was sent. This prevents alert spam when thresholds remain exceeded.

Example:
- Error alert fires at 10:00 AM
- Cooldown: 10 minutes
- System won't send another error alert until 10:10 AM, even if threshold remains exceeded

---

## Output Examples

### Log File Alert

```
[2026-05-12 14:32:10] production.WARNING: RLA Alert: Errors
Array
(
    [type] => errors
    [count] => 12
    [threshold] => 10
    [time_window] => 5
    [message] => Alert: 12 errors detected in the last 5 minutes (threshold: 10)
)
```

### Email Alert

Subject: `Request Log Alert: Errors`

Body:
```
🚨 Errors Alert

Alert: 12 errors detected in the last 5 minutes (threshold: 10)

Alert Type: Errors
Count: 12
Threshold: 10
Time Window: 5 minute(s)
Timestamp: 2026-05-12 14:32:10 UTC
```

### Discord Alert

A formatted embed sent to your Discord channel:

**Title:** 🚨 Errors Alert

**Description:** Alert: 12 errors detected in the last 5 minutes (threshold: 10)

**Fields:**
- Alert Type: Errors
- Count: 12
- Threshold: 10
- Time Window: 5 minute(s)

**Color:** Red (for errors) / Yellow (for slow requests)

**Footer:** Request Log Analyzer - Your App Name

---

## Best Practices

### 1. Tuning Thresholds

Start conservative and adjust based on your application:

- **Development**: Lower thresholds (5 errors, 5 slow requests)
- **Production**: Higher thresholds (20+ errors, 10+ slow requests)

### 2. Appropriate Channels

- **`log`** — Always enable for audit trail
- **`email`** — Enable for critical systems, disable for high-traffic apps

### 3. Cooldown Strategy

Set cooldown >= time window to avoid overlapping alerts:

```
Recommended: cooldown_minutes = time_window_minutes * 2
```

Example:
- Time window: 5 minutes
- Cooldown: 10 minutes

### 4. Monitor Alert Performance

Alerts are wrapped in try-catch to prevent exceptions from affecting request logging:

```
RLA Alert system error: ...
```

These errors appear in the log but don't disrupt the main logger.

### 5. Email Provider Setup

Ensure your mail driver is configured and working:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=alerts@example.com
```

---

## Troubleshooting

### Alerts Not Sending

**Check 1: Is the system enabled?**

```env
REQUEST_LOG_ANALYZER_ALERTS_ENABLED=true
```

**Check 2: Are channels configured?**

```env
REQUEST_LOG_ANALYZER_ALERTS_CHANNELS=log,email,discord
```

**Check 3: Email recipients configured?**

```env
REQUEST_LOG_ANALYZER_ALERTS_TO=admin@example.com
```

**Check 4: Mail driver working?**

Test mail delivery:
```bash
php artisan tinker
Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

**Check 5: Discord webhook URL configured?**

```env
REQUEST_LOG_ANALYZER_DISCORD_WEBHOOK=https://discordapp.com/api/webhooks/...
```

Test Discord webhook manually:
```bash
curl -X POST https://discordapp.com/api/webhooks/YOUR_ID/YOUR_TOKEN \
  -H "Content-Type: application/json" \
  -d '{"content": "Test message"}'
```

### Discord Alerts Not Working

**Issue: "webhook URL not configured"**

Ensure the webhook URL is set in `.env`:
```env
REQUEST_LOG_ANALYZER_DISCORD_WEBHOOK=https://discordapp.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
```

**Issue: "HTTP 401" or "Invalid webhook"**

The webhook URL has expired or is invalid. Create a new one:
1. Discord Server → Server Settings → Webhooks
2. Delete the old webhook
3. Create a new webhook
4. Copy the URL and update `.env`

**Issue: "HTTP 429" (rate limited)**

Discord is rate-limiting webhook requests. Increase cooldown:
```env
REQUEST_LOG_ANALYZER_ERROR_ALERTS_COOLDOWN=30
REQUEST_LOG_ANALYZER_SLOW_ALERTS_COOLDOWN=30
```

**Issue: Webhook doesn't have permission**

Check that the webhook has "Send Messages" permission in the target channel.

### Alerts Firing Too Often

**Increase cooldown:**

```env
REQUEST_LOG_ANALYZER_ERROR_ALERTS_COOLDOWN=30
REQUEST_LOG_ANALYZER_SLOW_ALERTS_COOLDOWN=30
```

### Alerts Never Firing

**Lower thresholds:**

```env
REQUEST_LOG_ANALYZER_ERROR_ALERTS_THRESHOLD=5
REQUEST_LOG_ANALYZER_SLOW_ALERTS_THRESHOLD=3
```

**Verify errors/slow requests are being logged:**

Dashboard → Request List → Filter by status code (5xx) or response time

---

## Architecture

### Components

1. **AlertChecker** (`Services\AlertChecker`)
   - Queries database for error/slow request counts
   - Checks thresholds and cooldown periods
   - Returns alert data if conditions met

2. **AlertNotifier** (`Services\AlertNotifier`)
   - Sends alerts via log and/or email
   - Builds formatted HTML email body
   - Records alert timestamp to cache

3. **AlertRepository** (`Repositories\AlertRepository`)
   - Manages alert state in cache
   - Tracks last send time per alert type
   - 24-hour cache retention

### Integration

Alert checking runs **after request data is persisted** in `TrackRequest` middleware:

- **Sync logging** → Alerts checked after DB insert
- **Async logging** → Alerts checked after job dispatch

Both paths respect the same thresholds and cooldowns.

---

## API Reference

### AlertChecker

```php
$checker = app('NIN\RequestLogAnalyzer\Services\AlertChecker');

// Returns alert data or null
$errorAlert = $checker->checkErrorAlert();
$slowAlert = $checker->checkSlowAlert();
```

### AlertNotifier

```php
$notifier = app('NIN\RequestLogAnalyzer\Services\AlertNotifier');

$notifier->sendErrorAlert($alertData);
$notifier->sendSlowAlert($alertData);
```

### AlertRepository

```php
$repo = app('NIN\RequestLogAnalyzer\Contracts\AlertRepositoryInterface');

$lastTime = $repo->getLastErrorAlertTime();  // Unix timestamp or null
$lastTime = $repo->getLastSlowAlertTime();   // Unix timestamp or null

$repo->recordErrorAlert();  // Record that alert was sent
$repo->recordSlowAlert();   // Record that alert was sent
```

---

## Environment Variables Summary

```env
# Master control
REQUEST_LOG_ANALYZER_ALERTS_ENABLED=true
REQUEST_LOG_ANALYZER_ALERTS_CHANNELS=log,email,discord

# Error alerts
REQUEST_LOG_ANALYZER_ERROR_ALERTS_ENABLED=true
REQUEST_LOG_ANALYZER_ERROR_ALERTS_THRESHOLD=10
REQUEST_LOG_ANALYZER_ERROR_ALERTS_WINDOW=5
REQUEST_LOG_ANALYZER_ERROR_ALERTS_COOLDOWN=10

# Slow request alerts
REQUEST_LOG_ANALYZER_SLOW_ALERTS_ENABLED=true
REQUEST_LOG_ANALYZER_SLOW_ALERTS_THRESHOLD=5
REQUEST_LOG_ANALYZER_SLOW_ALERTS_WINDOW=5
REQUEST_LOG_ANALYZER_SLOW_ALERTS_COOLDOWN=10

# Email configuration
REQUEST_LOG_ANALYZER_ALERTS_FROM=alerts@example.com
REQUEST_LOG_ANALYZER_ALERTS_TO=admin@example.com,ops@example.com

# Discord configuration
REQUEST_LOG_ANALYZER_DISCORD_WEBHOOK=https://discordapp.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
REQUEST_LOG_ANALYZER_DISCORD_USERNAME=Request Log Analyzer
REQUEST_LOG_ANALYZER_DISCORD_AVATAR=https://example.com/avatar.png
REQUEST_LOG_ANALYZER_DISCORD_COLOR_ERRORS=15158332
REQUEST_LOG_ANALYZER_DISCORD_COLOR_SLOW=16776960
```
