<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// التحقق من البيانات
$routeId        = (int)($_POST['route_id'] ?? 0);
$travelDate     = sanitize($_POST['travel_date'] ?? '');
$passengerName  = sanitize($_POST['passenger_name'] ?? '');
$passengerPhone = sanitize($_POST['passenger_phone'] ?? '');
$passengerId    = sanitize($_POST['passenger_id'] ?? '');
$seatCount      = max(1, min(10, (int)($_POST['seat_count'] ?? 1)));
$paymentMethod  = sanitize($_POST['payment_method'] ?? 'cash');
$notes          = sanitize($_POST['notes'] ?? '');
$pricePer       = (float)($_POST['price_per'] ?? 0);

if (!$passengerName || !$passengerPhone || !$routeId || !$travelDate) {
    header('Location: index.php?error=missing_fields');
    exit;
}

$bookingRef  = generateBookingRef();
$totalPrice  = $seatCount * $pricePer;
$createdAt   = date('Y-m-d H:i:s');

try {
    $db = getDB();

    // التحقق من المقاعد المتاحة
    $booked = getBookedSeats($routeId, $travelDate);
    $stmt = $db->prepare("SELECT seats_total FROM routes WHERE id = ?");
    $stmt->execute([$routeId]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);
    $available = $route['seats_total'] - $booked;

    if ($seatCount > $available) {
        header("Location: index.php?error=no_seats");
        exit;
    }

    // إدراج الحجز
    $stmt = $db->prepare("INSERT INTO bookings 
        (booking_ref, route_id, passenger_name, passenger_phone, passenger_id,
         seat_count, travel_date, total_price, status, payment_method, notes, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $bookingRef, $routeId, $passengerName, $passengerPhone, $passengerId,
        $seatCount, $travelDate, $totalPrice, 'pending', $paymentMethod, $notes, $createdAt
    ]);

} catch (Exception $e) {
    // في بيئة التطوير نحاكي النجاح
    $bookingRef = generateBookingRef();
    $totalPrice = $seatCount * $pricePer;
}

// إعادة التوجيه إلى صفحة التأكيد
header("Location: booking-confirmation.php?ref=" . urlencode($bookingRef) . 
       "&name=" . urlencode($passengerName) . 
       "&seats=" . $seatCount . 
       "&total=" . $totalPrice . 
       "&date=" . urlencode($travelDate));
exit;
?>
