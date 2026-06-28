<?php // includes/footer.php ?>
</main>
<footer class="site-footer">
  <div class="container">
    <div class="footer-shell">
      <div class="footer-brand-block">
        <div class="footer-logo-wrap">
          <img src="/images/new%20Logo.png" alt="Legacy Safaris Ltd" class="site-logo-img site-logo-img--footer">
        </div>
        <p class="footer-tagline"><?= e(setting('site_tagline', 'Authentic · Ethical · Unforgettable')) ?></p>
        <p class="footer-description">
          <?= e(setting('about_short', 'Tailor-made safaris across East Africa, designed with comfort, conservation, and unforgettable moments in mind.')) ?>
        </p>
        <div class="footer-contact-chips">
          <a class="footer-chip" href="tel:<?= e(setting('phone')) ?>"><i class="fas fa-phone-alt"></i><span><?= e(setting('phone')) ?></span></a>
          <a class="footer-chip" href="mailto:<?= e(setting('contact_email')) ?>"><i class="fas fa-envelope"></i><span><?= e(setting('contact_email')) ?></span></a>
        </div>
      </div>

      <div class="footer-column">
        <h4>Quick Links</h4>
        <ul class="footer-links">
          <li><a href="/tours.php">Safari Packages</a></li>
          <li><a href="/destinations.php">Destinations</a></li>
          <li><a href="/gallery.php">Gallery</a></li>
          <li><a href="/blog">Safari Journal</a></li>
          <li><a href="/contact.php">Contact Us</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h4>Contact Details</h4>
        <ul class="footer-details">
          <li><i class="fas fa-map-marker-alt"></i><span><?= e(setting('address')) ?></span></li>
          <li><i class="fas fa-clock"></i><span><?= e(setting('office_hours')) ?></span></li>
          <li><i class="fas fa-phone-alt"></i><span><?= e(setting('phone')) ?></span></li>
          <li><i class="fas fa-envelope"></i><span><?= e(setting('contact_email')) ?></span></li>
        </ul>
      </div>

      <div class="footer-column footer-action-card">
        <h4>Plan Your Trip</h4>
        <p>Need a custom safari, private family itinerary, or honeymoon package? We can build it around your dates and budget.</p>
        <div class="footer-action-links">
          <a class="btn-primary footer-action-btn" href="/tours.php">View Packages</a>
          <a class="btn-outline footer-action-btn" href="/contact.php">Request Quote</a>
        </div>
        <div class="social-links footer-social-links">
          <?php if (setting('facebook_url') && setting('facebook_url') !== '#'): ?>
          <a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <?php else: ?><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
          <?php if (setting('instagram_url') && setting('instagram_url') !== '#'): ?>
          <a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <?php else: ?><a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
          <?php if (setting('twitter_url') && setting('twitter_url') !== '#'): ?>
          <a href="<?= e(setting('twitter_url')) ?>" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <?php else: ?><a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a><?php endif; ?>
          <?php if (setting('whatsapp_number')): ?>
          <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp_number'))) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= e(setting('site_name', 'Legacy Safaris Ltd')) ?>. Where heritage meets the wild.</p>
      <p class="footer-bottom-note">Monitored and updated from the <a href="/admin/login.php">admin panel</a>.</p>
      <p class="footer-bottom-note">Website developed and managed by <a href="https://gwtitsolutions.com/" target="_blank" rel="noopener">GWT IT Solutions</a>.</p>
    </div>
  </div>
</footer>
</body>
</html>
