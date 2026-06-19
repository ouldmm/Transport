<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$booking = null;
$error   = '';
$ref     = sanitize($_GET['ref'] ?? '');

if ($ref) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT b.*, r.from_city, r.to_city, r.departure_time, r.company, r.bus_type
                              FROM bookings b LEFT JOIN routes r ON b.route_id = r.id
                              WHERE b.booking_ref = ?");
        $stmt->execute([$ref]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) $error = 'لم يتم العثور على حجز بهذا الرقم.';
    } catch (Exception $e) {
        // Demo data
        if ($ref === 'MRT-DEMO1234') {
            $booking = [
                'booking_ref'    => 'MRT-DEMO1234',
                'passenger_name' => 'محمد أحمد',
                'passenger_phone'=> '22 XX XX XX',
                'from_city'      => 'نواكشوط',
                'to_city'        => 'نواذيبو',
                'departure_time' => '06:00',
                'travel_date'    => '2025-08-15',
                'seat_count'     => 2,
                'total_price'    => 10000,
                'status'         => 'confirmed',
                'payment_method' => 'cash',
                'company'        => 'النقل الوطني',
                'bus_type'       => 'مكيف',
                'created_at'     => date('Y-m-d H:i:s'),
            ];
        } else {
            $error = 'لم يتم العثور على الحجز.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تتبع الحجز - نقل موريتانيا</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { background: #f0f8f4; font-family: 'Segoe UI', Tahoma, sans-serif; }
.search-card { max-width: 560px; margin: 3rem auto; background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 6px 24px rgba(0,80,50,0.12); }
.booking-result { max-width: 620px; margin: 2rem auto; }
.status-confirmed { color: #1a7a58; }
.status-pending   { color: #f39c12; }
.status-cancelled { color: #e74c3c; }
</style>
</head>
<body>
<div class="container">
  <div class="search-card">
    <div class="text-center mb-3">
      <i class="fa fa-search fa-2x text-success"></i>
      <h4 class="mt-2 fw-bold">تتبع حجزك</h4>
      <p class="text-muted small">أدخل رقم الحجز للتحقق من حالته</p>
    </div>
    <form method="GET">
      <div class="input-group mb-3">
        <input type="text" name="ref" class="form-control form-control-lg text-center" 
               placeholder="MRT-XXXXXXXX" value="<?= htmlspecialchars($ref) ?>"
               style="border-radius:10px 0 0 10px;font-weight:700;letter-spacing:2px;">
        <button type="submit" class="btn btn-success px-4" style="border-radius:0 10px 10px 0;font-weight:700;">
          <i class="fa fa-search me-1"></i>بحث
        </button>
      </div>
    </form>
    <p class="text-center text-muted small mt-2">
      مثال: <a href="?ref=MRT-DEMO1234" class="text-success">MRT-DEMO1234</a>
    </p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger text-center booking-result"><i class="fa fa-times-circle me-2"></i><?= $error ?></div>
  <?php endif; ?>

  <?php if ($booking): ?>
  <div class="booking-result">
    <div class="card border-0 shadow" style="border-radius:16px;overflow:hidden;">
      <div class="card-header py-3" style="background:<?= $booking['status']==='confirmed'?'#0a4f3a':($booking['status']==='cancelled'?'#e74c3c':'#f39c12') ?>;color:#fff;">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fa fa-ticket-alt me-2"></i><?= $booking['booking_ref'] ?></h5>
          <?= getStatusBadge($booking['status']) ?>
        </div>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-6">
            <small class="text-muted">المسافر</small>
            <div class="fw-bold"><?= htmlspecialchars($booking['passenger_name']) ?></div>
          </div>
          <div class="col-6">
            <small class="text-muted">الهاتف</small>
            <div class="fw-bold"><?= htmlspecialchars($booking['passenger_phone']) ?></div>
          </div>
          <div class="col-6">
            <small class="text-muted">الرحلة</small>
            <div class="fw-bold"><?= htmlspecialchars($booking['from_city']) ?> ← <?= htmlspecialchars($booking['to_city']) ?></div>
          </div>
          <div class="col-6">
            <small class="text-muted">موعد الانطلاق</small>
            <div class="fw-bold"><?= htmlspecialchars($booking['departure_time'] ?? '—') ?></div>
          </div>
          <div class="col-6">
            <small class="text-muted">تاريخ السفر</small>
            <div class="fw-bold"><?= $booking['travel_date'] ? date('d/m/Y', strtotime($booking['travel_date'])) : '—' ?></div>
          </div>
          <div class="col-6">
            <small class="text-muted">عدد المقاعد</small>
            <div class="fw-bold"><?= $booking['seat_count'] ?></div>
          </div>
          <div class="col-6">
            <small class="text-muted">الشركة</small>
            <div class="fw-bold"><?= htmlspecialchars($booking['company'] ?? '—') ?></div>
          </div>
          <div class="col-6">
            <small class="text-muted">إجمالي السعر</small>
            <div class="fw-bold text-success" style="font-size:1.2rem;"><?= number_format($booking['total_price'], 0) ?> أوقية</div>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center mt-3">
      <a href="index.php" class="btn btn-outline-success rounded-pill px-4">
        <i class="fa fa-home me-1"></i>الصفحة الرئيسية
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
