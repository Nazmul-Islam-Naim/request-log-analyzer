# ১. HTTP রিকোয়েস্ট ট্র্যাকিং

## 🎯 এই বৈশিষ্ট্যটি কি?

প্যাকেজটি আপনার Laravel অ্যাপ্লিকেশনের প্রতিটি HTTP রিকোয়েস্ট স্বয়ংক্রিয়ভাবে রেকর্ড করে এবং সংরক্ষণ করে।

## 🤔 কেন প্রয়োজন?

- আপনার ওয়েবসাইটে কত রিকোয়েস্ট আসছে তা জানুন
- কোন URL সবচেয়ে বেশি ব্যবহৃত হচ্ছে তা দেখুন
- কোন রিকোয়েস্ট ধীর বা ত্রুটিপূর্ণ তা চিহ্নিত করুন
- ব্যবহারকারীর আচরণ বুঝুন

## 📝 কি তথ্য সংরক্ষিত হয়?

প্রতিটি রিকোয়েস্টের জন্য এই ডেটা সংরক্ষিত হয়:

| ডেটা | বর্ণনা | উদাহরণ |
|------|-------|---------|
| **মেথড** | HTTP মেথড | GET, POST, PUT, DELETE |
| **URI** | অনুরোধের পাথ | /api/users, /dashboard |
| **স্ট্যাটাস কোড** | প্রতিক্রিয়ার কোড | ২००, ৪०४, ५००
| **সময়কাল** | মিলিসেকেন্ডে সময় | ১२३, ৪५६ ms |
| **ব্যবহারকারী আইডি** | লগইন করা ব্যবহারকারী | ১, २, ३ |
| **আইপি ঠিকানা** | ক্লায়েন্টের আইপি | १९२.१६८.१.१ |
| **ইউএ** | ব্রাউজার তথ্য | Chrome, Firefox |

## 🔧 কনফিগারেশন

### মাস্টার সুইচ (সক্রিয়/নিষ্ক্রিয়)

```php
// config/request-log-analyzer.php
'enabled' => env('REQUEST_LOG_ANALYZER_ENABLED', true),
```

অথবা আপনার `.env` ফাইলে:

```env
REQUEST_LOG_ANALYZER_ENABLED=true
```

### স্ট্যাটিক অ্যাসেট উপেক্ষা করুন

CSS, JS, ইমেজ ট্র্যাক করবেন না:

```php
'ignore_static_assets' => env('REQUEST_LOG_ANALYZER_IGNORE_STATIC', true),
```

এই সক্ষম থাকলে `.css`, `.js`, `.png` ইত্যাদি লগ হবে না।

### নির্দিষ্ট পাথ উপেক্ষা করুন

```php
'ignored_paths' => [
    'request-log-analyzer*',  // আমাদের নিজস্ব ড্যাশবোর্ড
    '_debugbar*',              // Laravel Debugbar
    'telescope*',              // Laravel Telescope
    'horizon*',                // Laravel Horizon
    'livewire*',               // Laravel Livewire
],
```

### নির্দিষ্ট রুট উপেক্ষা করুন

```php
'ignore_routes' => [
    'ping',     // স্বাস্থ্য চেক
    'health',   // স্বাস্থ্য পরীক্ষা
],
```

### রুট প্রিফিক্স পরিবর্তন করুন

ড্যাশবোর্ড কোথায় অ্যাক্সেস করতে চান?

```php
'route_prefix' => env('REQUEST_LOG_ANALYZER_PREFIX', 'request-log-analyzer'),
```

**ডিফল্ট**:
```
http://your-app.test/request-log-analyzer
```

**কাস্টম**:
```php
'route_prefix' => 'analytics', // http://your-app.test/analytics
```

## 📊 ড্যাশবোর্ডে দেখুন

প্রতিটি রিকোয়েস্ট এখানে দৃশ্যমান:

```
http://your-app.test/request-log-analyzer/requests
```

### উপলব্ধ ফিল্টার

| ফিল্টার | উপযোগ | উদাহরণ |
|--------|-------|---------|
| **মেথড** | নির্দিষ্ট HTTP মেথড | `?method=POST` |
| **স্ট্যাটাস** | স্ট্যাটাস কোড দ্বারা | `?status=5xx` |
| **URI** | পাথের অংশ দ্বারা | `?uri=/api/users` |
| **ট্যাগ** | কাস্টম ট্যাগ দ্বারা | `?tag=payment` |

### উদাহরণ ফিল্টার URL

```
/request-log-analyzer/requests?method=POST&status=5xx
/request-log-analyzer/requests?uri=/api/products
/request-log-analyzer/requests?tag=payment&method=GET
```

## ⚙️ মিডলওয়্যার রেজিস্টার করুন

রিকোয়েস্ট ট্র্যাকিং কাজ করার জন্য মিডলওয়্যার রেজিস্টার করতে হবে।

### Laravel ११+ এর জন্য

`bootstrap/app.php` খুলুন:

```php
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;

->withMiddleware(function (Middleware $middleware) {
    $middleware->append(TrackRequest::class);
})
```

### Laravel १० এর জন্য

`app/Http/Kernel.php` খুলুন:

```php
use NIN\RequestLogAnalyzer\Http\Middleware\TrackRequest;

protected $middleware = [
    // ... অন্যান্য মিডলওয়্যার
    TrackRequest::class,
];
```

### শুধুমাত্র নির্দিষ্ট রুট ট্র্যাক করুন

```php
Route::middleware([TrackRequest::class])->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('/api/users', [UserController::class, 'store']);
});
```

## 📈 রিকোয়েস্ট বিস্তারিত দেখুন

যেকোনো রিকোয়েস্টে ক্লিক করলে দেখবেন:

- **মেথড এবং URL**
- **প্রতিক্রিয়া কোড এবং সময়**
- **সমস্ত ডাটাবেস কোয়েরি**
- **যেকোনো ত্রুটি বা ব্যতিক্রম**
- **অনুরোধ এবং প্রতিক্রিয়া হেডার**

---

**পরবর্তী**: [২. ডাটাবেস কোয়েরি ট্র্যাকিং](01_database_queries.md)
