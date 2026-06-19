<?php
require_once 'includes/functions.php';

$ref   = sanitize($_GET['ref']   ?? '');
$name  = sanitize($_GET['name']  ?? '');
$seats = (int)($_GET['seats'] ?? 1);
$total = (float)($_GET['total'] ?? 0);
$date  = sanitize($_GET['date']  ?? '');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تأكيد الحجز - نقل موريتانيا</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { background: #f0f8f4; font-family: 'Segoe UI', Tahoma, sans-serif; }
.ticket-wrap { max-width: 580px; margin: 3rem auto; }
.ticket {
    background: #fff; border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0,80,50,0.15);
    overflow: hidden;
}
.ticket-header {
    background: linear-gradient(135deg, #0a4f3a, #2db37e);
    color: #fff; padding: 2rem; text-align: center;
}
.ticket-header .check-circle {
    width: 72px; height: 72px; background: rgba(255,255,255,0.2);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem; margin: 0 auto 1rem;
}
.ticket-body { padding: 2rem; }
.booking-ref {
    font-size: 2rem; font-weight: 800; color: #0a4f3a;
    letter-spacing: 3px; text-align: center; margin-bottom: 1.5rem;
    border: 2px dashed #2db37e; border-radius: 10px; padding: 0.8rem;
}
.detail-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.6rem 0; border-bottom: 1px solid #f0f0f0;
}
.detail-row:last-child { border: none; }
.detail-label { color: #888; font-size: 0.9rem; }
.detail-value { font-weight: 600; color: #1a1a1a; }
.ticket-divider {
    border: none; border-top: 2px dashed #e0e0e0; margin: 1.5rem 0;
    position: relative;
}
.btn-print {
    background: #0a4f3a; color: #fff; border: none; border-radius: 10px;
    padding: 0.8rem 2rem; font-weight: 700; width: 100%;
}
@media print { .no-print { display: none !important; } }
</style>
</head>
<body>
<div class="ticket-wrap">
    <div class="ticket">
        <div class="ticket-header">
            <div class="check-circle"><i class="fa fa-check"></i></div>
            <h3 class="mb-1">تم تأكيد حجزك!</h3>
            <p class="mb-0 opacity-75">سيتم التواصل معك قبل موعد السفر</p>
        </div>
        <div class="ticket-body">
            <div class="booking-ref"><?= htmlspecialchars($ref) ?></div>

            <div class="detail-row">
                <span class="detail-label"><i class="fa fa-user me-2"></i>اسم المسافر</span>
                <span class="detail-value"><?= htmlspecialchars($name) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fa fa-calendar me-2"></i>تاريخ السفر</span>
                <span class="detail-value"><?= $date ? date('d/m/Y', strtotime($date)) : '—' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fa fa-chair me-2"></i>عدد المقاعد</span>
                <span class="detail-value"><?= $seats ?> مقعد</span>
            </div>
            <hr class="ticket-divider">
            <div class="detail-row">
                <span class="detail-label" style="font-size:1rem;font-weight:600;">المبلغ الإجمالي</span>
                <span class="detail-value" style="font-size:1.4rem;color:#c8a227;"><?= number_format($total, 0) ?> أوقية</span>
            </div>
            <div class="alert alert-info mt-3 py-2 small">
                <i class="fa fa-info-circle me-1"></i>
                <strong>تعليمات:</strong> احتفظ برقم الحجز وأبرزه عند الصعود. تفضل قبل موعد الانطلاق بـ 15 دقيقة على الأقل.
            </div>
            <div class="d-flex gap-2 mt-3 no-print">
                <button onclick="window.print()" class="btn-print btn">
                    <i class="fa fa-print me-2"></i>طباعة التذكرة
                </button>
                <a href="index.php" class="btn btn-outline-secondary w-100 rounded-3">
                    <i class="fa fa-home me-2"></i>الرئيسية
                </a>
            </div>
        </div>
    </div>

    <div class="text-center mt-3 no-print">
        <a href="check-booking.php?ref=<?= urlencode($ref) ?>" class="text-success">
            <i class="fa fa-search me-1"></i>تتبع حالة الحجز
        </a>
    </div>
</div>
</body>
</html>
