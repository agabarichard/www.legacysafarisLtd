<?php
// ================================================================
//  admin/index.php – Dashboard
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo = getPDO();

$stats = [
    'tours'        => $pdo->query("SELECT COUNT(*) FROM tours WHERE is_active=1")->fetchColumn(),
    'bookings'     => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'new_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='new'")->fetchColumn(),
    'messages'     => $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn(),
    'subscribers'  => $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active=1")->fetchColumn(),
    'blog_posts'   => $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE is_published=1")->fetchColumn(),
];

$recentBookings = $pdo->query(
    "SELECT b.*, t.name AS tour_title FROM bookings b
     LEFT JOIN tours t ON t.id = b.tour_id
     ORDER BY b.created_at DESC LIMIT 8"
)->fetchAll();

$recentMessages = $pdo->query(
    "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<section class="dashboard-hero">
  <div>
    <p class="dashboard-kicker">Operations overview</p>
    <h3>Welcome back to Legacy Safaris Admin</h3>
    <p class="dashboard-intro">Track bookings, guest messages, and publishing activity from one streamlined workspace.</p>
  </div>
  <a href="/admin/bookings.php" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Review Bookings</a>
</section>

<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-map-marked-alt"></i></div>
    <div><div class="stat-num"><?= $stats['tours'] ?></div><div class="stat-label">Active Tours</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold"><i class="fas fa-calendar-check"></i></div>
    <div><div class="stat-num"><?= $stats['bookings'] ?></div><div class="stat-label">Total Bookings</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-bell"></i></div>
    <div><div class="stat-num"><?= $stats['new_bookings'] ?></div><div class="stat-label">New Bookings</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-envelope"></i></div>
    <div><div class="stat-num"><?= $stats['messages'] ?></div><div class="stat-label">Unread Messages</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-users"></i></div>
    <div><div class="stat-num"><?= $stats['subscribers'] ?></div><div class="stat-label">Subscribers</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-blog"></i></div>
    <div><div class="stat-num"><?= $stats['blog_posts'] ?></div><div class="stat-label">Published Posts</div></div>
  </div>
</div>

<div class="dashboard-grid">

  <!-- Recent Bookings -->
  <div class="card dashboard-bookings-card">
    <div class="card-header">
      <h3><i class="fas fa-calendar-check" style="color:var(--gold)"></i> Recent Bookings</h3>
      <a href="/admin/bookings.php" class="btn btn-light btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Name</th><th>Email</th><th>Tour</th><th>Travel Date</th><th>Group</th><th>Status</th><th>Action</th>
        </tr></thead>
        <tbody>
          <?php foreach ($recentBookings as $b): ?>
          <tr>
            <td><?= e($b['name']) ?></td>
            <td><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></td>
            <td><?= e((string)($b['tour_name'] ?: ($b['tour_title'] ?? '—'))) ?></td>
            <td><?= $b['travel_date'] ? date('M j, Y', strtotime($b['travel_date'])) : '—' ?></td>
            <td><?= (int)$b['group_size'] ?></td>
            <td>
              <span class="booking-status-pill <?= e((string)$b['status']) ?>"><?= ucfirst((string)$b['status']) ?></span>
            </td>
            <td>
              <a href="/admin/bookings.php?edit=<?= $b['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$recentBookings): ?><tr><td colspan="7" style="text-align:center;color:#888;padding:2rem;">No bookings yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Messages -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fas fa-envelope" style="color:var(--primary)"></i> Recent Messages</h3>
      <a href="/admin/contacts.php" class="btn btn-light btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Subject</th><th>Date</th><th>Read</th></tr></thead>
        <tbody>
          <?php foreach ($recentMessages as $m): ?>
          <tr>
            <td><?= e($m['name']) ?></td>
            <td><a href="/admin/contacts.php?view=<?= $m['id'] ?>"><?= e(mb_substr($m['subject'] ?: 'No subject', 0, 30)) ?></a></td>
            <td><?= timeAgo($m['created_at']) ?></td>
            <td><?= $m['is_read'] ? '<span class="badge badge-green">Read</span>' : '<span class="badge badge-gold">New</span>' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$recentMessages): ?><tr><td colspan="4" style="text-align:center;color:#888;padding:2rem;">No messages yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="card">
    <div class="card-header"><h3><i class="fas fa-bolt" style="color:var(--gold)"></i> Quick Actions</h3></div>
    <div class="card-body quick-actions-list">
      <a href="/admin/tours.php?action=new"       class="btn btn-primary"><i class="fas fa-plus"></i> Add New Tour</a>
      <a href="/admin/blog.php?action=new"         class="btn btn-gold"><i class="fas fa-plus"></i> New Blog Post</a>
      <a href="/admin/gallery.php"                 class="btn btn-light"><i class="fas fa-upload"></i> Upload Gallery Images</a>
      <a href="/admin/settings.php"                class="btn btn-light"><i class="fas fa-cog"></i> Site Settings</a>
      <a href="/admin/newsletter.php?action=send"  class="btn btn-light"><i class="fas fa-paper-plane"></i> Send Newsletter</a>
    </div>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
