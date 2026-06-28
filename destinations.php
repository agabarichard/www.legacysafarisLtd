<?php
// destinations.php – Destinations page
require_once __DIR__ . '/includes/functions.php';

$pdo  = getPDO();
$dests = $pdo->query("SELECT * FROM destinations WHERE is_active=1 ORDER BY sort_order ASC,id ASC")->fetchAll();

$countryNames = array_filter(array_map(static function ($d) {
    return trim((string)($d['country'] ?? ''));
}, $dests));
$countryCount = count(array_unique($countryNames));

$pageTitle = 'Destinations | ' . setting('site_name');
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="destination-page-hero">
  <div class="container destination-hero-copy">
    <p class="destination-kicker"><i class="fas fa-compass"></i> Signature Regions</p>
    <h1>African Destinations</h1>
    <p>Discover wild places that stay with you forever, from iconic savannah plains to rainforest gorilla trails.</p>
    <div class="destination-hero-stats">
      <div class="destination-stat">
        <strong><?= $dests ? count($dests) : 6 ?></strong>
        <span>Featured destinations</span>
      </div>
      <div class="destination-stat">
        <strong><?= $countryCount ?: 4 ?></strong>
        <span>Countries covered</span>
      </div>
      <div class="destination-stat">
        <strong>Tailored</strong>
        <span>Itineraries per region</span>
      </div>
    </div>
  </div>
</section>
<section class="container section-padding">
  <?php if ($dests): ?>
  <div class="destinations-grid dest-enhanced-grid">
    <?php foreach ($dests as $d): ?>
    <?php
      $imgSrc = '/' . ltrim((string)($d['image'] ?: 'images/image1.jpg'), '/');
      $name = (string)($d['name'] ?? 'Destination');
      $country = trim((string)($d['country'] ?? ''));
      $summary = trim((string)($d['short_desc'] ?? '')); 
    ?>
    <article class="dest-card dest-card-enhanced">
      <div class="dest-media-wrap">
        <img src="<?= e($imgSrc) ?>" alt="<?= e($name) ?>">
      </div>
      <div class="dest-card-body">
        <h3><?= e($name) ?></h3>
        <?php if ($country): ?><p class="dest-country"><i class="fas fa-map-marker-alt"></i> <?= e($country) ?></p><?php endif; ?>
        <p class="dest-excerpt"><?= e(mb_substr($summary ?: ($name . ' destination guide for your safari journey.'),0,130)) ?>...</p>
        <a href="/tours.php" class="btn-outline dest-cta">Explore Tours &rarr;</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <!-- Static fallback until DB populated -->
  <div class="destinations-grid dest-enhanced-grid">
    <?php
    $staticDests = [
      ['name'=>'Maasai Mara','country'=>'Kenya','img'=>'images/image1.jpg','desc'=>'Home of the Great Migration and big cats. One of the world\'s premier safari destinations.'],
      ['name'=>'Serengeti','country'=>'Tanzania','img'=>'images/image2.jpg','desc'=>'Endless plains teeming with wildlife. Iconic sunsets and dramatic predator-prey interactions.'],
      ['name'=>'Bwindi Impenetrable Forest','country'=>'Uganda','img'=>'images/image3.jpg','desc'=>'Last stronghold of mountain gorillas. A once-in-a-lifetime trekking experience.'],
      ['name'=>'Ngorongoro Crater','country'=>'Tanzania','img'=>'images/image4.jpg','desc'=>'The world\'s largest intact volcanic caldera, sheltering lions, elephants and endangered rhinos.'],
      ['name'=>'Amboseli','country'=>'Kenya','img'=>'images/image5.jpg','desc'=>'Iconic views of Kilimanjaro framing vast elephant herds across dusty plains.'],
      ['name'=>'Okavango Delta','country'=>'Botswana','img'=>'images/image6.jpg','desc'=>'The world\'s largest inland delta, a paradise of waterways, hippos and extraordinary birdlife.'],
    ];
    foreach ($staticDests as $d): ?>
    <article class="dest-card dest-card-enhanced">
      <div class="dest-media-wrap">
        <img src="<?= e('/' . ltrim((string)$d['img'], '/')) ?>" alt="<?= e($d['name']) ?>">
      </div>
      <div class="dest-card-body">
        <h3><?= e($d['name']) ?></h3>
        <p class="dest-country"><i class="fas fa-map-marker-alt"></i> <?= e($d['country']) ?></p>
        <p class="dest-excerpt"><?= e($d['desc']) ?></p>
        <a href="/tours.php" class="btn-outline dest-cta">Explore Tours &rarr;</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
