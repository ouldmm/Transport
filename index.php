<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$cities = getMauritanianCities();

// البحث عن رحلات
$routes = [];
$searchDone = false;
$fromCity = $toCity = $travelDate = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from'])) {
    $fromCity   = sanitize($_GET['from'] ?? '');
    $toCity     = sanitize($_GET['to'] ?? '');
    $travelDate = sanitize($_GET['date'] ?? '');
    $searchDone = true;

    if ($fromCity && $toCity && $travelDate) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM routes WHERE from_city = ? AND to_city = ? AND active = 1");
            $stmt->execute([$fromCity, $toCity]);
            $rawRoutes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rawRoutes as $r) {
                $booked = getBookedSeats($r['id'], $travelDate);
                $r['available_seats'] = $r['seats_total'] - $booked;
                $routes[] = $r;
            }
        } catch (Exception $e) {
            // في بيئة التطوير، نعرض بيانات تجريبية
            $routes = getDemoRoutes($fromCity, $toCity, $travelDate);
        }
    }
}

function getDemoRoutes($from, $to, $date) {
    return [
        ['id'=>1,'from_city'=>$from,'to_city'=>$to,'departure_time'=>'06:00','arrival_time'=>'12:00',
         'price'=>3500,'seats_total'=>40,'available_seats'=>22,'company'=>'النقل الوطني الموريتاني','bus_type'=>'مكيف - فئة A'],
        ['id'=>2,'from_city'=>$from,'to_city'=>$to,'departure_time'=>'09:30','arrival_time'=>'16:00',
         'price'=>4200,'seats_total'=>35,'available_seats'=>8,'company'=>'شركة الصحراء للنقل','bus_type'=>'VIP - مكيف فاخر'],
        ['id'=>3,'from_city'=>$from,'to_city'=>$to,'departure_time'=>'14:00','arrival_time'=>'21:30',
         'price'=>2800,'seats_total'=>50,'available_seats'=>31,'company'=>'خطوط الأمل','bus_type'=>'عادي'],
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>حجز تذاكر الباص - موريتانيا</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --green-dark: #0a4f3a;
    --green-mid:  #1a7a58;
    --green-light:#2db37e;
    --gold:       #c8a227;
    --sand:       #f5f0e8;
    --red-flag:   #d63031;
}
* { box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--sand); color: #1a1a1a; margin: 0; }

/* Header */
.site-header {
    background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-mid) 60%, var(--green-light) 100%);
    padding: 0;
    box-shadow: 0 2px 12px rgba(0,0,0,0.25);
}
.header-top {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 2rem;
    border-bottom: 2px solid var(--gold);
}
.logo-area { display: flex; align-items: center; gap: 12px; }
.logo-icon {
    width: 54px; height: 54px; background: var(--gold); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; color: var(--green-dark);
}
.site-name { color: #fff; }
.site-name h1 { font-size: 1.4rem; margin: 0; font-weight: 700; }
.site-name p  { font-size: 0.78rem; margin: 0; opacity: 0.85; }
.flag-stripe {
    height: 5px;
    background: linear-gradient(to right, var(--green-dark) 33%, var(--gold) 33%, var(--gold) 67%, var(--red-flag) 67%);
}

/* Hero */
.hero {
    background: linear-gradient(135deg, var(--green-dark) 0%, #1b6b4a 100%);
    padding: 3rem 1rem 5rem;
    text-align: center; color: #fff;
}
.hero h2 { font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem; }
.hero p  { font-size: 1.05rem; opacity: 0.9; }

/* Search Box */
.search-box {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    margin-top: -3rem;
    position: relative; z-index: 10;
    max-width: 900px; margin-left: auto; margin-right: auto;
}
.search-box .form-label { font-weight: 600; color: var(--green-dark); }
.search-box .form-control, .search-box .form-select {
    border: 2px solid #e0e0e0; border-radius: 10px;
    padding: 0.7rem 1rem; font-size: 1rem;
    transition: border-color .2s;
}
.search-box .form-control:focus, .search-box .form-select:focus {
    border-color: var(--green-light); box-shadow: 0 0 0 3px rgba(45,179,126,0.15);
}
.btn-search {
    background: linear-gradient(135deg, var(--green-mid), var(--green-light));
    color: #fff; font-weight: 700; font-size: 1.1rem;
    border: none; border-radius: 12px; padding: 0.8rem 2.5rem;
    width: 100%; transition: transform .1s, box-shadow .2s;
}
.btn-search:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(26,122,88,0.4); color:#fff; }

/* Route Cards */
.route-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 3px 16px rgba(0,0,0,0.09);
    padding: 1.5rem; margin-bottom: 1.2rem;
    border-right: 5px solid var(--green-light);
    transition: transform .2s, box-shadow .2s;
}
.route-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.14); }
.route-time { font-size: 1.6rem; font-weight: 800; color: var(--green-dark); }
.route-city { font-size: 0.9rem; color: #666; }
.route-arrow { color: var(--gold); font-size: 1.8rem; }
.route-price { font-size: 1.5rem; font-weight: 800; color: var(--gold); }
.route-badge { border-radius: 20px; font-size: 0.78rem; padding: 4px 12px; }
.seats-bar { height: 6px; border-radius: 3px; background: #e8e8e8; overflow: hidden; }
.seats-fill { height: 100%; border-radius: 3px; background: var(--green-light); }
.btn-book {
    background: var(--green-mid); color: #fff; border: none;
    border-radius: 10px; padding: 0.6rem 1.8rem; font-weight: 700;
    transition: background .2s;
}
.btn-book:hover { background: var(--green-dark); color: #fff; }
.btn-book:disabled { background: #ccc; }

/* Features */
.features { padding: 4rem 0; background: #fff; }
.feature-icon { font-size: 2.5rem; color: var(--green-mid); margin-bottom: 1rem; }

/* Footer */
footer {
    background: var(--green-dark); color: #fff;
    padding: 2.5rem 0 1rem; margin-top: 4rem;
}
footer a { color: var(--gold); text-decoration: none; }
.footer-divider { border-color: rgba(255,255,255,0.2); }

/* Modal Booking */
.modal-header { background: var(--green-dark); color: #fff; border-radius: 12px 12px 0 0; }
.modal-header .btn-close { filter: invert(1); }
.step-indicator span { display: inline-block; width: 28px; height: 28px; border-radius: 50%; 
    background: #ddd; color: #666; font-weight: 700; font-size: 0.85rem;
    line-height: 28px; text-align: center; margin: 0 4px; }
.step-indicator span.active { background: var(--green-mid); color: #fff; }

@media (max-width: 768px) {
    .hero h2 { font-size: 1.5rem; }
    .route-time { font-size: 1.2rem; }
    .route-price { font-size: 1.2rem; }
}
</style>
</head>
<body>

<!-- Header -->
<header class="site-header">
    <div class="header-top">
        <div class="logo-area">
            <div class="logo-icon"><i class="fa-solid fa-bus"></i></div>
            <div class="site-name">
                <h1>نقل موريتانيا</h1>
                <p>Transport Mauritanie — حجز التذاكر الإلكتروني</p>
            </div>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <a href="check-booking.php" class="btn btn-outline-light btn-sm rounded-pill">
                <i class="fa fa-search me-1"></i> تتبع حجزي
            </a>
            <a href="admin/login.php" class="btn btn-warning btn-sm rounded-pill text-dark fw-bold">
                <i class="fa fa-lock me-1"></i> لوحة التحكم
            </a>
        </div>
    </div>
    <div class="flag-stripe"></div>
</header>

<!-- Hero -->
<section class="hero">
    <h2><i class="fa fa-route me-2"></i>احجز رحلتك عبر موريتانيا</h2>
    <p>أسهل وأسرع طريقة لحجز تذاكر الباص بين مدن موريتانيا</p>
</section>

<!-- Search Form -->
<div class="container" style="margin-top:0; padding-bottom: 1rem;">
    <div class="search-box">
        <form method="GET" action="">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><i class="fa fa-map-marker-alt text-success me-1"></i>من مدينة</label>
                    <select name="from" class="form-select" required>
                        <option value="">اختر المدينة</option>
                        <?php foreach($cities as $c): ?>
                        <option value="<?= $c ?>" <?= $fromCity===$c?'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fa fa-map-marker text-danger me-1"></i>إلى مدينة</label>
                    <select name="to" class="form-select" required>
                        <option value="">اختر المدينة</option>
                        <?php foreach($cities as $c): ?>
                        <option value="<?= $c ?>" <?= $toCity===$c?'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fa fa-calendar text-info me-1"></i>تاريخ السفر</label>
                    <input type="date" name="date" class="form-control" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($travelDate) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-search btn"><i class="fa fa-search me-2"></i>ابحث عن رحلات</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="container py-3">
<?php if ($searchDone && $fromCity && $toCity && $travelDate): ?>
    <h4 class="mb-3 text-success fw-bold">
        <i class="fa fa-bus me-2"></i>
        الرحلات من <span class="text-dark"><?= htmlspecialchars($fromCity) ?></span> إلى <span class="text-dark"><?= htmlspecialchars($toCity) ?></span>
        — <?= date('d/m/Y', strtotime($travelDate)) ?>
    </h4>

    <?php if (empty($routes)): ?>
    <div class="alert alert-warning text-center py-4">
        <i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i>
        <strong>لا توجد رحلات متاحة</strong> بين هاتين المدينتين في هذا التاريخ.<br>
        <small class="text-muted">جرب تواريخ أو مدناً أخرى.</small>
    </div>
    <?php else: ?>
    <?php foreach($routes as $r): ?>
    <?php
        $seatsPercent = $r['seats_total'] > 0 ? ($r['available_seats']/$r['seats_total'])*100 : 0;
        $seatsColor = $seatsPercent > 50 ? '#2db37e' : ($seatsPercent > 20 ? '#f39c12' : '#e74c3c');
    ?>
    <div class="route-card">
        <div class="row align-items-center">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-center">
                        <div class="route-time"><?= htmlspecialchars($r['departure_time']) ?></div>
                        <div class="route-city"><?= htmlspecialchars($r['from_city']) ?></div>
                    </div>
                    <div class="route-arrow flex-grow-1 text-center">
                        <i class="fa fa-arrow-left"></i>
                        <div style="font-size:.75rem;color:#888;margin-top:2px;">
                            <?= htmlspecialchars($r['company']) ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="route-time"><?= htmlspecialchars($r['arrival_time']) ?></div>
                        <div class="route-city"><?= htmlspecialchars($r['to_city']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <span class="badge route-badge" style="background:#e8f5f0;color:var(--green-dark);">
                    <i class="fa fa-bus me-1"></i><?= htmlspecialchars($r['bus_type']) ?>
                </span>
            </div>
            <div class="col-md-2 text-center">
                <div style="font-size:.8rem;color:#888;">مقاعد متاحة</div>
                <div class="seats-bar mt-1">
                    <div class="seats-fill" style="width:<?= $seatsPercent ?>%;background:<?= $seatsColor ?>"></div>
                </div>
                <small style="color:<?= $seatsColor ?>;font-weight:600;"><?= $r['available_seats'] ?> / <?= $r['seats_total'] ?></small>
            </div>
            <div class="col-md-2 text-center">
                <div class="route-price"><?= number_format($r['price'], 0) ?></div>
                <small class="text-muted">أوقية / شخص</small>
            </div>
            <div class="col-md-1 text-center">
                <?php if ($r['available_seats'] > 0): ?>
                <button class="btn-book btn" onclick="openBooking(<?= $r['id'] ?>,
                    '<?= htmlspecialchars($r['from_city']) ?>',
                    '<?= htmlspecialchars($r['to_city']) ?>',
                    '<?= htmlspecialchars($r['departure_time']) ?>',
                    <?= $r['price'] ?>,
                    '<?= htmlspecialchars($travelDate) ?>',
                    <?= $r['available_seats'] ?>)">
                    <i class="fa fa-ticket-alt me-1"></i>احجز
                </button>
                <?php else: ?>
                <button class="btn-book btn" disabled>مكتمل</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

<?php elseif($searchDone): ?>
    <div class="alert alert-info">يرجى تعبئة جميع حقول البحث.</div>

<?php else: ?>
<!-- Features Section -->
<div class="row g-4 mt-2">
    <div class="col-md-3 text-center">
        <div class="feature-icon"><i class="fa fa-shield-alt"></i></div>
        <h5>حجز آمن ومضمون</h5>
        <p class="text-muted small">احجز بثقة مع تأكيد فوري ورقم مرجعي</p>
    </div>
    <div class="col-md-3 text-center">
        <div class="feature-icon"><i class="fa fa-clock"></i></div>
        <h5>رحلات يومية</h5>
        <p class="text-muted small">رحلات متعددة يومياً بين جميع مدن موريتانيا</p>
    </div>
    <div class="col-md-3 text-center">
        <div class="feature-icon"><i class="fa fa-mobile-alt"></i></div>
        <h5>حجز بسيط وسريع</h5>
        <p class="text-muted small">احجز تذكرتك في دقائق من هاتفك أو حاسوبك</p>
    </div>
    <div class="col-md-3 text-center">
        <div class="feature-icon"><i class="fa fa-headset"></i></div>
        <h5>دعم على مدار الساعة</h5>
        <p class="text-muted small">فريق الدعم جاهز للمساعدة في أي وقت</p>
    </div>
</div>

<!-- Popular Routes -->
<div class="mt-5">
    <h4 class="fw-bold mb-4"><i class="fa fa-star text-warning me-2"></i>الخطوط الأكثر شعبية</h4>
    <div class="row g-3">
        <?php
        $popularRoutes = [
            ['from'=>'نواكشوط','to'=>'نواذيبو','price'=>5000,'duration'=>'6 ساعات'],
            ['from'=>'نواكشوط','to'=>'روصو',   'price'=>2500,'duration'=>'3 ساعات'],
            ['from'=>'نواكشوط','to'=>'كيفة',   'price'=>3500,'duration'=>'5 ساعات'],
            ['from'=>'نواذيبو','to'=>'زويرات',  'price'=>3000,'duration'=>'4 ساعات'],
            ['from'=>'نواكشوط','to'=>'أطار',   'price'=>4500,'duration'=>'7 ساعات'],
            ['from'=>'روصو',   'to'=>'كيهيدي',  'price'=>2000,'duration'=>'2 ساعة'],
        ];
        foreach($popularRoutes as $pr):
        ?>
        <div class="col-md-4">
            <a href="?from=<?= urlencode($pr['from']) ?>&to=<?= urlencode($pr['to']) ?>&date=<?= date('Y-m-d') ?>"
               class="d-block text-decoration-none">
                <div class="route-card" style="padding:1rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= $pr['from'] ?></strong>
                            <i class="fa fa-arrow-left mx-2 text-success"></i>
                            <strong><?= $pr['to'] ?></strong>
                        </div>
                        <div class="text-end">
                            <div class="route-price" style="font-size:1.1rem;"><?= number_format($pr['price']) ?></div>
                            <small class="text-muted">أوقية</small>
                        </div>
                    </div>
                    <div class="mt-1">
                        <small class="text-muted"><i class="fa fa-clock me-1"></i><?= $pr['duration'] ?></small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;overflow:hidden;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-ticket-alt me-2"></i>حجز تذكرة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="trip-summary" class="alert alert-success mb-4 py-2"></div>
        <form id="bookingForm" method="POST" action="process-booking.php">
            <input type="hidden" name="route_id"    id="f-route-id">
            <input type="hidden" name="travel_date" id="f-travel-date">
            <input type="hidden" name="price_per"   id="f-price-per">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">الاسم الكامل *</label>
                <input type="text" name="passenger_name" class="form-control" required placeholder="محمد أحمد ولد محمد">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">رقم الهاتف *</label>
                <input type="tel" name="passenger_phone" class="form-control" required placeholder="20 XX XX XX">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">رقم الهوية / الجواز</label>
                <input type="text" name="passenger_id" class="form-control" placeholder="اختياري">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">عدد المقاعد *</label>
                <input type="number" name="seat_count" id="f-seats" class="form-control"
                       min="1" max="10" value="1" oninput="updateTotal()">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">طريقة الدفع</label>
                <select name="payment_method" class="form-select">
                    <option value="cash">دفع نقداً عند الصعود</option>
                    <option value="mobile">دفع عبر الهاتف (مصرف موريتانيا)</option>
                    <option value="card">بطاقة بنكية</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="أي طلبات خاصة..."></textarea>
              </div>
            </div>

            <div class="alert alert-warning mt-3 py-2 d-flex justify-content-between align-items-center">
                <span><i class="fa fa-money-bill me-2"></i>إجمالي السعر:</span>
                <strong id="total-price" style="font-size:1.3rem;"></strong>
            </div>

            <button type="submit" class="btn w-100 py-2 fw-bold mt-1" style="background:var(--green-mid);color:#fff;border-radius:10px;font-size:1.05rem;">
                <i class="fa fa-check-circle me-2"></i>تأكيد الحجز
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 class="text-warning fw-bold"><i class="fa fa-bus me-2"></i>نقل موريتانيا</h5>
                <p class="text-white-50 small">منصة حجز تذاكر الباص الرسمية في موريتانيا. نوفر أسهل وأسرع طريقة للسفر.</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-warning">روابط سريعة</h6>
                <ul class="list-unstyled small">
                    <li><a href="check-booking.php"><i class="fa fa-search me-1"></i>تتبع الحجز</a></li>
                    <li><a href="routes.php"><i class="fa fa-route me-1"></i>جدول الرحلات</a></li>
                    <li><a href="contact.php"><i class="fa fa-phone me-1"></i>اتصل بنا</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="text-warning">تواصل معنا</h6>
                <p class="small text-white-50">
                    <i class="fa fa-phone me-1"></i>+222 22 XX XX XX<br>
                    <i class="fa fa-envelope me-1"></i>info@transport-mr.com<br>
                    <i class="fa fa-map-marker-alt me-1"></i>نواكشوط، موريتانيا
                </p>
            </div>
        </div>
        <hr class="footer-divider">
        <p class="text-center text-white-50 small mb-0">© 2025 نقل موريتانيا — جميع الحقوق محفوظة</p>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
var bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
var currentPrice = 0;

function openBooking(routeId, from, to, time, price, date, availSeats) {
    currentPrice = price;
    document.getElementById('f-route-id').value   = routeId;
    document.getElementById('f-travel-date').value = date;
    document.getElementById('f-price-per').value   = price;
    document.getElementById('f-seats').max          = Math.min(availSeats, 10);

    var d = new Date(date);
    var dateStr = d.toLocaleDateString('ar-MR', {weekday:'long',year:'numeric',month:'long',day:'numeric'});

    document.getElementById('trip-summary').innerHTML =
        '<i class="fa fa-bus me-2"></i><strong>' + from + '</strong> ← <strong>' + to + '</strong>' +
        ' &nbsp;|&nbsp; <i class="fa fa-clock me-1"></i>' + time +
        ' &nbsp;|&nbsp; <i class="fa fa-calendar me-1"></i>' + dateStr;

    updateTotal();
    bookingModal.show();
}

function updateTotal() {
    var seats = parseInt(document.getElementById('f-seats').value) || 1;
    var total  = seats * currentPrice;
    document.getElementById('total-price').textContent = total.toLocaleString('ar') + ' أوقية';
}
</script>
</body>
</html>
