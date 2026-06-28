<?php
// admin/destinations.php – Manage Destinations
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dest'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $did  = (int)($_POST['did'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $ctry = sanitizeInput($_POST['country'] ?? '');
        $srt  = sanitizeInput($_POST['short_desc'] ?? '');
        $fll  = $_POST['full_desc'] ?? '';
        $feat = isset($_POST['is_featured']) ? 1 : 0;
        $act  = isset($_POST['is_active'])   ? 1 : 0;
        $slug = uniqueSlug($pdo, 'destinations', slugify($name), $did ?: null);
        $img  = handleImageUpload('image', 'destinations');
        if ($did) {
            $sql = "UPDATE destinations SET name=?,slug=?,country=?,short_desc=?,full_desc=?,is_featured=?,is_active=?";
            $p   = [$name,$slug,$ctry,$srt,$fll,$feat,$act];
            if ($img) { $sql .= ",image=?"; $p[] = $img; }
            $pdo->prepare($sql . " WHERE id=?")->execute(array_merge($p,[$did]));
        } else {
            $pdo->prepare("INSERT INTO destinations (name,slug,country,short_desc,full_desc,image,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$name,$slug,$ctry,$srt,$fll,$img,$feat,$act]);
        }
        setFlash('success','Destination saved.');
    }
    header('Location: /admin/destinations.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) { $pdo->prepare("DELETE FROM destinations WHERE id=?")->execute([(int)$_POST['delete_id']]); setFlash('success','Deleted.'); }
    header('Location: /admin/destinations.php'); exit;
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) { $s=$pdo->prepare("SELECT * FROM destinations WHERE id=?");$s->execute([$editId]);$editing=$s->fetch(); }
$dests = $pdo->query("SELECT * FROM destinations ORDER BY sort_order ASC,id DESC")->fetchAll();

$pageTitle = 'Destinations';
include __DIR__ . '/includes/header.php';
?>
<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-header"><h3><?= $editing?'Edit Destination':'Add Destination' ?></h3><?php if($editing):?><a href="/admin/destinations.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a><?php endif;?></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="save_dest" value="1">
      <input type="hidden" name="did" value="<?= $editing['id']??0 ?>">
      <div class="form-row">
        <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?= e($editing['name']??'') ?>" required></div>
        <div class="form-group"><label>Country</label><input type="text" name="country" class="form-control" value="<?= e($editing['country']??'') ?>"></div>
      </div>
      <div class="form-group"><label>Short Description</label><textarea name="short_desc" class="form-control" rows="3"><?= e($editing['short_desc']??'') ?></textarea></div>
      <div class="form-group"><label>Full Description</label><textarea name="full_desc" class="form-control" rows="6"><?= e($editing['full_desc']??'') ?></textarea></div>
      <div class="form-group">
        <?php if (!empty($editing['image'])): ?><img src="/<?= e($editing['image']) ?>" style="height:70px;border-radius:8px;object-fit:cover;margin-bottom:.5rem;"><?php endif; ?>
        <label>Image</label><input type="file" name="image" class="form-control" accept="image/*">
      </div>
      <div style="display:flex;gap:1.5rem;">
        <label style="display:flex;gap:.5rem;cursor:pointer;"><input type="checkbox" name="is_featured" value="1" <?= ($editing['is_featured']??0)?'checked':'' ?>> Featured</label>
        <label style="display:flex;gap:.5rem;cursor:pointer;"><input type="checkbox" name="is_active"   value="1" <?= ($editing['is_active']??1)?'checked':'' ?>> Active</label>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:1rem;"><i class="fas fa-save"></i> Save</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3><i class="fas fa-globe-africa" style="color:var(--gold)"></i> Destinations (<?= count($dests) ?>)</h3><a href="?action=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add</a></div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Image</th><th>Name</th><th>Country</th><th>Featured</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($dests as $d): ?>
        <tr>
          <td><?php if($d['image']):?><img src="/<?= e($d['image']) ?>" class="img-preview"><?php endif;?></td>
          <td><?= e($d['name']) ?></td>
          <td><?= e($d['country']) ?></td>
          <td><?= $d['is_featured']?'<span class="badge badge-gold">Yes</span>':'—' ?></td>
          <td><?= $d['is_active']?'<span class="badge badge-green">Yes</span>':'<span class="badge badge-red">No</span>' ?></td>
          <td>
            <a href="?edit=<?= $d['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
              <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $d['id'] ?>">
              <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
