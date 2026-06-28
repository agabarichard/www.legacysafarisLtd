<?php
// ================================================================
//  admin/testimonials.php – Manage Testimonials
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $tid    = (int)($_POST['tid'] ?? 0);
        $name   = sanitizeInput($_POST['name']     ?? '');
        $loc    = sanitizeInput($_POST['location'] ?? '');
        $quote  = sanitizeInput($_POST['quote']    ?? '');
        $rating = max(1,min(5,(int)($_POST['rating']??5)));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($tid) {
            $pdo->prepare("UPDATE testimonials SET name=?,location=?,quote=?,rating=?,is_active=? WHERE id=?")
                ->execute([$name,$loc,$quote,$rating,$active,$tid]);
        } else {
            $pdo->prepare("INSERT INTO testimonials (name,location,quote,rating,is_active) VALUES (?,?,?,?,?)")
                ->execute([$name,$loc,$quote,$rating,$active]);
        }
        setFlash('success','Testimonial saved.');
    }
    header('Location: /admin/testimonials.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("DELETE FROM testimonials WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success','Deleted.');
    }
    header('Location: /admin/testimonials.php'); exit;
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = $editId ? $pdo->prepare("SELECT * FROM testimonials WHERE id=?") : null;
if ($editing) { $editing->execute([$editId]); $editing = $editing->fetch(); }
$items = $pdo->query("SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC")->fetchAll();

$pageTitle = 'Testimonials';
include __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;flex-wrap:wrap;">

  <div class="card">
    <div class="card-header"><h3><?= $editing ? 'Edit Testimonial' : 'Add Testimonial' ?></h3></div>
    <div class="card-body">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="save_testimonial" value="1">
        <input type="hidden" name="tid" value="<?= $editing['id'] ?? 0 ?>">
        <div class="form-group"><label>Name</label>
          <input type="text" name="name" class="form-control" value="<?= e($editing['name'] ?? '') ?>" required></div>
        <div class="form-group"><label>Location (e.g. UK, USA)</label>
          <input type="text" name="location" class="form-control" value="<?= e($editing['location'] ?? '') ?>"></div>
        <div class="form-group"><label>Quote *</label>
          <textarea name="quote" class="form-control" rows="4" required><?= e($editing['quote'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Rating (1–5)</label>
          <input type="number" name="rating" class="form-control" min="1" max="5" value="<?= $editing['rating'] ?? 5 ?>"></div>
        <div class="form-group">
          <label style="display:flex;gap:.5rem;align-items:center;cursor:pointer;">
            <input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1)?'checked':'' ?>> Active (show on website)
          </label>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        <?php if ($editing): ?><a href="/admin/testimonials.php" class="btn btn-light" style="margin-left:.5rem;">Cancel</a><?php endif; ?>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><i class="fas fa-quote-right" style="color:var(--gold)"></i> All Testimonials (<?= count($items) ?>)</h3></div>
    <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Name</th><th>Location</th><th>Rating</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($items as $t): ?>
          <tr>
            <td><?= e($t['name']) ?></td>
            <td><?= e($t['location']) ?></td>
            <td><?= str_repeat('★', (int)$t['rating']) ?></td>
            <td><?= $t['is_active'] ? '<span class="badge badge-green">Yes</span>' : '<span class="badge badge-gray">No</span>' ?></td>
            <td>
              <a href="?edit=<?= $t['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $t['id'] ?>">
                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
