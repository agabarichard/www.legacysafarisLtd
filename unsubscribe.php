<?php
// ================================================================
//  unsubscribe.php – Newsletter unsubscribe handler
// ================================================================
require_once __DIR__ . '/includes/functions.php';

$pdo   = getPDO();
$token = sanitizeInput($_GET['token'] ?? '');
$msg   = '';

if ($token) {
    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE token = ? AND is_active = 1");
    $stmt->execute([$token]);
    $sub = $stmt->fetch();
    if ($sub) {
        $pdo->prepare("UPDATE newsletter_subscribers SET is_active=0 WHERE id=?")->execute([$sub['id']]);
        $msg = 'success';
    } else {
        $msg = 'invalid';
    }
}

$pageTitle = 'Unsubscribe | ' . setting('site_name');
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="container section-padding" style="text-align:center;min-height:50vh;display:flex;align-items:center;justify-content:center;">
  <div style="max-width:500px;">
    <?php if ($msg === 'success'): ?>
      <i class="fas fa-check-circle" style="font-size:4rem;color:#27ae60;"></i>
      <h2 style="margin-top:1rem;">You've been unsubscribed</h2>
      <p>You will no longer receive newsletter emails from <?= e(setting('site_name')) ?>.</p>
    <?php elseif ($msg === 'invalid'): ?>
      <i class="fas fa-exclamation-circle" style="font-size:4rem;color:#e74c3c;"></i>
      <h2>Invalid or expired link</h2>
      <p>This unsubscribe link is invalid or you are already unsubscribed.</p>
    <?php else: ?>
      <h2>Invalid Request</h2>
      <p>No unsubscribe token provided.</p>
    <?php endif; ?>
    <a href="/index.php" class="btn-primary" style="display:inline-block;margin-top:1.5rem;">Back to Website</a>
  </div>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
