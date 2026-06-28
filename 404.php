<?php
// 404.php – Not Found page
$pageTitle = 'Page Not Found | ' . setting('site_name', 'Legacy Safaris Ltd');
$metaDesc = 'The page you requested could not be found.';
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="container section-padding" style="min-height:56vh;display:flex;align-items:center;justify-content:center;">
  <div style="max-width:640px;text-align:center;background:#fff;border:1px solid rgba(139,90,43,.12);border-radius:28px;padding:2.2rem;box-shadow:0 18px 30px -20px rgba(0,0,0,.18);">
    <div style="font-size:3rem;color:var(--sunset-orange);margin-bottom:.5rem;"><i class="fas fa-map-signs"></i></div>
    <h1 style="margin-bottom:.6rem;color:var(--charcoal);">404 – Page Not Found</h1>
    <p style="color:#64594f;line-height:1.75;margin-bottom:1.2rem;">The page you are looking for does not exist or may have moved.</p>
    <a href="/index.php" class="btn-primary">Back to Home</a>
  </div>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
