<?php
// ================================================
// setup.php - إعداد أولي لقاعدة البيانات (SQLite)
// قم بتشغيله مرة واحدة ثم احذفه من الخادم!
// ================================================

require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css'>";
echo "</head><body class='container py-4'>";
echo "<h2>إعداد نظام نقل موريتانيا</h2>";

try {
    $db = getDB();
    initDatabase();
    echo "<div class='alert alert-success'>✅ تم إنشاء قاعدة البيانات بنجاح!</div>";

    // إنشاء مستخدم admin إذا لم يكن موجوداً
    $stmt = $db->query("SELECT COUNT(*) as c FROM users");
    if ($stmt->fetch()['c'] == 0) {
        $hash = hashPassword('admin123');
        $db->prepare("INSERT INTO users (username,password,full_name,role,active) VALUES (?,?,?,?,1)")
           ->execute(['admin', $hash, 'مدير النظام', 'admin']);
        echo "<div class='alert alert-info'>✅ تم إنشاء حساب المدير: admin / admin123</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ حساب المدير موجود مسبقاً.</div>";
    }

    // إدراج رحلات نموذجية إذا لم تكن موجودة
    $stmt = $db->query("SELECT COUNT(*) as c FROM routes");
    if ($stmt->fetch()['c'] == 0) {
        $routes = [
            ['نواكشوط','نواذيبو','06:00','12:30',5000,40,'النقل الوطني الموريتاني','مكيف - فئة A'],
            ['نواكشوط','روصو','08:00','11:00',2500,35,'شركة الصحراء للنقل','VIP'],
            ['نواكشوط','كيفة','07:30','13:00',3500,50,'خطوط الأمل','عادي'],
            ['نواذيبو','زويرات','09:00','13:30',3000,30,'النقل الوطني الموريتاني','مكيف'],
            ['نواكشوط','أطار','06:30','14:00',4500,40,'شركة الصحراء للنقل','VIP'],
            ['روصو','كيهيدي','10:00','12:00',2000,30,'خطوط الأمل','عادي'],
        ];
        foreach ($routes as $r) {
            $db->prepare("INSERT INTO routes (from_city,to_city,departure_time,arrival_time,price,seats_total,company,bus_type,active,created_at) VALUES (?,?,?,?,?,?,?,?,1,?)")
               ->execute(array_merge($r, [date('Y-m-d H:i:s')]));
        }
        echo "<div class='alert alert-success'>✅ تم إدراج " . count($routes) . " رحلات نموذجية!</div>";
    }

    echo "<hr><p><strong>⚠️ بعد الإعداد:</strong> احذف هذا الملف (setup.php) من الخادم!</p>";
    echo "<p><a href='index.php' class='btn btn-success'>→ الذهاب إلى الموقع</a> &nbsp; ";
    echo "<a href='admin/login.php' class='btn btn-warning'>→ لوحة التحكم</a></p>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ خطأ: " . $e->getMessage() . "</div>";
}
echo "</body></html>";
?>
