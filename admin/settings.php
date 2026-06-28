<?php
// ================================================================
//  admin/settings.php – Site Settings + Email Configuration
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $allowed = [
            'site_name','site_tagline','address','phone','phone_secondary','contact_email','site_email','office_hours',
            'facebook_url','instagram_url','twitter_url','whatsapp_number',
            'smtp_host','smtp_port','smtp_username','smtp_encryption','smtp_from_name','smtp_from_email',
            'hero_title','hero_subtitle',
            'about_short','meta_keywords','meta_description','google_analytics_id',
            'currency','currency_symbol','maintenance_mode',
        ];
        // smtp_password only if provided (non-empty)
        if (!empty($_POST['smtp_password'])) {
            $allowed[] = 'smtp_password';
        }

        $stmt = $pdo->prepare(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
        );
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $stmt->execute([$key, sanitizeInput($_POST[$key])]);
            }
        }

        // Handle hero image upload
        $heroImg = handleImageUpload('hero_image', 'settings');
        if ($heroImg) {
            $pdo->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES ('hero_image',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$heroImg,$heroImg]);
        }

        setFlash('success', 'Settings saved successfully!');
    }
    header('Location: /admin/settings.php'); exit;
}

// Test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $testTo = filter_var(sanitizeInput($_POST['test_to'] ?? ''), FILTER_VALIDATE_EMAIL);
        if ($testTo) {
            $ok = sendMail($testTo, 'Test', 'Test Email from Legacy Safaris Admin',
                '<h2>Test Email</h2><p>If you receive this, your email configuration is working correctly!</p>');
            setFlash($ok ? 'success' : 'error', $ok ? 'Test email sent successfully!' : 'Failed to send test email. Check SMTP settings.');
        } else {
            setFlash('error', 'Please enter a valid test email address.');
        }
    }
    header('Location: /admin/settings.php#email'); exit;
}

$s = getSettings(); // reload fresh
$pageTitle = 'Site Settings';
include __DIR__ . '/includes/header.php';
?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <input type="hidden" name="save_settings" value="1">

  <!-- ── General ────────────────────────────────────────────── -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h3><i class="fas fa-globe" style="color:var(--gold)"></i> General Information</h3></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label>Site Name</label>
          <input type="text" name="site_name" class="form-control" value="<?= e($s['site_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Tagline</label>
          <input type="text" name="site_tagline" class="form-control" value="<?= e($s['site_tagline'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="address" class="form-control" value="<?= e($s['address'] ?? '') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Primary Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= e($s['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Secondary Phone</label>
          <input type="text" name="phone_secondary" class="form-control" value="<?= e($s['phone_secondary'] ?? '') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Contact Email (shown to public)</label>
          <input type="email" name="contact_email" class="form-control" value="<?= e($s['contact_email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Admin Notification Email</label>
          <input type="email" name="site_email" class="form-control" value="<?= e($s['site_email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Office Hours</label>
        <input type="text" name="office_hours" class="form-control" value="<?= e($s['office_hours'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>WhatsApp Number (international format, e.g. +254712345678)</label>
        <input type="text" name="whatsapp_number" class="form-control" value="<?= e($s['whatsapp_number'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- ── Social Media ───────────────────────────────────────── -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h3><i class="fas fa-share-alt" style="color:var(--info)"></i> Social Media Links</h3></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label><i class="fab fa-facebook-f" style="color:#1877F2"></i> Facebook URL</label>
          <input type="url" name="facebook_url"  class="form-control" value="<?= e($s['facebook_url']  ?? '') ?>">
        </div>
        <div class="form-group">
          <label><i class="fab fa-instagram" style="color:#E4405F"></i> Instagram URL</label>
          <input type="url" name="instagram_url" class="form-control" value="<?= e($s['instagram_url'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label><i class="fab fa-twitter" style="color:#1DA1F2"></i> Twitter URL</label>
          <input type="url" name="twitter_url"   class="form-control" value="<?= e($s['twitter_url']   ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── Homepage ───────────────────────────────────────────── -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h3><i class="fas fa-home" style="color:var(--primary)"></i> Homepage Content</h3></div>
    <div class="card-body">
      <div class="form-group">
        <label>Hero Title</label>
        <input type="text" name="hero_title" class="form-control" value="<?= e($s['hero_title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Hero Subtitle</label>
        <textarea name="hero_subtitle" class="form-control" rows="2"><?= e($s['hero_subtitle'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Hero Background Image</label>
        <?php if (!empty($s['hero_image'])): ?>
        <div style="margin-bottom:.5rem;"><img src="<?= e($s['hero_image']) ?>" style="height:80px;border-radius:8px;object-fit:cover;"></div>
        <?php endif; ?>
        <input type="file" name="hero_image" class="form-control" accept="image/*">
      </div>
      <div class="form-group">
        <label>About Us Short Text (footer/sidebar)</label>
        <textarea name="about_short" class="form-control" rows="3"><?= e($s['about_short'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <!-- ── SEO ────────────────────────────────────────────────── -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h3><i class="fas fa-search" style="color:var(--gold)"></i> SEO &amp; Analytics</h3></div>
    <div class="card-body">
      <div class="form-group">
        <label>Meta Description</label>
        <textarea name="meta_description" class="form-control" rows="2"><?= e($s['meta_description'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Meta Keywords</label>
        <input type="text" name="meta_keywords" class="form-control" value="<?= e($s['meta_keywords'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Google Analytics ID (e.g. G-XXXXXXXXXX)</label>
        <input type="text" name="google_analytics_id" class="form-control" value="<?= e($s['google_analytics_id'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- ── Email / SMTP ───────────────────────────────────────── -->
  <div class="card" style="margin-bottom:1.5rem;" id="email">
    <div class="card-header"><h3><i class="fas fa-envelope" style="color:var(--primary)"></i> Email / SMTP Configuration</h3></div>
    <div class="card-body">
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> For Gmail: use <code>smtp.gmail.com</code>, port <code>587</code>, TLS. Create an <strong>App Password</strong> in your Google Account settings (not your regular password).
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>SMTP Host</label>
          <input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com" value="<?= e($s['smtp_host'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>SMTP Port</label>
          <input type="number" name="smtp_port" class="form-control" placeholder="587" value="<?= e($s['smtp_port'] ?? '587') ?>">
        </div>
        <div class="form-group">
          <label>Encryption</label>
          <select name="smtp_encryption" class="form-control">
            <option value="tls" <?= ($s['smtp_encryption']??'tls')==='tls'?'selected':'' ?>>TLS (STARTTLS)</option>
            <option value="ssl" <?= ($s['smtp_encryption']??'')==='ssl'?'selected':'' ?>>SSL</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>SMTP Username</label>
          <input type="email" name="smtp_username" class="form-control" value="<?= e($s['smtp_username'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>SMTP Password (leave blank to keep current)</label>
          <input type="password" name="smtp_password" class="form-control" placeholder="••••••••">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>From Name</label>
          <input type="text" name="smtp_from_name" class="form-control" value="<?= e($s['smtp_from_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>From Email</label>
          <input type="email" name="smtp_from_email" class="form-control" value="<?= e($s['smtp_from_email'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary" style="margin-bottom:1rem;"><i class="fas fa-save"></i> Save All Settings</button>
</form>

<!-- Test Email -->
<div class="card" style="margin-top:1rem;">
  <div class="card-header"><h3><i class="fas fa-vial" style="color:var(--info)"></i> Test Email Configuration</h3></div>
  <div class="card-body">
    <form method="POST" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
      <?= csrfField() ?>
      <input type="hidden" name="test_email" value="1">
      <div class="form-group" style="flex:1;min-width:240px;margin:0;">
        <label>Send test email to</label>
        <input type="email" name="test_to" class="form-control" placeholder="your@email.com" required>
      </div>
      <button type="submit" class="btn btn-gold"><i class="fas fa-paper-plane"></i> Send Test</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
