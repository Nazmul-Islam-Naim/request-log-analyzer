# Database Schema Reference

Complete MySQL database schema for Request Log Analyzer.

---

## 📋 Table Overview

| Table | Purpose | Records | Growth |
|-------|---------|---------|--------|
| `rla_requests` | HTTP requests | High | ~100-1000/hour |
| `rla_request_steps` | Lifecycle steps | Medium | ~500-5000/hour |
| `rla_queries` | Database queries | High | ~1000-10000/hour |
| `rla_errors` | Exceptions | Low | ~10-100/hour |
| `rla_user_login_histories` | Login/logout events | Medium | ~50-500/hour |
| `rla_api_rate_usage` | API rate tracking | Low | Aggregated per period |
| `rla_rate_limit_incidents` | Rate limit violations | Very Low | As violations occur |

---

## 🗄️ Complete Schema

### 1. `rla_requests` — HTTP Requests Table

**Primary table** — stores every captured HTTP request.

```sql
CREATE TABLE rla_requests (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  ulid VARCHAR(26) UNIQUE NOT NULL,
  
  -- HTTP Details
  method VARCHAR(10) NOT NULL,              -- GET, POST, PUT, DELETE, PATCH, etc.
  url LONGTEXT NOT NULL,                    -- Full URL with query string
  uri VARCHAR(1000) NOT NULL,               -- Path only (/api/users)
  query_string LONGTEXT,                    -- Raw query string (a=1&b=2)
  
  -- Client Information
  ip VARCHAR(45),                           -- IPv4 or IPv6
  user_agent LONGTEXT,                      -- Browser/client info (truncated)
  
  -- Authentication & Session
  user_id BIGINT UNSIGNED,                  -- Authenticated user ID (nullable)
  session_id VARCHAR(100),                  -- Laravel session ID
  
  -- Response Details
  status_code SMALLINT UNSIGNED NOT NULL,   -- HTTP status (200, 404, 500, etc.)
  response_time_ms INT UNSIGNED,            -- Total request time in milliseconds
  memory_usage_bytes INT UNSIGNED,          -- Peak PHP memory usage
  
  -- Geographic Data
  country VARCHAR(100),                     -- Country name from GeoIP
  city VARCHAR(100),                        -- City name from GeoIP
  
  -- JSON Storage
  request_headers JSON,                     -- HTTP request headers (masked)
  response_headers JSON,                    -- HTTP response headers
  tags JSON,                                -- Array of tags: ["payment", "admin"]
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Indexes
  INDEX idx_ulid (ulid),
  INDEX idx_method (method),
  INDEX idx_status_code (status_code),
  INDEX idx_user_id (user_id),
  INDEX idx_ip (ip),
  INDEX idx_created_at (created_at),
  INDEX idx_country (country),
  INDEX idx_requests_method_status (method, status_code),
  INDEX idx_requests_status_date (status_code, created_at),
  INDEX idx_requests_response_time (response_time_ms),
  INDEX idx_requests_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details**:

| Column | Type | Nullable | Description | Example |
|--------|------|----------|-------------|---------|
| `id` | BIGINT | ❌ | Primary key | 1, 2, 3... |
| `ulid` | VARCHAR(26) | ❌ | Sortable unique ID | `01ARZ3NDEKTSV4RRFFQ` |
| `method` | VARCHAR(10) | ❌ | HTTP verb | `GET`, `POST` |
| `url` | LONGTEXT | ❌ | Full URL | `https://app.test/api/users?page=1` |
| `uri` | VARCHAR(1000) | ❌ | Path only | `/api/users` |
| `query_string` | LONGTEXT | ✅ | Raw query | `page=1&limit=10` |
| `ip` | VARCHAR(45) | ✅ | Client IP | `192.168.1.1` |
| `user_agent` | LONGTEXT | ✅ | Browser info | `Mozilla/5.0...` |
| `user_id` | BIGINT | ✅ | Auth user | 1, 5, 10... |
| `session_id` | VARCHAR(100) | ✅ | Session ID | `abc123def456` |
| `status_code` | SMALLINT | ❌ | HTTP status | `200`, `404`, `500` |
| `response_time_ms` | INT | ✅ | Duration in ms | `150`, `5000` |
| `memory_usage_bytes` | INT | ✅ | Peak memory | `2097152` (2MB) |
| `country` | VARCHAR(100) | ✅ | GeoIP country | `United States` |
| `city` | VARCHAR(100) | ✅ | GeoIP city | `New York` |
| `request_headers` | JSON | ✅ | Request headers | `{"Accept": "application/json"}` |
| `response_headers` | JSON | ✅ | Response headers | `{"Content-Type": "application/json"}` |
| `tags` | JSON | ✅ | Custom tags | `["admin", "payment"]` |

**Sample Record**:

```json
{
  "id": 1,
  "ulid": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "method": "GET",
  "url": "https://app.test/api/users?page=1",
  "uri": "/api/users",
  "query_string": "page=1&limit=10",
  "ip": "127.0.0.1",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
  "user_id": 1,
  "session_id": "abc123def456",
  "status_code": 200,
  "response_time_ms": 145,
  "memory_usage_bytes": 4194304,
  "country": "United States",
  "city": "New York",
  "request_headers": {"Accept": "application/json", "Authorization": "[MASKED]"},
  "response_headers": {"Content-Type": "application/json", "X-Total": "100"},
  "tags": ["admin", "api"],
  "created_at": "2026-05-13 10:30:45",
  "updated_at": "2026-05-13 10:30:45"
}
```

---

### 2. `rla_request_steps` — Lifecycle Steps Table

**Timeline tracking** — records each step in request lifecycle (middleware, controller, events, etc.).

```sql
CREATE TABLE rla_request_steps (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  request_id BIGINT UNSIGNED NOT NULL,
  
  -- Step Information
  name VARCHAR(200) NOT NULL,               -- Step name (e.g., "middleware:Authenticate")
  type VARCHAR(50) NOT NULL,                -- middleware | controller | event | view | database | cache | other
  
  -- Timing (relative to request start in milliseconds)
  sequence TINYINT UNSIGNED DEFAULT 0,      -- Execution order (0, 1, 2, ...)
  started_at_ms INT UNSIGNED,               -- Offset from request start (ms)
  duration_ms INT UNSIGNED,                 -- How long step took (ms)
  
  -- Extra Data
  metadata JSON,                            -- Step-specific data
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Constraints & Indexes
  FOREIGN KEY (request_id) REFERENCES rla_requests(id) ON DELETE CASCADE,
  INDEX idx_request_id (request_id),
  INDEX idx_type (type),
  INDEX idx_steps_request_seq (request_id, sequence),
  INDEX idx_steps_request_type (request_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details**:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `request_id` | BIGINT | Parent request | 1 |
| `name` | VARCHAR(200) | Step name | `middleware:Authenticate` |
| `type` | VARCHAR(50) | Step type | `middleware`, `controller` |
| `sequence` | TINYINT | Execution order | 0, 1, 2 |
| `started_at_ms` | INT | Start time offset | 10 (10ms from request start) |
| `duration_ms` | INT | Time taken | 50 (took 50ms) |
| `metadata` | JSON | Extra info | `{"action": "auth"}` |

**Sample Record**:

```json
{
  "id": 1,
  "request_id": 1,
  "name": "middleware:Authenticate",
  "type": "middleware",
  "sequence": 0,
  "started_at_ms": 0,
  "duration_ms": 5,
  "metadata": {"status": "authenticated", "guard": "web"},
  "created_at": "2026-05-13 10:30:45"
}
```

---

### 3. `rla_queries` — Database Queries Table

**Query tracking** — logs all executed database queries.

```sql
CREATE TABLE rla_queries (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  request_id BIGINT UNSIGNED NOT NULL,
  
  -- Database Connection
  connection VARCHAR(50) DEFAULT 'mysql',   -- DB connection name
  
  -- SQL Details
  sql LONGTEXT NOT NULL,                    -- SQL with ? placeholders
  bindings JSON,                            -- Binding values: ["value1", "value2"]
  
  -- Performance
  time_ms DECIMAL(10, 3) NOT NULL,          -- Execution time (12.345 ms)
  is_slow BOOLEAN DEFAULT FALSE,            -- TRUE if exceeds slow_query_ms threshold
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Constraints & Indexes
  FOREIGN KEY (request_id) REFERENCES rla_requests(id) ON DELETE CASCADE,
  INDEX idx_request_id (request_id),
  INDEX idx_connection (connection),
  INDEX idx_time_ms (time_ms),
  INDEX idx_is_slow (is_slow),
  INDEX idx_queries_request_time (request_id, time_ms),
  INDEX idx_queries_slow_date (is_slow, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details**:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `request_id` | BIGINT | Parent request | 1 |
| `connection` | VARCHAR(50) | DB connection | `mysql`, `postgresql` |
| `sql` | LONGTEXT | SQL with placeholders | `SELECT * FROM users WHERE id = ?` |
| `bindings` | JSON | Binding values | `[42]` |
| `time_ms` | DECIMAL(10,3) | Execution time | `12.345` |
| `is_slow` | BOOLEAN | Flagged as slow | `0` or `1` |

**Sample Record**:

```json
{
  "id": 1,
  "request_id": 1,
  "connection": "mysql",
  "sql": "SELECT * FROM users WHERE id = ? LIMIT 1",
  "bindings": [1],
  "time_ms": 5.432,
  "is_slow": false,
  "created_at": "2026-05-13 10:30:45"
}
```

---

### 4. `rla_errors` — Exceptions & Errors Table

**Error logging** — captures all exceptions and errors.

```sql
CREATE TABLE rla_errors (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  request_id BIGINT UNSIGNED,               -- Nullable: errors can occur outside requests
  
  -- Exception Details
  exception_class VARCHAR(255) NOT NULL,    -- Full class name (App\Exceptions\CustomException)
  message LONGTEXT NOT NULL,                -- Exception message (may be redacted)
  file LONGTEXT NOT NULL,                   -- Source file path
  line INT UNSIGNED NOT NULL,               -- Line number
  trace LONGTEXT,                           -- Stack trace (redacted)
  
  -- Context
  context JSON,                             -- Extra context data
  
  -- Severity Level (PSR-3)
  severity ENUM('debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency')
           DEFAULT 'error',
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Constraints & Indexes
  FOREIGN KEY (request_id) REFERENCES rla_requests(id) ON DELETE SET NULL,
  INDEX idx_request_id (request_id),
  INDEX idx_exception_class (exception_class),
  INDEX idx_severity (severity),
  INDEX idx_created_at (created_at),
  INDEX idx_errors_severity_date (severity, created_at),
  INDEX idx_errors_class_severity (exception_class, severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details**:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `request_id` | BIGINT | Parent request | 1 or NULL |
| `exception_class` | VARCHAR(255) | Exception class | `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` |
| `message` | LONGTEXT | Error message | `The resource was not found` |
| `file` | LONGTEXT | File path | `/app/Http/Controllers/UserController.php` |
| `line` | INT | Line number | 42 |
| `trace` | LONGTEXT | Stack trace | `#0 UserController.php(42):...` |
| `context` | JSON | Extra data | `{"user_id": 1}` |
| `severity` | ENUM | PSR-3 level | `error`, `warning` |

**Sample Record**:

```json
{
  "id": 1,
  "request_id": 1,
  "exception_class": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException",
  "message": "The resource was not found",
  "file": "/app/Http/Controllers/UserController.php",
  "line": 42,
  "trace": "#0 /app/Http/Controllers/UserController.php(42)...",
  "context": {"user_id": 1, "resource": "user"},
  "severity": "error",
  "created_at": "2026-05-13 10:30:45"
}
```

**Severity Levels**:

| Level | Use Case | Example |
|-------|----------|---------|
| `debug` | Debugging info | Temporary debug logs |
| `info` | Informational | User created |
| `notice` | Normal but notable | Password reset |
| `warning` | Warning condition | Deprecated function used |
| `error` | Error | Exception thrown |
| `critical` | Critical problem | Database connection failed |
| `alert` | Must take action | Rate limit exceeded |
| `emergency` | System unusable | Out of memory |

---

### 5. `rla_user_login_histories` — User Login Events Table

**Authentication tracking** — records user login and logout events.

```sql
CREATE TABLE rla_user_login_histories (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- User Reference
  user_id BIGINT UNSIGNED,                  -- Authenticated user (nullable)
  
  -- Connection Details
  ip_address VARCHAR(45),                   -- IPv4 or IPv6 address
  user_agent LONGTEXT,                      -- Browser/client information
  
  -- Timestamps
  login_at TIMESTAMP,                       -- When user logged in
  logout_at TIMESTAMP,                      -- When user logged out (nullable)
  
  -- Indexes
  INDEX idx_user_id (user_id),
  INDEX idx_login_at (login_at),
  INDEX idx_login_hist_user_date (user_id, login_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details**:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `user_id` | BIGINT | User ID | 1, 5, 10 |
| `ip_address` | VARCHAR(45) | Client IP | `192.168.1.1` |
| `user_agent` | LONGTEXT | Browser info | `Mozilla/5.0...` |
| `login_at` | TIMESTAMP | Login time | `2026-05-13 10:30:45` |
| `logout_at` | TIMESTAMP | Logout time | `2026-05-13 18:30:45` or NULL |

**Sample Record**:

```json
{
  "id": 1,
  "user_id": 1,
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
  "login_at": "2026-05-13 10:30:45",
  "logout_at": "2026-05-13 18:30:45"
}
```

**Session Calculation**:
```sql
-- Calculate session duration
SELECT 
  user_id,
  login_at,
  logout_at,
  IF(logout_at IS NOT NULL, 
     TIMESTAMPDIFF(HOUR, login_at, logout_at),
     TIMESTAMPDIFF(HOUR, login_at, NOW())
  ) AS session_hours
FROM rla_user_login_histories
WHERE logout_at IS NOT NULL OR logout_at IS NULL;
```

---

### 6. `rla_api_rate_usage` — API Rate Tracking Table

**Rate limiting** — aggregated API request counts per user/IP/endpoint.

```sql
CREATE TABLE rla_api_rate_usage (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Identifier
  user_id BIGINT UNSIGNED,                  -- Authenticated user (nullable)
  ip VARCHAR(45) NOT NULL,                  -- IPv4 or IPv6
  endpoint VARCHAR(255),                    -- API endpoint (nullable)
  
  -- Usage Stats
  request_count INT UNSIGNED DEFAULT 0,     -- Number of requests in period
  first_request_at TIMESTAMP,               -- First request timestamp
  last_request_at TIMESTAMP,                -- Most recent request
  
  -- Rate Limit Status
  rate_limit_exceeded BOOLEAN DEFAULT FALSE,
  period_type VARCHAR(20) DEFAULT 'minute', -- minute | hour | day
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Indexes
  INDEX idx_user_id (user_id),
  INDEX idx_ip (ip),
  INDEX idx_user_period (user_id, period_type),
  INDEX idx_ip_period (ip, period_type),
  INDEX idx_rate_exceeded (rate_limit_exceeded, period_type),
  INDEX idx_last_request (last_request_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details**:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `user_id` | BIGINT | User ID | 1 or NULL |
| `ip` | VARCHAR(45) | Client IP | `192.168.1.1` |
| `endpoint` | VARCHAR(255) | API route | `/api/users` |
| `request_count` | INT | Requests in period | 50 |
| `first_request_at` | TIMESTAMP | Period start | `2026-05-13 10:00:00` |
| `last_request_at` | TIMESTAMP | Last request | `2026-05-13 10:59:45` |
| `rate_limit_exceeded` | BOOLEAN | Limit hit | `0` or `1` |
| `period_type` | VARCHAR(20) | Period | `minute`, `hour`, `day` |

**Sample Record**:

```json
{
  "id": 1,
  "user_id": 1,
  "ip": "127.0.0.1",
  "endpoint": "/api/users",
  "request_count": 45,
  "first_request_at": "2026-05-13 10:00:00",
  "last_request_at": "2026-05-13 10:59:45",
  "rate_limit_exceeded": false,
  "period_type": "minute",
  "created_at": "2026-05-13 10:00:00"
}
```

---

### 7. `rla_rate_limit_incidents` — Rate Limit Violations Log

**Incident tracking** — logs when rate limits are violated.

```sql
CREATE TABLE rla_rate_limit_incidents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Identifier
  user_id BIGINT UNSIGNED,                  -- Offending user (nullable)
  ip VARCHAR(45) NOT NULL,                  -- Offending IP
  endpoint VARCHAR(255),                    -- API endpoint
  
  -- Incident Details
  request_count INT UNSIGNED NOT NULL,      -- Requests made
  limit_threshold INT UNSIGNED NOT NULL,    -- Limit exceeded
  incident_type VARCHAR(50) NOT NULL,       -- user_limit | ip_limit | endpoint_limit
  
  -- Status
  resolved BOOLEAN DEFAULT FALSE,
  detected_at TIMESTAMP NOT NULL,
  cleared_at TIMESTAMP,
  notes LONGTEXT,
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Indexes
  INDEX idx_user_id (user_id),
  INDEX idx_ip (ip),
  INDEX idx_detected_at (detected_at),
  INDEX idx_incident_type_date (incident_type, detected_at),
  INDEX idx_resolved_date (resolved, detected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details**:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `user_id` | BIGINT | Offending user | 1 |
| `ip` | VARCHAR(45) | Offending IP | `192.168.1.1` |
| `endpoint` | VARCHAR(255) | API endpoint | `/api/users` |
| `request_count` | INT | Actual requests | 150 |
| `limit_threshold` | INT | Limit | 100 |
| `incident_type` | VARCHAR(50) | Type | `user_limit`, `ip_limit` |
| `resolved` | BOOLEAN | Fixed | `0` or `1` |
| `detected_at` | TIMESTAMP | When detected | `2026-05-13 10:30:45` |
| `cleared_at` | TIMESTAMP | When cleared | `2026-05-13 11:30:45` |
| `notes` | LONGTEXT | Notes | `Blocked for 1 hour` |

**Sample Record**:

```json
{
  "id": 1,
  "user_id": 1,
  "ip": "127.0.0.1",
  "endpoint": "/api/users",
  "request_count": 150,
  "limit_threshold": 100,
  "incident_type": "user_limit",
  "resolved": true,
  "detected_at": "2026-05-13 10:30:45",
  "cleared_at": "2026-05-13 11:30:45",
  "notes": "Rate limit exceeded, user blocked for 1 hour",
  "created_at": "2026-05-13 10:30:45"
}
```

---

## 🔄 Table Relationships

```
rla_requests (Parent)
    ├─ rla_request_steps (CASCADE DELETE)
    ├─ rla_queries (CASCADE DELETE)
    └─ rla_errors (SET NULL on delete)

rla_user_login_histories (Standalone)

rla_api_rate_usage (Standalone)

rla_rate_limit_incidents (Standalone)
```

---

## 📊 Database Growth Estimates

### Daily Growth (Example: 10,000 requests/day)

| Table | Records/Day | Daily Size | Monthly Size |
|-------|------------|-----------|-------------|
| `rla_requests` | 10,000 | ~50 MB | ~1.5 GB |
| `rla_request_steps` | 50,000 | ~75 MB | ~2.25 GB |
| `rla_queries` | 100,000 | ~100 MB | ~3 GB |
| `rla_errors` | 500 | ~2 MB | ~60 MB |
| `rla_user_login_histories` | 200 | ~1 MB | ~30 MB |
| `rla_api_rate_usage` | Aggregated | ~1 MB | ~30 MB |
| `rla_rate_limit_incidents` | Varies | <1 MB | ~10 MB |
| **TOTAL** | | **~230 MB** | **~6.9 GB** |

**For retention of 30 days**: ~6.9 GB
**For retention of 90 days**: ~20.7 GB

---

## 🔍 Useful Queries

### Get Request Summary

```sql
SELECT 
  DATE(created_at) as date,
  COUNT(*) as total_requests,
  AVG(response_time_ms) as avg_response_ms,
  MAX(response_time_ms) as max_response_ms,
  SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as errors
FROM rla_requests
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### Find Slow Requests

```sql
SELECT 
  id, method, uri, response_time_ms, status_code, user_id
FROM rla_requests
WHERE response_time_ms > 1000
ORDER BY response_time_ms DESC
LIMIT 20;
```

### Count Queries Per Request

```sql
SELECT 
  r.id, r.method, r.uri,
  COUNT(q.id) as query_count,
  SUM(q.time_ms) as total_query_time
FROM rla_requests r
LEFT JOIN rla_queries q ON r.id = q.request_id
GROUP BY r.id
HAVING query_count > 50
ORDER BY query_count DESC;
```

### Find Most Accessed Routes

```sql
SELECT 
  uri,
  COUNT(*) as hits,
  AVG(response_time_ms) as avg_ms,
  SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as error_count
FROM rla_requests
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY uri
ORDER BY hits DESC
LIMIT 20;
```

### Active Users Last 24 Hours

```sql
SELECT COUNT(DISTINCT user_id) as active_users
FROM rla_requests
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
  AND user_id IS NOT NULL;
```

### User Login Sessions

```sql
SELECT 
  user_id,
  COUNT(*) as login_count,
  MAX(login_at) as last_login,
  AVG(TIMESTAMPDIFF(HOUR, login_at, logout_at)) as avg_session_hours
FROM rla_user_login_histories
WHERE logout_at IS NOT NULL
GROUP BY user_id
ORDER BY last_login DESC;
```

### Find Rate Limit Violations

```sql
SELECT 
  user_id, ip, endpoint,
  COUNT(*) as violation_count,
  MAX(detected_at) as most_recent
FROM rla_rate_limit_incidents
WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND resolved = FALSE
GROUP BY user_id, ip, endpoint
ORDER BY violation_count DESC;
```

---

## 🔐 Indexes Summary

| Table | Index Name | Columns | Purpose |
|-------|-----------|---------|---------|
| rla_requests | idx_requests_status_date | (status_code, created_at) | Error rate queries |
| rla_requests | idx_requests_response_time | (response_time_ms) | Slow request detection |
| rla_requests | idx_requests_user_created | (user_id, created_at) | Active users queries |
| rla_requests | idx_requests_country | (country) | Geographic grouping |
| rla_queries | idx_queries_slow_date | (is_slow, created_at) | Slow query filtering |
| rla_errors | idx_errors_severity_date | (severity, created_at) | Error filtering |
| rla_user_login_histories | idx_login_hist_user_date | (user_id, login_at) | User session lookup |

---

## ⚠️ Performance Tips

1. **Archive old data** — Use `php artisan analyzer:clear --older-than=30 --force`
2. **Monitor indexes** — Run `ANALYZE TABLE` monthly
3. **Vacuum tables** — Run `OPTIMIZE TABLE` monthly for InnoDB
4. **Monitor growth** — Check `information_schema.TABLES` for size
5. **Use pagination** — Always limit results in queries
6. **Add WHERE clauses** — Use `created_at` to narrow result sets

---

## 🗑️ Cleanup Queries

### Delete Old Requests (Older than 30 days)

```sql
DELETE FROM rla_requests 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Cascades to: rla_request_steps, rla_queries, rla_errors
```

### Delete Rate Limit Incidents (Resolved and older than 7 days)

```sql
DELETE FROM rla_rate_limit_incidents
WHERE resolved = TRUE 
  AND detected_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Clear All Data

```sql
TRUNCATE TABLE rla_request_steps;
TRUNCATE TABLE rla_queries;
TRUNCATE TABLE rla_errors;
TRUNCATE TABLE rla_requests;
TRUNCATE TABLE rla_user_login_histories;
TRUNCATE TABLE rla_api_rate_usage;
TRUNCATE TABLE rla_rate_limit_incidents;
```

---

## 📈 Scaling Recommendations

### For High Traffic (>1M requests/day)

1. **Archive strategy** — Keep only 7-14 days of data
2. **Sharding** — Consider sharding by `user_id` or date ranges
3. **Separate database** — Use dedicated DB for analytics
4. **Caching** — Cache aggregated stats in Redis
5. **Partitioning** — Use table partitioning by date

### For High Volume of Queries

1. **Increase slow_query_ms** — Don't log all queries, just slow ones
2. **Sample requests** — Use `REQUEST_LOG_ANALYZER_SAMPLE_RATE=10`
3. **Async logging** — Enable async to prevent request blocking
4. **Query optimization** — Add strategic indexes

---

**Database Version**: MySQL 5.7+, 8.0+  
**Character Set**: UTF-8 MB4  
**Collation**: utf8mb4_unicode_ci

