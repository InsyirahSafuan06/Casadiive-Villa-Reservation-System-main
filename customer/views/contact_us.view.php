<?php include __DIR__ . '/../../includes/header.php'; ?>

  <section class="hero" id="top" style="background-image:linear-gradient(rgba(124,106,70,.55), rgba(124,106,70,.55)), url('../assets/images/casadive-sign-porch.jpg');background-size:cover;background-position:center;">
    <h1>Contact us</h1>
    <p>The elegant luxury bedrooms in this gallery showcase custom interior designs &amp; decorating ideas. View pictures and find your perfect luxury bedroom design.</p>
    <a href="#get-in-touch" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <section class="contact-section" id="get-in-touch">
    <div class="container contact-grid">

      <div class="contact-card">
        <h2 class="contact-heading">Get in touch</h2>

        <div class="contact-item">
          <span class="contact-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .8-.3 1L6.6 10.8z"/></svg>
          </span>
          <div>
            <h4>Phone</h4>
            <p>+60 10-385 1892<br>Everyday: 9AM – 6PM</p>
          </div>
        </div>

        <div class="contact-item">
          <span class="contact-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 6h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1zm1.4 2 6.6 5.4L18.6 8H5.4z"/></svg>
          </span>
          <div>
            <h4>Email</h4>
            <p>casadivevilla@gmail.com</p>
          </div>
        </div>

        <div class="contact-item">
          <span class="contact-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 2C7.6 2 4 5.6 4 10c0 6 8 12 8 12s8-6 8-12c0-4.4-3.6-8-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
          </span>
          <div>
            <h4>Address</h4>
            <p>PT 195, Kg Baru, Kampung Pulau Sayak, 08500 Kota Kuala Muda, Kedah</p>
          </div>
        </div>

        <div class="contact-item">
          <span class="contact-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3.5 2" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
          </span>
          <div>
            <h4>Operating Hours</h4>
            <p>Monday – Sunday<br>9AM – 6PM</p>
          </div>
        </div>

      </div>

      <div class="contact-photo">
        <iframe
          class="photo-frame"
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3970.4215302242847!2d100.33672987551235!3d5.65199939432933!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x304b31402af25209%3A0x9962d5c56510a18f!2sCasadive%20Villa!5e0!3m2!1sen!2smy!4v1786056988799!5m2!1sen!2smy"
          allowfullscreen
          loading="lazy"
          referrerpolicy="strict-origin-when-cross-origin"
          title="Casadive Villa location on Google Maps"
        ></iframe>
        <?php # calling function urlencode() untuk bina link google maps direction dgn alamat villa ?>
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode('Casadive Villa, PT 195, Kg Baru, Kampung Pulau Sayak, 08500 Kota Kuala Muda, Kedah') ?>" class="directions-btn" target="_blank" rel="noopener">Get Direction</a>
      </div>

    </div>
  </section>

  <section class="socials">
    <div class="container social-grid">

      <div class="social-card">
        <span class="social-icon whatsapp" aria-hidden="true">
          <svg viewBox="0 0 32 32"><path d="M16 5a11 11 0 0 0-9.5 16.4L5 27l5.8-1.5A11 11 0 1 0 16 5z" fill="none"/><path d="M16 6.6a9.4 9.4 0 0 0-8 14.4l.2.4-1 3.6 3.7-1 .4.2A9.4 9.4 0 1 0 16 6.6z" fill="#fff"/><path d="M12.9 10.7c-.2-.5-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.4-.2.3-1 1-.9 2.4.1 1.4 1 2.7 1.1 2.9.1.2 2 3.2 5 4.3 2.5 1 2.9.8 3.5.7.6-.1 1.7-.7 2-1.3.2-.7.2-1.2.1-1.3-.1-.1-.3-.2-.5-.4-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.6.1-.2.3-.7.9-.9 1.1-.1.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.2-.5-1.5-.8-2.1z" fill="#25D366"/></svg>
        </span>
        <h4>WhatsApp</h4>
        <a href="https://wa.me/60103851892" class="social-btn filled">Chat Now</a>
      </div>

      <div class="social-card">
        <span class="social-icon facebook" aria-hidden="true">
          <svg viewBox="0 0 32 32"><path d="M18 11h3V7h-3c-2.8 0-5 2.2-5 5v2h-2v4h2v8h4v-8h3l1-4h-4v-2c0-.6.4-1 1-1z" fill="#fff"/></svg>
        </span>
        <h4>Facebook</h4>
        <a href="https://www.facebook.com/Casadive%20Villa" class="social-btn outline">Follow</a>
      </div>

      <div class="social-card">
        <span class="social-icon instagram" aria-hidden="true">
          <svg viewBox="0 0 32 32"><rect x="7" y="7" width="18" height="18" rx="5" fill="none" stroke="#fff" stroke-width="2"/><circle cx="16" cy="16" r="4.5" fill="none" stroke="#fff" stroke-width="2"/><circle cx="22" cy="10" r="1.4" fill="#fff"/></svg>
        </span>
        <h4>Instagram</h4>
        <a href="https://www.instagram.com/casadivevilla" class="social-btn outline">Follow</a>
      </div>

      <div class="social-card">
        <span class="social-icon tiktok" aria-hidden="true">
          <svg viewBox="0 0 32 32"><path d="M21 8.5c1 1.4 2.4 2.3 4 2.5v3.1c-1.6-.1-3-.6-4.2-1.5v6.4a6 6 0 1 1-6-6c.4 0 .8 0 1.2.1v3.2a2.9 2.9 0 1 0 2 2.7V6h3v2.5z" fill="#26F4EE"/><path d="M20.4 8.1c1 1.4 2.4 2.3 4 2.5v3.1c-1.6-.1-3-.6-4.2-1.5v6.4a6 6 0 1 1-6-6c.4 0 .8 0 1.2.1v3.2a2.9 2.9 0 1 0 2 2.7V5.6h3v2.5z" fill="#FB2C53"/><path d="M20.7 8.3c1 1.4 2.4 2.3 4 2.5v3.1c-1.6-.1-3-.6-4.2-1.5v6.4a6 6 0 1 1-6-6c.4 0 .8 0 1.2.1v3.2a2.9 2.9 0 1 0 2 2.7V5.8h3v2.5z" fill="#fff"/></svg>
        </span>
        <h4>TikTok</h4>
        <a href="https://www.tiktok.com/@casadive.villa" class="social-btn outline">Follow</a>
      </div>

    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
