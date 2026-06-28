<?php
// ================================================================
//  admin/blog.php – Manage Blog Posts (Full CRUD)
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo    = getPDO();
$action = sanitizeInput($_GET['action'] ?? 'list');
$editId = (int)($_GET['edit'] ?? 0);
$post   = null;

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("DELETE FROM blog_posts WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success', 'Post deleted.');
    }
    header('Location: /admin/blog.php'); exit;
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); header('Location:/admin/blog.php');exit; }

    $pid       = (int)($_POST['post_id'] ?? 0);
    $title     = sanitizeInput($_POST['title']   ?? '');
    $excerpt   = sanitizeInput($_POST['excerpt'] ?? '');
    $body      = $_POST['body'] ?? '';
    $author    = sanitizeInput($_POST['author']   ?? 'Legacy Safaris');
    $category  = sanitizeInput($_POST['category'] ?? '');
    $tags      = sanitizeInput($_POST['tags']     ?? '');
    $published = isset($_POST['is_published']) ? 1 : 0;
    $slug      = uniqueSlug($pdo, 'blog_posts', slugify($title), $pid ?: null);
    $pubDate   = $published ? ($_POST['published_at'] ?: date('Y-m-d H:i:s')) : null;

    $imgPath = handleImageUpload('image', 'blog');

    if ($pid) {
        $sql = "UPDATE blog_posts SET title=?,slug=?,excerpt=?,body=?,author=?,category=?,tags=?,is_published=?,published_at=?";
        $params = [$title,$slug,$excerpt,$body,$author,$category,$tags,$published,$pubDate];
        if ($imgPath) { $sql .= ",image=?"; $params[] = $imgPath; }
        $sql .= " WHERE id=?"; $params[] = $pid;
        $pdo->prepare($sql)->execute($params);
    } else {
        $pdo->prepare(
            "INSERT INTO blog_posts (title,slug,excerpt,body,author,category,tags,is_published,published_at,image)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        )->execute([$title,$slug,$excerpt,$body,$author,$category,$tags,$published,$pubDate,$imgPath]);
    }

    setFlash('success','Post saved!');
    header('Location: /admin/blog.php'); exit;
}

// LOAD for edit
if ($editId) {
    $action = 'edit';
    $s = $pdo->prepare("SELECT * FROM blog_posts WHERE id=?"); $s->execute([$editId]); $post = $s->fetch();
}

$posts = $pdo->query("SELECT id,title,category,author,is_published,published_at,views FROM blog_posts ORDER BY id DESC")->fetchAll();

$pageTitle = 'Manage Blog';
include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'list'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-blog" style="color:var(--gold)"></i> Blog Posts (<?= count($posts) ?>)</h3>
    <a href="?action=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Post</a>
  </div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Published</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($posts as $p): ?>
        <tr>
          <td><strong><?= e($p['title']) ?></strong></td>
          <td><?= e($p['category']) ?></td>
          <td><?= e($p['author']) ?></td>
          <td><?= $p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : '—' ?></td>
          <td><?= (int)$p['views'] ?></td>
          <td><?= $p['is_published'] ? '<span class="badge badge-green">Published</span>' : '<span class="badge badge-gray">Draft</span>' ?></td>
          <td>
            <a href="?edit=<?= $p['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
            <a href="/blog-post.php?slug=<?= e(slugify($p['title'])) ?>" target="_blank" class="btn btn-light btn-sm"><i class="fas fa-eye"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this post?')">
              <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<div class="card">
  <div class="card-header">
    <h3><?= $post ? 'Edit Post' : 'New Blog Post' ?></h3>
    <a href="/admin/blog.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="save_post" value="1">
      <input type="hidden" name="post_id"   value="<?= (int)($post['id'] ?? 0) ?>">

      <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" class="form-control" value="<?= e($post['title'] ?? '') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Author</label>
          <input type="text" name="author" class="form-control" value="<?= e($post['author'] ?? 'Legacy Safaris') ?>">
        </div>
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" class="form-control" placeholder="wildlife, travel tips, conservation…" value="<?= e($post['category'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Excerpt (short preview)</label>
        <textarea name="excerpt" class="form-control" rows="3"><?= e($post['excerpt'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Body (HTML / full article)</label>
        <textarea name="body" class="form-control" rows="16" id="bodyEditor"><?= e($post['body'] ?? '') ?></textarea>
        <small style="color:#888;">You can paste plain text or HTML. For rich editing, install a WYSIWYG library.</small>
      </div>

      <div class="form-group">
        <label>Tags (comma-separated)</label>
        <input type="text" name="tags" class="form-control" placeholder="safari, kenya, wildlife" value="<?= e($post['tags'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Featured Image</label>
        <?php if (!empty($post['image'])): ?>
        <div style="margin-bottom:.5rem;"><img src="<?= e('/' . ltrim($post['image'], '/')) ?>" style="height:80px;border-radius:8px;object-fit:cover;"></div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>

      <div class="form-row" style="align-items:center;">
        <div class="form-group">
          <label>Publish Date</label>
          <input type="datetime-local" name="published_at" class="form-control"
                 value="<?= $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : date('Y-m-d\TH:i') ?>">
        </div>
        <div class="form-group" style="padding-top:1.8rem;">
          <label style="display:flex;gap:.5rem;align-items:center;cursor:pointer;">
            <input type="checkbox" name="is_published" value="1" <?= ($post['is_published'] ?? 0) ? 'checked' : '' ?>>
            Publish immediately
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Post</button>
      <a href="/admin/blog.php" class="btn btn-light" style="margin-left:.5rem;">Cancel</a>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
