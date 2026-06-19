<?php
// =============================================
// قاعدة البيانات - SQLite Connection
// =============================================

define('DB_PATH', __DIR__ . '/../database/transport_mauritanie.db');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $dsn = 'sqlite:' . DB_PATH;
        try {
            $conn = new PDO($dsn);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // تفعيل Foreign Keys في SQLite
            $conn->exec("PRAGMA foreign_keys = ON");
        } catch (PDOException $e) {
            die(json_encode(['error' => 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage()]));
        }
    }
    return $conn;
}

// =============================================
// إنشاء قاعدة البيانات إذا لم تكن موجودة
// ملاحظة: يجب إنشاء ملف MDB يدوياً أو باستخدام ADOX
// =============================================
function initDatabase() {
    $db = getDB();
    
    // جدول الباصات / الرحلات
    $db->exec("CREATE TABLE IF NOT EXISTS routes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_city TEXT NOT NULL,
        to_city TEXT NOT NULL,
        departure_time TEXT,
        arrival_time TEXT,
        price REAL,
        seats_total INTEGER DEFAULT 40,
        company TEXT,
        bus_type TEXT,
        active INTEGER DEFAULT 1,
        created_at TEXT
    )");

    // جدول الحجوزات
    $db->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_ref TEXT UNIQUE,
        route_id INTEGER,
        passenger_name TEXT,
        passenger_phone TEXT,
        passenger_id TEXT,
        seat_count INTEGER DEFAULT 1,
        travel_date TEXT,
        total_price REAL,
        status TEXT DEFAULT 'pending',
        payment_method TEXT,
        notes TEXT,
        created_at TEXT,
        FOREIGN KEY(route_id) REFERENCES routes(id)
    )");

    // جدول المدن
    $db->exec("CREATE TABLE IF NOT EXISTS cities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name_ar TEXT,
        name_fr TEXT,
        region TEXT
    )");

    // جدول المستخدمين (admin)
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT,
        full_name TEXT,
        role TEXT DEFAULT 'staff',
        active INTEGER DEFAULT 1,
        last_login TEXT
    )");
}
?>
