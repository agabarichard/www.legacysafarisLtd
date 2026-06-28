<?php
// ================================================================
//  admin/reset-admin.php – Emergency Admin Credential Reset
//  USE ONCE, THEN DELETE THIS FILE!
// ================================================================

// --- CONFIGURATION -------------------------------------------------
// Set a strong secret token – change this to a random string!
define('RESET_TOKEN', 'CHANGE_THIS_TO_A_RANDOM_STRING');

// Which admin user to update? (default: ID 1)
define('ADMIN_ID', 1);
// ------------------------------------------------------------------

require_once dirname(__DIR__) . '/includes/functions.php';

$error   = '';
$success = '';

// Check token
$token = $_GET['token'] ?? '';
if ($token !== RESET_TOKEN) {
    die('Invalid or missing reset token. Access denied.');
}

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$email) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET email = ?, password = ? WHERE id = ?");
        if ($stmt->execute([$email, $hash, ADMIN_ID])) {
            $success = "Admin credentials updated successfully! You can now <a href='/admin/login.php'>log in</a>.";
        } else {
            $error = 'Database error – please check your server logs.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Admin Credentials</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: #1a3a2a;
      font-family: 'Inter', sans-serif;
    }
    .reset-box {
      background: white;
      border-radius: 24px;
      padding: 2.5rem;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 20px 60px rgba(0,0,0,.4);
    }
    .reset-box h2 {
      color: #2F6B3E;
      margin-bottom: 0.25rem;
    }
    .reset-box p.sub {
      color: #888;
      margin-bottom: 1.5rem;
    }
    .reset-box input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid #ddd;
      border-radius: 12px;
      font-size: 1rem;
      margin-bottom: 1rem;
      box-sizing: border-box;
    }
    .reset-box button {
      width: 100%;
      padding: 13px;
      background: #2F6B3E;
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
    }
    .reset-box button:hover { background: #1a3a2a; }
    .message-ok {
      background: #d4edda;
      color: #155724;
      padding: 0.75rem 1rem;
      border-radius: 10px;
      margin-bottom: 1rem;
    }
    .error-msg {
      background: #f8d7da;
      color: #721c24;
      padding: 0.75rem 1rem;
      border-radius: 10px;
      margin-bottom: 1rem;
    }
    .warning {
      background: #fff3cd;
      color: #856404;
      padding: 0.75rem 1rem;
      border-radius: 10px;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<div class="reset-box">
  <h2>🔑 Reset Admin</h2>
  <p class="sub">Set a new email and password for your admin account.</p>

  <?php if ($success): ?>
    <div class="message-ok"><?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="error-msg"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="warning">
    <strong>⚠️ Security</strong> – This script is unprotected except for the token in the URL.
    After using it, <strong>delete this file</strong> or change the <code>RESET_TOKEN</code>.
  </div>

  <form method="POST">
    <input type="email" name="email" placeholder="New admin email" required>
    <input type="password" name="password" placeholder="New password (min 8 chars)" required>
    <input type="password" name="confirm" placeholder="Confirm new password" required>
    <button type="submit"><i class="fas fa-save"></i> Update Credentials</button>
  </form>
</div>
</body>
</html>