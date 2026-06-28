<?php
// gallery.php – Dynamic Gallery
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$catFilter = sanitizeInput($_GET['cat'] ?? 'all');

$cats = $pdo->query(
    "SELECT DISTINCT category FROM gallery_images WHERE is_active=1 ORDER BY category"
)->fetchAll(PDO::FETCH_COLUMN);

$sql    = "SELECT * FROM gallery_images WHERE is_active = 1";
$params = [];
if ($catFilter !== 'all') {
    $sql    .= " AND category = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY sort_order ASC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$images = $stmt->fetchAll();

$pageTitle = 'Gallery | ' . setting('site_name');
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="page-header">
  <div class="container">
    <h1>Safari Gallery</h1>
    <p>Moments captured in the wild</p>
  </div>
</section>
<section class="container section-padding">
  <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;justify-content:center;">
    <a href="/gallery.php" style="padding:7px 20px;border-radius:30px;border:2px solid var(--savanna-gold);text-decoration:none;font-weight:600;<?= $catFilter==='all'?'background:var(--savanna-gold);color:white':'color:var(--baobab-brown)' ?>">All</a>
    <?php foreach ($cats as $c): ?>
    <a href="/gallery.php?cat=<?= urlencode($c) ?>" style="padding:7px 20px;border-radius:30px;border:2px solid var(--savanna-gold);text-decoration:none;font-weight:600;<?= $catFilter===$c?'background:var(--savanna-gold);color:white':'color:var(--baobab-brown)' ?>">
      <?= e(ucfirst($c)) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div id="gallery-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;">
    <?php if ($images): ?>
      <?php foreach ($images as $img): ?>
      <div style="border-radius:16px;overflow:hidden;cursor:pointer;" onclick="openLightbox('<?= e(addslashes($img['filename'])) ?>','<?= e(addslashes($img['alt_text'])) ?>')">
        <img src="/<?= e($img['filename']) ?>" alt="<?= e($img['alt_text']) ?>"
             style="width:100%;height:220px;object-fit:cover;display:block;transition:transform .3s;"
             onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
        <?php if ($img['caption']): ?>
        <p style="padding:.5rem 1rem;margin:0;font-size:.85rem;background:#fff;"><?= e($img['caption']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:#888;padding:3rem;text-align:center;grid-column:1/-1;">Gallery coming soon.</p>
    <?php endif; ?>
  </div>
</section>
</main>

<!-- Lightbox -->
<div id="lightbox" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.9);z-index:2000;justify-content:center;align-items:center;flex-direction:column;" onclick="closeLightbox()">
  <img id="lightboxImg" src="" alt="" style="max-width:90%;max-height:85vh;border-radius:12px;">
  <p id="lightboxCaption" style="color:white;margin-top:1rem;font-size:1rem;"></p>
  <button onclick="closeLightbox()" style="position:absolute;top:20px;right:24px;background:transparent;border:none;color:white;font-size:2rem;cursor:pointer;">&times;</button>
</div>
<script>
function openLightbox(src, alt) {
  document.getElementById('lightboxImg').src = '/' + src;
  document.getElementById('lightboxImg').alt = alt;
  document.getElementById('lightboxCaption').textContent = alt;
  const lb = document.getElementById('lightbox');
  lb.style.display = 'flex';
}
function closeLightbox() { document.getElementById('lightbox').style.display = 'none'; }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLightbox(); });
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
