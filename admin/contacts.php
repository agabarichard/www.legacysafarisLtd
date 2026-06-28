<?php
// ================================================================
//  admin/contacts.php – View Contact Messages
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo    = getPDO();
$viewId = (int)($_GET['view'] ?? 0);
$msg    = null;

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("DELETE FROM contacts WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success','Message deleted.');
    }
    header('Location: /admin/contacts.php'); exit;
}

if ($viewId) {
    $s = $pdo->prepare("SELECT * FROM contacts WHERE id=?"); $s->execute([$viewId]); $msg = $s->fetch();
    if ($msg) $pdo->prepare("UPDATE contacts SET is_read=1 WHERE id=?")->execute([$viewId]);
}

$msgs = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Contact Messages';
include __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
<div class="card">
  <div class="card-header">
    <h3>Message from <?= e($msg['name']) ?></h3>
    <a href="/admin/contacts.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <div style="background:#f8f9fa;padding:1rem;border-radius:12px;margin-bottom:1.5rem;">
      <p><strong>From:</strong> <?= e($msg['name']) ?> &lt;<a href="mailto:<?= e($msg['email']) ?>"><?= e($msg['email']) ?></a>&gt;</p>
      <?php if ($msg['phone']): ?><p><strong>Phone:</strong> <?= e($msg['phone']) ?></p><?php endif; ?>
      <p><strong>Subject:</strong> <?= e($msg['subject'] ?: 'No subject') ?></p>
      <p><strong>Received:</strong> <?= date('F j, Y \a\t g:i A', strtotime($msg['created_at'])) ?></p>
    </div>
    <div style="line-height:1.8;font-size:1rem;"><?= nl2br(e($msg['message'])) ?></div>
    <div style="margin-top:1.5rem;display:flex;gap:1rem;">
      <a href="mailto:<?= e($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject'] ?: 'Your Inquiry') ?>"
         class="btn btn-primary"><i class="fas fa-reply"></i> Reply via Email</a>
      <a href="https://wa.me/<?= preg_replace('/\D/','',$msg['phone']??'') ?>" target="_blank"
         class="btn btn-gold" <?= !$msg['phone']?'style="display:none"':'' ?>><i class="fab fa-whatsapp"></i> WhatsApp</a>
      <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
        <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
        <button class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-envelope" style="color:var(--primary)"></i> All Messages (<?= count($msgs) ?>)</h3>
  </div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($msgs as $m): ?>
        <tr <?= !$m['is_read']?'style="font-weight:600;"':'' ?>>
          <td><?= e($m['name']) ?></td>
          <td><?= e($m['email']) ?></td>
          <td><a href="?view=<?= $m['id'] ?>"><?= e(mb_substr($m['subject']?:'(No subject)',0,40)) ?></a></td>
          <td><?= timeAgo($m['created_at']) ?></td>
          <td><?= $m['is_read'] ? '<span class="badge badge-gray">Read</span>' : '<span class="badge badge-gold">New</span>' ?></td>
          <td>
            <a href="?view=<?= $m['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-eye"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
              <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
              <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$msgs): ?><tr><td colspan="6" style="text-align:center;padding:2rem;color:#888;">No messages yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
