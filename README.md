# Request Log Analyzer for Laravel

**Monitor, analyze, and debug HTTP requests, database queries, errors, and performance metrics in real-time with an interactive dashboard.**

[![Latest Version](https://img.shields.io/badge/version-2.0-blue)]()
[![Laravel Version](https://img.shields.io/badge/Laravel-10%2B-brightgreen)]()
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)]()
[![License](https://img.shields.io/badge/License-MIT-green)]()

---

## 🎯 Quick Start (60 Seconds)

```bash
# 1. Install
composer require nintis/request-log-analyzer

# 2. Run setup command
php artisan analyzer:install

# 3. Open dashboard
php artisan serve
# Visit: http://localhost:8000/request-log-analyzer
```

---

## ✨ Key Features

| Feature | Description |
|---------|-------------|
| 🔗 **HTTP Requests** | Capture method, URL, status, duration, response time |
| 💾 **Database Queries** | Monitor SQL queries, execution time, slow queries |
| ⚠️ **Error Tracking** | Catch exceptions and errors with stack traces |
| 📈 **Analytics** | Response times, memory usage, performance insights |
| 🌍 **GeoIP Tracking** | Geographic location from IP address |
| 🔐 **Security** | Login attempts, active users, suspicious activity |
| 🔒 **Data Masking** | Auto-hide passwords, API keys, tokens |
| ⚡ **Async Logging** | Non-blocking queue-based logging |
| 📊 **Dashboard** | Interactive charts, filters, and insights |
| 🔌 **JSON API** | Programmatic access to all data |

---

## 📋 Requirements

- **Laravel**: 10, 11, 12, 13
- **PHP**: 8.1+
- **Database**: MySQL, PostgreSQL, SQLite
- **Optional**: Redis (for async logging)

---

## 📚 Documentation

**Quick Links**:
- ⚡ [Installation (01_installation.md)](docs_en/01_installation.md) — Step-by-step setup (10 min)
- ⚙️ [Configuration (02_configuration.md)](docs_en/02_configuration.md) — All options explained (15 min)
- 🖥️ [Dashboard Guide (11_dashboard_guide.md)](docs_en/11_dashboard_guide.md) — Using the dashboard (20 min)
- 🆘 [Troubleshooting (13_troubleshooting.md)](docs_en/13_troubleshooting.md) — Common issues & fixes
- ❓ [FAQ (14_faq.md)](docs_en/14_faq.md) — Frequently asked questions
- 🔄 [Migration Guide (15_migration_guide.md)](docs_en/15_migration_guide.md) — Upgrading from v1

**Full Documentation**:
- [Complete Index (docs_en/INDEX.md)](docs_en/INDEX.md) — All documentation files
- 🇧🇩 [Bengali Docs (docs_bn/README_BN.md)](docs_bn/README_BN.md) — বাংলা ডকুমেন্টেশন

---

## 📊 Dashboard Preview

The dashboard provides real-time insights into your application:

### 🖥️ Main Dashboard
![Dashboard Overview](resources/images/dashboard-01.png)

![Dashboard Details](resources/images/dashboard-02.png)

### 📈 Analytics
![Analytics](resources/images/analytics.png)

### 🌍 Geo Analytics
![Geo Analytics](resources/images/geo-analytics.png)

### 🔌 API Insights
![API Insights](resources/images/api-insight.png)



---

## 🚀 Getting Started in 3 Steps

### Step 1: Install Package
```bash
composer require nintis/request-log-analyzer
```

### Step 2: Run Setup
```bash
php artisan analyzer:install
```
This command automatically:
- ✅ Publishes configuration file
- ✅ Registers middleware  
- ✅ Runs database migrations

### Step 3: Access Dashboard
Visit: `http://your-app.test/request-log-analyzer`

You should see requests flowing in real-time!

---

## 💡 Common Configurations

### For Development (Log Everything)
```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
REQUEST_LOG_ANALYZER_TRACK_ERRORS=true
```

### For Production (Optimized)
```env
REQUEST_LOG_ANALYZER_ENABLED=true
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10
REQUEST_LOG_ANALYZER_MASKING_ENABLED=true
REQUEST_LOG_ANALYZER_RETENTION_DAYS=30
REQUEST_LOG_ANALYZER_IGNORE_STATIC=true
```

### For Debugging Specific Issues
```env
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_SLOW_REQUEST_THRESHOLD_MS=1000
REQUEST_LOG_ANALYZER_TRACK_QUERIES=true
```

---

## 📊 What You Can Track

The package automatically captures and displays:

- 🔗 **HTTP Requests** — Method, URL, status code, response time
- 💾 **Database Queries** — SQL executed, query time, slow queries
- ⚠️ **Errors & Exceptions** — Stack traces, error details, context
- 👤 **User Activity** — Login history, active users, user routes
- 🌍 **Geographic Data** — IP, country, city of requests
- ⏱️ **Performance Metrics** — Response times, memory usage, bottlenecks
- 🔐 **Security Events** — Suspicious activity, failed authentication

---

## 🔒 Security & Privacy

All data handling respects security and privacy:

✅ **Built-in Masking** — Passwords, API keys, tokens automatically hidden
✅ **Access Control** — Restrict dashboard to authenticated users
✅ **Data Retention** — Automatically delete old records
✅ **GDPR Ready** — Delete user data on request
✅ **Configurable** — Enable/disable features as needed

See [Data Protection Guide](docs_en/09_features_data_protection.md) for details.

---

## ⚡ Performance Impact

With recommended production settings, the overhead is **< 5%**:

| Setting | Impact |
|---------|--------|
| `SAMPLE_RATE=10` | Reduce storage by 90% |
| `ASYNC=true` | Non-blocking request logging |
| `IGNORE_STATIC=true` | Skip CSS/JS/images |
| `RETENTION_DAYS=30` | Auto cleanup old data |
| `TRACK_GEO=false` | Skip expensive GeoIP lookup |

**Expected storage**: ~1-5 MB per 1,000 requests

---

## 🎯 Use Cases

### Development & Debugging
- Understand exactly what requests your app is handling
- Debug slow requests and queries
- Catch exceptions before users do
- Trace performance bottlenecks

### Production Monitoring
- Monitor real user traffic
- Alert on errors and 5xx responses
- Track API performance
- Understand user behavior

### Performance Optimization
- Identify slow database queries
- Find N+1 query problems
- Optimize response times
- Reduce memory usage

### Security & Compliance
- Monitor login attempts
- Track user activity
- Audit API access
- Ensure GDPR compliance

---

## 📖 Documentation Structure

The documentation is organized for different needs:

| Goal | Start Here |
|------|-----------|
| **Just installed?** | [Installation Guide](docs_en/01_installation.md) (10 min) |
| **Want to configure?** | [Configuration Reference](docs_en/02_configuration.md) (15 min) |
| **Using the dashboard?** | [Dashboard Guide](docs_en/11_dashboard_guide.md) (20 min) |
| **Having issues?** | [Troubleshooting](docs_en/13_troubleshooting.md) |
| **Questions?** | [FAQ](docs_en/14_faq.md) |
| **Upgrading from v1?** | [Migration Guide](docs_en/15_migration_guide.md) (20 min) |
| **Want all details?** | [Complete Index](docs_en/INDEX.md) |

---

## 🔧 Common Tasks

### Make the Dashboard Accessible Only to Admins
See [Security Guide](docs_en/08_features_security.md#access-control)

### Reduce Storage Usage
See [Optimization Guide](docs_en/10_features_optimization.md#storage-optimization)

### Mask Custom Sensitive Fields
See [Data Protection](docs_en/09_features_data_protection.md#custom-masking)

### Export Data Programmatically  
See [API Reference](docs_en/16_api_reference.md)

### Monitor Queue-Based Logging
See [Async Logging](docs_en/10_features_optimization.md#async-logging)

---

## ❓ Quick Answers

**Q: Is this for production use?**  
A: Yes! With async logging and sampling enabled, it adds < 5% overhead. See [Best Practices](docs_en/12_best_practices.md).

**Q: Will it slow down my app?**  
A: Not if configured correctly. Use async logging (`REQUEST_LOG_ANALYZER_ASYNC=true`) and sampling (`REQUEST_LOG_ANALYZER_SAMPLE_RATE=10`).

**Q: How much disk space?**  
A: ~1-5 MB per 1,000 requests. Set retention to auto-cleanup: `REQUEST_LOG_ANALYZER_RETENTION_DAYS=30`.

**Q: Can I use it with other monitoring tools?**  
A: Yes! It coexists with Laravel Telescope, Debugbar, Sentry, New Relic, etc.

**Q: How do I delete data?**  
A: Use the artisan command: `php artisan analyzer:clear --older-than=7 --force` or retention auto-cleanup.

See [FAQ](docs_en/14_faq.md) for more questions.

---

## 🆘 Need Help?

1. **Installation issues?** → [Installation Guide](docs_en/01_installation.md)
2. **Configuration problems?** → [Configuration Reference](docs_en/02_configuration.md)
3. **Dashboard not working?** → [Troubleshooting](docs_en/13_troubleshooting.md)
4. **Something else?** → [FAQ](docs_en/14_faq.md)

---

## 📚 Full Feature Documentation

- 🔗 [HTTP Request Tracking](docs_en/03_features_request_tracking.md)
- 💾 [Database Query Monitoring](docs_en/04_features_database_queries.md)
- ⚠️ [Error Tracking & Debugging](docs_en/05_features_error_tracking.md)
- 📈 [Performance Analytics](docs_en/06_features_performance_analytics.md)
- 🌍 [GeoIP Tracking](docs_en/07_features_geoip.md)
- 🔐 [Security Monitoring](docs_en/08_features_security.md)
- 🔒 [Data Protection & Masking](docs_en/09_features_data_protection.md)
- ⚡ [Async Logging & Sampling](docs_en/10_features_optimization.md)
- 🖥️ [Dashboard Complete Guide](docs_en/11_dashboard_guide.md)
- 💡 [Best Practices & Tips](docs_en/12_best_practices.md)
- 🔌 [JSON API Documentation](docs_en/16_api_reference.md)

---

## 🌍 Language Support

- 🇬🇧 **English**: Full documentation in `docs_en/`
- 🇧🇩 **Bengali**: বাংলা ডকুমেন্টেশন in `docs_bn/`

---

## 🚀 What's New in v2

- ✨ **Redesigned Dashboard** — Beautiful new UI with hero cards and charts
- 👥 **User Tracking** — Active users, login history, per-user analytics
- 🔒 **Enhanced Security** — Data masking, GDPR compliance
- ⚡ **Better Performance** — Async logging, smart sampling
- 🔌 **New JSON API** — Programmatic data access
- 🌍 **GeoIP Integration** — Geographic insights
- 📈 **Advanced Analytics** — Route analytics, performance metrics

See [Migration Guide](docs_en/15_migration_guide.md) if upgrading from v1.

---

## 📄 License

MIT License — Free to use and modify

---

## 🙏 Support

- 📚 **Documentation**: [docs_en/INDEX.md](docs_en/INDEX.md)
- ❓ **FAQ**: [docs_en/14_faq.md](docs_en/14_faq.md)
- 🐛 **Issues**: [GitHub Issues](https://github.com/nin-company/request-log-analyzer)
- 💬 **Questions**: Create an issue or contact support

---

## 📊 Happy Monitoring!

You now have powerful insights into every request your Laravel app handles. 

**Next steps:**
1. ✅ Visit the dashboard at `/request-log-analyzer`
2. ✅ Make some test requests to see data
3. ✅ Read [Configuration Guide](docs_en/02_configuration.md) to customize
4. ✅ Check [Best Practices](docs_en/12_best_practices.md) for production

Happy logging! 🚀
