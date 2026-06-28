<?php
// ================================================================
//  index.php – Home Page (Dynamic)
// ================================================================
require_once __DIR__ . '/includes/functions.php';

// If the server routes all requests to index.php, resolve known clean URLs here.
$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$normalizedPath = strtolower(trim($requestPath, '/'));

$routeMap = [
  'about'        => 'about.php',
  'blog'         => 'blog.php',
  'blog-post'    => 'blog-post.php',
  'contact'      => 'contact.php',
  'destinations' => 'destinations.php',
  'gallery'      => 'gallery.php',
  'tours'        => 'tours.php',
  'unsubscribe'  => 'unsubscribe.php',
];

if ($normalizedPath !== '' && !in_array($normalizedPath, ['index', 'index.php'], true)) {
  if (isset($routeMap[$normalizedPath])) {
    require __DIR__ . '/' . $routeMap[$normalizedPath];
    exit;
  }

  http_response_code(404);
  include __DIR__ . '/404.php';
  exit;
}

$pdo = getPDO();

// Featured tours
$featuredTours = $pdo->query(
    "SELECT * FROM tours WHERE is_featured = 1 AND is_active = 1 ORDER BY sort_order ASC LIMIT 3"
)->fetchAll();

// Hero slider images (settings + featured tour images)
$heroSlidesRaw = [];
$heroSetting = trim((string) setting('hero_image'));
if ($heroSetting !== '') {
  $heroSlidesRaw[] = $heroSetting;
}
foreach ($featuredTours as $tourImg) {
  if (!empty($tourImg['image'])) {
    $heroSlidesRaw[] = (string) $tourImg['image'];
  }
}
if (!$heroSlidesRaw) {
  $heroSlidesRaw = ['images/image1.jpg', 'images/image2.jpg', 'images/image3.jpg'];
}
$heroSlides = array_values(array_unique(array_filter(array_map(static function ($img) {
  $img = trim((string) $img);
  if ($img === '') return '';
  if (preg_match('#^https?://#i', $img)) return $img;
  return '/' . ltrim($img, '/');
}, $heroSlidesRaw))));

// Testimonials
$testimonials = $pdo->query(
    "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3"
)->fetchAll();

// Recent blog posts
$recentPosts = $pdo->query(
    "SELECT id, slug, title, excerpt, image, published_at FROM blog_posts
      WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3"
)->fetchAll();

// Newsletter subscription handler
$newsletterMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $newsletterMsg = ['type' => 'error', 'text' => 'Security error. Please try again.'];
    } else {
        $email = filter_var(sanitizeInput($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $newsletterMsg = ['type' => 'error', 'text' => 'Please enter a valid email address.'];
        } else {
            $exists = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $exists->execute([$email]);
            if ($exists->fetch()) {
                $newsletterMsg = ['type' => 'info', 'text' => 'You are already subscribed!'];
            } else {
                $token = generateToken();
                $stmt  = $pdo->prepare(
                    "INSERT INTO newsletter_subscribers (email, token) VALUES (?, ?)"
                );
                $stmt->execute([$email, $token]);
                // Send welcome email
                sendMail($email, '', 'Welcome to the Legacy Safari Circle!',
                    '<h2>Thank you for subscribing!</h2><p>You\'ll now receive exclusive safari offers and conservation news from Legacy Safaris Ltd.</p>
                     <p><a href="' . SITE_URL . '/unsubscribe.php?token=' . $token . '">Unsubscribe</a></p>');
                $newsletterMsg = ['type' => 'success', 'text' => 'Thank you for subscribing! Check your email.'];
            }
        }
    }
}

$pageTitle = setting('site_name') . ' | Authentic African Safari Adventures';
$metaDesc  = setting('meta_description');
$extraHead = '<style>
.hero{position:relative;min-height:85vh;display:flex;align-items:center;overflow:hidden}
.hero-slider{position:absolute;inset:0;z-index:0}
.hero-slide{position:absolute;inset:0;opacity:0;transition:opacity .9s ease}
.hero-slide.active{opacity:1}
.hero-slide img{width:100%;height:100%;object-fit:cover;display:block}
.hero::before{content:"";position:absolute;inset:0;background:linear-gradient(105deg,rgba(0,0,0,.62),rgba(0,0,0,.34));z-index:1}
.hero .container,.hero-content{position:relative;z-index:2}
.hero-dots{position:absolute;left:50%;bottom:24px;transform:translateX(-50%);z-index:2;display:flex;gap:10px}
.hero-dot{width:12px;height:12px;border-radius:50%;border:1px solid rgba(255,255,255,.85);background:rgba(255,255,255,.35);cursor:pointer;transition:.2s transform,.2s background}
.hero-dot.active,.hero-dot:hover{background:#fff;transform:scale(1.08)}
.featured-tours{background:#FFF9F2;border-radius:48px;padding:2rem 0}
.tour-card-featured{background:white;border-radius:28px;overflow:hidden;transition:all .3s;box-shadow:0 12px 20px -12px rgba(0,0,0,.08)}
.tour-card-featured:hover{transform:translateY(-8px);box-shadow:0 20px 30px -12px rgba(0,0,0,.15)}
.tour-card-featured .tour-img img{width:100%;height:220px;object-fit:cover;display:block}
.home-tour-meta{display:flex;flex-wrap:wrap;gap:.55rem;margin:.8rem 0 .65rem}
.home-tour-meta span{display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .75rem;border-radius:999px;background:rgba(47,107,62,.08);color:#4a5a43;font-weight:700;font-size:.86rem}
.home-tour-category{display:inline-flex;align-items:center;gap:.35rem;padding:.34rem .75rem;border-radius:999px;background:rgba(212,163,115,.18);color:var(--baobab-brown);font-size:.78rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
.testimonial-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2rem;margin:2rem 0}
.testimonial-card{background:white;border-radius:28px;padding:1.8rem;text-align:center;box-shadow:0 12px 20px -10px rgba(0,0,0,.05)}
.testimonial-card i{font-size:2rem;color:var(--savanna-gold);margin-bottom:1rem}
.testimonial-card h4{margin-top:1rem;color:var(--acacia-green)}
.cta-banner{background:linear-gradient(135deg,var(--acacia-green),#1E4A2E);border-radius:48px;padding:3rem 2rem;text-align:center;color:white;margin:2rem 0}
.blog-preview{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2rem}
.blog-card{background:white;border-radius:28px;overflow:hidden;box-shadow:0 8px 16px -8px rgba(0,0,0,.05)}
.blog-card img{width:100%;height:180px;object-fit:cover}
.blog-card-content{padding:1.2rem}
.newsletter{background:var(--dusty-cream);border-radius:48px;padding:2.5rem;text-align:center}
.newsletter-form{display:flex;flex-wrap:wrap;justify-content:center;gap:1rem;margin-top:1.5rem}
.newsletter-form input{padding:12px 20px;border-radius:40px;border:none;width:280px;max-width:100%;font-family:"Inter",sans-serif}
.partners-logos{display:flex;flex-wrap:wrap;justify-content:center;gap:2rem;align-items:center;margin:2rem 0}
@media (max-width: 768px){
  .hero{min-height:74vh}
  .hero-dots{bottom:16px}
}
</style>';

include __DIR__ . '/includes/header.php';
?>
<main>

<!-- Hero Section -->
<section class="hero">
  <div class="hero-slider" id="heroSlider">
    <?php foreach ($heroSlides as $i => $slide): ?>
    <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>">
      <img src="<?= e($slide) ?>" alt="Safari slide <?= $i + 1 ?>">
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (count($heroSlides) > 1): ?>
  <div class="hero-dots" id="heroDots">
    <?php foreach ($heroSlides as $i => $slide): ?>
    <button type="button" class="hero-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Go to slide <?= $i + 1 ?>"></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="container">
    <div class="hero-content">
      <h1><?= e(setting('hero_title', 'Unleash the untamed beauty of Africa')) ?></h1>
      <p><?= e(setting('hero_subtitle', 'Experience legendary wildlife, raw landscapes, and authentic safari adventures.')) ?></p>
      <a href="/tours.php" class="btn-primary">Explore Our Tours <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- Why Legacy Safaris -->
<section class="container section-padding">
  <h2 class="section-title">Why Legacy Safaris</h2>
  <div class="features-grid">
    <div class="feature-card">
      <i class="fas fa-tree feature-icon"></i>
      <h3>Eco&#8209;Conscious</h3>
      <p>We support conservation and local communities in every safari.</p>
    </div>
    <div class="feature-card">
      <i class="fas fa-user-tie feature-icon"></i>
      <h3>Expert Guides</h3>
      <p>Certified naturalists with decades of bush experience.</p>
    </div>
    <div class="feature-card">
      <i class="fas fa-camera-retro feature-icon"></i>
      <h3>Photographic Focus</h3>
      <p>Vehicles designed for prime shooting angles.</p>
    </div>
  </div>
</section>

<!-- Featured Tours (Dynamic from DB) -->
<section class="featured-tours section-padding">
  <div class="container">
    <h2 class="section-title">Signature Safaris</h2>
    <p class="section-sub">Handpicked journeys that immerse you in wild majesty</p>
    <div class="tours-grid">
      <?php if ($featuredTours): ?>
        <?php foreach ($featuredTours as $tour): ?>
        <div class="tour-card-featured">
          <div class="tour-img">
            <img src="<?= e('/' . ltrim((string)($tour['image'] ?: 'images/image1.jpg'), '/')) ?>" alt="<?= e($tour['name']) ?>">
          </div>
          <div class="tour-info">
            <span class="home-tour-category"><i class="fas fa-compass"></i> <?= e(ucfirst((string)($tour['category'] ?? 'Safari'))) ?></span>
            <h3><?= e($tour['name']) ?></h3>
            <div class="home-tour-meta">
              <span><i class="fas fa-clock"></i> <?= (int)($tour['duration_days'] ?? 0) ?> Days</span>
              <span><i class="fas fa-users"></i> Max <?= (int)($tour['max_group'] ?? 0) ?></span>
            </div>
            <div class="tour-price">from <?= formatPrice((float)$tour['price'], 'UGX') ?></div>
            <p><?= e($tour['short_desc']) ?></p>
            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
              <a href="/tours.php?id=<?= $tour['id'] ?>" class="btn-outline">Discover &rarr;</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center;color:#888;">Tours coming soon.</p>
      <?php endif; ?>
    </div>
    <div style="text-align:center;margin-top:2rem;">
      <a href="/tours.php" class="btn-primary">View All Packages <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- Testimonials (Dynamic) -->
<section class="container section-padding">
  <h2 class="section-title">Wilderness Stories</h2>
  <p class="section-sub">What our guests say about their Legacy Safari</p>
  <div class="testimonial-grid">
    <?php foreach ($testimonials as $t): ?>
    <div class="testimonial-card">
      <i class="fas fa-quote-left"></i>
      <p>"<?= e($t['quote']) ?>"</p>
      <h4>&mdash; <?= e($t['name']) ?><?= $t['location'] ? ', ' . e($t['location']) : '' ?></h4>
      <div><?= str_repeat('<i class="fas fa-star" style="color:var(--savanna-gold)"></i>', (int)$t['rating']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Conservation Banner -->
<div class="container">
  <div class="cta-banner">
    <i class="fas fa-leaf" style="font-size:3rem;margin-bottom:1rem;display:inline-block;"></i>
    <h2>We give back 5% of every safari to anti&#8209;poaching &amp; reforestation</h2>
    <p>Travel with purpose &mdash; every booking plants 10 trees and supports local rangers.</p>
    <a href="/about.php" class="btn-primary" style="background:white;color:var(--acacia-green);margin-top:1rem;">Learn About Our Impact</a>
  </div>
</div>

<!-- Latest Blog Posts (Dynamic) -->
<section class="container section-padding" style="padding-top:0;">
  <h2 class="section-title">Safari Journal</h2>
  <p class="section-sub">Insights, tips, and stories from the bush</p>
  <div class="blog-preview">
    <?php if ($recentPosts): ?>
      <?php foreach ($recentPosts as $post): ?>
      <div class="blog-card">
        <img src="<?= e('/' . ltrim((string)($post['image'] ?: 'images/image1.jpg'), '/')) ?>" alt="<?= e($post['title']) ?>">
        <div class="blog-card-content">
          <h3><?= e($post['title']) ?></h3>
          <p><?= e(mb_substr($post['excerpt'], 0, 100)) ?>...</p>
          <a href="/blog-post.php?slug=<?= e($post['slug']) ?>" class="read-more">Read more &rarr;</a>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:#888;">Blog posts coming soon.</p>
    <?php endif; ?>
  </div>
</section>

<!-- Newsletter Signup -->
<div class="container section-padding">
  <div class="newsletter">
    <i class="fas fa-envelope-open-text" style="font-size:2.5rem;color:var(--sunset-orange);"></i>
    <h3>Join the Legacy Circle</h3>
    <p>Get exclusive safari offers, conservation news, and travel inspiration.</p>
    <?php if ($newsletterMsg): ?>
    <p style="color:<?= $newsletterMsg['type'] === 'success' ? '#27ae60' : ($newsletterMsg['type'] === 'error' ? '#c0392b' : '#555') ?>; font-weight:600; margin-top:1rem;">
      <?= e($newsletterMsg['text']) ?>
    </p>
    <?php endif; ?>
    <form class="newsletter-form" method="POST">
      <?= csrfField() ?>
      <input type="email" name="newsletter_email" placeholder="Your email address" required>
      <button type="submit" class="btn-primary">Subscribe</button>
    </form>
    <p style="font-size:.8rem;margin-top:1rem;">No spam, unsubscribe anytime.</p>
  </div>
</div>

<script>
(function () {
  const slider = document.getElementById('heroSlider');
  if (!slider) return;

  const slides = Array.from(slider.querySelectorAll('.hero-slide'));
  if (slides.length <= 1) return;

  const dotsWrap = document.getElementById('heroDots');
  const dots = dotsWrap ? Array.from(dotsWrap.querySelectorAll('.hero-dot')) : [];
  let index = 0;

  function render(nextIndex) {
    index = (nextIndex + slides.length) % slides.length;
    slides.forEach((s, i) => s.classList.toggle('active', i === index));
    dots.forEach((d, i) => d.classList.toggle('active', i === index));
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => render(i));
  });

  setInterval(() => render(index + 1), 5000);
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
