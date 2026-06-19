<?php
// =============================================
// دوال مساعدة عامة
// =============================================

session_start();

function generateBookingRef() {
    return 'MRT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        // Detect path depth to build correct redirect
        $script = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if (strpos($script, '/admin/') !== false) {
            header('Location: login.php');
        } else {
            header('Location: admin/login.php');
        }
        exit;
    }
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// تشفير كلمة المرور قبل تخزينها في قاعدة البيانات
function hashPassword($plainPassword) {
    return password_hash($plainPassword, PASSWORD_DEFAULT);
}

// التحقق من تطابق كلمة المرور المدخلة مع النسخة المشفرة المخزنة
function verifyPassword($plainPassword, $hashedPassword) {
    return password_verify($plainPassword, $hashedPassword);
}

function formatPrice($price) {
    return number_format($price, 0, '.', ',') . ' أوقية';
}

function getStatusBadge($status) {
    $badges = [
        'pending'   => ['label' => 'في الانتظار', 'class' => 'warning'],
        'confirmed' => ['label' => 'مؤكد',        'class' => 'success'],
        'cancelled' => ['label' => 'ملغي',         'class' => 'danger'],
        'completed' => ['label' => 'مكتمل',        'class' => 'info'],
    ];
    $b = $badges[$status] ?? ['label' => $status, 'class' => 'secondary'];
    return "<span class=\"badge bg-{$b['class']}\">{$b['label']}</span>";
}

// الحصول على عدد المقاعد المحجوزة لرحلة في يوم محدد
function getBookedSeats($routeId, $travelDate) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(seat_count) as total FROM bookings 
                          WHERE route_id = ? AND travel_date = ? AND status != 'cancelled'");
    $stmt->execute([$routeId, $travelDate]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($row['total'] ?? 0);
}

// المدن الموريتانية
function getMauritanianCities() {
    return [
        'نواكشوط', 'نواذيبو', 'روصو', 'زويرات', 'كيفة',
        'كيهيدي', 'سيلبابي', 'ألاك', 'تيجيكجة', 'أطار',
        'أيون', 'نمامقة', 'بوتلميت', 'مقطع لحجار', 'لعيون',
        'تمبدغة', 'وادان', 'شنقيط', 'تيشيت', 'ولاتة',
        'أكجوجت', 'بئر أم اكرين', 'فدريك'
    ];
}
?>
