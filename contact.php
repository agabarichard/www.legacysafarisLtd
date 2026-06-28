<?php
// ================================================================
//  contact.php – Contact Page with DB storage + email
// ================================================================
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$formMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $formMsg = ['type' => 'error', 'text' => 'Security token mismatch. Please refresh.'];
    } else {
        $name    = sanitizeInput($_POST['name']    ?? '');
        $email   = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone   = sanitizeInput($_POST['phone']   ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? 'General Inquiry');
        $message = sanitizeInput($_POST['message'] ?? '');

        if (!$name || !$email || !$message) {
            $formMsg = ['type' => 'error', 'text' => 'Please fill in all required fields.'];
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $phone, $subject, $message]);

            // Notify admin
            sendMail(
                setting('site_email'), 'Admin',
                "New Contact: {$subject}",
                "<h3>New Contact Message</h3>
                 <p><strong>From:</strong> {$name} ({$email})<br>
                 <strong>Phone:</strong> {$phone}<br>
                 <strong>Subject:</strong> {$subject}</p>
                 <p><strong>Message:</strong><br>" . nl2br(e($message)) . "</p>"
            );

            // Auto-reply
            sendMail(
                $email, $name,
                'Thank you for contacting Legacy Safaris Ltd',
                "<h2>Hello {$name},</h2>
                 <p>Thank you for reaching out to us! We have received your message and will respond within 24 hours.</p>
                 <p>In the meantime, feel free to browse our <a href='" . SITE_URL . "/tours.php'>Safari Packages</a>.</p>
                 <p>Warm regards,<br><strong>" . setting('site_name') . "</strong></p>"
            );

            $formMsg = ['type' => 'success', 'text' => 'Your message has been sent! We will get back to you within 24 hours.'];
        }
    }
}

$pageTitle = 'Contact Us | ' . setting('site_name');
$extraHead = '<style>
.contact-intro{display:grid;grid-template-columns:1fr;gap:1.5rem;margin-bottom:2rem}
.contact-hero-card,.contact-form-card{background:white;border-radius:32px;box-shadow:0 20px 35px -12px rgba(0,0,0,.08)}
.contact-hero-card{padding:2rem;background:linear-gradient(135deg,rgba(47,107,62,.06),rgba(231,111,81,.05))}
.contact-hero-card h2{color:var(--acacia-green);font-size:2.1rem;margin-bottom:.8rem}
.contact-hero-card p{color:#5f564f;line-height:1.85;max-width:42rem}
.contact-badges{display:flex;flex-wrap:wrap;gap:.8rem;margin-top:1.2rem}
.contact-badge{display:inline-flex;align-items:center;gap:.55rem;padding:.75rem 1rem;border-radius:999px;background:#fff;border:1px solid #ece2d6;color:var(--charcoal);font-weight:600}
.contact-inline-contacts{display:flex;flex-wrap:wrap;gap:.85rem;margin-top:1.35rem}
.contact-inline-contacts a{display:inline-flex;align-items:center;gap:.65rem;background:#fff;border:1px solid #eadfce;border-radius:18px;padding:.95rem 1.05rem;color:var(--charcoal);text-decoration:none;font-weight:600;transition:transform .2s ease,box-shadow .2s ease}
.contact-inline-contacts a:hover{transform:translateY(-2px);box-shadow:0 14px 24px -18px rgba(0,0,0,.18)}
.contact-inline-contacts a.primary{background:linear-gradient(135deg,var(--sunset-orange),#c95a3e);border-color:transparent;color:#fff}
.contact-inline-contacts a.primary:hover{box-shadow:0 16px 28px -18px rgba(231,111,81,.55)}
.contact-layout{display:grid;grid-template-columns:.92fr 1.08fr;gap:1.5rem;align-items:start}
.contact-info{padding:1.8rem}
.contact-info h3{font-size:1.35rem;color:var(--acacia-green);margin-bottom:1rem}
.contact-info p{display:flex;align-items:flex-start;gap:.75rem;color:#5f564f;line-height:1.75;margin-bottom:.95rem}
.contact-info p i{margin-top:.2rem;min-width:18px;text-align:center}
.contact-social{margin-top:1.2rem;display:flex;gap:.75rem;flex-wrap:wrap}
.contact-social a{width:42px;height:42px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#f7efe6;color:var(--charcoal);text-decoration:none;transition:transform .2s ease,background .2s ease,color .2s ease}
.contact-social a:hover{transform:translateY(-2px);background:var(--sunset-orange);color:white}
.contact-form-card{padding:1.8rem}
.contact-form-card h3{color:var(--acacia-green);margin-bottom:.4rem}
.contact-form-card .section-sub{margin-bottom:1.4rem;text-align:left}
.contact-form-card input,.contact-form-card textarea{background:#fffdfb;border:1px solid #e8d9c8;transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease}
.contact-form-card input:focus,.contact-form-card textarea:focus{outline:none;border-color:var(--savanna-gold);box-shadow:0 0 0 4px rgba(212,163,115,.14)}
.contact-form-card textarea{min-height:160px;resize:vertical}
.contact-form-card .btn-primary{width:100%;justify-content:center;display:inline-flex;align-items:center;gap:.6rem}
.contact-note{margin-top:1rem;color:#7a6a5a;font-size:.9rem;line-height:1.7}
@media (max-width: 900px){
  .contact-layout{grid-template-columns:1fr}
}
</style>';
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="page-header">
  <div class="container">
    <h1>Plan Your Safari</h1>
    <p>Tell us what you want to see, when you want to travel, and we’ll shape the right journey.</p>
  </div>
</section>

<section class="container section-padding">
  <div class="contact-intro">
    <div class="contact-hero-card">
      <h2>Reach the team that plans the journey</h2>
      <p>
        Whether you need a honeymoon safari, a private family itinerary, or a custom East Africa route,
        our team can help you choose the right lodges, pacing, and travel dates.
      </p>
      <div class="contact-badges">
        <span class="contact-badge"><i class="fas fa-route"></i> Custom itineraries</span>
        <span class="contact-badge"><i class="fas fa-clock"></i> Reply within 24 hours</span>
      </div>

      <div class="contact-inline-contacts">
        <?php if (setting('whatsapp_number')): ?>
        <a class="primary" href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp_number'))) ?>" target="_blank" rel="noopener">
          <i class="fab fa-whatsapp"></i> WhatsApp us
        </a>
        <?php endif; ?>
        <a href="tel:<?= e(setting('phone')) ?>"><i class="fas fa-phone-alt"></i> <?= e(setting('phone')) ?></a>
        <a href="mailto:<?= e(setting('contact_email')) ?>"><i class="fas fa-envelope"></i> Email team</a>
      </div>
    </div>
  </div>

  <div class="contact-layout">
    <div class="contact-hero-card contact-info">
      <h3><?= e(setting('site_name')) ?></h3>
      <p><i class="fas fa-map-marker-alt"></i> <span><?= e(setting('address')) ?></span></p>
      <p><i class="fas fa-envelope"></i> <span><?= e(setting('contact_email')) ?></span></p>
      <p><i class="fas fa-phone-alt"></i> <span><?= e(setting('phone')) ?></span></p>

      <div class="contact-social">
        <?php if (setting('facebook_url')): ?><a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
        <?php if (setting('instagram_url')): ?><a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
        <?php if (setting('twitter_url')): ?><a href="<?= e(setting('twitter_url')) ?>" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a><?php endif; ?>
        <?php if (setting('whatsapp_number')): ?><a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp_number'))) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
      </div>
    </div>

    <div class="contact-form-card">
      <h3>Send an inquiry</h3>
      <p class="section-sub">Use the form below and we’ll get back to you with options that fit your trip.</p>
      <form method="POST">
      <?= csrfField() ?>
      <?php if ($formMsg): ?>
      <div style="background:<?= $formMsg['type']==='success'?'#d4edda':'#f8d7da' ?>;color:<?= $formMsg['type']==='success'?'#155724':'#721c24' ?>;padding:1rem;border-radius:12px;margin-bottom:1.5rem;">
        <?= e($formMsg['text']) ?>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <input type="text"  name="name"    placeholder="Full name *"    required value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <input type="email" name="email"   placeholder="Email address *" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <input type="tel"   name="phone"   placeholder="Phone number"    value="<?= e($_POST['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <input type="text"  name="subject" placeholder="Subject"         value="<?= e($_POST['subject'] ?? '') ?>">
      </div>
      <div class="form-group">
        <textarea rows="5" name="message" placeholder="Tell us about your dream safari... *" required><?= e($_POST['message'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn-primary">Send Inquiry <i class="fas fa-paper-plane"></i></button>
      <p class="contact-note">By sending this form, you agree to be contacted about your safari request. We never share your details with third parties.</p>
      </form>
    </div>
  </div>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
