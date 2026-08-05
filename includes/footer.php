<?php
/**
 * Shared footer layout for the website.
 * This file displays the footer links and contact information used on public pages.
 */
declare(strict_types=1);

/**
 * Expected variables from the including page:
 *   $base          string  '' at the project root, '../' one level deep
 *   $showWhatsapp  bool    show the floating WhatsApp card (index.php only)
 */
$base ??= '';
$showWhatsapp ??= false;
?>
  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col footer-brand">
          <div class="brand">Casadive Villa</div>
          <p>Casadive villa offers a relaxing beachfront stay with modern villas and campsite. Your perfect gateway awaits.</p>
        </div>

        <div class="footer-col">
          <h4>Quick links</h4>
          <ul>
            <li><a href="<?= $base ?>index.php">Home</a></li>
            <li><a href="<?= $base ?>customer/villa.php">Villa</a></li>
            <li><a href="<?= $base ?>customer/campsite.php">Campsite</a></li>
            <li><a href="<?= $base ?>customer/gallery.php">Gallery</a></li>
            <li><a href="<?= $base ?>customer/mybooking.php">MyBooking</a></li>
            <li><a href="<?= $base ?>customer/contact_us.php">Contact Us</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Company</h4>
          <ul>
            <li><a href="#privacy">Privacy policy</a></li>
            <li><a href="#refund">Refund policy</a></li>
            <li><a href="#faq">F.A.Q</a></li>
            <li><a href="#about">About Us</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Social media</h4>
          <ul>
            <li><a href="#facebook">Facebook</a></li>
            <li><a href="#tiktok">TikTok</a></li>
            <li><a href="#instagram">Instagram</a></li>
            <li><a href="#whatsapp">WhatsApp</a></li>
          </ul>
        </div>

        <div class="footer-col newsletter">
          <h4>Newsletter</h4>
          <p>Kindly subscribe to our newsletter to get latest updates on our rooms and campsite.</p>
          <form class="newsletter-form" onsubmit="return false;">
            <label for="newsletter-email" style="position:absolute;left:-9999px;">Enter your email</label>
            <input id="newsletter-email" type="email" placeholder="Enter your email" required>
            <button type="submit">Subscribe</button>
          </form>
        </div>
      </div>

      <hr class="footer-divider">
      <p class="footer-bottom">&copy; 2026 Casadive Villa. All rights reserved.</p>
    </div>
  </footer>

  <?php if ($showWhatsapp): ?>
  <!-- WHATSAPP FLOATING CARD -->
  <!-- href is a placeholder; replace with a real wa.me/<number> link once a WhatsApp business number is available -->
  <a class="whatsapp-card" href="https://wa.me/60103851892" aria-label="Chat with us on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
      <circle cx="16" cy="16" r="16" fill="#25D366"/>
      <path d="M16 7a9 9 0 0 0-7.8 13.5L7 25l4.6-1.2A9 9 0 1 0 16 7z" fill="#fff"/>
      <path d="M16 8.6a7.4 7.4 0 0 0-6.4 11.1l.2.4-.8 2.9 3-.8.4.2A7.4 7.4 0 1 0 16 8.6z" fill="#25D366"/>
      <path d="M12.9 11.7c-.2-.5-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.4-.2.3-1 1-.9 2.4.1 1.4 1 2.7 1.1 2.9.1.2 2 3.2 5 4.3 2.5 1 2.9.8 3.5.7.6-.1 1.7-.7 2-1.3.2-.7.2-1.2.1-1.3-.1-.1-.3-.2-.5-.4-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.6.1-.2.3-.7.9-.9 1.1-.1.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.2-.5-1.5-.8-2.1z" fill="#fff"/>
    </svg>
    <span>
      <strong>Need help?</strong>
      <small>Chat with us on WhatsApp!</small>
    </span>
  </a>
  <?php endif; ?>

</body>
</html>
