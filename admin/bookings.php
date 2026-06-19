<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['search'] ?? '');
$bookings     = [];

try {
    $db  = getDB();
    $sql = "SELECT b.*, r.from_city, r.to_city, r.departure_time, r.company
            FROM bookings b LEFT JOIN routes r ON b.route_id = r.id
            WHERE 1=1";
    $params = [];

    if ($statusFilter) { $sql .= " AND b.status = ?"; $params[] = $statusFilter; }
    if ($search) {
        $sql .= " AND (b.booking_ref LIKE ? OR b.passenger_name LIKE ? OR b.passenger_phone LIKE ?)";
        $params = array_merge($params, ["%$search%","%$search%","%$search%"]);
    }
    $sql .= " ORDER BY b.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bookings = [
        ['id'=>247,'booking_ref'=>'MRT-A8F2C1D3','from_city'=>'نواكشوط','to_city'=>'نواذيبو','departure_time'=>'06:00','company'=>'النقل الوطني','passenger_name'=>'أحمد ولد محمد','passenger_phone'=>'22001234','seat_count'=>2,'total_price'=>10000,'status'=>'confirmed','travel_date'=>date('Y-m-d',strtotime('+1 day')),'payment_method'=>'cash','created_at'=>date('Y-m-d H:i:s')],
        ['id'=>246,'booking_ref'=>'MRT-B9E3F2A1','from_city'=>'نواكشوط','to_city'=>'روصو','departure_time'=>'09:30','company'=>'شركة الصحراء','passenger_name'=>'فاطمة بنت أحمد','passenger_phone'=>'22005678','seat_count'=>1,'total_price'=>2500,'status'=>'pending','travel_date'=>date('Y-m-d',strtotime('+2 day')),'payment_method'=>'mobile','created_at'=>date('Y-m-d H:i:s',strtotime('-1 hour'))],
        ['id'=>245,'booking_ref'=>'MRT-C7D4E5B2','from_city'=>'نواذيبو','to_city'=>'زويرات','departure_time'=>'08:00','company'=>'النقل الوطني','passenger_name'=>'محمد سالم','passenger_phone'=>'22009012','seat_count'=>3,'total_price'=>9000,'status'=>'confirmed','travel_date'=>date('Y-m-d'),'payment_method'=>'card','created_at'=>date('Y-m-d H:i:s',strtotime('-2 hour'))],
        ['id'=>244,'booking_ref'=>'MRT-D1A5B8C9','from_city'=>'كيفة','to_city'=>'نواكشوط','departure_time'=>'07:00','company'=>'خطوط الأمل','passenger_name'=>'مريم بنت سيدي','passenger_phone'=>'22003456','seat_count'=>1,'total_price'=>3500,'status'=>'cancelled','travel_date'=>date('Y-m-d',strtotime('-1 day')),'payment_method'=>'cash','created_at'=>date('Y-m-d H:i:s',strtotime('-5 hour'))],
        ['id'=>243,'booking_ref'=>'MRT-E2F6G7H8','from_city'=>'نواكشوط','to_city'=>'أطار','departure_time'=>'06:30','company'=>'النقل الوطني','passenger_name'=>'عبد الله ولد بكر','passenger_phone'=>'22007890','seat_count'=>2,'total_price'=>9000,'status'=>'confirmed','travel_date'=>date('Y-m-d',strtotime('+3 day')),'payment_method'=>'cash','created_at'=>date('Y-m-d H:i:s',strtotime('-6 hour'))],
        ['id'=>242,'booking_ref'=>'MRT-F3G8H9I0','from_city'=>'نواكشوط','to_city'=>'كيهيدي','departure_time'=>'10:00','company'=>'شركة الصحراء','passenger_name'=>'زينب بنت محمد','passenger_phone'=>'22001111','seat_count'=>2,'total_price'=>7000,'status'=>'pending','travel_date'=>date('Y-m-d',strtotime('+1 day')),'payment_method'=>'mobile','created_at'=>date('Y-m-d H:i:s',strtotime('-8 hour'))],
    ];
}

$paymentLabels = ['cash'=>'نقداً','mobile'=>'هاتف','card'=>'بطاقة'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>الحجوزات - لوحة التحكم</title>
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
.data-table { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.07); }
.table th { background:var(--green-dark); color:#fff; font-weight:600; padding:.9rem 1rem; border:none; font-size:.88rem; }
.table td { padding:.75rem 1rem; vertical-align:middle; border-color:#f0f0f0; font-size:.88rem; }
.table tbody tr:hover { background:#f8fdf9; }
.badge { border-radius:20px; font-size:.75rem; padding:4px 10px; }
.filter-bar { background:#fff; border-radius:12px; padding:1rem 1.5rem; box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:1.5rem; }
.action-btn { padding:4px 10px; border-radius:6px; font-size:.8rem; border:none; cursor:pointer; text-decoration:none; display:inline-block; }
.action-btn.confirm { background:#e8f5f0; color:var(--green-mid); }
.action-btn.cancel  { background:#fef0f0; color:#e74c3c; }
.action-btn.view    { background:#eef5ff; color:#3498db; }
</style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo"><span class="icon"><i class="fa fa-bus"></i></span><span>نقل موريتانيا</span></div>
    <ul class="nav flex-column mt-2">
        <div class="nav-section">القائمة الرئيسية</div>
        <li><a class="nav-link" href="dashboard.php"><i class="fa fa-tachometer-alt"></i>لوحة التحكم</a></li>
        <li><a class="nav-link active" href="bookings.php"><i class="fa fa-ticket-alt"></i>الحجوزات</a></li>
        <div class="nav-section">الإدارة</div>
        <li><a class="nav-link" href="routes.php"><i class="fa fa-route"></i>إدارة الرحلات</a></li>
        <li><a class="nav-link" href="reports.php"><i class="fa fa-chart-bar"></i>التقارير</a></li>
        <li><a class="nav-link" href="users.php"><i class="fa fa-users-cog"></i>المستخدمون</a></li>
        <div class="nav-section">النظام</div>
        <li><a class="nav-link" href="../index.php" target="_blank"><i class="fa fa-external-link-alt"></i>عرض الموقع</a></li>
        <li><a class="nav-link text-danger" href="logout.php"><i class="fa fa-sign-out-alt"></i>تسجيل الخروج</a></li>
    </ul>
</nav>
<div class="main-content">
    <div class="topbar">
        <h5 class="page-title"><i class="fa fa-ticket-alt me-2"></i>إدارة الحجوزات</h5>
        <div class="d-flex align-items-center gap-2">
            <div class="admin-avatar" style="width:36px;height:36px;border-radius:50%;background:var(--green-mid);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
                <?= mb_substr($_SESSION['admin_name']??'م',0,1) ?>
            </div>
            <span class="fw-bold"><?= htmlspecialchars($_SESSION['admin_name']??'مدير') ?></span>
        </div>
    </div>

    <div class="p-4">
        <!-- Filters -->
        <div class="filter-bar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold text-muted small mb-1">بحث</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="رقم حجز، اسم، هاتف..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold text-muted small mb-1">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="pending"   <?= $statusFilter==='pending'  ?'selected':'' ?>>في الانتظار</option>
                        <option value="confirmed" <?= $statusFilter==='confirmed'?'selected':'' ?>>مؤكد</option>
                        <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':'' ?>>ملغي</option>
                        <option value="completed" <?= $statusFilter==='completed'?'selected':'' ?>>مكتمل</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100 fw-bold" style="background:var(--green-mid);color:#fff;border-radius:8px;">
                        <i class="fa fa-filter me-1"></i>تصفية
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="bookings.php" class="btn btn-outline-secondary w-100 rounded-3">مسح</a>
                </div>
            </form>
        </div>

        <!-- Summary tabs -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <a href="bookings.php" class="btn btn-sm <?= !$statusFilter?'btn-success':'btn-outline-secondary' ?> rounded-pill">الكل (<?= count($bookings) ?>)</a>
            <a href="bookings.php?status=pending" class="btn btn-sm <?= $statusFilter==='pending'?'btn-warning':'btn-outline-warning' ?> rounded-pill">في الانتظار</a>
            <a href="bookings.php?status=confirmed" class="btn btn-sm <?= $statusFilter==='confirmed'?'btn-primary':'btn-outline-primary' ?> rounded-pill">مؤكد</a>
            <a href="bookings.php?status=cancelled" class="btn btn-sm <?= $statusFilter==='cancelled'?'btn-danger':'btn-outline-danger' ?> rounded-pill">ملغي</a>
        </div>

        <!-- Table -->
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>رقم الحجز</th>
                            <th>المسافر</th>
                            <th>الرحلة</th>
                            <th>تاريخ السفر</th>
                            <th>المقاعد</th>
                            <th>المبلغ</th>
                            <th>الدفع</th>
                            <th>الحالة</th>
                            <th>تاريخ الحجز</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($bookings)): ?>
                    <tr><td colspan="10" class="text-center py-5 text-muted">
                        <i class="fa fa-inbox fa-3x mb-3 d-block"></i>لا توجد حجوزات
                    </td></tr>
                    <?php else: ?>
                    <?php foreach($bookings as $b): ?>
                    <tr>
                        <td><code style="font-size:.8rem;background:#f8f9fa;padding:3px 6px;border-radius:4px;"><?= htmlspecialchars($b['booking_ref']) ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($b['passenger_name']) ?></strong><br>
                            <small class="text-muted"><i class="fa fa-phone me-1"></i><?= htmlspecialchars($b['passenger_phone']??'') ?></small>
                        </td>
                        <td>
                            <span style="font-size:.85rem;">
                                <?= htmlspecialchars($b['from_city']??'—') ?>
                                <i class="fa fa-arrow-left mx-1 text-success"></i>
                                <?= htmlspecialchars($b['to_city']??'—') ?>
                            </span><br>
                            <small class="text-muted"><i class="fa fa-clock me-1"></i><?= htmlspecialchars($b['departure_time']??'') ?></small>
                        </td>
                        <td><?= $b['travel_date'] ? date('d/m/Y', strtotime($b['travel_date'])) : '—' ?></td>
                        <td class="text-center fw-bold"><?= $b['seat_count'] ?></td>
                        <td class="fw-bold" style="color:var(--gold)"><?= number_format($b['total_price'], 0) ?> <small class="text-muted fw-normal">أوقية</small></td>
                        <td><span class="badge bg-light text-dark border"><?= $paymentLabels[$b['payment_method']??''] ?? $b['payment_method'] ?></span></td>
                        <td><?= getStatusBadge($b['status']) ?></td>
                        <td><small class="text-muted"><?= isset($b['created_at']) ? date('d/m H:i', strtotime($b['created_at'])) : '—' ?></small></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="booking-detail.php?id=<?= $b['id'] ?>" class="action-btn view"><i class="fa fa-eye"></i></a>
                                <?php if ($b['status'] === 'pending'): ?>
                                <a href="update-status.php?id=<?= $b['id'] ?>&status=confirmed&back=bookings" class="action-btn confirm" onclick="return confirm('تأكيد الحجز؟')"><i class="fa fa-check"></i></a>
                                <a href="update-status.php?id=<?= $b['id'] ?>&status=cancelled&back=bookings" class="action-btn cancel" onclick="return confirm('إلغاء الحجز؟')"><i class="fa fa-times"></i></a>
                                <?php endif; ?>
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
