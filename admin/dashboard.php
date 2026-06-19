<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

// إحصائيات لوحة التحكم
$stats = ['total_bookings'=>0,'confirmed'=>0,'pending'=>0,'revenue'=>0,'routes'=>0];
$recentBookings = [];

try {
    $db = getDB();
    $r = $db->query("SELECT COUNT(*) as cnt FROM bookings")->fetch(); $stats['total_bookings'] = $r['cnt'];
    $r = $db->query("SELECT COUNT(*) as cnt FROM bookings WHERE status='confirmed'")->fetch(); $stats['confirmed'] = $r['cnt'];
    $r = $db->query("SELECT COUNT(*) as cnt FROM bookings WHERE status='pending'")->fetch(); $stats['pending'] = $r['cnt'];
    $r = $db->query("SELECT SUM(total_price) as rev FROM bookings WHERE status!='cancelled'")->fetch(); $stats['revenue'] = $r['rev'] ?? 0;
    $r = $db->query("SELECT COUNT(*) as cnt FROM routes WHERE active=1")->fetch(); $stats['routes'] = $r['cnt'];

    $stmt = $db->query("SELECT b.*, r.from_city, r.to_city FROM bookings b LEFT JOIN routes r ON b.route_id=r.id ORDER BY b.id DESC");
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Demo data
    $stats = ['total_bookings'=>247,'confirmed'=>189,'pending'=>41,'revenue'=>892500,'routes'=>18];
    $recentBookings = [
        ['id'=>247,'booking_ref'=>'MRT-A8F2C1D3','from_city'=>'نواكشوط','to_city'=>'نواذيبو','passenger_name'=>'أحمد ولد محمد','passenger_phone'=>'22001234','seat_count'=>2,'total_price'=>10000,'status'=>'confirmed','travel_date'=>date('Y-m-d',strtotime('+1 day')),'created_at'=>date('Y-m-d H:i:s')],
        ['id'=>246,'booking_ref'=>'MRT-B9E3F2A1','from_city'=>'نواكشوط','to_city'=>'روصو','passenger_name'=>'فاطمة بنت أحمد','passenger_phone'=>'22005678','seat_count'=>1,'total_price'=>2500,'status'=>'pending','travel_date'=>date('Y-m-d',strtotime('+2 day')),'created_at'=>date('Y-m-d H:i:s',strtotime('-1 hour'))],
        ['id'=>245,'booking_ref'=>'MRT-C7D4E5B2','from_city'=>'نواذيبو','to_city'=>'زويرات','passenger_name'=>'محمد سالم','passenger_phone'=>'22009012','seat_count'=>3,'total_price'=>9000,'status'=>'confirmed','travel_date'=>date('Y-m-d'),'created_at'=>date('Y-m-d H:i:s',strtotime('-2 hour'))],
        ['id'=>244,'booking_ref'=>'MRT-D1A5B8C9','from_city'=>'كيفة','to_city'=>'نواكشوط','passenger_name'=>'مريم بنت سيدي','passenger_phone'=>'22003456','seat_count'=>1,'total_price'=>3500,'status'=>'cancelled','travel_date'=>date('Y-m-d',strtotime('-1 day')),'created_at'=>date('Y-m-d H:i:s',strtotime('-5 hour'))],
        ['id'=>243,'booking_ref'=>'MRT-E2F6G7H8','from_city'=>'نواكشوط','to_city'=>'أطار','passenger_name'=>'عبد الله ولد بكر','passenger_phone'=>'22007890','seat_count'=>2,'total_price'=>9000,'status'=>'confirmed','travel_date'=>date('Y-m-d',strtotime('+3 day')),'created_at'=>date('Y-m-d H:i:s',strtotime('-6 hour'))],
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>لوحة التحكم - نقل موريتانيا</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root { --green-dark:#0a4f3a; --green-mid:#1a7a58; --green-light:#2db37e; --gold:#c8a227; }
body { font-family:'Segoe UI',Tahoma,sans-serif; background:#f4f6f9; margin:0; }
.sidebar {
    width: 260px; min-height: 100vh;
    background: linear-gradient(180deg, var(--green-dark) 0%, #0d6644 100%);
    position: fixed; top: 0; right: 0; z-index: 100;
    box-shadow: -4px 0 20px rgba(0,0,0,0.2);
}
.sidebar-logo {
    padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex; align-items: center; gap: 10px;
}
.sidebar-logo .icon { font-size: 1.6rem; color: var(--gold); }
.sidebar-logo span { color: #fff; font-weight: 700; font-size: 1.05rem; }
.nav-link {
    color: rgba(255,255,255,0.75) !important; padding: 0.75rem 1.5rem !important;
    display: flex; align-items: center; gap: 10px;
    border-radius: 0; transition: all .2s; font-size: .95rem;
}
.nav-link:hover, .nav-link.active {
    background: rgba(255,255,255,0.12) !important;
    color: #fff !important;
    border-right: 3px solid var(--gold);
}
.nav-link i { width: 20px; text-align: center; }
.nav-section { padding: 0.5rem 1.5rem; font-size: .7rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1px; margin-top: 1rem; }

.main-content { margin-right: 260px; min-height: 100vh; }
.topbar {
    background: #fff; padding: 1rem 1.5rem;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 50;
}
.page-title { font-size: 1.2rem; font-weight: 700; color: var(--green-dark); margin: 0; }
.admin-badge { display: flex; align-items: center; gap: 8px; }
.admin-avatar {
    width: 36px; height: 36px; border-radius: 50%; background: var(--green-mid);
    display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700;
}

.stat-card {
    background: #fff; border-radius: 14px; padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07); border-top: 4px solid;
    transition: transform .2s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-card.green  { border-color: var(--green-light); }
.stat-card.blue   { border-color: #3498db; }
.stat-card.orange { border-color: #f39c12; }
.stat-card.gold   { border-color: var(--gold); }
.stat-card.purple { border-color: #9b59b6; }
.stat-number { font-size: 2rem; font-weight: 800; margin: 0.5rem 0; }
.stat-icon { font-size: 2rem; opacity: 0.15; float: left; }

.data-table { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.table th { background: var(--green-dark); color: #fff; font-weight: 600; padding: 0.9rem 1rem; border: none; font-size: .9rem; }
.table td { padding: 0.8rem 1rem; vertical-align: middle; border-color: #f0f0f0; font-size: .9rem; }
.table tbody tr:hover { background: #f8fdf9; }
.badge { border-radius: 20px; font-size: .78rem; padding: 4px 12px; }

.action-btn { padding: 4px 10px; border-radius: 6px; font-size: .8rem; border: none; cursor: pointer; }
.action-btn.confirm { background: #e8f5f0; color: var(--green-mid); }
.action-btn.cancel  { background: #fef0f0; color: #e74c3c; }
.action-btn.view    { background: #eef5ff; color: #3498db; }
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
        <li class="nav-item">
            <a class="nav-link active" href="dashboard.php"><i class="fa fa-tachometer-alt"></i>لوحة التحكم</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="bookings.php"><i class="fa fa-ticket-alt"></i>الحجوزات
                <?php if($stats['pending']>0): ?>
                <span class="badge bg-warning text-dark ms-auto"><?= $stats['pending'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <div class="nav-section">الإدارة</div>
        <li class="nav-item">
            <a class="nav-link" href="routes.php"><i class="fa fa-route"></i>إدارة الرحلات</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="reports.php"><i class="fa fa-chart-bar"></i>التقارير</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="users.php"><i class="fa fa-users-cog"></i>المستخدمون</a>
        </li>
        <div class="nav-section">النظام</div>
        <li class="nav-item">
            <a class="nav-link" href="../index.php" target="_blank"><i class="fa fa-external-link-alt"></i>عرض الموقع</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="logout.php"><i class="fa fa-sign-out-alt"></i>تسجيل الخروج</a>
        </li>
    </ul>
</nav>

<!-- Main -->
<div class="main-content">
    <!-- Top Bar -->
    <div class="topbar">
        <h5 class="page-title"><i class="fa fa-tachometer-alt me-2"></i>لوحة التحكم</h5>
        <div class="admin-badge">
            <span class="text-muted small"><?= date('d/m/Y H:i') ?></span>
            <div class="admin-avatar"><?= mb_substr($_SESSION['admin_name'] ?? 'م', 0, 1) ?></div>
            <span class="fw-bold"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'مدير') ?></span>
        </div>
    </div>

    <div class="p-4">
        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-2-4 col-6 col-xl">
                <div class="stat-card green">
                    <i class="fa fa-ticket-alt stat-icon text-success"></i>
                    <div class="text-muted small">إجمالي الحجوزات</div>
                    <div class="stat-number text-success"><?= number_format($stats['total_bookings']) ?></div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 col-xl">
                <div class="stat-card blue">
                    <i class="fa fa-check-circle stat-icon text-primary"></i>
                    <div class="text-muted small">مؤكدة</div>
                    <div class="stat-number text-primary"><?= number_format($stats['confirmed']) ?></div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 col-xl">
                <div class="stat-card orange">
                    <i class="fa fa-clock stat-icon text-warning"></i>
                    <div class="text-muted small">في الانتظار</div>
                    <div class="stat-number text-warning"><?= number_format($stats['pending']) ?></div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 col-xl">
                <div class="stat-card gold">
                    <i class="fa fa-money-bill stat-icon" style="color:var(--gold)"></i>
                    <div class="text-muted small">الإيرادات</div>
                    <div class="stat-number" style="color:var(--gold)"><?= number_format($stats['revenue'], 0) ?></div>
                    <div class="text-muted" style="font-size:.75rem;">أوقية</div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 col-xl">
                <div class="stat-card purple">
                    <i class="fa fa-route stat-icon text-purple" style="color:#9b59b6"></i>
                    <div class="text-muted small">خطوط نشطة</div>
                    <div class="stat-number" style="color:#9b59b6"><?= $stats['routes'] ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <a href="bookings.php?status=pending" class="btn w-100 py-2 fw-bold" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:10px;">
                    <i class="fa fa-clock me-2"></i>حجوزات في الانتظار (<?= $stats['pending'] ?>)
                </a>
            </div>
            <div class="col-md-3">
                <a href="routes.php?action=add" class="btn w-100 py-2 fw-bold" style="background:#d1e7dd;color:#0a4f3a;border:1px solid #2db37e;border-radius:10px;">
                    <i class="fa fa-plus me-2"></i>إضافة رحلة جديدة
                </a>
            </div>
            <div class="col-md-3">
                <a href="reports.php" class="btn w-100 py-2 fw-bold" style="background:#cff4fc;color:#055160;border:1px solid #0dcaf0;border-radius:10px;">
                    <i class="fa fa-download me-2"></i>تصدير التقرير
                </a>
            </div>
            <div class="col-md-3">
                <a href="../index.php" target="_blank" class="btn w-100 py-2 fw-bold" style="background:#f8d7da;color:#842029;border:1px solid #f5c2c7;border-radius:10px;">
                    <i class="fa fa-eye me-2"></i>معاينة الموقع
                </a>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="data-table">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="fa fa-list me-2 text-success"></i>أحدث الحجوزات</h6>
                <a href="bookings.php" class="btn btn-sm btn-outline-success rounded-pill">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الحجز</th>
                            <th>المسافر</th>
                            <th>الرحلة</th>
                            <th>تاريخ السفر</th>
                            <th>المقاعد</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach(array_slice($recentBookings, 0, 10) as $b): ?>
                    <tr>
                        <td class="text-muted"><?= $b['id'] ?></td>
                        <td><code style="font-size:.8rem;"><?= $b['booking_ref'] ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($b['passenger_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($b['passenger_phone'] ?? '') ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($b['from_city'] ?? '—') ?>
                            <i class="fa fa-arrow-left mx-1 text-success" style="font-size:.8rem;"></i>
                            <?= htmlspecialchars($b['to_city'] ?? '—') ?>
                        </td>
                        <td><?= $b['travel_date'] ? date('d/m/Y', strtotime($b['travel_date'])) : '—' ?></td>
                        <td class="text-center"><?= $b['seat_count'] ?></td>
                        <td class="fw-bold" style="color:var(--gold)"><?= number_format($b['total_price'], 0) ?></td>
                        <td><?= getStatusBadge($b['status']) ?></td>
                        <td>
                            <a href="booking-detail.php?id=<?= $b['id'] ?>" class="action-btn view me-1">
                                <i class="fa fa-eye"></i>
                            </a>
                            <?php if ($b['status'] === 'pending'): ?>
                            <a href="update-status.php?id=<?= $b['id'] ?>&status=confirmed" class="action-btn confirm me-1" onclick="return confirm('تأكيد الحجز؟')">
                                <i class="fa fa-check"></i>
                            </a>
                            <a href="update-status.php?id=<?= $b['id'] ?>&status=cancelled" class="action-btn cancel" onclick="return confirm('إلغاء الحجز؟')">
                                <i class="fa fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
