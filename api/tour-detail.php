<?php
// ================================================================
//  api/tour-detail.php – AJAX endpoint for tour detail modal
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<p>Tour not found.</p>'; exit; }

$pdo = getPDO();
$ts  = $pdo->prepare("SELECT * FROM tours WHERE id = ? AND is_active = 1");
$ts->execute([$id]);
$tour = $ts->fetch();
if (!$tour) { echo '<p>Tour not found.</p>'; exit; }

$hls = $pdo->prepare("SELECT highlight FROM tour_highlights WHERE tour_id = ? ORDER BY sort_order");
$hls->execute([$id]);
$highlights = array_column($hls->fetchAll(), 'highlight');

$its = $pdo->prepare("SELECT day_num, title, description FROM tour_itinerary WHERE tour_id = ? ORDER BY day_num");
$its->execute([$id]);
$itinerary = $its->fetchAll();

$inc = $pdo->prepare("SELECT item, type FROM tour_includes WHERE tour_id = ?");
$inc->execute([$id]);
$all      = $inc->fetchAll();
$includes = array_column(array_filter($all, fn($r) => $r['type'] === 'include'), 'item');
$excludes = array_column(array_filter($all, fn($r) => $r['type'] === 'exclude'), 'item');
?>
<div class="detail-top">
  <img class="detail-image" src="<?= e('/' . ltrim((string)($tour['image'] ?: 'images/image1.jpg'), '/')) ?>" alt="<?= e($tour['name']) ?>">
  <div>
    <h3><?= e($tour['name']) ?></h3>
    <div class="detail-meta">
      <span><i class="fas fa-clock"></i> <?= (int)$tour['duration_days'] ?> Days</span>
      <span class="tour-detail-price"><i class="fas fa-tag"></i> <?= formatPrice((float)$tour['price'], 'UGX') ?> per person</span>
      <span><i class="fas fa-users"></i> Max <?= (int)$tour['max_group'] ?></span>
      <span class="tour-badge" style="position:static;background:var(--savanna-gold);color:white;"><?= e(ucfirst($tour['category'])) ?></span>
    </div>
    <p><?= e($tour['short_desc']) ?></p>
    <?php if ($tour['full_desc']): ?>
    <p style="margin-top:.5rem;line-height:1.7;color:#5f564d;"><?= nl2br(e($tour['full_desc'])) ?></p>
    <?php endif; ?>
  </div>
</div>

<?php if ($highlights): ?>
<h4><i class="fas fa-star" style="color:var(--savanna-gold)"></i> Highlights</h4>
<ul><?php foreach ($highlights as $h): ?><li><?= e($h) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if ($itinerary): ?>
<h4><i class="fas fa-map-marked-alt" style="color:var(--acacia-green)"></i> Itinerary</h4>
<?php foreach ($itinerary as $day): ?>
<p><strong>Day <?= (int)$day['day_num'] ?>: <?= e($day['title']) ?></strong><br>
<?= e($day['description']) ?></p>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($includes || $excludes): ?>
<div class="detail-grid">
  <?php if ($includes): ?>
  <div class="detail-panel">
    <h4 style="color:#27ae60"><i class="fas fa-check-circle"></i> Included</h4>
    <ul><?php foreach ($includes as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>
  <?php if ($excludes): ?>
  <div class="detail-panel">
    <h4 style="color:#c0392b"><i class="fas fa-times-circle"></i> Excluded</h4>
    <ul><?php foreach ($excludes as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tour['accommodation']): ?>
<p style="margin-top:1rem;"><i class="fas fa-bed"></i> <strong>Accommodation:</strong> <?= e($tour['accommodation']) ?></p>
<?php endif; ?>

<div class="detail-actions">
  <button class="btn-primary" onclick="closeModal('detailModal');openBooking(<?= $tour['id'] ?>,'<?= addslashes(e($tour['name'])) ?>','<?= e(formatPrice((float)$tour['price'],'UGX')) ?>')">
    Book This Safari <i class="fas fa-arrow-right"></i>
  </button>
</div>
