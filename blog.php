<?php
// ================================================================
//  blog.php – Blog listing page (Dynamic)
// ================================================================
require_once __DIR__ . '/includes/functions.php';

$pdo     = getPDO();
$perPage = 9;
$page    = max(1, (int)($_GET['page'] ?? 1));
$cat     = sanitizeInput($_GET['cat'] ?? '');

$where  = "WHERE is_published = 1";
$params = [];
if ($cat) {
    $where  .= " AND category = ?";
    $params[] = $cat;
}

$totalCount = $pdo->prepare("SELECT COUNT(*) FROM blog_posts {$where}");
$totalCount->execute($params);
$total = (int)$totalCount->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$stmt = $pdo->prepare(
    "SELECT id, slug, title, excerpt, image, author, category, published_at
     FROM blog_posts {$where} ORDER BY published_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT DISTINCT category FROM blog_posts WHERE is_published=1 AND category IS NOT NULL AND category != '' ORDER BY category"
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Safari Blog | ' . setting('site_name');
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="blog-page-hero">
  <div class="container blog-hero-copy">
    <p class="blog-kicker"><i class="fas fa-feather-alt"></i> Legacy Journal</p>
    <h1>Stories from the Bush</h1>
    <p>Travel diaries, wildlife moments, destination tips, and behind-the-scenes stories from East Africa.</p>
    <div class="blog-hero-stats">
      <div class="blog-stat">
        <strong><?= $total ?></strong>
        <span>Published stories</span>
      </div>
      <div class="blog-stat">
        <strong><?= count($categories) ?></strong>
        <span>Story categories</span>
      </div>
      <div class="blog-stat">
        <strong>Weekly</strong>
        <span>Fresh safari insights</span>
      </div>
    </div>
  </div>
</section>

<section class="container section-padding">
  <!-- Category filters -->
  <?php if ($categories): ?>
  <div class="blog-toolbar">
    <h2>Browse by category</h2>
    <div class="blog-categories">
    <a href="/blog" class="blog-chip <?= !$cat ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $c): ?>
    <a href="/blog?cat=<?= urlencode($c) ?>" class="blog-chip <?= $cat === $c ? 'active' : '' ?>">
      <?= e(ucfirst($c)) ?>
    </a>
    <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="blog-list">
    <?php if ($posts): ?>
      <?php foreach ($posts as $post): ?>
      <article class="blog-post">
        <a class="blog-post-media" href="/blog-post.php?slug=<?= e($post['slug']) ?>">
          <img src="<?= e('/' . ltrim($post['image'] ?: 'images/image1.jpg', '/')) ?>" alt="<?= e($post['title']) ?>">
        </a>
        <div class="blog-post-content">
          <div class="blog-post-top">
            <?php if ($post['category']): ?>
            <span class="blog-pill"><?= e(ucfirst($post['category'])) ?></span>
            <?php endif; ?>
            <span class="blog-date"><i class="fas fa-calendar"></i> <?= $post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : 'Unscheduled' ?></span>
          </div>
          <h3><a href="/blog-post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
          <p class="blog-author"><i class="fas fa-user"></i> <?= e((string)($post['author'] ?? 'Legacy Safaris')) ?></p>
          <p class="blog-excerpt"><?= e(mb_substr(trim((string)($post['excerpt'] ?? strip_tags((string)($post['title'] ?? '')))), 0, 170)) ?>...</p>
          <a href="/blog-post.php?slug=<?= e($post['slug']) ?>" class="read-more">Read Story <span aria-hidden="true">&rarr;</span></a>
        </div>
      </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="blog-empty">No blog posts yet. Check back soon!</p>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pg['total_pages'] > 1): ?>
  <div class="blog-pagination">
    <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
    <a href="?page=<?= $i ?><?= $cat ? '&cat='.urlencode($cat) : '' ?>"
       class="blog-page-dot <?= $i === $page ? 'active' : '' ?>">
      <?= $i ?>
    </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
