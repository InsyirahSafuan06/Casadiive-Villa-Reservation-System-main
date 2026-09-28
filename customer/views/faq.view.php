<?php include __DIR__ . '/../../includes/header.php'; ?>

  <section class="hero" id="top">
    <h1>Frequently Asked Questions</h1>
    <p>Everything you need to know about booking, paying, and staying at Casadive Villa.</p>
  </section>

  <section class="info-section">
    <div class="container">
      <div class="info-card">

        <?php
        $faqs = [
            [
                'q' => 'How do I make a booking?',
                'a' => 'Browse our Villa or Campsite pages, pick a package and your dates, and fill in the booking form. You\'ll receive a booking reference — keep it together with the phone number you booked with, since you\'ll need both to look up or manage your booking later.',
            ],
            [
                'q' => 'What\'s the difference between Villa and Campsite?',
                'a' => 'Villa Casa units are private, air-conditioned rooms with en-suite bathrooms — best for families or groups who want indoor comfort. Campsite packages are outdoor camping spots for guests who want a closer-to-nature stay. Both have access to shared amenities like the pool and BBQ area, depending on the package.',
            ],
            [
                'q' => 'How do I pay for my booking?',
                'a' => 'Payment is made online through our secure payment page after booking. We accept online banking (FPX) via our payment gateway. Some bookings may involve a deposit followed by the balance — this is shown clearly on your booking summary before you pay.',
            ],
            [
                'q' => 'What are the check-in and check-out times?',
                'a' => 'Check-in is from 3:00 PM and check-out is by 12:00 PM. Your door lock code (if applicable to your unit) is sent to you as part of your check-in reminder.',
            ],
            [
                'q' => 'Can I cancel or change my booking?',
                'a' => 'You can cancel a booking yourself from the MyBooking page, as long as it hasn\'t been checked in, checked out, or already cancelled. See our Refund Policy for what happens to any payment already made. For date changes, please contact us directly via WhatsApp.',
            ],
            [
                'q' => 'How do I view or print my booking receipt?',
                'a' => 'Go to the MyBooking page and enter your booking reference and the phone number you booked with. You\'ll be able to view your booking details and print your receipt from there.',
            ],
            [
                'q' => 'Can I leave a review?',
                'a' => 'Yes — you can leave a quick review with a photo from the homepage at any time, or leave a fuller review (tied to your specific stay) from the MyBooking page once your booking is marked checked out.',
            ],
            [
                'q' => 'Do you have WiFi, a pool, and BBQ facilities?',
                'a' => 'Most of our packages include access to WiFi, the pool, and a BBQ area — check the "Facilities" list on each package\'s detail page, since this can vary slightly by unit.',
            ],
            [
                'q' => 'I have another question — how do I reach you?',
                'a' => 'The fastest way is WhatsApp. You can also call or email us — see the buttons below or visit our Contact Us page for our full details and location.',
            ],
        ];
        ?>

        <?php foreach ($faqs as $i => $faq): ?>
          <div class="faq-item<?= $i === 0 ? ' is-open' : '' ?>">
            <button type="button" class="faq-question">
              <span><?= htmlspecialchars($faq['q']) ?></span>
              <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
            <div class="faq-answer">
              <p><?= htmlspecialchars($faq['a']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="info-contact-row">
          <a href="https://wa.me/60103851892" target="_blank" rel="noopener">WhatsApp Us</a>
          <a href="contact_us.php">Contact Us</a>
        </div>

      </div>
    </div>
  </section>

  <script>
    document.querySelectorAll('.faq-question').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.parentElement.classList.toggle('is-open');
      });
    });
  </script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
