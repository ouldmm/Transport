# 🚌 نظام حجز تذاكر باصات موريتانيا
## Transport Mauritanie — Bus Ticket Booking System

---

## 📁 هيكل الملفات

```
bus-mauritania/
├── index.php                    ← الصفحة الرئيسية (البحث والحجز)
├── process-booking.php          ← معالجة طلب الحجز
├── booking-confirmation.php     ← صفحة تأكيد الحجز
├── check-booking.php            ← تتبع حالة الحجز
├── setup.php                    ← إعداد أولي (احذفه بعد الاستخدام!)
│
├── includes/
│   ├── db.php                   ← الاتصال بقاعدة البيانات MS Access
│   └── functions.php            ← دوال مساعدة عامة
│
├── admin/
│   ├── login.php                ← تسجيل دخول الإدارة
│   ├── dashboard.php            ← لوحة التحكم الرئيسية
│   ├── bookings.php             ← إدارة الحجوزات
│   ├── routes.php               ← إدارة الرحلات
│   ├── update-status.php        ← تحديث حالة الحجز
│   └── logout.php               ← تسجيل الخروج
│
└── database/
    ├── transport_mauritanie.mdb  ← قاعدة البيانات (أنشئها)
    ├── create_db.vbs             ← سكريبت VBScript لإنشاء قاعدة البيانات
    └── README.txt
```

---

## ⚙️ متطلبات التشغيل

- **خادم ويب:** Apache / IIS مع PHP 7.4 أو أحدث
- **PHP Extensions:** `pdo_odbc`, `odbc`
- **قاعدة البيانات:** Microsoft Access (.mdb أو .accdb)
- **نظام التشغيل:** Windows (مطلوب لـ ODBC Driver)
- **Microsoft Access Driver (*.mdb, *.accdb)** مثبت على الخادم

---

## 🚀 خطوات التثبيت

### الخطوة 1: رفع الملفات
```
رفع جميع الملفات إلى مجلد الموقع على الخادم (htdocs أو public_html)
```

### الخطوة 2: إنشاء قاعدة البيانات

**الطريقة أ: باستخدام سكريبت VBScript (Windows)**
```batch
cd database
cscript create_db.vbs
```

**الطريقة ب: يدوياً في Microsoft Access**
1. افتح Microsoft Access
2. أنشئ قاعدة بيانات جديدة: `database/transport_mauritanie.mdb`
3. أنشئ الجداول التالية:

**جدول `routes` (الرحلات):**
| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | AutoNumber | المعرف |
| from_city | Text(100) | مدينة الانطلاق |
| to_city | Text(100) | مدينة الوصول |
| departure_time | Text(10) | وقت الانطلاق |
| arrival_time | Text(10) | وقت الوصول |
| price | Double | السعر |
| seats_total | Integer | عدد المقاعد |
| company | Text(100) | اسم الشركة |
| bus_type | Text(50) | نوع الباص |
| active | Integer | نشط (1/0) |
| created_at | Text(30) | تاريخ الإنشاء |

**جدول `bookings` (الحجوزات):**
| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | AutoNumber | المعرف |
| booking_ref | Text(20) | رقم الحجز |
| route_id | Integer | رقم الرحلة |
| passenger_name | Text(150) | اسم المسافر |
| passenger_phone | Text(20) | رقم الهاتف |
| passenger_id | Text(20) | رقم الهوية |
| seat_count | Integer | عدد المقاعد |
| travel_date | Text(20) | تاريخ السفر |
| total_price | Double | إجمالي السعر |
| status | Text(20) | الحالة |
| payment_method | Text(30) | طريقة الدفع |
| notes | Text(255) | ملاحظات |
| created_at | Text(30) | تاريخ الحجز |

**جدول `users` (المستخدمون):**
| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | AutoNumber | المعرف |
| username | Text(50) | اسم المستخدم |
| password | Text(255) | كلمة المرور (مشفرة) |
| full_name | Text(100) | الاسم الكامل |
| role | Text(20) | الصلاحية |
| active | Integer | نشط |
| last_login | Text(30) | آخر دخول |

### الخطوة 3: توليد كلمة مرور Admin

```php
// شغّل هذا في PHP لتوليد hash لكلمة المرور
echo password_hash('admin123', PASSWORD_DEFAULT);
```

ضع الناتج في حقل `password` لمستخدم admin في جدول `users`.

### الخطوة 4: تشغيل setup.php

افتح في المتصفح:
```
http://localhost/bus-mauritania/setup.php
```

### الخطوة 5: احذف setup.php

**مهم جداً:** بعد الإعداد احذف `setup.php` من الخادم.

---

## 🔐 بيانات الدخول الافتراضية

| المستخدم | كلمة المرور |
|----------|-------------|
| admin | admin123 |

**غيّر كلمة المرور فوراً بعد أول دخول!**

---

## 🌐 روابط الموقع

| الصفحة | الرابط |
|--------|--------|
| الرئيسية | `/index.php` |
| تتبع الحجز | `/check-booking.php` |
| لوحة التحكم | `/admin/login.php` |
| الإعداد | `/setup.php` (احذفه بعد الاستخدام) |

---

## 🗄️ إعداد ODBC على Windows

1. افتح **لوحة التحكم** → **أدوات إدارية** → **مصادر بيانات ODBC**
2. اضغط **إضافة** → **Microsoft Access Driver (*.mdb, *.accdb)**
3. سمّه `transport_mauritanie`
4. حدد مسار ملف `.mdb`

أو عبر سطر الأوامر:
```batch
odbcconf CONFIGSYSDSN "Microsoft Access Driver (*.mdb, *.accdb)" "DSN=transport_mauritanie|DBQ=C:\path\to\transport_mauritanie.mdb"
```

---

## ✨ مميزات النظام

### الموقع العام
- 🔍 البحث عن الرحلات بين المدن الموريتانية
- 🪑 عرض المقاعد المتاحة في الوقت الفعلي
- 📝 نموذج حجز تفصيلي مع التحقق من البيانات
- ✅ صفحة تأكيد الحجز مع رقم مرجعي
- 🔎 تتبع حالة الحجز برقم الحجز
- 🖨️ طباعة التذكرة

### لوحة التحكم (Admin)
- 📊 لوحة إحصائيات شاملة
- 📋 إدارة جميع الحجوزات
- ✔️ تأكيد / إلغاء الحجوزات
- 🚌 إضافة وتعديل الرحلات
- 🔍 بحث وتصفية متقدم
- 📈 تقارير الإيرادات

---

## 🛠️ تخصيص النظام

### تغيير اسم الموقع
في ملف `index.php` ابحث عن "نقل موريتانيا" وعدّله.

### إضافة شركة نقل جديدة
افتح لوحة التحكم → إدارة الرحلات → إضافة رحلة.

### تغيير العملة
في `includes/functions.php` عدّل دالة `formatPrice()`.

---

## 📞 الدعم والتواصل

لأي استفسار تقني حول النظام، راجع ملفات الكود أو تواصل مع فريق التطوير.

---
**© 2025 نظام نقل موريتانيا**
