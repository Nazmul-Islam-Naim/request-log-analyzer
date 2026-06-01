# ७. সংবেদনশীল ডেটা সুরক্ষা

## 🎯 এই বৈশিষ্ট্যটি কি?

প্যাকেজটি স্বয়ংক্রিয়ভাবে পাসওয়ার্ড, টোকেন, ক্রেডিট কার্ড এবং অন্যান্য সংবেদনশীল তথ্য লুকিয়ে দেয়।

## 🤔 কেন প্রয়োজন?

- গ্রাহকের ডেটা সুরক্ষিত রাখুন
- GDPR এবং PCI-DSS সম্মতি বজায় রাখুন
- কর্মচারী যারা লগ দেখেন তাদের কাছ থেকে সংবেদনশীল ডেটা লুকান
- আইনি ঝুঁকি কমান
- ডেটা লঙ্ঘনের ঝুঁকি হ্রাস করুন

## 🛡️ স্বয়ংক্রিয় মাস্কিং

### কী স্বয়ংক্রিয়ভাবে লুকানো হয়?

প্যাকেজটি এই ক্ষেত্রগুলি স্বয়ংক্রিয়ভাবে `***` দিয়ে প্রতিস্থাপন করে:

| ক্ষেত্র | উদাহরণ | লুকানো আকার |
|--------|---------|------------|
| পাসওয়ার্ড | myPassword123 | `***` |
| API কী | sk_test_4242424242 | `***` |
| ক্রেডিট কার্ড | 4111111111111111 | `***` |
| টোকেন | eyJhbGciOi... | `***` |
| অনুমোদন হেডার | Bearer abc123def | `***` |
| কুকি | session=xyz789 | `***` |
| সামাজিক নিরাপত্তা নম্বর | 123-45-6789 | `***` |
| OTP কোড | 123456 | `***` |

## 🔧 কনফিগারেশন

### লুকানো ক্ষেত্র যোগ করুন/পরিবর্তন করুন

```php
// config/request-log-analyzer.php
'hidden_fields' => [
    'password',
    'password_confirmation',
    'token',
    'api_key',
    'secret',
    '_token',
    'secret_key',
    'private_key',
    'credit_card',
    'card_number',
    'cvv',
    'ssn',
    'otp',
    'mfa_code',
],
```

নতুন ক্ষেত্র যোগ করুন:

```php
'hidden_fields' => [
    // ... ডিফল্ট
    'custom_secret',
    'access_token',
    'refresh_token',
],
```

### প্যাটার্ন-ভিত্তিক স্ক্রাবিং

নিয়মিত অভিব্যক্তি ব্যবহার করে জটিল প্যাটার্ন মাস্ক করুন:

```php
'scrub_patterns' => [
    // Bearer টোকেন
    '/Bearer\s+[\w\-\.]+/i',
    
    // পাসওয়ার্ড অ্যাসাইনমেন্ট
    '/password["\']?\s*[:=]\s*["\']?[^\s,&"\'>]+/i',
    
    // API কী (দীর্ঘ স্ট্রিং)
    '/api[_]?key["\']?\s*[:=]\s*["\']?[\w\-\.]{10,}/i',
    
    // JSON Web টোকেন
    '/eyJ[\w\-\.]+/i',
    
    // ক্রেডিট কার্ড (১৬ সংখ্যা)
    '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/',
],
```

## 🔐 বিস্তারিত মাস্কিং নিয়ম

### ফর্ম ডেটা মাস্কিং

যখন একটি ফর্ম জমা দেওয়া হয়:

```
Input:
{
  "email": "john@example.com",
  "password": "SecurePass123!",
  "credit_card": "4111111111111111"
}

Output (লগে দেখা যাবে):
{
  "email": "john@example.com",
  "password": "***",
  "credit_card": "***"
}
```

### কোয়েরি প্যারামিটার মাস্কিং

URL এ সংবেদনশীল ডেটা থাকলে:

```
URL:
/api/login?email=john@example.com&password=secret123&remember_token=abc

লগে দেখা যাবে:
/api/login?email=john@example.com&password=***&remember_token=***
```

### হেডার মাস্কিং

Authorization হেডার স্বয়ংক্রিয়ভাবে মাস্ক করা হয়:

```
Original Header:
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

লগে দেখা যাবে:
Authorization: Bearer ***
```

## 📋 মাস্ক করার ব্যতিক্রম

### যদি কিছু মাস্ক না করতে চান

তালিকায় থেকে সরান:

```php
'hidden_fields' => [
    'password',
    // 'email',  // ইমেইল দৃশ্যমান থাকবে
    'api_key',
],
```

### যদি নতুন সংবেদনশীল ক্ষেত্র যোগ করতে চান

```php
'hidden_fields' => [
    // ... ডিফল্ট
    'bank_account',
    'routing_number',
    'tax_id',
],
```

## 🔄 কিভাবে মাস্কিং কাজ করে

### ১. প্রাথমিক স্তর

ক্ষেত্রের নামের উপর ভিত্তি করে (নির্ভরযোগ্য):

```php
if (in_array($fieldName, $hiddenFields)) {
    $value = '***';  // লুকান
}
```

### २. উন্নত স্তর

regex প্যাটার্ন ম্যাচিং (অতি নিশ্চিত):

```php
foreach ($scrubPatterns as $pattern) {
    $text = preg_replace($pattern, '***', $text);
}
```

### ३. Context স্তর

যেখানে ডেটা উপস্থিত (সর্বোত্তম):

```php
// JSON পেলোড
$payload = json_decode($request->getContent());
$payload->password = '***';

// Query স্ট্রিং
$query = parse_str($request->getQueryString());
$query['token'] = '***';

// Headers
$headers['Authorization'] = '***';
```

## 📊 মাস্ক করা ডেটা দেখুন

### ড্যাশবোর্ডে

যখন আপনি রিকোয়েস্টের বিস্তারিত দেখবেন:

```
/request-log-analyzer/requests/123
```

পাবেন:
```
POST /api/login

Form Data:
- email: john@example.com
- password: ***
- remember: true

Response:
{
  "token": "***",
  "user": { ... }
}
```

### কোয়েরি লগে

SQL প্যারামিটারগুলি মাস্ক করা হয়:

```
Query:
INSERT INTO users (email, password_hash) 
VALUES (?, ?)

Parameters: john@example.com, *** (হ্যাশ মাস্ক করা)
```

## 🔍 ডেভেলপার ভিউ

### লুকানো ডেটা আবার দেখতে

কনফিগ পরিবর্তন করুন (শুধুমাত্র উন্নয়নে):

```php
// LOCAL DEVELOPMENT ONLY
'hidden_fields' => [],
'scrub_patterns' => [],
```

⚠️ **সর্বদা সংবেদনশীল ডেটা লুকান উৎপাদনে!**

## 🎯 সম্মতি মান

### GDPR সম্মতি

```php
// ব্যক্তিগত ডেটা মাস্ক করুন
'mask_personal_data' => true,
'mask_pii_fields' => [
    'phone',
    'address',
    'name',
],
```

### PCI-DSS সম্মতি

```php
// কার্ড ডেটা মাস্কিং
'mask_credit_cards' => true,
'mask_card_patterns' => [
    '/\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}/',
],
```

## ✅ সেরা অনুশীলন

१. **ডিফল্ট লুকান সব** - শুধুমাত্র যা আবশ্যক তা প্রকাশ করুন

२. **নিয়মিত অডিট** - লগ পরীক্ষা করুন সংবেদনশীল ডেটা আছে কি না

३. **ডেভেলপারদের প্রশিক্ষণ দিন** - নতুন সংবেদনশীল ক্ষেত্র সম্পর্কে জানান

४. **পরীক্ষা করুন** - নিশ্চিত করুন মাস্কিং কাজ করছে

५. **নিরাপত্তা প্রথম** - যখন সন্দেহ, লুকান

---

**পূর্ববর্তী**: [६. লগইন ইতিহাস এবং সক্রিয় ব্যবহারকারী](06_login_history.md)  
**পরবর্তী**: [८. নমুনা এবং অ্যাসিঙ্ক লগিং](07_sampling_async.md)
