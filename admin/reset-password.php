<?php
// ================================================================
//  admin/reset-password.php – Complete password reset
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$pdo = getPDO();
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_reset_admin (admin_id),
    INDEX idx_admin_reset_token (token_hash),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$email = filter_var(sanitizeInput($_GET['email'] ?? $_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$tokenRaw = sanitizeInput($_GET['token'] ?? $_POST['token'] ?? '');
$token = strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$tokenRaw));

$error = '';
$success = '';
$validToken = false;
$resetRow = null;
$admin = null;

if ($email && strlen($token) === 64) {
    $stmt = $pdo->prepare("SELECT id, name, email FROM admin_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin) {
        $tokenHash = hash('sha256', $token);
        $rs = $pdo->prepare("SELECT * FROM admin_password_resets
                             WHERE admin_id = ? AND token_hash = ? AND used_at IS NULL AND expires_at >= NOW()
                             ORDER BY id DESC LIMIT 1");
        $rs->execute([(int)$admin['id'], $tokenHash]);
        $resetRow = $rs->fetch();
        $validToken = (bool)$resetRow;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security error. Please refresh and try again.';
    } elseif (!$email || !$token || !$validToken || !$admin || !$resetRow) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?")
                ->execute([$newHash, (int)$admin['id']]);

            $pdo->prepare("UPDATE admin_password_resets SET used_at = NOW() WHERE id = ?")
                ->execute([(int)$resetRow['id']]);

            header('Location: /admin/login.php?reset=success');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password – Legacy Safaris Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/jpeg" href="/images/logo.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:linear-gradient(135deg,#1a3a2a,#2F6B3E); font-family:'Inter',sans-serif; }
    .login-box { background:white; border-radius:24px; padding:2.5rem; width:100%; max-width:440px; box-shadow:0 20px 60px rgba(0,0,0,.3); }
    .login-box h2 { color:#2F6B3E; margin-bottom:.4rem; }
    .login-box p { color:#6b7280; margin-bottom:1.2rem; line-height:1.6; }
    .login-box input { width:100%; padding:12px 16px; border:1px solid #ddd; border-radius:12px; font-size:1rem; margin-bottom:1rem; }
    .login-box button { width:100%; padding:13px; background:#2F6B3E; color:white; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer; }
    .login-box button:hover { background:#1a3a2a; }
    .msg { padding:.75rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:.9rem; }
    .msg.error { background:#f8d7da; color:#721c24; }
    .logo-area { text-align:center; margin-bottom:1.4rem; font-size:1.5rem; font-weight:700; color:#2F6B3E; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo-area"><i class="fas fa-lock"></i> Set New Password</div>

    <?php if (!$validToken): ?>
      <div class="msg error"><i class="fas fa-exclamation-circle"></i> This reset link is invalid or expired.</div>
      <p><a href="/admin/forgot-password.php" style="color:#2F6B3E;font-weight:600;text-decoration:none;">Request a new reset link</a></p>
    <?php else: ?>
      <p>Reset password for <strong><?= e((string)$admin['email']) ?></strong>.</p>

      <?php if ($error): ?>
      <div class="msg error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <input type="hidden" name="email" value="<?= e((string)$email) ?>">
        <input type="hidden" name="token" value="<?= e((string)$token) ?>">
        <input type="password" name="password" placeholder="New Password" required minlength="8">
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
        <button type="submit"><i class="fas fa-save"></i> Update Password</button>
      </form>
    <?php endif; ?>

    <p style="margin-top:1rem;text-align:center;">
      <a href="/admin/login.php" style="color:#2F6B3E;font-weight:600;text-decoration:none;">Back to login</a>
    </p>
  </div>
</body>
</html>
