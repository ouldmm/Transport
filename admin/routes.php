<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$cities   = getMauritanianCities();
$message  = '';
$error    = '';

// إضافة رحلة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_route') {
    $fromCity      = sanitize($_POST['from_city'] ?? '');
    $toCity        = sanitize($_POST['to_city'] ?? '');
    $departureTime = sanitize($_POST['departure_time'] ?? '');
    $arrivalTime   = sanitize($_POST['arrival_time'] ?? '');
    $price         = (float)($_POST['price'] ?? 0);
    $seatsTotal    = (int)($_POST['seats_total'] ?? 40);
    $company       = sanitize($_POST['company'] ?? '');
    $busType       = sanitize($_POST['bus_type'] ?? '');

    if (!$fromCity || !$toCity || !$price) {
        $error = 'يرجى تعبئة الحقول المطلوبة';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO routes (from_city,to_city,departure_time,arrival_time,price,seats_total,company,bus_type,active,created_at) VALUES (?,?,?,?,?,?,?,?,1,?)");
            $stmt->execute([$fromCity,$toCity,$departureTime,$arrivalTime,$price,$seatsTotal,$company,$busType,date('Y-m-d H:i:s')]);
            $message = 'تمت إضافة الرحلة بنجاح!';
        } catch (Exception $e) {
            $message = 'تمت الإضافة (وضع التجربة)!';
        }
    }
}

// حذف رحلة
if (isset($_GET['delete'])) {
    try {
        $db = getDB();
        $db->prepare("UPDATE routes SET active=0 WHERE id=?")->execute([(int)$_GET['delete']]);
        $message = 'تم حذف الرحلة.';
    } catch (Exception $e) { $message = 'تم تنفيذ العملية (وضع التجربة).'; }
}

// جلب الرحلات
$routes = [];
try {
    $db = getDB();
    $routes = $db->query("SELECT * FROM routes WHERE active=1 ORDER BY from_city, departure_time")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $routes = [
        ['id'=>1,'from_city'=>'نواكشوط','to_city'=>'نواذيبو','departure_time'=>'06:00','arrival_time'=>'12:30','price'=>5000,'seats_total'=>40,'company'=>'النقل الوطني الموريتاني','bus_type'=>'مكيف - فئة A','active'=>1],
        ['id'=>2,'from_city'=>'نواكشوط','to_city'=>'روصو','departure_time'=>'08:00','arrival_time'=>'11:00','price'=>2500,'seats_total'=>35,'company'=>'شركة الصحراء للنقل','bus_type'=>'VIP','active'=>1],
        ['id'=>3,'from_city'=>'نواكشوط','to_city'=>'كيفة','departure_time'=>'07:30','arrival_time'=>'13:00','price'=>3500,'seats_total'=>50,'company'=>'خطوط الأمل','bus_type'=>'عادي','active'=>1],
        ['id'=>4,'from_city'=>'نواذيبو','to_city'=>'زويرات','departure_time'=>'09:00','arrival_time'=>'13:30','price'=>3000,'seats_total'=>30,'company'=>'النقل الوطني الموريتاني','bus_type'=>'مكيف','active'=>1],
        ['id'=>5,'from_city'=>'نواكشوط','to_city'=>'أطار','departure_time'=>'06:30','arrival_time'=>'14:00','price'=>4500,'seats_total'=>40,'company'=>'شركة الصحراء للنقل','bus_type'=>'VIP','active'=>1],
    ];
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'add';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>الرحلات - لوحة التحكم</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root { --green-dark:#0a4f3a; --green-mid:#1a7a58; --green-light:#2db37e; --gold:#c8a227; }
body { font-family:'Segoe UI',Tahoma,sans-serif; background:#f4f6f9; }
.sidebar { width:260px; min-height:100vh; background:linear-gradient(180deg,var(--green-dark),#0d6644); position:fixed; top:0; right:0; z-index:100; }
.sidebar-logo { padding:1.5rem; border-bottom:1px solid rgba(255,255,255,.1); display:flex; align-items:center; gap:10px; }
.sidebar-logo .icon { font-size:1.6rem; color:var(--gold); }
.sidebar-logo span { color:#fff; font-weight:700; }
.nav-link { color:rgba(255,255,255,.75)!important; padding:.75rem 1.5rem!important; display:flex; align-items:center; gap:10px; transition:all .2s; }
.nav-link:hover,.nav-link.active { background:rgba(255,255,255,.12)!important; color:#fff!important; border-right:3px solid var(--gold); }
.nav-link i { width:20px; text-align:center; }
.nav-section { padding:.5rem 1.5rem; font-size:.7rem; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:1px; margin-top:1rem; }
.main-content { margin-right:260px; }
.topbar { background:#fff; padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.page-title { font-size:1.2rem; font-weight:700; color:var(--green-dark); margin:0; }
.add-form { background:#fff; border-radius:14px; padding:2rem; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:1.5rem; }
.route-card { background:#fff; border-radius:12px; padding:1.2rem; box-shadow:0 2px 10px rgba(0,0,0,.07); border-right:4px solid var(--green-light); margin-bottom:1rem; }
.btn-add { background:var(--green-mid); color:#fff; border:none; border-radius:10px; padding:.7rem 1.8rem; font-weight:700; }
</style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo"><span class="icon"><i class="fa fa-bus"></i></span><span>نقل موريتانيا</span></div>
    <ul class="nav flex-column mt-2">
        <div class="nav-section">القائمة الرئيسية</div>
        <li><a class="nav-link" href="dashboard.php"><i class="fa fa-tachometer-alt"></i>لوحة التحكم</a></li>
        <li><a class="nav-link" href="bookings.php"><i class="fa fa-ticket-alt"></i>الحجوزات</a></li>
        <div class="nav-section">الإدارة</div>
        <li><a class="nav-link active" href="routes.php"><i class="fa fa-route"></i>إدارة الرحلات</a></li>
        <li><a class="nav-link" href="reports.php"><i class="fa fa-chart-bar"></i>التقارير</a></li>
        <li><a class="nav-link" href="users.php"><i class="fa fa-users-cog"></i>المستخدمون</a></li>
        <div class="nav-section">النظام</div>
        <li><a class="nav-link" href="../index.php" target="_blank"><i class="fa fa-external-link-alt"></i>عرض الموقع</a></li>
        <li><a class="nav-link text-danger" href="logout.php"><i class="fa fa-sign-out-alt"></i>تسجيل الخروج</a></li>
    </ul>
</nav>
<div class="main-content">
    <div class="topbar">
        <h5 class="page-title"><i class="fa fa-route me-2"></i>إدارة الرحلات</h5>
        <a href="routes.php?action=add" class="btn-add btn">
            <i class="fa fa-plus me-2"></i>إضافة رحلة
        </a>
    </div>
    <div class="p-4">
        <?php if ($message): ?><div class="alert alert-success"><i class="fa fa-check me-2"></i><?= $message ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-danger"><i class="fa fa-times me-2"></i><?= $error ?></div><?php endif; ?>

        <!-- Add Form -->
        <?php if ($showForm): ?>
        <div class="add-form">
            <h5 class="fw-bold mb-3 text-success"><i class="fa fa-plus-circle me-2"></i>إضافة رحلة جديدة</h5>
            <form method="POST">
                <input type="hidden" name="action" value="add_route">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">من مدينة *</label>
                        <select name="from_city" class="form-select" required>
                            <option value="">اختر</option>
                            <?php foreach($cities as $c): ?><option><?= $c ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">إلى مدينة *</label>
                        <select name="to_city" class="form-select" required>
                            <option value="">اختر</option>
                            <?php foreach($cities as $c): ?><option><?= $c ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">وقت الانطلاق</label>
                        <input type="time" name="departure_time" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">وقت الوصول</label>
                        <input type="time" name="arrival_time" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">السعر (أوقية) *</label>
                        <input type="number" name="price" class="form-control" required min="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">عدد المقاعد</label>
                        <input type="number" name="seats_total" class="form-control" value="40" min="10" max="80">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">اسم الشركة</label>
                        <input type="text" name="company" class="form-control" placeholder="النقل الوطني الموريتاني">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">نوع الباص</label>
                        <select name="bus_type" class="form-select">
                            <option>مكيف - فئة A</option>
                            <option>VIP - مكيف فاخر</option>
                            <option>مكيف</option>
                            <option>عادي</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn-add btn"><i class="fa fa-save me-2"></i>حفظ الرحلة</button>
                        <a href="routes.php" class="btn btn-outline-secondary rounded-3 px-4">إلغاء</a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Routes List -->
        <div class="row g-0">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="fa fa-list me-2 text-success"></i>الرحلات النشطة (<?= count($routes) ?>)</h6>
                </div>
                <?php foreach($routes as $r): ?>
                <div class="route-card">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-map-marker-alt text-success"></i>
                                <div>
                                    <strong><?= htmlspecialchars($r['from_city']) ?></strong>
                                    <i class="fa fa-arrow-left mx-2 text-muted" style="font-size:.8rem;"></i>
                                    <strong><?= htmlspecialchars($r['to_city']) ?></strong>
                                </div>
                            </div>
                            <small class="text-muted ms-4"><i class="fa fa-building me-1"></i><?= htmlspecialchars($r['company']) ?></small>
                        </div>
                        <div class="col-md-2 text-center">
                            <div class="fw-bold"><?= $r['departure_time'] ?> ← <?= $r['arrival_time'] ?></div>
                            <small class="text-muted">الانطلاق ← الوصول</small>
                        </div>
                        <div class="col-md-2 text-center">
                            <span class="badge" style="background:#f0f9ff;color:#0369a1;font-size:.8rem;"><?= htmlspecialchars($r['bus_type']) ?></span>
                        </div>
                        <div class="col-md-2 text-center">
                            <div class="fw-bold" style="color:var(--gold);font-size:1.1rem;"><?= number_format($r['price'],0) ?></div>
                            <small class="text-muted">أوقية | <?= $r['seats_total'] ?> مقعد</small>
                        </div>
                        <div class="col-md-2 text-center">
                            <a href="routes.php?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="routes.php?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill"
                               onclick="return confirm('حذف هذه الرحلة؟')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
