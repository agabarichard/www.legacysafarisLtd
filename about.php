<?php
// ================================================================
//  about.php – About Page (Dynamic team + stats from DB)
// ================================================================
require_once __DIR__ . '/includes/functions.php';

$pdo  = getPDO();
$team = $pdo->query("SELECT * FROM team_members WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'About Us | ' . setting('site_name');
$extraHead = '';
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="about-page-hero">
  <div class="container">
    <div class="about-hero-copy">
      <div class="about-badge">Who we are</div>
      <h1>Our Story</h1>
      <p>Born from a love of Africa &mdash; built on trust, experience, and purpose</p>
    </div>
  </div>
</section>

<section class="container section-padding">
  <p class="about-intro">
    <?= nl2br(e(setting('about_short', 'Legacy Safaris Ltd is a premier East African safari operator with over 15 years of experience crafting unforgettable journeys into the wild.'))) ?>
  </p>

  <div class="mission-vision">
    <div class="mission-card">
      <i class="fas fa-crosshairs"></i>
      <h3>Our Mission</h3>
      <p>To provide authentic, ethical, and life-changing safari experiences that connect travellers to the raw beauty of Africa while supporting conservation and local communities.</p>
    </div>
    <div class="vision-card">
      <i class="fas fa-eye"></i>
      <h3>Our Vision</h3>
      <p>A world where responsible tourism safeguards Africa's wildlife and wilderness for generations to come.</p>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-number">15+</div><p>Years of Experience</p></div>
    <div class="stat-card"><div class="stat-number">5,000+</div><p>Happy Travellers</p></div>
    <div class="stat-card"><div class="stat-number">12</div><p>Countries Covered</p></div>
    <div class="stat-card"><div class="stat-number">98%</div><p>5-Star Reviews</p></div>
  </div>
</section>

<!-- Team -->
<?php if ($team): ?>
<section class="container section-padding" style="padding-top:0;">
  <h2 class="section-title">Meet the Team</h2>
  <p class="about-section-subtitle">Passionate experts who live &amp; breathe Africa</p>
  <div class="team-grid">
    <?php foreach ($team as $member): ?>
    <div class="team-card">
      <img src="/<?= e($member['image'] ?: 'images/image1.jpg') ?>" alt="<?= e($member['name']) ?>">
      <div class="team-info">
        <h3><?= e($member['name']) ?></h3>
        <p class="team-role"><?= e($member['role']) ?></p>
        <?php if ($member['bio']): ?><p class="team-bio"><?= e(mb_substr($member['bio'],0,120)) ?>...</p><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Conservation -->
<div class="container" style="margin-bottom:3rem;">
  <div class="conservation-banner">
    <i class="fas fa-leaf"></i>
    <h2>Our Conservation Commitment</h2>
    <p>5% of every safari booking goes directly to anti-poaching, reforestation, and community education programs across East Africa.</p>
    <a href="/contact.php" class="btn-primary">Partner With Us</a>
  </div>
</div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
