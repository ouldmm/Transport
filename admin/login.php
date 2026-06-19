<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];

            // تحديث آخر تسجيل دخول
            $db->prepare("UPDATE users SET last_login = ? WHERE id = ?")
               ->execute([date('Y-m-d H:i:s'), $user['id']]);

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    } catch (Exception $e) {
        // Demo login: admin / admin123
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['admin_id']   = 1;
            $_SESSION['admin_name'] = 'مدير النظام';
            $_SESSION['admin_role'] = 'admin';
            header('Location: dashboard.php');
            exit;
        }
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تسجيل الدخول - لوحة التحكم</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    min-height: 100vh;
    background: linear-gradient(135deg, #0a4f3a 0%, #1a7a58 50%, #0a4f3a 100%);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}
.login-card {
    background: #fff; border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 420px; overflow: hidden;
}
.login-header {
    background: linear-gradient(135deg, #0a4f3a, #2db37e);
    padding: 2.5rem 2rem; text-align: center; color: #fff;
}
.login-header .icon-wrap {
    width: 70px; height: 70px; background: rgba(255,255,255,0.2);
    border-radius: 50%; margin: 0 auto 1rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
}
.login-body { padding: 2rem; }
.form-control { border-radius: 10px; border: 2px solid #e8e8e8; padding: 0.75rem 1rem; }
.form-control:focus { border-color: #2db37e; box-shadow: 0 0 0 3px rgba(45,179,126,0.15); }
.btn-login {
    background: linear-gradient(135deg, #0a4f3a, #2db37e);
    color: #fff; font-weight: 700; border: none; border-radius: 10px;
    padding: 0.8rem; font-size: 1.05rem; width: 100%;
    transition: transform .1s, box-shadow .2s;
}
.btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(10,79,58,0.4); color: #fff; }
.input-icon { position: relative; }
.input-icon i { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #aaa; }
.input-icon input { padding-right: 2.8rem; }
</style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="icon-wrap"><i class="fa fa-bus"></i></div>
        <h4 class="mb-0 fw-bold">نقل موريتانيا</h4>
        <p class="mb-0 mt-1" style="opacity:.8;font-size:.9rem;">لوحة تحكم الإدارة</p>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
        <div class="alert alert-danger py-2 text-center">
            <i class="fa fa-exclamation-circle me-1"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold text-muted small">اسم المستخدم</label>
                <div class="input-icon">
                    <i class="fa fa-user"></i>
                    <input type="text" name="username" class="form-control" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="أدخل اسم المستخدم">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold text-muted small">كلمة المرور</label>
                <div class="input-icon">
                    <i class="fa fa-lock"></i>
                    <input type="password" name="password" class="form-control" required
                           placeholder="أدخل كلمة المرور">
                </div>
            </div>
            <button type="submit" class="btn-login btn">
                <i class="fa fa-sign-in-alt me-2"></i>تسجيل الدخول
            </button>
        </form>

        <div class="mt-4 p-3 bg-light rounded text-center small text-muted">
            <i class="fa fa-info-circle me-1"></i>
            واجهة خاصة بالادارة <strong></strong>  <strong></strong>
        </div>
        <div class="text-center mt-3">
            <a href="../index.php" class="text-muted small">
                <i class="fa fa-arrow-right me-1"></i>العودة للموقع
            </a>
        </div>
    </div>
</div>
</body>
</html>
