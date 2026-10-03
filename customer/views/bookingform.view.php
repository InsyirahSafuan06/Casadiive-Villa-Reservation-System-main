<?php include __DIR__ . '/../../includes/header.php'; ?>

  <div class="step-bar" aria-label="Booking progress">
    <div class="step is-active">
      <span class="step-circle">1</span>
      <span class="step-label">Details</span>
    </div>
    <span class="step-line"></span>
    <div class="step">
      <span class="step-circle">2</span>
      <span class="step-label">Payment</span>
    </div>
    <span class="step-line"></span>
    <div class="step">
      <span class="step-circle">3</span>
      <span class="step-label">Complete</span>
    </div>
  </div>

  <div class="container page-title">
    <div class="page-title-heading">
      <span class="page-title-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      </span>
      <div>
        <h1>Booking Details</h1>
        <p>Please fill in your details correctly for reservation.</p>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="policy-notice">
      <h3>Sila baca sebelum membuat tempahan</h3>
      <p class="policy-location">CASADIVE VILLA | Kg Baru Pulau Sayak, Kedah</p>
      <ul class="policy-detail-list">
        <li>Deposit tempahan RM<?= number_format(BOOKING_DEPOSIT_AMOUNT, 0) ?> untuk satu villa perlu dibayar dalam masa 24 jam untuk mengesahkan tempahan.</li>
        <li>Tempahan hanya disahkan selepas pihak kami menerima deposit. Bayaran penuh perlu dijelaskan sebelum tarikh daftar masuk.</li>
        <li>Deposit tempahan tidak akan dipulangkan jika tempahan dibatalkan.</li>
        <li>Deposit RM<?= number_format(BOOKING_DEPOSIT_AMOUNT, 0) ?> bagi setiap villa akan dipulangkan dalam masa 24 jam selepas daftar keluar jika tiada kerosakan atau kehilangan barang.</li>
        <li>Add-on set BBQ boleh disewa dengan harga RM<?= number_format(ADDON_BBQ_PRICE, 0) ?>.</li>
        <li>Untuk mandi kolam, sila pakai pakaian renang bagi menjaga kualiti air dan mengelakkan kerosakan pam serta penapis kolam.</li>
        <li>Makan dan minum di dalam kolam adalah dilarang.</li>
        <li>Kos kerosakan atau kehilangan barang akan dicaj kepada tetamu.</li>
        <li>Daftar masuk selepas 3.00 petang; daftar keluar sebelum 12.00 tengah hari.</li>
        <li>Daftar masuk adalah secara kendiri. Sila hubungi admin sehari sebelum daftar masuk untuk mendapatkan kod kotak kunci.</li>
      </ul>
      <div class="policy-bank-details">
        <strong>Bayaran melalui perbankan dalam talian</strong>
        <span>MAYBANK BERHAD</span>
        <span>ATHIRAH FIKRIYAH BINTI AHMAD FUAD</span>
        <span>0080 3864 7569</span>
      </div>
    </div>
  </div>

  <section class="booking-section">
    <div class="container">
      <?php # check kalau $errors ada isi, kalau ada baru papar kotak error ni ?>
      <?php if ($errors): ?>
        <div style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;">
          <ul style="margin-left:18px;">
            <?php # loop setiap error dalam $errors untuk papar mesej satu-satu ?>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form id="booking-form" method="post" novalidate>
        <?php # calling function csrf_field() untuk bina hidden input token, elak CSRF attack masa submit form ?>
        <?= csrf_field() ?>
        <div class="booking-grid">

          <div class="booking-form">
            <div class="form-field">
              <label for="full-name">Full Name</label>
              <input type="text" id="full-name" name="full_name" placeholder="Enter your full name" value="<?= htmlspecialchars($old['full_name']) ?>" required>
            </div>

            <div class="form-field">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?= htmlspecialchars($old['phone']) ?>" required>
            </div>

            <div class="form-field">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?= htmlspecialchars($old['email']) ?>" required>
            </div>

            <div class="form-field">
              <label for="ic-passport">IC / Passport Number</label>
              <input type="text" id="ic-passport" name="ic_passport" placeholder="e.g. 010101-01-1234" value="<?= htmlspecialchars($old['ic_passport']) ?>" required>
            </div>

            <div class="form-field">
              <label for="plate">Car Plate Number</label>
              <input type="text" id="plate" name="plate_num" placeholder="Enter your car plate number" value="<?= htmlspecialchars($old['plate_num']) ?>">
            </div>

            <div class="form-field">
              <label for="location">Location (optional)</label>
              <input type="text" id="location" name="location" placeholder="e.g. Kuala Lumpur, Malaysia" value="<?= htmlspecialchars($old['location']) ?>">
            </div>

            <div class="form-row">
              <div class="form-field">
                <label for="check-in">Check-in</label>
                <input type="date" id="check-in" name="check_in" value="<?= htmlspecialchars($old['check_in']) ?>" required>
              </div>
              <div class="form-field">
                <label for="check-out">Check-out</label>
                <input type="date" id="check-out" name="check_out" value="<?= htmlspecialchars($old['check_out']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-field">
                <label for="guests">Number of guests</label>
                <input type="number" id="guests" name="total_guest" min="1" value="<?= htmlspecialchars($old['total_guest']) ?>" required>
              </div>
              <div class="form-field">
                <label for="accommodation">Accommodation</label>
                <select id="accommodation" name="accommodation_id" required>
                  <option value="">Select a package</option>
                  <?php # loop setiap accommodation dalam $accommodations untuk papar pilihan dalam dropdown ?>
                  <?php foreach ($accommodations as $acc): ?>
                    <option
                      value="<?= (int) $acc['accommodation_id'] ?>"
                      data-name="<?= htmlspecialchars($acc['accommodation_name']) ?>"
                      data-type="<?= htmlspecialchars($acc['accommodation_type']) ?>"
                      data-price="<?= (float) $acc['price'] ?>"
                      <?php # check kalau price_weekend takde punya, guna price biasa je sbb fallback ?>
                      data-price-weekend="<?= $acc['price_weekend'] !== null ? (float) $acc['price_weekend'] : (float) $acc['price'] ?>"
                      data-capacity="<?= (int) $acc['capacity'] ?>"
                      data-image="<?= htmlspecialchars($acc['image'] ?? '') ?>"
                      data-rate-periods="<?= htmlspecialchars(json_encode($ratePeriodsByAccommodation[(int) $acc['accommodation_id']] ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                      <?php # check kalau accommodation ni sama dengan $old['accommodation_id'] punya value lama untuk mark 'selected' balik lepas submit gagal ?>
                      <?= $old['accommodation_id'] !== '' && (int) $old['accommodation_id'] === (int) $acc['accommodation_id'] ? 'selected' : '' ?>
                    ><?= htmlspecialchars($acc['accommodation_name']) ?> — RM <?= number_format((float) $acc['price'], 2) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-field">
              <label>Add-ons (optional)</label>
              <div class="addon-options">
                <label class="addon-option">
                  <?php # check $old['addon_bbq'] untuk kekalkan checkbox tercentang lepas submit gagal ?>
                  <input type="checkbox" id="addon-bbq" name="addon_bbq" <?= $old['addon_bbq'] ? 'checked' : '' ?>>
                  BBQ Set <span class="addon-price">+RM <?= number_format(ADDON_BBQ_PRICE, 2) ?></span>
                </label>
                <label class="addon-option">
                  <?php # check $old['addon_mattress'] untuk kekalkan checkbox tercentang lepas submit gagal ?>
                  <input type="checkbox" id="addon-mattress" name="addon_mattress" <?= $old['addon_mattress'] ? 'checked' : '' ?>>
                  Extra Mattress <span class="addon-price">+RM <?= number_format(ADDON_MATTRESS_PRICE, 2) ?></span>
                </label>
              </div>
            </div>

            <div class="form-field">
              <label for="request">Special Request</label>
              <textarea id="request" name="special_request" rows="4" placeholder="Any special request? (optional)"><?= htmlspecialchars($old['special_request']) ?></textarea>
            </div>
          </div>

          <aside class="summary-card" aria-live="polite">
            <h2 class="summary-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
              Booking Summary
            </h2>

            <div class="summary-package">
              <div class="summary-thumb" id="summary-thumb">
                <span class="summary-badge" id="summary-type">Villa</span>
              </div>
              <div>
                <p class="summary-package-name" id="summary-name">Select a package</p>
                <p class="summary-package-cap" id="summary-capacity">—</p>
              </div>
            </div>

            <div class="summary-rows">
              <div class="summary-row"><span>Full Name</span><span id="s-name">—</span></div>
              <div class="summary-row"><span>Phone Number</span><span id="s-phone">—</span></div>
              <div class="summary-row"><span>Email Address</span><span id="s-email">—</span></div>
              <div class="summary-row"><span>Car Plate Number</span><span id="s-plate">—</span></div>
              <div class="summary-row"><span>Check-in</span><span id="s-checkin">—</span></div>
              <div class="summary-row"><span>Check-out</span><span id="s-checkout">—</span></div>
              <div class="summary-row"><span>Number of guests</span><span id="s-guests">1</span></div>
              <div class="summary-row"><span>Nights</span><span id="s-nights">1</span></div>
              <div class="summary-row"><span>Rate</span><span id="s-price">RM 0.00</span></div>
              <div class="summary-row"><span>Add-ons</span><span id="s-addons">RM 0.00</span></div>
              <div class="summary-row" id="s-discount-row" hidden><span>Long Stay Discount</span><span id="s-discount">-RM 0.00</span></div>
              <div class="summary-row"><span>Deposit</span><span id="s-deposit">RM 0.00</span></div>
              <div class="summary-row total"><span>Total Price</span><span id="s-total">RM 0.00</span></div>
            </div>
          </aside>

        </div>

        <div class="form-checks">
          <label class="form-check">
            <?php # check $old['agree_terms'] untuk kekalkan checkbox tercentang lepas submit gagal ?>
            <input type="checkbox" name="agree_terms" <?= $old['agree_terms'] ? 'checked' : '' ?> required>
            Saya telah membaca dan bersetuju dengan syarat tempahan di atas.
          </label>
          <label class="form-check">
            <?php # check $old['whatsapp_optin'] untuk kekalkan checkbox tercentang lepas submit gagal ?>
            <input type="checkbox" name="whatsapp_optin" <?= $old['whatsapp_optin'] ? 'checked' : '' ?>>
            Receive updates via WhatsApp
          </label>
        </div>

        <div class="form-actions">
          <a href="villa.php" class="proceed-btn proceed-btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Packages
          </a>
          <button type="submit" class="proceed-btn">
            Proceed to Payment
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>
        </div>
      </form>
    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
  const form = document.getElementById('booking-form');
  const accommodationSelect = document.getElementById('accommodation');
  const addonBbq = document.getElementById('addon-bbq');
  const addonMattress = document.getElementById('addon-mattress');
  <?php # calling function json_encode() 4x untuk hantar constant PHP ni ke JS supaya boleh kira harga kat client side ?>
  const ADDON_BBQ_PRICE = <?= json_encode(ADDON_BBQ_PRICE) ?>;
  const ADDON_MATTRESS_PRICE = <?= json_encode(ADDON_MATTRESS_PRICE) ?>;
  const LONG_STAY_DISCOUNT_MIN_NIGHTS = <?= json_encode(LONG_STAY_DISCOUNT_MIN_NIGHTS) ?>;
  const LONG_STAY_DISCOUNT_AMOUNT = <?= json_encode(LONG_STAY_DISCOUNT_AMOUNT) ?>;

  function computeStay(checkInStr, checkOutStr, weekdayPrice, weekendPrice, ratePeriods){
    let weekdayNights = 0, weekendNights = 0, specialNights = 0, total = 0;
    if (checkInStr && checkOutStr) {
      const cursor = new Date(checkInStr + 'T00:00:00');
      const end = new Date(checkOutStr + 'T00:00:00');
      while (cursor < end) {
        const nightDate = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}-${String(cursor.getDate()).padStart(2, '0')}`;
        const specialRate = ratePeriods.find(period => nightDate >= period.start_date && nightDate <= period.end_date);
        if (specialRate) {
          specialNights++;
          total += Number(specialRate.price);
        } else if (cursor.getDay() === 5 || cursor.getDay() === 6) {
          weekendNights++;
          total += weekendPrice;
        } else {
          weekdayNights++;
          total += weekdayPrice;
        }
        cursor.setDate(cursor.getDate() + 1);
      }
    }
    if (weekdayNights + weekendNights + specialNights === 0) {
      weekdayNights = 1;
      total = weekdayPrice;
    }
    return { weekdayNights, weekendNights, specialNights, total };
  }

  function updateSummary(){
    const opt = accommodationSelect.selectedOptions[0];
    const hasPackage = Boolean(opt && opt.value);
    const weekdayPrice = hasPackage ? Number(opt.dataset.price) : 0;
    const weekendPrice = hasPackage ? Number(opt.dataset.priceWeekend) : 0;
    const ratePeriods = hasPackage ? JSON.parse(opt.dataset.ratePeriods || '[]') : [];
    const capacity = hasPackage ? opt.dataset.capacity : null;

    document.getElementById('summary-type').textContent = hasPackage ? opt.dataset.type : 'Villa';
    document.getElementById('summary-name').textContent = hasPackage ? opt.dataset.name : 'Select a package';
    document.getElementById('summary-capacity').textContent = capacity ? `Max ${capacity} guests` : '—';

    const thumb = document.getElementById('summary-thumb');
    const image = hasPackage ? opt.dataset.image : '';
    if (image) {
      thumb.style.backgroundImage = `url('${image}')`;
      thumb.classList.add('has-image');
    } else {
      thumb.style.backgroundImage = '';
      thumb.classList.remove('has-image');
    }

    document.getElementById('s-name').textContent = document.getElementById('full-name').value || '—';
    document.getElementById('s-phone').textContent = document.getElementById('phone').value || '—';
    document.getElementById('s-email').textContent = document.getElementById('email').value || '—';
    document.getElementById('s-plate').textContent = document.getElementById('plate').value || '—';

    const checkIn = document.getElementById('check-in').value;
    const checkOut = document.getElementById('check-out').value;
    document.getElementById('s-checkin').textContent = checkIn || '—';
    document.getElementById('s-checkout').textContent = checkOut || '—';
    document.getElementById('s-guests').textContent = document.getElementById('guests').value || 1;

    const stay = computeStay(checkIn, checkOut, weekdayPrice, weekendPrice, ratePeriods);
    const totalNights = stay.weekdayNights + stay.weekendNights + stay.specialNights;
    const deposit = hasPackage && opt.dataset.type === 'Villa' ? <?= json_encode(BOOKING_DEPOSIT_AMOUNT) ?> : 0;

    document.getElementById('s-nights').textContent = totalNights;

    let priceLabel = `RM ${weekdayPrice.toFixed(2)} / night`;
    if (checkIn) {
      const checkInDay = new Date(checkIn + 'T00:00:00').getDay();
      const checkInPeriod = ratePeriods.find(period => checkIn >= period.start_date && checkIn <= period.end_date);
      const checkInRate = checkInPeriod
        ? Number(checkInPeriod.price)
        : (checkInDay === 5 || checkInDay === 6) ? weekendPrice : weekdayPrice;
      priceLabel = `RM ${checkInRate.toFixed(2)} / night${checkInPeriod ? ` (${checkInPeriod.label})` : ''}`;
    }
    const addonTotal = (addonBbq.checked ? ADDON_BBQ_PRICE : 0) + (addonMattress.checked ? ADDON_MATTRESS_PRICE : 0);
    const discount = (hasPackage && totalNights >= LONG_STAY_DISCOUNT_MIN_NIGHTS)
      ? Math.min(stay.total, LONG_STAY_DISCOUNT_AMOUNT)
      : 0;

    document.getElementById('s-price').textContent = priceLabel;
    document.getElementById('s-addons').textContent = `RM ${addonTotal.toFixed(2)}`;
    document.getElementById('s-discount-row').hidden = discount <= 0;
    document.getElementById('s-discount').textContent = `-RM ${discount.toFixed(2)}`;
    document.getElementById('s-deposit').textContent = `RM ${deposit.toFixed(2)}`;
    document.getElementById('s-total').textContent = `RM ${(stay.total + addonTotal + deposit - discount).toFixed(2)}`;
  }

  const params = new URLSearchParams(window.location.search);
  const preselectName = params.get('accommodation');
  const preselectType = params.get('type');
  if (preselectName) {
    const match = [...accommodationSelect.options].find(o => o.dataset.name === preselectName);
    if (match) accommodationSelect.value = match.value;
  } else if (preselectType) {
    const match = [...accommodationSelect.options].find(o => o.dataset.type === preselectType);
    if (match) accommodationSelect.value = match.value;
  }

  form.addEventListener('input', updateSummary);
  updateSummary();
</script>
