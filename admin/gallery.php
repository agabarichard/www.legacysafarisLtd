<?php
// ================================================================
//  admin/gallery.php – Manage Gallery Images
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo = getPDO();

// Upload images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $category  = sanitizeInput($_POST['category'] ?? 'general');
        $alt       = sanitizeInput($_POST['alt_text']  ?? '');
        $uploaded  = 0;

        if (!empty($_FILES['images']['name'][0])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                $_FILES_single = [
                    'name'     => $_FILES['images']['name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                    'size'     => $_FILES['images']['size'][$i],
                ];
                // Temporarily override for handleImageUpload
                $_FILES['_gallery_upload'] = $_FILES_single;
                $path = handleImageUpload('_gallery_upload', 'gallery');
                if ($path) {
                    $pdo->prepare(
                        "INSERT INTO gallery_images (filename, alt_text, category) VALUES (?,?,?)"
                    )->execute([ltrim($path, '/'), $alt, $category]);
                    $uploaded++;
                }
            }
        }
        setFlash('success', "{$uploaded} image(s) uploaded.");
    }
    header('Location: /admin/gallery.php'); exit;
}

// Update caption / alt / category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_img'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $pdo->prepare("UPDATE gallery_images SET alt_text=?,caption=?,category=?,is_active=? WHERE id=?")
            ->execute([
                sanitizeInput($_POST['alt_text']  ?? ''),
                sanitizeInput($_POST['caption']   ?? ''),
                sanitizeInput($_POST['category']  ?? 'general'),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$_POST['img_id'],
            ]);
        setFlash('success','Image updated.');
    }
    header('Location: /admin/gallery.php'); exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $row = $pdo->prepare("SELECT filename FROM gallery_images WHERE id=?"); $row->execute([(int)$_POST['delete_id']]); $row = $row->fetch();
        if ($row && file_exists(ROOT_PATH . '/' . $row['filename'])) {
            @unlink(ROOT_PATH . '/' . $row['filename']);
        }
        $pdo->prepare("DELETE FROM gallery_images WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success','Image deleted.');
    }
    header('Location: /admin/gallery.php'); exit;
}

$images     = $pdo->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, id DESC")->fetchAll();
$categories = ['general','wildlife','landscape','camps','people','birds'];

$pageTitle = 'Gallery Manager';
include __DIR__ . '/includes/header.php';
?>

<!-- Upload Form -->
<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-header"><h3><i class="fas fa-upload" style="color:var(--primary)"></i> Upload Images</h3></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="upload_images" value="1">
      <div class="form-row">
        <div class="form-group">
          <label>Images (select multiple)</label>
          <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category" class="form-control">
            <?php foreach ($categories as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Default Alt Text</label>
          <input type="text" name="alt_text" class="form-control" placeholder="e.g. Elephant at sunset">
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
    </form>
  </div>
</div>

<!-- Image grid -->
<div class="card">
  <div class="card-header"><h3><i class="fas fa-images" style="color:var(--gold)"></i> Gallery (<?= count($images) ?> images)</h3></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
      <?php foreach ($images as $img): ?>
      <?php
        $imgFile = '/' . ltrim((string)($img['filename'] ?? ''), '/');
        $imgAlt = (string)($img['alt_text'] ?? '');
        $imgCaption = (string)($img['caption'] ?? '');
      ?>
      <div style="background:#f8f9fa;border-radius:12px;overflow:hidden;border:1px solid #eee;">
        <img src="<?= e($imgFile) ?>" alt="<?= e($imgAlt) ?>"
             style="width:100%;height:140px;object-fit:cover;">
        <div style="padding:.7rem;">
          <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="update_img" value="1">
            <input type="hidden" name="img_id"     value="<?= $img['id'] ?>">
            <input type="text" name="alt_text"  class="form-control" placeholder="Alt text"
                   value="<?= e($imgAlt) ?>" style="margin-bottom:.4rem;">
            <input type="text" name="caption"   class="form-control" placeholder="Caption"
                   value="<?= e($imgCaption) ?>" style="margin-bottom:.4rem;">
            <select name="category" class="form-control" style="margin-bottom:.4rem;">
              <?php foreach ($categories as $c): ?><option value="<?= $c ?>" <?= $img['category']===$c?'selected':'' ?>><?= ucfirst($c) ?></option><?php endforeach; ?>
            </select>
            <label style="display:flex;align-items:center;gap:.3rem;font-size:.82rem;margin-bottom:.4rem;cursor:pointer;">
              <input type="checkbox" name="is_active" value="1" <?= $img['is_active']?'checked':'' ?>> Visible
            </label>
            <div style="display:flex;gap:.4rem;">
              <button type="submit" class="btn btn-primary btn-sm" style="flex:1"><i class="fas fa-save"></i></button>
            </div>
          </form>
          <form method="POST" style="margin-top:.4rem;" onsubmit="return confirm('Delete image?')">
            <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $img['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm" style="width:100%"><i class="fas fa-trash"></i> Delete</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$images): ?><p style="color:#888;">No images uploaded yet.</p><?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
