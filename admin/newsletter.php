<?php
// ================================================================
//  admin/newsletter.php – Newsletter Subscribers + Send Campaign
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo    = getPDO();
$action = sanitizeInput($_GET['action'] ?? 'list');

// Send newsletter campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_newsletter'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $body    = $_POST['body'] ?? '';
        if (!$subject || !$body) { setFlash('error','Subject and body are required.'); }
        else {
            $subs = $pdo->query("SELECT email, name, token FROM newsletter_subscribers WHERE is_active=1")->fetchAll();
            $sent = 0;
            foreach ($subs as $sub) {
                $unsubLink = SITE_URL . '/unsubscribe.php?token=' . urlencode($sub['token']);
                $plainBody = trim($body) . "\n\n---\n" .
                  "You are receiving this because you subscribed to Legacy Safaris news.\n" .
                  "Unsubscribe: " . $unsubLink;
                if (sendMail($sub['email'], $sub['name'] ?: '', $subject, $plainBody, $plainBody, false)) $sent++;
            }
            setFlash('success', "Campaign sent to {$sent} subscriber(s).");
        }
    }
    header('Location: /admin/newsletter.php'); exit;
}

// Toggle subscriber active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("UPDATE newsletter_subscribers SET is_active = 1 - is_active WHERE id=?")->execute([(int)$_POST['toggle_id']]);
        setFlash('success','Subscriber updated.');
    }
    header('Location: /admin/newsletter.php'); exit;
}

// Delete subscriber
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success','Subscriber removed.');
    }
    header('Location: /admin/newsletter.php'); exit;
}

$subscribers = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll();
$totalActive = count(array_filter($subscribers, fn($s) => $s['is_active']));
$totalSubscribers = count($subscribers);

$pageTitle = 'Newsletter';
include __DIR__ . '/includes/header.php';
?>

<div class="newsletter-tabs">
  <a href="?action=list" class="btn <?= $action!=='send'?'btn-primary':'btn-light' ?>"><i class="fas fa-users"></i> Subscribers (<?= $totalActive ?> active)</a>
  <a href="?action=send" class="btn <?= $action==='send'?'btn-primary':'btn-light' ?>"><i class="fas fa-paper-plane"></i> Send Campaign</a>
</div>

<?php if ($action === 'send'): ?>
<div class="newsletter-hero card">
  <div class="card-body newsletter-hero-body">
    <div>
      <p class="newsletter-kicker">Campaign composer</p>
      <h3>Send a newsletter to your active subscribers</h3>
      <p class="newsletter-intro">Write one polished message and send it to everyone who opted in. The unsubscribe link is added automatically.</p>
    </div>
    <div class="newsletter-summary">
      <div class="newsletter-summary-card">
        <span class="label">Active recipients</span>
        <strong><?= $totalActive ?></strong>
      </div>
      <div class="newsletter-summary-card">
        <span class="label">Total subscribers</span>
        <strong><?= $totalSubscribers ?></strong>
      </div>
      <div class="newsletter-summary-card">
        <span class="label">Delivery mode</span>
        <strong>Plain text</strong>
      </div>
    </div>
  </div>
</div>

<div class="newsletter-layout">
  <div class="newsletter-panel card">
    <div class="card-header"><h3><i class="fas fa-pen-nib" style="color:var(--gold)"></i> Compose campaign</h3></div>
    <div class="card-body">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="send_newsletter" value="1">
        <div class="form-group">
          <label>Email Subject *</label>
          <input type="text" name="subject" class="form-control" placeholder="🦁 Exclusive Safari Offer This Season" required>
        </div>
        <div class="form-group">
          <label>Email Body</label>
          <textarea name="body" class="form-control newsletter-body" rows="14" placeholder="Hello Safari Lover,\n\nWe have an exciting offer for you..." required></textarea>
          <small class="newsletter-help">Write a normal message. A plain-text unsubscribe link is added automatically.</small>
        </div>
        <div class="newsletter-actions">
          <button type="submit" class="btn btn-primary" onclick="return confirm('Send to <?= $totalActive ?> subscribers?')">
            <i class="fas fa-paper-plane"></i> Send Campaign
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="newsletter-panel card">
    <div class="card-header"><h3><i class="fas fa-bolt" style="color:var(--primary)"></i> Tips & checklist</h3></div>
    <div class="card-body">
      <div class="newsletter-note">
        <h4>Before you send</h4>
        <ul>
          <li>Confirm the subject line reads well on mobile.</li>
          <li>Use one clear offer or update per campaign.</li>
          <li>Keep HTML simple so it renders cleanly in email clients.</li>
        </ul>
      </div>
      <div class="newsletter-note">
        <h4>Audience</h4>
        <p>This message will go to all <strong><?= $totalActive ?></strong> currently active subscribers. Inactive contacts are skipped automatically.</p>
      </div>
      <div class="newsletter-note newsletter-note-soft">
        <h4>Suggested structure</h4>
        <p>Use a strong heading, a short paragraph, one featured image or offer, and a single call to action.</p>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<div class="card">
  <div class="card-header"><h3><i class="fas fa-users" style="color:var(--primary)"></i> All Subscribers (<?= count($subscribers) ?>)</h3>
    <!-- Export CSV -->
    <a href="?export=csv" class="btn btn-light btn-sm"><i class="fas fa-download"></i> Export CSV</a>
  </div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Email</th><th>Name</th><th>Subscribed</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($subscribers as $sub): ?>
        <tr>
          <td><?= e($sub['email']) ?></td>
          <td><?= e($sub['name'] ?: '—') ?></td>
          <td><?= date('M j, Y', strtotime($sub['subscribed_at'])) ?></td>
          <td><?= $sub['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' ?></td>
          <td>
            <form method="POST" style="display:inline">
              <?= csrfField() ?><input type="hidden" name="toggle_id" value="<?= $sub['id'] ?>">
              <button class="btn btn-light btn-sm"><?= $sub['is_active']?'Deactivate':'Activate' ?></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Remove subscriber?')">
              <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $sub['id'] ?>">
              <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$subscribers): ?><tr><td colspan="5" style="text-align:center;padding:2rem;color:#888;">No subscribers yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php
// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Name', 'Status', 'Subscribed']);
    foreach ($subscribers as $s) {
        fputcsv($out, [$s['email'], $s['name'], $s['is_active']?'Active':'Inactive', $s['subscribed_at']]);
    }
    fclose($out);
    exit;
}
?>

<?php include __DIR__ . '/includes/footer.php'; ?>
