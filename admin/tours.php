<?php
// ================================================================
//  admin/tours.php – Manage Tours (Full CRUD)
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo    = getPDO();
$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['edit'] ?? 0);
$tour   = null;

// ── DELETE ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'CSRF error.'); }
    else {
        $pdo->prepare("DELETE FROM tours WHERE id = ?")->execute([(int)$_POST['delete_id']]);
        setFlash('success', 'Tour deleted successfully.');
    }
    header('Location: /admin/tours.php'); exit;
}

// ── SAVE (Create / Update) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tour'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'CSRF error.'); header('Location: /admin/tours.php'); exit; }

    $tid   = (int)($_POST['tour_id'] ?? 0);
    $name  = sanitizeInput($_POST['name']  ?? '');
    $short = sanitizeInput($_POST['short_desc'] ?? '');
    $full  = $_POST['full_desc'] ?? '';        // HTML from editor
    $price = (float)($_POST['price'] ?? 0);
    $days  = (int)($_POST['duration_days'] ?? 1);
    $cat   = sanitizeInput($_POST['category'] ?? 'budget');
    $accom = sanitizeInput($_POST['accommodation'] ?? '');
    $max   = max(1, (int)($_POST['max_group'] ?? 12));
    $feat  = isset($_POST['is_featured']) ? 1 : 0;
    $active= isset($_POST['is_active'])   ? 1 : 0;
    $slug  = uniqueSlug($pdo, 'tours', slugify($name), $tid ?: null);

    $imgPath = handleImageUpload('image', 'tours');

    if ($tid) {
        // Update
        $sql = "UPDATE tours SET name=?,slug=?,short_desc=?,full_desc=?,price=?,duration_days=?,category=?,accommodation=?,max_group=?,is_featured=?,is_active=?,updated_at=NOW()";
        $params = [$name,$slug,$short,$full,$price,$days,$cat,$accom,$max,$feat,$active];
        if ($imgPath) { $sql .= ",image=?"; $params[] = $imgPath; }
        $sql .= " WHERE id=?"; $params[] = $tid;
        $pdo->prepare($sql)->execute($params);
    } else {
        $pdo->prepare(
            "INSERT INTO tours (name,slug,short_desc,full_desc,price,duration_days,category,accommodation,max_group,is_featured,is_active,image)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([$name,$slug,$short,$full,$price,$days,$cat,$accom,$max,$feat,$active,$imgPath]);
        $tid = (int)$pdo->lastInsertId();
    }

    // Save highlights
    $pdo->prepare("DELETE FROM tour_highlights WHERE tour_id=?")->execute([$tid]);
    foreach (array_filter(explode("\n", $_POST['highlights'] ?? '')) as $i => $hl) {
        $pdo->prepare("INSERT INTO tour_highlights (tour_id, highlight, sort_order) VALUES (?,?,?)")
            ->execute([$tid, trim($hl), $i]);
    }

    // Save itinerary
    $pdo->prepare("DELETE FROM tour_itinerary WHERE tour_id=?")->execute([$tid]);
    foreach (array_filter(explode("\n", $_POST['itinerary'] ?? '')) as $i => $line) {
        if (preg_match('/^Day\s*(\d+)[:\-]\s*(.+)/i', $line, $m)) {
            $pdo->prepare("INSERT INTO tour_itinerary (tour_id,day_num,title) VALUES (?,?,?)")
                ->execute([$tid, (int)$m[1], trim($m[2])]);
        }
    }

    // Save includes / excludes
    $pdo->prepare("DELETE FROM tour_includes WHERE tour_id=?")->execute([$tid]);
    foreach (array_filter(explode("\n", $_POST['includes'] ?? '')) as $item) {
        $pdo->prepare("INSERT INTO tour_includes (tour_id,item,type) VALUES (?,?,'include')")->execute([$tid, trim($item)]);
    }
    foreach (array_filter(explode("\n", $_POST['excludes'] ?? '')) as $item) {
        $pdo->prepare("INSERT INTO tour_includes (tour_id,item,type) VALUES (?,?,'exclude')")->execute([$tid, trim($item)]);
    }

    setFlash('success', 'Tour saved successfully!');
    header('Location: /admin/tours.php'); exit;
}

// ── LOAD for edit ─────────────────────────────────────────────
if ($editId) {
    $action = 'edit';
    $tour   = $pdo->prepare("SELECT * FROM tours WHERE id=?")->execute([$editId]) ? null : null;
    $s = $pdo->prepare("SELECT * FROM tours WHERE id=?"); $s->execute([$editId]); $tour = $s->fetch();

    $hls = $pdo->prepare("SELECT highlight FROM tour_highlights WHERE tour_id=? ORDER BY sort_order");
    $hls->execute([$editId]);
    $tour['_highlights'] = implode("\n", array_column($hls->fetchAll(), 'highlight'));

    $its = $pdo->prepare("SELECT day_num, title FROM tour_itinerary WHERE tour_id=? ORDER BY day_num");
    $its->execute([$editId]);
    $tour['_itinerary'] = implode("\n", array_map(fn($r) => "Day {$r['day_num']}: {$r['title']}", $its->fetchAll()));

    $inc = $pdo->prepare("SELECT item, type FROM tour_includes WHERE tour_id=?");
    $inc->execute([$editId]);
    $all = $inc->fetchAll();
    $tour['_includes'] = implode("\n", array_column(array_filter($all, fn($r)=>$r['type']==='include'), 'item'));
    $tour['_excludes'] = implode("\n", array_column(array_filter($all, fn($r)=>$r['type']==='exclude'), 'item'));
}

// ── LIST ─────────────────────────────────────────────────────
$tours = $pdo->query("SELECT * FROM tours ORDER BY sort_order ASC, id DESC")->fetchAll();

$pageTitle = 'Manage Tours';
include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'list'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-map-marked-alt" style="color:var(--gold)"></i> All Tours (<?= count($tours) ?>)</h3>
    <a href="?action=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Tour</a>
  </div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Image</th><th>Name</th><th>Price</th><th>Days</th><th>Category</th><th>Featured</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($tours as $t): ?>
        <tr>
          <td><?php if ($t['image']): ?><img src="/<?= e($t['image']) ?>" class="img-preview"><?php endif; ?></td>
          <td><strong><?= e($t['name']) ?></strong><br><small style="color:#888"><?= e($t['slug']) ?></small></td>
          <td><?= formatPrice((float)$t['price'], 'UGX') ?></td>
          <td><?= (int)$t['duration_days'] ?></td>
          <td><span class="badge badge-blue"><?= e(ucfirst($t['category'])) ?></span></td>
          <td><?= $t['is_featured'] ? '<span class="badge badge-gold">Yes</span>' : '<span class="badge badge-gray">No</span>' ?></td>
          <td><?= $t['is_active']   ? '<span class="badge badge-green">Yes</span>' : '<span class="badge badge-red">No</span>' ?></td>
          <td>
            <a href="?edit=<?= $t['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this tour?')">
              <?= csrfField() ?>
              <input type="hidden" name="delete_id" value="<?= $t['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$tours): ?><tr><td colspan="8" style="text-align:center;padding:2rem;color:#888;">No tours yet. <a href="?action=new">Add one!</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: /* new / edit form */ ?>
<div class="card">
  <div class="card-header">
    <h3><?= $tour ? 'Edit Tour' : 'Add New Tour' ?></h3>
    <a href="/admin/tours.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="save_tour" value="1">
      <input type="hidden" name="tour_id"   value="<?= (int)($tour['id'] ?? 0) ?>">

      <div class="form-row">
        <div class="form-group">
          <label>Tour Name *</label>
          <input type="text" name="name" class="form-control" value="<?= e($tour['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category" class="form-control">
            <?php foreach (['budget','premium','family','adventure','honeymoon'] as $c): ?>
            <option value="<?= $c ?>" <?= ($tour['category'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Price (UGX)</label>
          <input type="number" name="price" class="form-control" step="0.01" value="<?= e($tour['price'] ?? '0') ?>">
        </div>
        <div class="form-group">
          <label>Duration (Days)</label>
          <input type="number" name="duration_days" class="form-control" min="1" value="<?= e($tour['duration_days'] ?? '1') ?>">
        </div>
        <div class="form-group">
          <label>Max Group Size</label>
          <input type="number" name="max_group" class="form-control" min="1" value="<?= e($tour['max_group'] ?? '12') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Short Description</label>
        <textarea name="short_desc" class="form-control" rows="3"><?= e($tour['short_desc'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Full Description (HTML allowed)</label>
        <textarea name="full_desc" class="form-control" rows="8" id="fullDescEditor"><?= e($tour['full_desc'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Accommodation</label>
        <input type="text" name="accommodation" class="form-control" value="<?= e($tour['accommodation'] ?? '') ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Highlights (one per line)</label>
          <textarea name="highlights" class="form-control" rows="6" placeholder="Hot air balloon safari&#10;River crossing viewing&#10;Luxury tented camps"><?= e($tour['_highlights'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Itinerary (format: "Day 1: Description")</label>
          <textarea name="itinerary" class="form-control" rows="6" placeholder="Day 1: Arrival in Nairobi&#10;Day 2: Fly to Maasai Mara"><?= e($tour['_itinerary'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>What's Included (one per line)</label>
          <textarea name="includes" class="form-control" rows="5" placeholder="All park fees&#10;Full board accommodation"><?= e($tour['_includes'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>What's Excluded (one per line)</label>
          <textarea name="excludes" class="form-control" rows="5" placeholder="International flights&#10;Visa fees"><?= e($tour['_excludes'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-group">
        <label>Tour Image</label>
        <?php if (!empty($tour['image'])): ?>
        <div style="margin-bottom:.5rem;"><img src="/<?= e($tour['image']) ?>" style="height:80px;border-radius:8px;object-fit:cover;"> <small style="color:#888">Current image</small></div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>

      <div style="display:flex;gap:1.5rem;align-items:center;margin-bottom:1.5rem;">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
          <input type="checkbox" name="is_featured" value="1" <?= ($tour['is_featured'] ?? 0) ? 'checked' : '' ?>> Featured on Homepage
        </label>
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
          <input type="checkbox" name="is_active"   value="1" <?= ($tour['is_active'] ?? 1)   ? 'checked' : '' ?>> Active (visible on site)
        </label>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Tour</button>
      <a href="/admin/tours.php" class="btn btn-light" style="margin-left:.5rem;">Cancel</a>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
