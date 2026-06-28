<?php
// ================================================================
//  tours.php – Safari Packages Page (Dynamic)
// ================================================================
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();

// Filter by category
$categoryFilter = sanitizeInput($_GET['category'] ?? 'all');
$allowed = ['all', 'budget', 'premium', 'family', 'adventure', 'honeymoon'];
if (!in_array($categoryFilter, $allowed)) $categoryFilter = 'all';

$sql    = "SELECT * FROM tours WHERE is_active = 1";
$params = [];
if ($categoryFilter !== 'all') {
    $sql    .= " AND category = ?";
    $params[] = $categoryFilter;
}
$sql .= " ORDER BY sort_order ASC, id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll();

// Single tour detail via AJAX or direct param
$tourDetail = null;
if (!empty($_GET['id'])) {
    $ts = $pdo->prepare("SELECT * FROM tours WHERE id = ? AND is_active = 1");
    $ts->execute([(int)$_GET['id']]);
    $tourDetail = $ts->fetch();
    if ($tourDetail) {
        $hls = $pdo->prepare("SELECT highlight FROM tour_highlights WHERE tour_id = ? ORDER BY sort_order");
        $hls->execute([$tourDetail['id']]);
        $tourDetail['highlights'] = array_column($hls->fetchAll(), 'highlight');

        $its = $pdo->prepare("SELECT day_num, title, description FROM tour_itinerary WHERE tour_id = ? ORDER BY day_num");
        $its->execute([$tourDetail['id']]);
        $tourDetail['itinerary'] = $its->fetchAll();

        $inc = $pdo->prepare("SELECT item, type FROM tour_includes WHERE tour_id = ?");
        $inc->execute([$tourDetail['id']]);
        $all = $inc->fetchAll();
        $tourDetail['includes'] = array_column(array_filter($all, fn($r) => $r['type'] === 'include'), 'item');
        $tourDetail['excludes'] = array_column(array_filter($all, fn($r) => $r['type'] === 'exclude'), 'item');
    }
}

// Handle booking form submission
$bookingMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_tour'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bookingMsg = ['type' => 'error', 'text' => 'Security error. Please refresh and try again.'];
    } else {
        $bName  = sanitizeInput($_POST['name']  ?? '');
        $bEmail = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $bPhone = sanitizeInput($_POST['phone']  ?? '');
        $bDate  = sanitizeInput($_POST['travel_date'] ?? '');
        $bSize  = max(1, (int)($_POST['group_size'] ?? 1));
        $bMsg   = sanitizeInput($_POST['message'] ?? '');
        $bTour  = (int)($_POST['tour_id'] ?? 0);
        $bTName = sanitizeInput($_POST['tour_name'] ?? '');

        if (!$bName || !$bEmail) {
            $bookingMsg = ['type' => 'error', 'text' => 'Name and email are required.'];
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO bookings (tour_id, tour_name, name, email, phone, travel_date, group_size, message)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->execute([
                $bTour ?: null, $bTName, $bName, $bEmail,
                $bPhone, $bDate ?: null, $bSize, $bMsg
            ]);

            // Notify admin
            $adminEmail = setting('site_email');
            sendMail($adminEmail, 'Admin', 'New Booking Request: ' . $bTName,
                "<h3>New Booking Request</h3>
                 <p><strong>Tour:</strong> {$bTName}<br>
                 <strong>Name:</strong> {$bName}<br>
                 <strong>Email:</strong> {$bEmail}<br>
                 <strong>Phone:</strong> {$bPhone}<br>
                 <strong>Travel Date:</strong> {$bDate}<br>
                 <strong>Group Size:</strong> {$bSize}<br>
                 <strong>Message:</strong> {$bMsg}</p>"
            );
            // Confirm to guest
            sendMail($bEmail, $bName, 'Your Safari Booking Request – Legacy Safaris Ltd',
                "<h2>Hi {$bName},</h2>
                 <p>Thank you for your booking request for <strong>{$bTName}</strong>!</p>
                 <p>Our team will contact you within 24 hours to confirm details.</p>
                 <p>Best regards,<br>Legacy Safaris Ltd</p>"
            );
            $bookingMsg = ['type' => 'success', 'text' => 'Your booking request has been sent! We will contact you within 24 hours.'];
        }
    }
}

$pageTitle = 'Safari Packages | ' . setting('site_name');
$extraHead = '<style>
.filter-bar{display:flex;justify-content:center;gap:.75rem;flex-wrap:wrap;margin-bottom:2rem}
.filter-btn{background:rgba(255,255,255,.72);border:1px solid rgba(139,90,43,.14);padding:10px 18px;border-radius:999px;font-weight:700;cursor:pointer;transition:.2s color,.2s background,.2s transform;text-decoration:none;color:var(--baobab-brown);box-shadow:0 10px 20px -16px rgba(44,42,41,.35)}
.filter-btn.active,.filter-btn:hover{background:var(--savanna-gold);color:white;transform:translateY(-1px)}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);z-index:1200;justify-content:center;align-items:center}
.modal.active{display:flex}
.modal-content{background:white;max-width:520px;width:90%;border-radius:32px;padding:2rem;position:relative;box-shadow:0 28px 60px rgba(0,0,0,.24)}
.detail-modal{max-width:800px;max-height:85vh;overflow-y:auto;text-align:left}
.close-modal{position:absolute;top:16px;right:20px;font-size:1.8rem;cursor:pointer}
.modal input,.modal textarea,.modal select{margin-bottom:1rem;width:100%;padding:12px 14px;border-radius:18px;border:1px solid #ddd0be;font-family:inherit;background:#fffdfa}
.tour-actions{display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;justify-content:flex-end}
.detail-top{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:1.25rem;align-items:start}
.detail-image{width:100%;height:100%;min-height:240px;object-fit:cover;border-radius:22px;box-shadow:0 16px 30px -20px rgba(0,0,0,.35)}
.detail-meta{display:flex;flex-wrap:wrap;gap:.7rem;margin:.75rem 0 1rem}
.detail-meta span{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem .8rem;border-radius:999px;background:rgba(47,107,62,.08);color:#4a5a43;font-weight:700;font-size:.92rem}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-top:1rem}
.detail-panel{background:#fffaf4;border:1px solid rgba(139,90,43,.1);border-radius:20px;padding:1rem 1.1rem}
.detail-panel h4{margin-bottom:.6rem}
.detail-panel ul{margin-left:1.1rem;color:#5f564d;line-height:1.6}
.detail-actions{display:flex;justify-content:center;margin-top:1.5rem}
.detail-actions .btn-primary{padding:12px 26px}
.tour-detail-price{font-weight:800;color:var(--sunset-orange)}
</style>';
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="tour-page-hero">
  <div class="container">
    <div class="tour-hero-copy">
      <div class="tour-kicker">Uganda safari collection</div>
    <h1>Signature Safari Packages</h1>
    <p>Handpicked journeys – each with detailed itinerary, inclusions &amp; expert guides</p>
    <div class="tour-hero-stats">
      <div class="tour-stat"><strong><?= count($tours) ?></strong><span>active packages</span></div>
      <div class="tour-stat"><strong>UGX</strong><span>local pricing</span></div>
      <div class="tour-stat"><strong>Custom</strong><span>booking support</span></div>
    </div>
    </div>
  </div>
</section>

<section class="container section-padding">
  <div class="tour-toolbar">
    <div>
      <p class="tour-kicker" style="margin-bottom:.35rem;">Refine your search</p>
      <h2>Browse packages by travel style</h2>
    </div>
    <div class="tour-filter-note"><?= count($tours) ?> package<?= count($tours) === 1 ? '' : 's' ?> shown</div>
  </div>

  <!-- Category Filters -->
  <div class="filter-bar">
    <a href="?category=all"       class="filter-btn <?= $categoryFilter === 'all'       ? 'active' : '' ?>">All Packages</a>
    <a href="?category=budget"    class="filter-btn <?= $categoryFilter === 'budget'    ? 'active' : '' ?>">Budget</a>
    <a href="?category=premium"   class="filter-btn <?= $categoryFilter === 'premium'   ? 'active' : '' ?>">Premium</a>
    <a href="?category=family"    class="filter-btn <?= $categoryFilter === 'family'    ? 'active' : '' ?>">Family</a>
    <a href="?category=adventure" class="filter-btn <?= $categoryFilter === 'adventure' ? 'active' : '' ?>">Adventure</a>
    <a href="?category=honeymoon" class="filter-btn <?= $categoryFilter === 'honeymoon' ? 'active' : '' ?>">Honeymoon</a>
  </div>

  <?php if ($bookingMsg): ?>
  <div style="background:<?= $bookingMsg['type']==='success'?'#d4edda':'#f8d7da' ?>;color:<?= $bookingMsg['type']==='success'?'#155724':'#721c24' ?>;padding:1rem;border-radius:12px;margin-bottom:1.5rem;text-align:center;">
    <?= e($bookingMsg['text']) ?>
  </div>
  <?php endif; ?>

  <div class="tours-grid">
    <?php if ($tours): ?>
      <?php foreach ($tours as $tour): ?>
      <div class="tour-card">
        <div class="tour-img">
          <img src="<?= e('/' . ltrim((string)($tour['image'] ?: 'images/image1.jpg'), '/')) ?>" alt="<?= e($tour['name']) ?>">
          <span class="tour-badge"><?= e(ucfirst($tour['category'])) ?></span>
          <?php if (!empty($tour['is_featured'])): ?><span class="tour-featured"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
        </div>
        <div class="tour-info">
          <h3><?= e($tour['name']) ?></h3>
          <div class="tour-meta">
            <span><i class="fas fa-clock"></i> <?= (int)$tour['duration_days'] ?> Days</span>
            <span><i class="fas fa-users"></i> Max <?= (int)$tour['max_group'] ?></span>
          </div>
          <p><?= e($tour['short_desc']) ?></p>
          <div class="tour-footer">
            <div class="tour-price">from <?= formatPrice((float)$tour['price'], 'UGX') ?></div>
            <div class="tour-actions">
              <button class="btn-small" onclick="openDetail(<?= (int)$tour['id'] ?>)">Details</button>
              <button class="btn-primary" style="padding:6px 16px;font-size:.9rem;" onclick="openBooking(<?= (int)$tour['id'] ?>, '<?= addslashes(e($tour['name'])) ?>', '<?= e(formatPrice((float)$tour['price'], 'UGX')) ?>')">Book Now</button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align:center;color:#888;padding:3rem;">No tours found in this category.</p>
    <?php endif; ?>
  </div>
</section>
</main>

<!-- Booking Modal -->
<div id="bookingModal" class="modal">
  <div class="modal-content">
    <span class="close-modal" onclick="closeModal('bookingModal')">&times;</span>
    <h3 style="color:var(--acacia-green);">Request Booking</h3>
    <p id="modalTourLabel" style="font-weight:bold;color:var(--savanna-gold);"></p>
    <form method="POST" action="/tours.php">
      <?= csrfField() ?>
      <input type="hidden" name="book_tour" value="1">
      <input type="hidden" name="tour_id"   id="modalTourId">
      <input type="hidden" name="tour_name" id="modalTourName">
      <input type="text"   name="name"  placeholder="Full name"      required>
      <input type="email"  name="email" placeholder="Email address"   required>
      <input type="tel"    name="phone" placeholder="Phone number">
      <input type="date"   name="travel_date" placeholder="Preferred travel date">
      <input type="number" name="group_size" placeholder="Group size" min="1" max="50" value="2">
      <textarea name="message" rows="3" placeholder="Additional requests or questions"></textarea>
      <button type="submit" class="btn-primary" style="width:100%">Send Booking Request</button>
    </form>
  </div>
</div>

<!-- Tour Detail Modal (loaded via AJAX) -->
<div id="detailModal" class="modal">
  <div class="modal-content detail-modal">
    <span class="close-modal" onclick="closeModal('detailModal')">&times;</span>
    <div id="detailContent"><p style="text-align:center;padding:2rem;">Loading...</p></div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function openBooking(id, name, price) {
  document.getElementById('modalTourId').value   = id;
  document.getElementById('modalTourName').value = name;
  document.getElementById('modalTourLabel').textContent = name + ' — ' + price;
  document.getElementById('bookingModal').classList.add('active');
}
function openDetail(id) {
  document.getElementById('detailModal').classList.add('active');
  document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:2rem;">Loading...</p>';
  fetch('/api/tour-detail.php?id=' + id)
    .then(r => r.text())
    .then(html => { document.getElementById('detailContent').innerHTML = html; })
    .catch(() => { document.getElementById('detailContent').innerHTML = '<p>Could not load details.</p>'; });
}
function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}
document.addEventListener('keydown', e => { if(e.key==='Escape') { closeModal('bookingModal'); closeModal('detailModal'); }});
document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', function(e){
  if(e.target === this) this.classList.remove('active');
}));
</script>
