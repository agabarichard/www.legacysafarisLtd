<?php
// admin/includes/header.php – Admin panel top include
requireAdmin();
$adminUser   = currentAdmin();
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin') ?> – Legacy Safaris Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/jpeg" href="/images/logo.jpg">
  <link rel="shortcut icon" type="image/jpeg" href="/images/logo.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="/admin/assets/css/admin.css">
  <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
<div class="admin-wrapper">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <img src="/images/new%20Logo.png" alt="Legacy Safaris Ltd" class="sidebar-logo-img">
      <div>Legacy Safaris<span>Admin Panel</span></div>
    </div>
    <nav class="sidebar-nav">
      <a href="/admin/index.php" class="<?= $currentFile==='index.php'?'active':'' ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>

      <div class="sidebar-section-label">Content</div>
      <a href="/admin/tours.php" class="<?= $currentFile==='tours.php'?'active':'' ?>">
        <i class="fas fa-map-marked-alt"></i> Tours
      </a>
      <a href="/admin/destinations.php" class="<?= $currentFile==='destinations.php'?'active':'' ?>">
        <i class="fas fa-globe-africa"></i> Destinations
      </a>
      <a href="/admin/blog.php" class="<?= $currentFile==='blog.php'?'active':'' ?>">
        <i class="fas fa-blog"></i> Blog Posts
      </a>
      <a href="/admin/gallery.php" class="<?= $currentFile==='gallery.php'?'active':'' ?>">
        <i class="fas fa-images"></i> Gallery
      </a>
      <a href="/admin/team.php" class="<?= $currentFile==='team.php'?'active':'' ?>">
        <i class="fas fa-users"></i> Team
      </a>
      <a href="/admin/testimonials.php" class="<?= $currentFile==='testimonials.php'?'active':'' ?>">
        <i class="fas fa-quote-right"></i> Testimonials
      </a>

      <div class="sidebar-section-label">Inquiries</div>
      <a href="/admin/bookings.php" class="<?= $currentFile==='bookings.php'?'active':'' ?>">
        <i class="fas fa-calendar-check"></i> Bookings
      </a>
      <a href="/admin/contacts.php" class="<?= $currentFile==='contacts.php'?'active':'' ?>">
        <i class="fas fa-envelope"></i> Messages
      </a>
      <a href="/admin/newsletter.php" class="<?= $currentFile==='newsletter.php'?'active':'' ?>">
        <i class="fas fa-paper-plane"></i> Newsletter
      </a>

      <div class="sidebar-section-label">Settings</div>
      <a href="/admin/settings.php" class="<?= $currentFile==='settings.php'?'active':'' ?>">
        <i class="fas fa-cog"></i> Site Settings
      </a>
      <?php if (($adminUser['role'] ?? '') === 'superadmin'): ?>
      <a href="/admin/admins.php" class="<?= $currentFile==='admins.php'?'active':'' ?>">
        <i class="fas fa-user-shield"></i> Admin Users
      </a>
      <?php endif; ?>

      <div class="sidebar-section-label">Site</div>
      <a href="/" target="_blank"><i class="fas fa-external-link-alt"></i> View Website</a>
      <a href="/admin/logout.php" style="color:#ff7675;">
        <i class="fas fa-sign-out-alt"></i> Log Out
      </a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h2><?= e($pageTitle ?? 'Dashboard') ?></h2>
      </div>
      <div class="topbar-right">
        <div class="admin-avatar"><?= strtoupper(substr($adminUser['name'] ?? 'A', 0, 1)) ?></div>
        <span style="font-size:.9rem;color:#555;"><?= e($adminUser['name'] ?? '') ?></span>
        <a href="/admin/logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </div>
    <div class="page-content">
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
  <?= e($flash['msg']) ?>
</div>
<?php endif; ?>
