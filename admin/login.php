<?php
// ================================================================
//  admin/login.php – Admin Login
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$info = '';
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
  $info = 'Password updated successfully. Please sign in with your new password.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security error. Please refresh.';
    } else {
        $email    = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Email and password are required.';
        } else {
            $pdo  = getPDO();
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $user['id'];
                $_SESSION['admin_user'] = [
                    'id'   => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                ];
                // Update last login
                $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                header('Location: /admin/index.php');
                exit;
            } else {
                // Prevent timing attacks
                password_verify('dummy', '$2y$12$dummy_hash_to_prevent_timing');
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login – Legacy Safaris Ltd</title>
  <link rel="icon" type="image/jpeg" href="/images/logo.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="/admin/assets/css/admin.css">
  <style>
    body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:linear-gradient(135deg,#1a3a2a,#2F6B3E); }
    .login-box { background:white; border-radius:24px; padding:2.5rem; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,.3); }
    .login-box h2 { color:#2F6B3E; margin-bottom:.5rem; }
    .login-box p { color:#888; margin-bottom:1.5rem; }
    .login-box input { width:100%; padding:12px 16px; border:1px solid #ddd; border-radius:12px; font-size:1rem; margin-bottom:1rem; font-family:'Inter',sans-serif; }
    .login-box button { width:100%; padding:13px; background:#2F6B3E; color:white; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer; }
    .login-box button:hover { background:#1a3a2a; }
    .error-msg { background:#f8d7da; color:#721c24; padding:.75rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:.9rem; }
    .logo-area { text-align:center; margin-bottom:1.2rem; }
    .logo-area img { width: clamp(120px, 22vw, 180px); height: auto; display:block; margin:0 auto .6rem; }
    .logo-area strong { font-size:1.35rem; color:#2F6B3E; display:block; }
    .logo-area span { font-size:.85rem; color:#888; font-weight:400; display:block; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo-area">
      <img src="/images/new%20Logo.png" alt="Legacy Safaris Ltd logo">
      <strong>Legacy Safaris</strong>
      <span>Admin Panel</span>
    </div>
    <?php if ($error): ?>
    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($info): ?>
    <div class="error-msg" style="background:#d1ecf1;color:#0c5460;"><i class="fas fa-info-circle"></i> <?= e($info) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
      <?= csrfField() ?>
      <input type="email" name="email" placeholder="Admin Email" required autofocus>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit"><i class="fas fa-sign-in-alt"></i> Sign In</button>
    </form>
    <p style="margin-top:1rem;text-align:center;font-size:.9rem;">
      <a href="/admin/forgot-password.php" style="color:#2F6B3E;font-weight:600;text-decoration:none;">Forgot password?</a>
    </p>
  </div>
</body>
</html>
