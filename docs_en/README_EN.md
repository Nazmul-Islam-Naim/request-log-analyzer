# Request Log Analyzer - Complete English Documentation

**Version**: 2.0  
**Language**: English  
**Last Updated**: May 2026

---

## 📋 Table of Contents

1. [What is This?](#what-is-this)
2. [Why You Need It](#why-you-need-it)
3. [Quick Start](#quick-start)
4. [Features Overview](#features-overview)
5. [Learning Guide](#learning-guide)
6. [Configuration Quick Reference](#configuration-quick-reference)
7. [Key Metrics](#key-metrics)
8. [Best Practices](#best-practices)
9. [Support Resources](#support-resources)

---

## What is This?

**Request Log Analyzer** is a Laravel package that automatically captures and analyzes:

✅ **HTTP Requests** - Every request: method, URL, status, response time  
✅ **Database Queries** - All SQL queries with timing and performance data  
✅ **Errors & Exceptions** - Complete stack traces and context  
✅ **Performance Metrics** - Response times, slow queries, bottlenecks  
✅ **User Activity** - Login/logout history, active users, location  
✅ **Security Data** - IP addresses, user agents, geographic origin  

All presented in a beautiful, interactive web dashboard.

---

## Why You Need It

### Problem: "My App is Slow"

**Without This Package**: 
- "Which endpoint?"
- "Which database query?"
- "Is it a code issue or database?"
- Days of debugging...

**With This Package**:
- Dashboard shows slowest endpoints instantly
- Each query timing is logged
- See exactly where time is spent
- Fix in minutes instead of days

### Problem: "Users Report Errors I Don't See"

**Without**: Dig through logs, hope for stack trace  
**With**: Complete error details, exact request context, user info

### Problem: "Who is Accessing My App?"

**Without**: No idea who, when, or from where  
**With**: Login history, active users, geographic breakdown

### Problem: "Am I Storing Sensitive Data Safely?"

**Without**: Hope you're not exposing passwords or tokens  
**With**: Automatic masking of passwords, tokens, credit cards

---

## Quick Start

### Step 1: Install (5 seconds)

```bash
php artisan analyzer:install
```

### Step 2: Access Dashboard (5 seconds)

```
http://your-app.test/request-log-analyzer
```

### Step 3: Start Using (immediately)

Dashboard works! Data is already being collected.

### That's It! 🎉

---

## Features Overview

### 1. HTTP Request Tracking

**What**: Every HTTP request logged  
**Captures**: Method, URL, status code, response time, user  
**Use**: Find slow endpoints, track traffic  
**Config**: `REQUEST_LOG_ANALYZER_ENABLED=true`

### 2. Database Query Logging

**What**: Every SQL query tracked  
**Captures**: Query, parameters, execution time, origin  
**Use**: Find slow queries, optimize database  
**Config**: `REQUEST_LOG_ANALYZER_TRACK_QUERIES=true`

### 3. Error Tracking

**What**: All exceptions and errors caught  
**Captures**: Message, stack trace, context, user  
**Use**: Debug issues, spot patterns, alert on new errors  
**Config**: `REQUEST_LOG_ANALYZER_TRACK_ERRORS=true`

### 4. Performance Analytics

**What**: Automatic performance analysis  
**Captures**: Response time trends, route performance, bottlenecks  
**Use**: Identify optimization opportunities  
**Config**: `REQUEST_LOG_ANALYZER_SLOW_MS=500`

### 5. GeoIP Tracking

**What**: User location detection  
**Captures**: Country, city, coordinates  
**Use**: Understand user geography, detect fraud  
**Config**: `REQUEST_LOG_ANALYZER_TRACK_GEO=true`

### 6. Login Tracking

**What**: User login/logout history  
**Captures**: User, time, IP, device  
**Use**: Monitor user activity, detect anomalies  
**Config**: `REQUEST_LOG_ANALYZER_TRACK_LOGIN_HISTORY=true`

### 7. Active Users

**What**: Live user count  
**Captures**: Current online users  
**Use**: Monitor real-time usage  
**Config**: `REQUEST_LOG_ANALYZER_ACTIVE_WINDOW_MINUTES=5`

### 8. Sensitive Data Masking

**What**: Automatic data protection  
**Masks**: Passwords, tokens, credit cards, SSN  
**Use**: GDPR/PCI compliance  
**Config**: `REQUEST_LOG_ANALYZER_MASKING_ENABLED=true`

### 9. Sampling

**What**: Control what's logged  
**Captures**: Full requests or percentage  
**Use**: Manage disk space, reduce overhead  
**Config**: `REQUEST_LOG_ANALYZER_SAMPLE_RATE=100` (all) or `10` (10%)

### 10. Async Logging

**What**: Background logging  
**Captures**: Logs to queue instead of immediate DB  
**Use**: Faster response times, reduced overhead  
**Config**: `REQUEST_LOG_ANALYZER_ASYNC=true`

---

## Learning Guide

### For Complete Beginners

**Time**: 2 hours

```
1. Read this file (README_EN.md) - 20 min
2. Installation Guide (01_installation.md) - 15 min
3. Configuration Guide (02_configuration.md) - 30 min
4. Dashboard Guide (11_dashboard.md) - 45 min
5. Pick 1 feature and read it (15 min)
```

### For Developers

**Time**: 1 hour

```
1. Quick Start section above
2. Configuration Quick Reference below
3. Jump to needed feature documentation
4. Use Troubleshooting as needed
```

### For Ops/DevOps

**Time**: 1.5 hours

```
1. Installation (01_installation.md)
2. Configuration (02_configuration.md)
3. Optimization (10_optimization.md)
4. Setup monitoring alerts
```

---

## Configuration Quick Reference

### Master Controls

| Setting | Values | Default | Purpose |
|---------|--------|---------|---------|
| `ENABLED` | true/false | true | Turn package on/off |
| `SAMPLE_RATE` | 0-100 | 100 | % of requests to log |
| `ASYNC` | true/false | false | Log asynchronously |

### Feature Toggles

| Setting | Values | Default | Logs |
|---------|--------|---------|------|
| `TRACK_QUERIES` | true/false | true | Database queries |
| `TRACK_ERRORS` | true/false | true | Exceptions |
| `TRACK_GEO` | true/false | true | User location |
| `TRACK_LOGIN_HISTORY` | true/false | true | Login events |

### Performance

| Setting | Values | Default | Purpose |
|---------|--------|---------|---------|
| `SLOW_MS` | milliseconds | 500 | What's "slow" |
| `CAPTURE_ERRORS` | true/false | true | Always log errors |
| `CAPTURE_SLOW_MS` | milliseconds | 0 | Always log slow |
| `ACTIVE_WINDOW_MINUTES` | minutes | 5 | Active user window |

### Data Protection

| Setting | Values | Default | Purpose |
|---------|--------|---------|---------|
| `MASKING_ENABLED` | true/false | true | Mask sensitive data |
| `IGNORE_STATIC` | true/false | true | Skip static files |

### Recommended Configurations

**Development**
```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_ASYNC=false
```

**Production**
```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true
REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS=500
```

---

## Key Metrics

### You'll See In Dashboard

| Metric | Meaning | Example |
|--------|---------|---------|
| **Total Requests** | Requests today | 15,234 |
| **Avg Response Time** | Average latency | 142ms |
| **Slow Requests** | > 500ms (configurable) | 234 |
| **Error Rate** | % of failed requests | 0.5% |
| **Active Users** | Online right now | 12 |
| **Top Route** | Most visited | /dashboard |
| **Slow Query** | Slowest SQL | 2,341ms |
| **Top Error** | Most common error | SQLException |

---

## Best Practices

### ✅ DO

| Practice | Why | Example |
|----------|-----|---------|
| Enable in development | Catch issues early | Day 1 |
| Enable in production | Monitor real usage | Always |
| Review daily | Find patterns early | Spend 5 min morning |
| Set alerts | Know about problems | Error threshold |
| Mask sensitive data | GDPR compliance | Leave default |
| Use async logging | Faster responses | Production only |
| Review slow queries | Database optimization | Weekly |
| Monitor active users | Know user load | Real-time |

### ❌ DON'T

| Mistake | Problem | Fix |
|---------|---------|-----|
| Skip error masking | Expose passwords | Enable masking |
| Log 100% in high-traffic | Disk fills quickly | Use sampling |
| Disable async | Slow responses | Enable async |
| Ignore slow queries | Performance degrades | Review weekly |
| Use weak permissions | Anyone sees logs | Require auth |
| Never clear old data | Disk fills | Set retention |

---

## Daily Routine

### Morning (5 minutes)

```
9:00 AM
├─ Open dashboard
├─ Check for errors overnight
├─ Note any performance issues
└─ Continue day
```

### Weekly (30 minutes)

```
Friday 5:00 PM
├─ Check analytics page
├─ Review performance trends
├─ Look at top errors
├─ Plan optimizations
└─ Close week
```

### Monthly (1 hour)

```
Last day
├─ Full analytics review
├─ Performance comparison to last month
├─ User growth/decline
├─ Plan next month improvements
└─ Document findings
```

---

## Support Resources

### Documentation Files

| File | Topic | Time |
|------|-------|------|
| `01_installation.md` | Setup steps | 10 min |
| `02_configuration.md` | All options | 15 min |
| `03_http_tracking.md` | Request logging | 12 min |
| `04_database_tracking.md` | Query logging | 12 min |
| `05_error_tracking.md` | Error tracking | 12 min |
| `06_performance.md` | Analytics | 12 min |
| `07_geoip.md` | Location tracking | 10 min |
| `08_security.md` | Login/security | 12 min |
| `09_data_protection.md` | Data masking | 12 min |
| `10_optimization.md` | Sampling/async | 12 min |
| `11_dashboard.md` | Dashboard guide | 15 min |
| `12_api.md` | API reference | 10 min |
| `13_troubleshooting.md` | Common issues | 10 min |

### Quick Answers

**Q: Dashboard not loading?**  
A: See `13_troubleshooting.md` → Installation Issues

**Q: Data not being captured?**  
A: Check middleware registration, see `01_installation.md` → Step 3

**Q: Want to change settings?**  
A: See `02_configuration.md` → Configuration Reference

**Q: App slow?**  
A: See `06_performance.md` → Find Bottlenecks

**Q: Want to mask data?**  
A: See `09_data_protection.md` → Masking Configuration

---

## Getting Started (Next 30 Minutes)

### Step 1: Verify Installation (5 min)

```bash
# Check dashboard loads
curl http://your-app.test/request-log-analyzer
# Should return HTML, not 404
```

### Step 2: Generate Some Data (5 min)

```bash
# Make requests to your app
curl http://your-app.test/api/users
curl http://your-app.test/dashboard
# Visit dashboard in browser
```

### Step 3: View in Dashboard (5 min)

```
Visit: http://your-app.test/request-log-analyzer
You should see:
✅ Recent requests listed
✅ Response times showing
✅ Status codes visible
```

### Step 4: Check Configuration (10 min)

```bash
# Review your settings
cat config/request-log-analyzer.php
# Or see .env file for overrides
```

### Step 5: Read Relevant Guides (5 min)

Pick 1-2 features you want to use and read their guides.

---

## Next Steps

1. **Complete Installation**: [01_installation.md](01_installation.md)
2. **Configure Settings**: [02_configuration.md](02_configuration.md)
3. **Explore Features**: Pick any from 03-10 guides
4. **Master Dashboard**: [11_dashboard.md](11_dashboard.md)
5. **Integrate API**: [12_api.md](12_api.md)
6. **Troubleshoot Issues**: [13_troubleshooting.md](13_troubleshooting.md)

---

## Summary

| Feature | Benefit | Time to Value |
|---------|---------|----------------|
| HTTP Tracking | Know what's slow | 5 min |
| Query Logging | Database optimization | 10 min |
| Error Tracking | Fix issues faster | 5 min |
| Analytics | Understand patterns | 15 min |
| Security | Monitor users | 10 min |
| Performance | Improve UX | 20 min |

---

**Start**: [Read Installation Guide](01_installation.md)

**Status**: ✅ Ready to use  
**Time to setup**: 5 minutes  
**Time to first insights**: 10 minutes

---

_Questions? See [13_troubleshooting.md](13_troubleshooting.md)_
