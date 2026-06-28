<?php
// ================================================================
//  admin/bookings.php – Manage Booking Requests
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo    = getPDO();
$editId = (int)($_GET['edit'] ?? 0);
$booking = null;

// Update status/notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $id     = (int)$_POST['booking_id'];
        $status = sanitizeInput($_POST['status'] ?? 'new');
        $notes  = sanitizeInput($_POST['admin_notes'] ?? '');
        $pdo->prepare("UPDATE bookings SET status=?, admin_notes=? WHERE id=?")->execute([$status, $notes, $id]);

        // Send status email to guest if confirmed
        if ($status === 'confirmed') {
            $bk = $pdo->prepare("SELECT * FROM bookings WHERE id=?"); $bk->execute([$id]); $bk = $bk->fetch();
            if ($bk) {
                sendMail($bk['email'], $bk['name'], 'Your Safari Booking is Confirmed! 🎉',
                    "<h2>Great news, {$bk['name']}!</h2>
                     <p>Your booking for <strong>{$bk['tour_name']}</strong> has been <strong>confirmed</strong>.</p>
                     <p>Travel Date: {$bk['travel_date']}<br>Group Size: {$bk['group_size']}</p>
                     " . ($notes ? "<p><strong>Notes from us:</strong> {$notes}</p>" : '') . "
                     <p>We will be in touch with detailed itinerary soon!</p>
                     <p>Best regards,<br>" . setting('site_name') . "</p>"
                );
            }
        }
        setFlash('success','Booking updated.');
    }
    header('Location: /admin/bookings.php'); exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success','Booking deleted.');
    }
    header('Location: /admin/bookings.php'); exit;
}

if ($editId) {
    $s = $pdo->prepare("SELECT b.*, t.name AS tour_title FROM bookings b LEFT JOIN tours t ON t.id=b.tour_id WHERE b.id=?");
    $s->execute([$editId]); $booking = $s->fetch();
}

$status_filter = sanitizeInput($_GET['status'] ?? '');
$sql = "SELECT b.*, t.name AS tour_title FROM bookings b LEFT JOIN tours t ON t.id=b.tour_id";
$params = [];
if ($status_filter) { $sql .= " WHERE b.status=?"; $params[] = $status_filter; }
$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Bookings';
include __DIR__ . '/includes/header.php';
?>

<?php if ($booking): ?>
<!-- Edit booking -->
<div class="card">
  <div class="card-header">
    <div class="booking-hero">
      <div>
        <h3>Booking #<?= $booking['id'] ?> · <?= e($booking['name']) ?></h3>
        <p>Manage the request, update the status, and keep guest notes in one place.</p>
      </div>
      <a href="/admin/bookings.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back to bookings</a>
    </div>
  </div>
  <div class="card-body">
    <div class="booking-summary-grid">
      <div class="booking-summary-card"><span class="label">Guest</span><div class="value"><?= e($booking['name']) ?></div></div>
      <div class="booking-summary-card"><span class="label">Email</span><div class="value"><a href="mailto:<?= e($booking['email']) ?>"><?= e($booking['email']) ?></a></div></div>
      <div class="booking-summary-card"><span class="label">Status</span><div class="value"><?= e(ucfirst($booking['status'])) ?></div></div>
      <div class="booking-summary-card"><span class="label">Received</span><div class="value"><?= timeAgo($booking['created_at']) ?></div></div>
    </div>
    <div class="booking-detail-grid">
      <div class="booking-panel">
        <div class="booking-panel-header">Booking details</div>
        <div class="booking-panel-body">
          <div class="booking-summary-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-bottom:1rem;">
            <div class="booking-summary-card"><span class="label">Phone</span><div class="value"><?= e($booking['phone'] ?: '—') ?></div></div>
            <div class="booking-summary-card"><span class="label">Tour</span><div class="value"><?= e($booking['tour_name'] ?: $booking['tour_title'] ?: '—') ?></div></div>
            <div class="booking-summary-card"><span class="label">Travel Date</span><div class="value"><?= $booking['travel_date'] ? date('F j, Y',strtotime($booking['travel_date'])) : '—' ?></div></div>
            <div class="booking-summary-card"><span class="label">Group Size</span><div class="value"><?= (int)$booking['group_size'] ?></div></div>
          </div>
          <div class="booking-message"><strong style="display:block;margin-bottom:.45rem;color:var(--primary-d);">Message</strong><?= nl2br(e($booking['message'])) ?></div>
        </div>
      </div>

      <div class="booking-panel">
        <div class="booking-panel-header">Update booking</div>
        <div class="booking-panel-body">
          <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="update_booking" value="1">
            <input type="hidden" name="booking_id"     value="<?= $booking['id'] ?>">
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <?php foreach (['new','contacted','confirmed','cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $booking['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Admin Notes</label>
              <textarea name="admin_notes" class="form-control" rows="5" placeholder="Add internal notes or a confirmation message for the guest"><?= e($booking['admin_notes'] ?? '') ?></textarea>
            </div>
            <div class="booking-actions">
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Booking</button>
              <a href="mailto:<?= e($booking['email']) ?>" class="btn btn-light btn-sm"><i class="fas fa-envelope"></i> Email Guest</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-calendar-check" style="color:var(--gold)"></i> Bookings (<?= count($bookings) ?>)</h3>
    <div class="booking-filters">
      <?php foreach (['','new','contacted','confirmed','cancelled'] as $sf): ?>
      <a href="?status=<?= $sf ?>" class="btn btn-sm <?= $status_filter===$sf?'btn-primary':'btn-light' ?>"><?= $sf?ucfirst($sf):'All' ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="booking-table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Tour</th><th>Date</th><th>Group</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr>
          <td><?= e($b['name']) ?></td>
          <td><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></td>
          <td><?= e(mb_substr($b['tour_name'] ?: $b['tour_title'] ?: '—', 0, 30)) ?></td>
          <td><?= $b['travel_date'] ? date('M j, Y',strtotime($b['travel_date'])) : '—' ?></td>
          <td><?= (int)$b['group_size'] ?></td>
          <td><?= timeAgo($b['created_at']) ?></td>
          <td><span class="booking-status-pill <?= e($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
          <td>
            <a href="?edit=<?= $b['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
              <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $b['id'] ?>">
              <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?><tr><td colspan="8" style="text-align:center;padding:2rem;color:#888;">No bookings yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
