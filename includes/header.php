<?php
// includes/header.php – Dynamic site header
require_once dirname(__DIR__) . '/includes/functions.php';
$settings    = getSettings();
$currentPage = basename($_SERVER['PHP_SELF']);
$siteName = (string) setting('site_name', 'Legacy Safaris Ltd');
$metaDescription = trim((string)($metaDesc ?? setting('meta_description', 'Authentic safari adventures across East Africa.')));
$metaKeywords = (string) setting('meta_keywords', 'safari, Uganda safari, Kenya safari, Tanzania safari, wildlife tours');
$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$canonicalUrl = rtrim(SITE_URL, '/') . $requestUri;
$shareImage = rtrim(SITE_URL, '/') . '/images/logo.jpg';

// Active nav should follow requested route, not only PHP_SELF.
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$currentRoute = strtolower(trim($requestPath, '/'));
if ($currentRoute === '' || $currentRoute === 'index' || $currentRoute === 'index.php') {
  $currentRoute = 'home';
} else {
  $currentRoute = preg_replace('/\.php$/', '', $currentRoute);
}
$activeNav = $currentRoute === 'blog-post' ? 'blog' : $currentRoute;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? $siteName) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <meta name="keywords" content="<?= e($metaKeywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">

  <!-- Favicon / App Icons -->
  <link rel="icon" type="image/jpeg" href="/images/logo.jpg">
  <link rel="shortcut icon" type="image/jpeg" href="/images/logo.jpg">
  <link rel="apple-touch-icon" href="/images/logo.jpg">

  <!-- Open Graph -->
  <meta property="og:title" content="<?= e($pageTitle ?? $siteName) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:image" content="<?= e($shareImage) ?>">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle ?? $siteName) ?>">
  <meta name="twitter:description" content="<?= e($metaDescription) ?>">
  <meta name="twitter:image" content="<?= e($shareImage) ?>">

  <link rel="stylesheet" href="/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": <?= json_encode($siteName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    "url": <?= json_encode(rtrim(SITE_URL, '/'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    "logo": <?= json_encode($shareImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  }
  </script>

  <?php if ($extraHead ?? false) echo $extraHead; ?>
  <?php if (setting('google_analytics_id')): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(setting('google_analytics_id')) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e(setting('google_analytics_id')) ?>');</script>
  <?php endif; ?>
</head>
<body>
<header>
  <div class="container">
    <div class="navbar">
      <a href="/index.php" class="logo site-brand" aria-label="Legacy Safaris Ltd home">
        <img src="/images/new%20Logo.png" alt="Legacy Safaris Ltd" class="site-logo-img">
      </a>
      <div class="hamburger" id="hamburger"><i class="fas fa-bars"></i></div>
      <ul class="nav-links" id="navLinks">
        <li><a href="/index.php"        <?= $activeNav === 'home'         ? 'class="active"' : '' ?>>Home</a></li>
        <li><a href="/about.php"        <?= $activeNav === 'about'        ? 'class="active"' : '' ?>>About</a></li>
        <li><a href="/tours.php"        <?= $activeNav === 'tours'        ? 'class="active"' : '' ?>>Tours</a></li>
        <li><a href="/gallery.php"      <?= $activeNav === 'gallery'      ? 'class="active"' : '' ?>>Gallery</a></li>
        <li><a href="/destinations.php" <?= $activeNav === 'destinations' ? 'class="active"' : '' ?>>Destinations</a></li>
        <li><a href="/blog.php"         <?= $activeNav === 'blog'         ? 'class="active"' : '' ?>>Blog</a></li>
        <li><a href="/contact.php"      <?= $activeNav === 'contact'      ? 'class="active"' : '' ?>>Contact</a></li>
      </ul>
    </div>
  </div>
</header>
<script>
  document.getElementById('hamburger')?.addEventListener('click', function(){
    document.getElementById('navLinks')?.classList.toggle('active');
  });
</script>
