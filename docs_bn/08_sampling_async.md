# ८. নমুনা এবং অ্যাসিঙ্ক লগিং

## 🎯 নমুনা কি?

**নমুনা** মানে প্রতিটি রিকোয়েস্ট লগ করার পরিবর্তে শুধুমাত্র কিছু লগ করা।

## 🤔 কেন প্রয়োজন?

- **উচ্চ ট্রাফিক** - ১০০,০০০ রিকোয়েস্ট/দিন হলে সব লগ করা অসম্ভব
- **ডাটাবেস বাঁচান** - স্টোরেজ কম করুন
- **পারফরম্যান্স** - লগিং ওভারহেড কমান
- **ডিস্ক I/O** - সার্ভার লোড কমান

## 📊 নমুনা হার

### কি নমুনা হার অর্থ?

```
নমুনা হার = ১০০
→ প্রতিটি রিকোয়েস্ট লগ করুন (সব)

নমুনা হার = ৫০
→ ৫০% রিকোয়েস্ট লগ করুন (অর্ধেক)

নমুনা হার = १০
→ ১০% রিকোয়েস্ট লগ করুন (প্রতি ১০টিতে ১টি)

নমুনা হার = १
→ १% রিকোয়েস্ট লগ করুন (খুবই কম)
```

## 🔧 কনফিগারেশন

### নমুনা হার সেট করুন

```php
// config/request-log-analyzer.php
'sample_rate' => (int) env('REQUEST_LOG_ANALYZER_SAMPLE_RATE', 100),
```

**.env ফাইলে**:

```env
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100   # সব লগ করুন
REQUEST_LOG_ANALYZER_SAMPLE_RATE=50    # অর্ধেক লগ করুন
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10    # ১০% লগ করুন
```

### নমুনার ব্যতিক্রম

এমনকি নমুনা ১% থাকলেও এই রিকোয়েস্টগুলি **সবসময়** লগ করুন:

```php
// সব ত্রুটি সবসময় লগ করুন
'always_capture_errors' => env('REQUEST_LOG_ANALYZER_CAPTURE_ERRORS', true),

// ধীম রিকোয়েস্ট সবসময় লগ করুন
'always_capture_slow_ms' => (int) env('REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS', 0),
```

**উদাহরণ**:

```php
'sample_rate' => 1,  // শুধুমাত্র 1% লগ করুন
'always_capture_errors' => true,  // কিন্তু সব ত্রুটি লগ করুন
'always_capture_slow_ms' => 1000,  // এবং 1 সেকেন্ডের বেশি সব রিকোয়েস্ট
```

এতে:
- ১% সাধারণ রিকোয়েস্ট লগ করা হবে
- সমস্ত ত্রুটি লগ করা হবে
- ১ সেকেন্ডের বেশি সব রিকোয়েস্ট লগ করা হবে

## 📈 পরিবেশ অনুযায়ী সেটিংস

### উন্নয়ন পরিবেশ (Local)

সব বিস্তারিত দেখুন:

```env
# .env.local
REQUEST_LOG_ANALYZER_SAMPLE_RATE=100
REQUEST_LOG_ANALYZER_ASYNC=false
```

### স্টেজিং পরিবেশ

উল্লেখযোগ্য রিকোয়েস্ট লগ করুন:

```env
# .env.staging
REQUEST_LOG_ANALYZER_SAMPLE_RATE=50
REQUEST_LOG_ANALYZER_ASYNC=true
```

### উৎপাদন (Live)

শুধুমাত্র গুরুত্বপূর্ণ রিকোয়েস্ট:

```env
# .env.production
REQUEST_LOG_ANALYZER_SAMPLE_RATE=10
REQUEST_LOG_ANALYZER_ASYNC=true
REQUEST_LOG_ANALYZER_CAPTURE_ERRORS=true
REQUEST_LOG_ANALYZER_CAPTURE_SLOW_MS=500
```

## ⚡ অ্যাসিঙ্ক লগিং কি?

**অ্যাসিঙ্ক লগিং** মানে লগিং পটভূমিতে করা, মূল রিকোয়েস্টকে ব্লক না করে।

## 🤔 কেন প্রয়োজন?

- লগিং ধীর করে রিকোয়েস্ট
- অ্যাসিঙ্ক = পটভূমিতে করুন
- ব্যবহারকারী দ্রুত প্রতিক্রিয়া পান
- সার্ভার লোড বিতরণ করুন

## 🔧 অ্যাসিঙ্ক লগিং কনফিগার করুন

### অ্যাসিঙ্ক চালু করুন

```php
// config/request-log-analyzer.php
'async_logging' => env('REQUEST_LOG_ANALYZER_ASYNC', false),
```

**.env** এ:

```env
REQUEST_LOG_ANALYZER_ASYNC=true
```

### কিউ কানেকশন নির্বাচন করুন

কোন কিউ ব্যবহার করবেন?

```php
'queue_connection' => env('REQUEST_LOG_ANALYZER_QUEUE_CONNECTION', null),
'queue_name' => env('REQUEST_LOG_ANALYZER_QUEUE_NAME', 'default'),
```

**উদাহরণ**:

```env
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=redis
REQUEST_LOG_ANALYZER_QUEUE_NAME=analytics
```

অথবা:

```env
REQUEST_LOG_ANALYZER_QUEUE_CONNECTION=database
REQUEST_LOG_ANALYZER_QUEUE_NAME=default
```

## 📊 কিউ সিস্টেম ডায়েগ্রাম

```
HTTP Request আসে
         ↓
Middleware লগ তৈরি করে
         ↓
Queue-তে রাখে (অ্যাসিঙ্ক)
         ↓
তাৎক্ষণিক প্রতিক্রিয়া পাঠায়
         ↓
পটভূমিতে Queue Worker:
  - ডাটাবেসে সংরক্ষণ করে
  - বিশ্লেষণ করে
  - মেট্রিক্স আপডেট করে
```

## ⚙️ কিউ ওয়ার্কার চালান

### Redis কিউ

```bash
# একটি ওয়ার্কার চালান
php artisan queue:work redis

# একাধিক ওয়ার্কার (বিভিন্ন প্রসেসে)
php artisan queue:work redis --queue=analytics,default
```

### ডাটাবেস কিউ

```bash
# ডাটাবেস ওয়ার্কার
php artisan queue:work database

# পলিং ইন্টারভাল পরিবর্তন করুন
php artisan queue:work database --poll=3
```

## 📈 মনিটর করুন

### কিউ চেক করুন

```bash
# লঞ্চড কাজ দেখুন
php artisan queue:failed

# পুনরায় চেষ্টা করুন
php artisan queue:retry
```

### কিউ আপনার

```bash
# সব কাজ দেখুন
redis-cli LRANGE analytics 0 -1
```

## 🔧 উন্নত কনফিগারেশন

### ব্যর্থতা সম্ভালনা

```php
'failed_jobs_retry' => 3,  // ৩ বার পুনরায় চেষ্টা করুন
```

### কাজের সময়কাল

```php
'job_timeout' => 300,  // ৫ মিনিট সময়সীমা
```

## ✅ সেরা অনুশীলন

१. **উন্নয়নে নমুনা ১০০%** - সব বিবরণ দেখুন

२. **উৎপাদনে ১০-২০%** - উল্লেখযোগ্য প্যাটার্ন দেখুন

३. **সর্বদা ত্রুটি ধরুন** - নমুনা সত্ত্বেও

४. **অ্যাসিঙ্ক ব্যবহার করুন** - লাইভ পরিবেশে

५. **কিউ মনিটর করুন** - আটকে আছে কিনা চেক করুন

---

**পূর্ববর্তী**: [७. সংবেদনশীল ডেটা সুরক্ষা](07_sensitive_data.md)  
**পরবর্তী**: [९. ড্যাশবোর্ড ব্যবহার](08_dashboard.md)
