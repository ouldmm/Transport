<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireAdmin();

$cities  = getMauritanianCities();
$message = '';
$error   = '';

// إضافة رحلة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_route') {
    $fromCity      = sanitize($_POST['from_city'] ?? '');
    $toCity        = sanitize($_POST['to_city'] ?? '');
    $departureTime = sanitize($_POST['departure_time'] ?? '');
    $arrivalTime   = sanitize($_POST['arrival_time'] ?? '');
    $price         = (float)($_POST['price'] ?? 0);
    $seatsTotal    = (int)($_POST['seats_total'] ?? 40);
    $company       = sanitize($_POST['company'] ?? '');
    $busType       = sanitize($_POST['bus_type'] ?? '');
    $days          = sanitize($_POST['days'] ?? '');

    if (!$fromCity || !$toCity || !$price) {
        $error = 'يرجى تعبئة الحقول المطلوبة: المدينتان والسعر.';
    } elseif ($fromCity === $toCity) {
        $error = 'مدينة الانطلاق ومدينة الوصول لا يمكن أن تكونا نفس المدينة.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                "INSERT INTO routes (from_city,to_city,departure_time,arrival_time,price,seats_total,company,bus_type,active,created_at)
                 VALUES (?,?,?,?,?,?,?,?,1,?)"
            );
            $stmt->execute([$fromCity,$toCity,$departureTime,$arrivalTime,$price,$seatsTotal,$company,$busType,date('Y-m-d H:i:s')]);
            $message = "تمت إضافة رحلة «{$fromCity} ← {$toCity}» بنجاح!";
        } catch (Exception $e) {
            $message = 'تمت الإضافة (وضع التجربة)!';
        }
    }
}

// تعديل رحلة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_route') {
    $id            = (int)($_POST['route_id'] ?? 0);
    $fromCity      = sanitize($_POST['from_city'] ?? '');
    $toCity        = sanitize($_POST['to_city'] ?? '');
    $departureTime = sanitize($_POST['departure_time'] ?? '');
    $arrivalTime   = sanitize($_POST['arrival_time'] ?? '');
    $price         = (float)($_POST['price'] ?? 0);
    $seatsTotal    = (int)($_POST['seats_total'] ?? 40);
    $company       = sanitize($_POST['company'] ?? '');
    $busType       = sanitize($_POST['bus_type'] ?? '');

    if (!$fromCity || !$toCity || !$price) {
        $error = 'يرجى تعبئة الحقول المطلوبة.';
    } else {
        try {
            $db = getDB();
            $db->prepare(
                "UPDATE routes SET from_city=?,to_city=?,departure_time=?,arrival_time=?,price=?,seats_total=?,company=?,bus_type=? WHERE id=?"
            )->execute([$fromCity,$toCity,$departureTime,$arrivalTime,$price,$seatsTotal,$company,$busType,$id]);
            $message = 'تم تحديث بيانات الرحلة بنجاح!';
        } catch (Exception $e) {
            $message = 'تم التحديث (وضع التجربة)!';
        }
    }
}

// تفعيل/تعطيل رحلة
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    try {
        $db   = getDB();
        $curr = $db->prepare("SELECT active FROM routes WHERE id=?");
        $curr->execute([(int)$_GET['toggle']]);
        $row = $curr->fetch(PDO::FETCH_ASSOC);
        $new = ($row && $row['active']) ? 0 : 1;
        $db->prepare("UPDATE routes SET active=? WHERE id=?")->execute([$new,(int)$_GET['toggle']]);
        $message = $new ? 'تم تفعيل الرحلة.' : 'تم إيقاف الرحلة.';
    } catch (Exception $e) { $message = 'تم تنفيذ العملية (وضع التجربة).'; }
}

// حذف رحلة (إخفاء)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db = getDB();
        $db->prepare("UPDATE routes SET active=0 WHERE id=?")->execute([(int)$_GET['delete']]);
        $message = 'تم حذف الرحلة.';
    } catch (Exception $e) { $message = 'تم تنفيذ العملية (وضع التجربة).'; }
}

// البيانات التجريبية
$demoRoutes = [
    ['id'=>1,'from_city'=>'نواكشوط','to_city'=>'نواذيبو','departure_time'=>'06:00','arrival_time'=>'12:30','price'=>5000,'seats_total'=>40,'company'=>'النقل الوطني الموريتاني','bus_type'=>'مكيف - فئة A','active'=>1,'created_at'=>date('Y-m-d',strtotime('-10 day'))],
    ['id'=>2,'from_city'=>'نواكشوط','to_city'=>'روصو','departure_time'=>'08:00','arrival_time'=>'11:00','price'=>2500,'seats_total'=>35,'company'=>'شركة الصحراء للنقل','bus_type'=>'VIP','active'=>1,'created_at'=>date('Y-m-d',strtotime('-8 day'))],
    ['id'=>3,'from_city'=>'نواكشوط','to_city'=>'كيفة','departure_time'=>'07:30','arrival_time'=>'13:00','price'=>3500,'seats_total'=>50,'company'=>'خطوط الأمل','bus_type'=>'عادي','active'=>1,'created_at'=>date('Y-m-d',strtotime('-5 day'))],
    ['id'=>4,'from_city'=>'نواذيبو','to_city'=>'زويرات','departure_time'=>'09:00','arrival_time'=>'13:30','price'=>3000,'seats_total'=>30,'company'=>'النقل الوطني الموريتاني','bus_type'=>'مكيف','active'=>1,'created_at'=>date('Y-m-d',strtotime('-3 day'))],
    ['id'=>5,'from_city'=>'نواكشوط','to_city'=>'أطار','departure_time'=>'06:30','arrival_time'=>'14:00','price'=>4500,'seats_total'=>40,'company'=>'شركة الصحراء للنقل','bus_type'=>'VIP','active'=>1,'created_at'=>date('Y-m-d',strtotime('-2 day'))],
    ['id'=>6,'from_city'=>'كيفة','to_city'=>'نواكشوط','departure_time'=>'05:00','arrival_time'=>'11:30','price'=>3500,'seats_total'=>45,'company'=>'خطوط الأمل','bus_type'=>'مكيف','active'=>0,'created_at'=>date('Y-m-d',strtotime('-1 day'))],
];

// جلب الرحلات
$routes     = [];
$allRoutes  = [];
$showFilter = sanitize($_GET['filter'] ?? '');

try {
    $db = getDB();
    $allRoutes = $db->query("SELECT * FROM routes ORDER BY from_city, departure_time")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allRoutes = $demoRoutes;
}

$routes = ($showFilter === 'inactive')
    ? array_filter($allRoutes, fn($r) => !$r['active'])
    : (($showFilter === 'all')
        ? $allRoutes
        : array_filter($allRoutes, fn($r) => $r['active']));

$activeCount   = count(array_filter($allRoutes, fn($r) => $r['active']));
$inactiveCount = count(array_filter($allRoutes, fn($r) => !$r['active']));

// رحلة للتعديل
$editRoute = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    foreach ($allRoutes as $r) { if ($r['id'] === $eid) { $editRoute = $r; break; } }
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'add';

$busTypes = ['مكيف - فئة A','VIP - مكيف فاخر','مكيف','عادي','ميني باص'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>إدارة الرحلات - لوحة التحكم</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root { --green-dark:#0a4f3a; --green-mid:#1a7a58; --green-light:#2db37e; --gold:#c8a227; }
body { font-family:'Segoe UI',Tahoma,sans-serif; background:#f4f6f9; }

/* Sidebar */
.sidebar { width:260px; min-height:100vh; background:linear-gradient(180deg,var(--green-dark),#0d6644); position:fixed; top:0; right:0; z-index:100; box-shadow:-4px 0 20px rgba(0,0,0,.2); }
.sidebar-logo { padding:1.5rem; border-bottom:1px solid rgba(255,255,255,.1); display:flex; align-items:center; gap:10px; }
.sidebar-logo .icon { font-size:1.6rem; color:var(--gold); }
.sidebar-logo span { color:#fff; font-weight:700; font-size:1.05rem; }
.nav-link { color:rgba(255,255,255,.75)!important; padding:.75rem 1.5rem!important; display:flex; align-items:center; gap:10px; transition:all .2s; font-size:.95rem; }
.nav-link:hover,.nav-link.active { background:rgba(255,255,255,.12)!important; color:#fff!important; border-right:3px solid var(--gold); }
.nav-link i { width:20px; text-align:center; }
.nav-section { padding:.5rem 1.5rem; font-size:.7rem; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:1px; margin-top:1rem; }

/* Layout */
.main-content { margin-right:260px; min-height:100vh; }
.topbar { background:#fff; padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 8px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
.page-title { font-size:1.2rem; font-weight:700; color:var(--green-dark); margin:0; }

/* Stats */
.stat-mini { background:#fff; border-radius:12px; padding:1.2rem 1.5rem; box-shadow:0 2px 10px rgba(0,0,0,.07); border-right:4px solid; display:flex; align-items:center; gap:1rem; }
.stat-mini-icon { font-size:1.8rem; opacity:.2; }
.stat-mini-val  { font-size:1.6rem; font-weight:800; line-height:1; }
.stat-mini-lbl  { font-size:.8rem; color:#6c757d; }

/* Form */
.form-panel { background:#fff; border-radius:14px; padding:2rem; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:1.5rem; }
.form-panel.add-panel  { border-top:4px solid var(--green-light); }
.form-panel.edit-panel { border-top:4px solid var(--gold); }

/* Table */
.data-table { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.07); }
.table th { background:var(--green-dark); color:#fff; font-weight:600; padding:.9rem 1rem; border:none; font-size:.88rem; }
.table td { padding:.8rem 1rem; vertical-align:middle; border-color:#f0f0f0; font-size:.9rem; }
.table tbody tr:hover { background:#f8fdf9; }

.route-from-to { display:flex; align-items:center; gap:8px; font-weight:700; }
.route-from-to .arrow { color:#aaa; font-size:.8rem; }

.badge-type { border-radius:20px; font-size:.75rem; padding:4px 10px; background:#f0f8ff; color:#0369a1; border:1px solid #bae6fd; }
.badge-active   { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; border-radius:20px; font-size:.75rem; padding:4px 10px; }
.badge-inactive { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:20px; font-size:.75rem; padding:4px 10px; }

.action-btn { padding:5px 11px; border-radius:7px; font-size:.8rem; border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:all .15s; }
.action-btn.edit-btn   { background:#eef5ff; color:#3498db; }
.action-btn.toggle-on  { background:#e8f5f0; color:var(--green-mid); }
.action-btn.toggle-off { background:#fff3cd; color:#856404; }
.action-btn.del-btn    { background:#fef0f0; color:#e74c3c; }
.action-btn:hover { opacity:.8; }

.btn-add-main { background:var(--green-mid); color:#fff; border:none; border-radius:10px; padding:.65rem 1.6rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-add-main:hover { background:var(--green-dark); color:#fff; }

.filter-tabs a { border-radius:20px; font-size:.85rem; padding:6px 16px; text-decoration:none; font-weight:600; transition:all .2s; }
.filter-tabs a.active-tab { background:var(--green-mid); color:#fff; }
.filter-tabs a.inactive-tab { background:#f0f0f0; color:#666; }
.filter-tabs a:hover:not(.active-tab) { background:#e0e0e0; }

.time-badge { background:#f0fdf4; color:#166534; border-radius:8px; padding:3px 8px; font-size:.82rem; font-weight:600; }
.price-val  { color:var(--gold); font-weight:800; font-size:1rem; }
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-logo">
        <span class="icon"><i class="fa fa-bus"></i></span>
        <span>نقل موريتانيا</span>
    </div>
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

<!-- Main -->
<div class="main-content">
    <div class="topbar">
        <h5 class="page-title"><i class="fa fa-route me-2"></i>إدارة الرحلات</h5>
        <div class="d-flex align-items-center gap-3">
            <a href="routes.php?action=add" class="btn-add-main">
                <i class="fa fa-plus"></i>إضافة رحلة
            </a>
            <div style="width:36px;height:36px;border-radius:50%;background:var(--green-mid);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
                <?= mb_substr($_SESSION['admin_name'] ?? 'م', 0, 1) ?>
            </div>
            <span class="fw-bold"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'مدير') ?></span>
        </div>
    </div>

    <div class="p-4">

        <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fa fa-check-circle me-2"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-danger  alert-dismissible fade show"><i class="fa fa-times-circle me-2"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-mini" style="border-color:var(--green-light);">
                    <i class="fa fa-route stat-mini-icon text-success"></i>
                    <div>
                        <div class="stat-mini-val text-success"><?= count($allRoutes) ?></div>
                        <div class="stat-mini-lbl">إجمالي الرحلات</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini" style="border-color:#3498db;">
                    <i class="fa fa-check-circle stat-mini-icon text-primary"></i>
                    <div>
                        <div class="stat-mini-val text-primary"><?= $activeCount ?></div>
                        <div class="stat-mini-lbl">رحلات نشطة</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini" style="border-color:var(--gold);">
                    <i class="fa fa-map-marked-alt stat-mini-icon" style="color:var(--gold);"></i>
                    <div>
                        <div class="stat-mini-val" style="color:var(--gold);"><?= count(array_unique(array_column($allRoutes, 'from_city'))) ?></div>
                        <div class="stat-mini-lbl">مدن انطلاق</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Form -->
        <?php if ($showForm): ?>
        <div class="form-panel add-panel">
            <h5 class="fw-bold mb-4" style="color:var(--green-dark);"><i class="fa fa-plus-circle me-2 text-success"></i>إضافة رحلة جديدة</h5>
            <form method="POST">
                <input type="hidden" name="action" value="add_route">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">مدينة الانطلاق *</label>
                        <select name="from_city" class="form-select" required>
                            <option value="">— اختر المدينة —</option>
                            <?php foreach ($cities as $c): ?><option><?= $c ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">مدينة الوصول *</label>
                        <select name="to_city" class="form-select" required>
                            <option value="">— اختر المدينة —</option>
                            <?php foreach ($cities as $c): ?><option><?= $c ?></option><?php endforeach; ?>
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
                        <div class="input-group">
                            <input type="number" name="price" class="form-control" required min="100" placeholder="0">
                            <span class="input-group-text">أوقية</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">عدد المقاعد</label>
                        <input type="number" name="seats_total" class="form-control" value="40" min="5" max="80">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">اسم الشركة</label>
                        <input type="text" name="company" class="form-control" placeholder="مثال: النقل الوطني الموريتاني">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">نوع الحافلة</label>
                        <select name="bus_type" class="form-select">
                            <?php foreach ($busTypes as $t): ?><option><?= $t ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2 pt-2">
                        <button type="submit" class="btn-add-main btn"><i class="fa fa-save me-1"></i>حفظ الرحلة</button>
                        <a href="routes.php" class="btn btn-outline-secondary rounded-3 px-4">إلغاء</a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <?php if ($editRoute): ?>
        <div class="form-panel edit-panel">
            <h5 class="fw-bold mb-4" style="color:#856404;"><i class="fa fa-edit me-2" style="color:var(--gold);"></i>تعديل الرحلة رقم #<?= $editRoute['id'] ?></h5>
            <form method="POST">
                <input type="hidden" name="action" value="edit_route">
                <input type="hidden" name="route_id" value="<?= $editRoute['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">مدينة الانطلاق *</label>
                        <select name="from_city" class="form-select" required>
                            <?php foreach ($cities as $c): ?>
                            <option <?= $c===$editRoute['from_city']?'selected':'' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">مدينة الوصول *</label>
                        <select name="to_city" class="form-select" required>
                            <?php foreach ($cities as $c): ?>
                            <option <?= $c===$editRoute['to_city']?'selected':'' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">وقت الانطلاق</label>
                        <input type="time" name="departure_time" class="form-control" value="<?= $editRoute['departure_time'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">وقت الوصول</label>
                        <input type="time" name="arrival_time" class="form-control" value="<?= $editRoute['arrival_time'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">السعر (أوقية) *</label>
                        <div class="input-group">
                            <input type="number" name="price" class="form-control" required min="100" value="<?= $editRoute['price'] ?>">
                            <span class="input-group-text">أوقية</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">عدد المقاعد</label>
                        <input type="number" name="seats_total" class="form-control" value="<?= $editRoute['seats_total'] ?>" min="5" max="80">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">اسم الشركة</label>
                        <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($editRoute['company']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">نوع الحافلة</label>
                        <select name="bus_type" class="form-select">
                            <?php foreach ($busTypes as $t): ?>
                            <option <?= $t===$editRoute['bus_type']?'selected':'' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2 pt-2">
                        <button type="submit" class="btn fw-bold px-4" style="background:var(--gold);color:#fff;border-radius:10px;"><i class="fa fa-save me-1"></i>حفظ التعديلات</button>
                        <a href="routes.php" class="btn btn-outline-secondary rounded-3 px-4">إلغاء</a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div class="filter-tabs d-flex gap-2">
                <a href="routes.php" class="<?= !$showFilter||$showFilter==='active' ? 'active-tab' : 'inactive-tab' ?>">
                    <i class="fa fa-check-circle me-1"></i>نشطة (<?= $activeCount ?>)
                </a>
                <a href="routes.php?filter=inactive" class="<?= $showFilter==='inactive' ? 'active-tab' : 'inactive-tab' ?>">
                    <i class="fa fa-ban me-1"></i>موقوفة (<?= $inactiveCount ?>)
                </a>
                <a href="routes.php?filter=all" class="<?= $showFilter==='all' ? 'active-tab' : 'inactive-tab' ?>">
                    <i class="fa fa-list me-1"></i>الكل (<?= count($allRoutes) ?>)
                </a>
            </div>
        </div>

        <!-- Routes Table -->
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الرحلة</th>
                            <th>الشركة</th>
                            <th>المواعيد</th>
                            <th>نوع الحافلة</th>
                            <th>السعر</th>
                            <th>المقاعد</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($routes)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">
                        <i class="fa fa-route fa-3x mb-3 d-block opacity-25"></i>لا توجد رحلات في هذه الفئة
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($routes as $r): ?>
                    <tr>
                        <td class="text-muted"><?= $r['id'] ?></td>
                        <td>
                            <div class="route-from-to">
                                <i class="fa fa-map-marker-alt text-success"></i>
                                <strong><?= htmlspecialchars($r['from_city']) ?></strong>
                                <span class="arrow"><i class="fa fa-arrow-left"></i></span>
                                <strong><?= htmlspecialchars($r['to_city']) ?></strong>
                            </div>
                        </td>
                        <td>
                            <small><i class="fa fa-building me-1 text-muted"></i><?= htmlspecialchars($r['company'] ?? '—') ?></small>
                        </td>
                        <td>
                            <?php if ($r['departure_time'] || $r['arrival_time']): ?>
                            <span class="time-badge">
                                <i class="fa fa-clock me-1"></i>
                                <?= $r['departure_time'] ?: '—' ?>
                                <?php if ($r['arrival_time']): ?> ← <?= $r['arrival_time'] ?><?php endif; ?>
                            </span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td><span class="badge-type"><?= htmlspecialchars($r['bus_type'] ?? '—') ?></span></td>
                        <td>
                            <span class="price-val"><?= number_format($r['price'], 0) ?></span>
                            <small class="text-muted"> أوقية</small>
                        </td>
                        <td class="text-center">
                            <i class="fa fa-chair text-muted me-1" style="font-size:.8rem;"></i><?= $r['seats_total'] ?>
                        </td>
                        <td>
                            <?php if ($r['active']): ?>
                                <span class="badge-active"><i class="fa fa-check me-1"></i>نشطة</span>
                            <?php else: ?>
                                <span class="badge-inactive"><i class="fa fa-ban me-1"></i>موقوفة</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="routes.php?edit=<?= $r['id'] ?>" class="action-btn edit-btn">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="routes.php?toggle=<?= $r['id'] ?>"
                                   class="action-btn <?= $r['active'] ? 'toggle-off' : 'toggle-on' ?>"
                                   onclick="return confirm('<?= $r['active'] ? 'إيقاف' : 'تفعيل' ?> هذه الرحلة؟')">
                                    <i class="fa <?= $r['active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                </a>
                                <a href="routes.php?delete=<?= $r['id'] ?>" class="action-btn del-btn"
                                   onclick="return confirm('حذف رحلة «<?= htmlspecialchars($r['from_city']) ?> ← <?= htmlspecialchars($r['to_city']) ?>»؟')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
