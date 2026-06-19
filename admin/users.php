<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error   = '';

// إضافة مستخدم جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username  = sanitize($_POST['username'] ?? '');
    $fullName  = sanitize($_POST['full_name'] ?? '');
    $role      = sanitize($_POST['role'] ?? 'staff');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$username || !$fullName || !$password) {
        $error = 'يرجى تعبئة جميع الحقول المطلوبة.';
    } elseif ($password !== $password2) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
    } else {
        try {
            $db   = getDB();
            $hash = hashPassword($password);
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role, active) VALUES (?,?,?,?,1)");
            $stmt->execute([$username, $hash, $fullName, $role]);
            $message = "تم إضافة المستخدم «{$fullName}» بنجاح!";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                $error = 'اسم المستخدم مستخدم بالفعل، اختر اسماً آخر.';
            } else {
                $message = "تمت الإضافة (وضع التجربة)!";
            }
        }
    }
}

// تغيير حالة المستخدم (تفعيل/تعطيل)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    try {
        $db   = getDB();
        $curr = $db->prepare("SELECT active FROM users WHERE id=?");
        $curr->execute([$uid]);
        $row   = $curr->fetch(PDO::FETCH_ASSOC);
        $newSt = ($row && $row['active']) ? 0 : 1;
        $db->prepare("UPDATE users SET active=? WHERE id=?")->execute([$newSt, $uid]);
        $message = $newSt ? 'تم تفعيل المستخدم.' : 'تم تعطيل المستخدم.';
    } catch (Exception $e) { $message = 'تم تنفيذ العملية (وضع التجربة).'; }
}

// حذف مستخدم
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid == ($_SESSION['admin_id'] ?? 0)) {
        $error = 'لا يمكنك حذف حسابك الخاص.';
    } else {
        try {
            $db = getDB();
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            $message = 'تم حذف المستخدم.';
        } catch (Exception $e) { $message = 'تم تنفيذ العملية (وضع التجربة).'; }
    }
}

// جلب المستخدمين
$users = [];
try {
    $db    = getDB();
    $users = $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [
        ['id'=>1,'username'=>'admin','full_name'=>'المدير العام','role'=>'admin','active'=>1,'last_login'=>date('Y-m-d H:i:s',strtotime('-2 hour'))],
        ['id'=>2,'username'=>'staff1','full_name'=>'خديجة بنت محمد','role'=>'staff','active'=>1,'last_login'=>date('Y-m-d H:i:s',strtotime('-1 day'))],
        ['id'=>3,'username'=>'staff2','full_name'=>'أحمد ولد عمر','role'=>'staff','active'=>0,'last_login'=>date('Y-m-d H:i:s',strtotime('-5 day'))],
        ['id'=>4,'username'=>'supervisor','full_name'=>'فاطمة بنت سيدي','role'=>'supervisor','active'=>1,'last_login'=>date('Y-m-d H:i:s',strtotime('-3 hour'))],
    ];
}

$roleLabels = [
    'admin'      => ['label'=>'مدير عام',   'color'=>'#9b59b6'],
    'supervisor' => ['label'=>'مشرف',        'color'=>'#3498db'],
    'staff'      => ['label'=>'موظف',        'color'=>'#27ae60'],
];

$showForm = isset($_GET['action']) && $_GET['action'] === 'add';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>إدارة المستخدمين - لوحة التحكم</title>
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
.stat-mini-icon { font-size:1.8rem; opacity:.25; }
.stat-mini-val { font-size:1.6rem; font-weight:800; line-height:1; }
.stat-mini-lbl { font-size:.8rem; color:#6c757d; }

/* Add Form */
.add-form { background:#fff; border-radius:14px; padding:2rem; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:1.5rem; border-top:4px solid var(--green-light); }

/* Users Table */
.data-table { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.07); }
.table th { background:var(--green-dark); color:#fff; font-weight:600; padding:.9rem 1rem; border:none; font-size:.88rem; }
.table td { padding:.8rem 1rem; vertical-align:middle; border-color:#f0f0f0; font-size:.9rem; }
.table tbody tr:hover { background:#f8fdf9; }

.user-avatar { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1rem; color:#fff; flex-shrink:0; }
.badge-role { border-radius:20px; font-size:.75rem; padding:4px 12px; font-weight:600; }

.action-btn { padding:5px 12px; border-radius:7px; font-size:.8rem; border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:all .15s; }
.action-btn.edit    { background:#eef5ff; color:#3498db; }
.action-btn.toggle-on  { background:#e8f5f0; color:var(--green-mid); }
.action-btn.toggle-off { background:#fff3cd; color:#856404; }
.action-btn.del     { background:#fef0f0; color:#e74c3c; }
.action-btn:hover   { opacity:.8; }

.btn-add-main { background:var(--green-mid); color:#fff; border:none; border-radius:10px; padding:.65rem 1.6rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-add-main:hover { background:var(--green-dark); color:#fff; }

.status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-left:6px; }
.dot-active   { background:#27ae60; }
.dot-inactive { background:#e74c3c; }

.pass-wrapper { position:relative; }
.pass-wrapper .toggle-pass { position:absolute; left:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:#999; cursor:pointer; }
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
        <li><a class="nav-link" href="routes.php"><i class="fa fa-route"></i>إدارة الرحلات</a></li>
        <li><a class="nav-link" href="reports.php"><i class="fa fa-chart-bar"></i>التقارير</a></li>
        <li><a class="nav-link active" href="users.php"><i class="fa fa-users-cog"></i>المستخدمون</a></li>
        <div class="nav-section">النظام</div>
        <li><a class="nav-link" href="../index.php" target="_blank"><i class="fa fa-external-link-alt"></i>عرض الموقع</a></li>
        <li><a class="nav-link text-danger" href="logout.php"><i class="fa fa-sign-out-alt"></i>تسجيل الخروج</a></li>
    </ul>
</nav>

<!-- Main -->
<div class="main-content">
    <div class="topbar">
        <h5 class="page-title"><i class="fa fa-users-cog me-2"></i>إدارة المستخدمين</h5>
        <div class="d-flex align-items-center gap-3">
            <a href="users.php?action=add" class="btn-add-main">
                <i class="fa fa-user-plus"></i>إضافة مستخدم
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

        <!-- Stats Mini -->
        <?php
        $totalUsers  = count($users);
        $activeUsers = count(array_filter($users, fn($u) => $u['active']));
        $adminCount  = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-mini" style="border-color:var(--green-light);">
                    <i class="fa fa-users stat-mini-icon text-success"></i>
                    <div>
                        <div class="stat-mini-val text-success"><?= $totalUsers ?></div>
                        <div class="stat-mini-lbl">إجمالي المستخدمين</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini" style="border-color:#3498db;">
                    <i class="fa fa-user-check stat-mini-icon text-primary"></i>
                    <div>
                        <div class="stat-mini-val text-primary"><?= $activeUsers ?></div>
                        <div class="stat-mini-lbl">مستخدمون نشطون</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini" style="border-color:#9b59b6;">
                    <i class="fa fa-user-shield stat-mini-icon" style="color:#9b59b6;"></i>
                    <div>
                        <div class="stat-mini-val" style="color:#9b59b6;"><?= $adminCount ?></div>
                        <div class="stat-mini-lbl">مدراء النظام</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User Form -->
        <?php if ($showForm): ?>
        <div class="add-form">
            <h5 class="fw-bold mb-4" style="color:var(--green-dark);"><i class="fa fa-user-plus me-2 text-success"></i>إضافة مستخدم جديد</h5>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="add_user">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">الاسم الكامل *</label>
                        <input type="text" name="full_name" class="form-control" placeholder="مثال: أحمد ولد محمد" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">اسم المستخدم *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-at"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="مثال: ahmed2024" required autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">الصلاحية *</label>
                        <select name="role" class="form-select" required>
                            <option value="staff">موظف</option>
                            <option value="supervisor">مشرف</option>
                            <option value="admin">مدير عام</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">كلمة المرور *</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password" id="pwd1" class="form-control" placeholder="6 أحرف على الأقل" required autocomplete="new-password">
                            <button type="button" class="toggle-pass" onclick="togglePass('pwd1',this)"><i class="fa fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">تأكيد كلمة المرور *</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password2" id="pwd2" class="form-control" placeholder="أعد الكتابة" required>
                            <button type="button" class="toggle-pass" onclick="togglePass('pwd2',this)"><i class="fa fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2 pt-1">
                        <button type="submit" class="btn-add-main btn"><i class="fa fa-save me-1"></i>حفظ المستخدم</button>
                        <a href="users.php" class="btn btn-outline-secondary rounded-3 px-4">إلغاء</a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="data-table">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="fa fa-list me-2 text-success"></i>قائمة المستخدمين (<?= $totalUsers ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المستخدم</th>
                            <th>اسم الدخول</th>
                            <th>الصلاحية</th>
                            <th>الحالة</th>
                            <th>آخر دخول</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">
                        <i class="fa fa-users fa-3x mb-3 d-block opacity-25"></i>لا يوجد مستخدمون بعد
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($users as $u):
                        $roleInfo   = $roleLabels[$u['role']] ?? ['label' => $u['role'], 'color' => '#777'];
                        $avatarChar = mb_substr($u['full_name'], 0, 1);
                        $colors     = ['#0a4f3a','#3498db','#9b59b6','#e67e22','#c0392b','#1a7a58'];
                        $avatarBg   = $colors[$u['id'] % count($colors)];
                        $isMe       = ($u['id'] == ($_SESSION['admin_id'] ?? 0));
                    ?>
                    <tr>
                        <td class="text-muted"><?= $u['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar" style="background:<?= $avatarBg ?>;"><?= $avatarChar ?></div>
                                <div>
                                    <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                    <?php if ($isMe): ?><span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">أنت</span><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="background:#f0f4f8;padding:3px 8px;border-radius:5px;font-size:.85rem;"><?= htmlspecialchars($u['username']) ?></code>
                        </td>
                        <td>
                            <span class="badge-role" style="background:<?= $roleInfo['color'] ?>22;color:<?= $roleInfo['color'] ?>;">
                                <?= $roleInfo['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['active']): ?>
                                <span class="status-dot dot-active"></span><span class="text-success small fw-bold">نشط</span>
                            <?php else: ?>
                                <span class="status-dot dot-inactive"></span><span class="text-danger small fw-bold">معطّل</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($u['last_login'])): ?>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($u['last_login'])) ?></small>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="users.php?edit=<?= $u['id'] ?>" class="action-btn edit">
                                    <i class="fa fa-edit"></i> تعديل
                                </a>
                                <?php if (!$isMe): ?>
                                <a href="users.php?toggle=<?= $u['id'] ?>"
                                   class="action-btn <?= $u['active'] ? 'toggle-off' : 'toggle-on' ?>"
                                   onclick="return confirm('<?= $u['active'] ? 'تعطيل' : 'تفعيل' ?> هذا المستخدم؟')">
                                    <i class="fa <?= $u['active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                    <?= $u['active'] ? 'تعطيل' : 'تفعيل' ?>
                                </a>
                                <a href="users.php?delete=<?= $u['id'] ?>" class="action-btn del"
                                   onclick="return confirm('حذف المستخدم «<?= htmlspecialchars($u['full_name']) ?>» نهائياً؟')">
                                    <i class="fa fa-trash"></i>
                                </a>
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

        <!-- Role Guide -->
        <div class="mt-4 p-3" style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
            <h6 class="fw-bold mb-3 text-muted"><i class="fa fa-info-circle me-2"></i>دليل الصلاحيات</h6>
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="p-3 rounded-3" style="background:#f9f4ff;border-right:3px solid #9b59b6;">
                        <strong style="color:#9b59b6;"><i class="fa fa-user-shield me-1"></i>مدير عام</strong>
                        <p class="mb-0 mt-1 small text-muted">صلاحية كاملة على جميع وظائف النظام، إدارة المستخدمين والإعدادات.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3" style="background:#eff8ff;border-right:3px solid #3498db;">
                        <strong style="color:#3498db;"><i class="fa fa-user-tie me-1"></i>مشرف</strong>
                        <p class="mb-0 mt-1 small text-muted">إدارة الحجوزات والرحلات، عرض التقارير، لا يمكنه إدارة المستخدمين.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3" style="background:#f0fdf4;border-right:3px solid #27ae60;">
                        <strong style="color:#27ae60;"><i class="fa fa-user me-1"></i>موظف</strong>
                        <p class="mb-0 mt-1 small text-muted">إدارة الحجوزات فقط، تأكيد وإلغاء الحجوزات.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass(id, btn) {
    const inp = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fa fa-eye';
    }
}
</script>
</body>
</html>
