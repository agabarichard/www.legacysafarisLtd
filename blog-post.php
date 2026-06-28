<?php
// ================================================================
//  blog-post.php – Single Blog Post Page
// ================================================================
require_once __DIR__ . '/includes/functions.php';

$pdo  = getPDO();
$slug = sanitizeInput($_GET['slug'] ?? '');
if (!$slug) { header('Location: /blog'); exit; }

$stmt = $pdo->prepare(
    "SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1 LIMIT 1"
);
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) { header('HTTP/1.0 404 Not Found'); include __DIR__ . '/404.php'; exit; }

// Increment view count
$pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);

// Related posts
$related = $pdo->prepare(
    "SELECT id, slug, title, image, published_at FROM blog_posts
     WHERE is_published=1 AND id != ? AND category = ? LIMIT 3"
);
$related->execute([$post['id'], $post['category']]);
$relatedPosts = $related->fetchAll();

$pageTitle = e($post['title']) . ' | ' . setting('site_name');
$metaDesc  = e(mb_substr(strip_tags($post['excerpt']), 0, 160));
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="page-header">
  <div class="container">
    <p style="margin-bottom:.5rem;"><a href="/blog" style="color:white;">&larr; Back to Blog</a></p>
    <h1><?= e($post['title']) ?></h1>
    <p>
      <i class="fas fa-user"></i> <?= e($post['author']) ?> &nbsp;|&nbsp;
      <i class="fas fa-calendar"></i> <?= $post['published_at'] ? date('F j, Y', strtotime($post['published_at'])) : '' ?>
      <?php if ($post['category']): ?>&nbsp;|&nbsp; <span style="background:var(--savanna-gold);padding:2px 10px;border-radius:20px;"><?= e(ucfirst($post['category'])) ?></span><?php endif; ?>
    </p>
  </div>
</section>

<section class="container section-padding">
  <div style="max-width:800px;margin:0 auto;">
    <?php if ($post['image']): ?>
        <img src="<?= e('/' . ltrim($post['image'], '/')) ?>" alt="<?= e($post['title']) ?>"
         style="width:100%;height:400px;object-fit:cover;border-radius:24px;margin-bottom:2rem;">
    <?php endif; ?>
    <div class="blog-body" style="line-height:1.9;font-size:1.05rem;">
      <?= $post['body'] /* stored as HTML from WYSIWYG editor */ ?>
    </div>
    <?php if ($post['tags']): ?>
    <div style="margin-top:2rem;">
      <?php foreach (explode(',', $post['tags']) as $tag): ?>
      <span style="background:var(--dusty-cream);padding:4px 12px;border-radius:20px;font-size:.85rem;margin-right:6px;"><?= e(trim($tag)) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Share buttons -->
    <div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;">
      <span style="font-weight:600;">Share:</span>
      <a href="https://www.facebook.com/sharer/sharer?u=<?= urlencode(SITE_URL . '/blog-post.php?slug=' . $post['slug']) ?>"
         target="_blank" style="background:#1877F2;color:white;padding:6px 16px;border-radius:20px;text-decoration:none;">
        <i class="fab fa-facebook-f"></i> Facebook
      </a>
      <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/blog-post.php?slug=' . $post['slug']) ?>&text=<?= urlencode($post['title']) ?>"
         target="_blank" style="background:#1DA1F2;color:white;padding:6px 16px;border-radius:20px;text-decoration:none;">
        <i class="fab fa-twitter"></i> Twitter
      </a>
      <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . SITE_URL . '/blog-post.php?slug=' . $post['slug']) ?>"
         target="_blank" style="background:#25D366;color:white;padding:6px 16px;border-radius:20px;text-decoration:none;">
        <i class="fab fa-whatsapp"></i> WhatsApp
      </a>
    </div>
  </div>

  <!-- Related Posts -->
  <?php if ($relatedPosts): ?>
  <div style="margin-top:3rem;">
    <h3 class="section-title">Related Posts</h3>
    <div class="blog-preview" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;">
      <?php foreach ($relatedPosts as $r): ?>
      <div class="blog-card">
        <img src="<?= e('/' . ltrim($r['image'] ?: 'images/image1.jpg', '/')) ?>" alt="<?= e($r['title']) ?>">
        <div class="blog-card-content">
          <h3><?= e($r['title']) ?></h3>
          <a href="/blog-post.php?slug=<?= e($r['slug']) ?>" class="read-more">Read more &rarr;</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
