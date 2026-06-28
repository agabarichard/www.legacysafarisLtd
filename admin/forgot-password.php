<?php
// ================================================================
//  admin/forgot-password.php – Request password reset
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Security error. Please refresh and try again.';
        $messageType = 'error';
    } else {
        $email = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        // Always use the same response to avoid account enumeration.
        $message = 'If an account exists for that email, a reset link has been sent.';
        $messageType = 'info';

        if ($email) {
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

            $stmt = $pdo->prepare("SELECT id, name, email FROM admin_users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin) {
                $pdo->prepare("UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL")
                    ->execute([(int)$admin['id']]);

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                // Use database time for expiry to avoid PHP/DB timezone drift issues.
                $pdo->prepare("INSERT INTO admin_password_resets (admin_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))")
                  ->execute([(int)$admin['id'], $tokenHash]);

                $resetLink = SITE_URL . '/admin/reset-password.php?email=' . urlencode((string)$admin['email']) . '&token=' . urlencode($token);

                $html = '<h2>Password Reset Request</h2>'
                    . '<p>Hello ' . e((string)$admin['name']) . ',</p>'
                    . '<p>Use the link below to reset your admin password. This link expires in 1 hour.</p>'
                    . '<p><a href="' . $resetLink . '">Reset Password</a></p>'
                    . '<p>If you did not request this, you can ignore this email.</p>';

                $text = "Password Reset Request\n\n"
                    . "Hello " . (string)$admin['name'] . ",\n\n"
                    . "Use this link to reset your password (valid for 1 hour):\n"
                    . $resetLink . "\n\n"
                    . "If you did not request this, ignore this email.";

                sendMail((string)$admin['email'], (string)$admin['name'], 'Admin Password Reset', $html, $text);
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
  <title>Forgot Password – Legacy Safaris Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/jpeg" href="/images/logo.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:linear-gradient(135deg,#1a3a2a,#2F6B3E); font-family:'Inter',sans-serif; }
    .login-box { background:white; border-radius:24px; padding:2.5rem; width:100%; max-width:430px; box-shadow:0 20px 60px rgba(0,0,0,.3); }
    .login-box h2 { color:#2F6B3E; margin-bottom:.4rem; }
    .login-box p { color:#6b7280; margin-bottom:1.2rem; line-height:1.6; }
    .login-box input { width:100%; padding:12px 16px; border:1px solid #ddd; border-radius:12px; font-size:1rem; margin-bottom:1rem; }
    .login-box button { width:100%; padding:13px; background:#2F6B3E; color:white; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer; }
    .login-box button:hover { background:#1a3a2a; }
    .msg { padding:.75rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:.9rem; }
    .msg.error { background:#f8d7da; color:#721c24; }
    .msg.info { background:#d1ecf1; color:#0c5460; }
    .logo-area { text-align:center; margin-bottom:1.4rem; font-size:1.5rem; font-weight:700; color:#2F6B3E; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo-area"><i class="fas fa-key"></i> Password Reset</div>
    <p>Enter your admin email address and we will send a secure password reset link.</p>

    <?php if ($message): ?>
    <div class="msg <?= $messageType === 'error' ? 'error' : 'info' ?>"><i class="fas fa-info-circle"></i> <?= e($message) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <?= csrfField() ?>
      <input type="email" name="email" placeholder="Admin Email" required autofocus>
      <button type="submit"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
    </form>

    <p style="margin-top:1rem;text-align:center;">
      <a href="/admin/login.php" style="color:#2F6B3E;font-weight:600;text-decoration:none;">Back to login</a>
    </p>
  </div>
</body>
</html>
