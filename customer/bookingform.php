<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
# default value supaya tak keluar "undefined index" / deprecated null masa first load
$errors = $errors ?? [];
$old = array_merge([
    'full_name'        => '',
    'phone'            => '',
    'email'            => '',
    'plate_num'        => '',
    'location'         => '',
    'check_in'         => '',
    'check_out'        => '',
    'total_guest'      => 1,
    'accommodation_id' => '',
    'addon_bbq'        => false,
    'addon_mattress'   => false,
    'special_request'  => '',
    'agree_terms'      => false,
    'whatsapp_optin'   => false,
], $old ?? []);
?>

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

        <?php # kotak error untuk validation kat browser (JS) ?>
        <div id="client-errors" role="alert" hidden style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;">
            <ul id="client-errors-list" style="margin-left:18px;"></ul>
        </div>

        <form id="booking-form" method="post" novalidate>
            <?php # calling function csrf_field() untuk bina hidden input token, elak CSRF attack masa submit form ?>
            <?= csrf_field() ?>

            <div class="booking-grid">
                <div class="booking-form">
                    <div class="form-field">
                        <label for="full-name">Full Name</label>
                        <input type="text" id="full-name" name="full_name" placeholder="Enter your full name" value="<?= htmlspecialchars((string) $old['full_name']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?= htmlspecialchars((string) $old['phone']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?= htmlspecialchars((string) $old['email']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="plate">Car Plate Number</label>
                        <input type="text" id="plate" name="plate_num" placeholder="Enter your car plate number" value="<?= htmlspecialchars((string) $old['plate_num']) ?>">
                    </div>
                    <div class="form-field">
                        <label for="location">Location (optional)</label>
                        <input type="text" id="location" name="location" placeholder="e.g. Kuala Lumpur, Malaysia" value="<?= htmlspecialchars((string) $old['location']) ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="check-in">Check-in</label>
                            <input type="date" id="check-in" name="check_in" value="<?= htmlspecialchars((string) $old['check_in']) ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="check-out">Check-out</label>
                            <input type="date" id="check-out" name="check_out" value="<?= htmlspecialchars((string) $old['check_out']) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="guests">Number of guests</label>
                            <input type="number" id="guests" name="total_guest" min="1" value="<?= htmlspecialchars((string) $old['total_guest']) ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="accommodation">Accommodation</label>
                            <select id="accommodation" name="accommodation_id" required>
                                <option value="">Select a package</option>
                                <?php # loop setiap accommodation dalam $accommodations untuk papar pilihan dalam dropdown ?>
                                <?php foreach ($accommodations as $acc): ?>
                                    <option value="<?= (int) $acc['accommodation_id'] ?>"
                                            data-name="<?= htmlspecialchars($acc['accommodation_name']) ?>"
                                            data-type="<?= htmlspecialchars($acc['accommodation_type']) ?>"
                                            data-price="<?= (float) $acc['price'] ?>"
                                            <?php # kalau price_weekend takde, guna price biasa sebagai fallback ?>
                                            data-price-weekend="<?= $acc['price_weekend'] !== null ? (float) $acc['price_weekend'] : (float) $acc['price'] ?>"
                                            data-capacity="<?= (int) $acc['capacity'] ?>"
                                            data-image="<?= htmlspecialchars($acc['image'] ?? '') ?>"
                                            data-rate-periods="<?= htmlspecialchars(json_encode($ratePeriodsByAccommodation[(int) $acc['accommodation_id']] ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                                            <?php # mark 'selected' balik pilihan lama lepas submit gagal ?>
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
                                <?php # kekalkan checkbox tercentang lepas submit gagal ?>
                                <input type="checkbox" id="addon-bbq" name="addon_bbq" <?= $old['addon_bbq'] ? 'checked' : '' ?>> BBQ Set <span class="addon-price">+RM <?= number_format(ADDON_BBQ_PRICE, 2) ?></span>
                            </label>
                            <label class="addon-option">
                                <input type="checkbox" id="addon-mattress" name="addon_mattress" <?= $old['addon_mattress'] ? 'checked' : '' ?>> Extra Mattress <span class="addon-price">+RM <?= number_format(ADDON_MATTRESS_PRICE, 2) ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="request">Special Request</label>
                        <textarea id="request" name="special_request" rows="4" placeholder="Any special request? (optional)"><?= htmlspecialchars((string) $old['special_request']) ?></textarea>
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
                    <input type="checkbox" id="agree-terms" name="agree_terms" <?= $old['agree_terms'] ? 'checked' : '' ?> required>
                    Saya telah membaca dan bersetuju dengan syarat tempahan di atas.
                </label>
                <label class="form-check">
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

<?php # script diletak SEBELUM footer supaya masih dalam <body>, dan dibungkus IIFE supaya tak clash dengan script lain ?>
<script>
(function () {
    'use strict';

    // pemalar dari PHP, hantar ke JS supaya boleh kira harga kat client side
    const ADDON_BBQ_PRICE = <?= json_encode((float) ADDON_BBQ_PRICE) ?>;
    const ADDON_MATTRESS_PRICE = <?= json_encode((float) ADDON_MATTRESS_PRICE) ?>;
    const LONG_STAY_DISCOUNT_MIN_NIGHTS = <?= json_encode((int) LONG_STAY_DISCOUNT_MIN_NIGHTS) ?>;
    const LONG_STAY_DISCOUNT_AMOUNT = <?= json_encode((float) LONG_STAY_DISCOUNT_AMOUNT) ?>;
    const BOOKING_DEPOSIT_AMOUNT = <?= json_encode((float) BOOKING_DEPOSIT_AMOUNT) ?>;

    const form = document.getElementById('booking-form');
    const accommodationSelect = document.getElementById('accommodation');
    const addonBbq = document.getElementById('addon-bbq');
    const addonMattress = document.getElementById('addon-mattress');
    const checkInInput = document.getElementById('check-in');
    const checkOutInput = document.getElementById('check-out');
    const guestsInput = document.getElementById('guests');
    const errBox = document.getElementById('client-errors');
    const errList = document.getElementById('client-errors-list');

    const money = (n) => 'RM ' + Number(n).toFixed(2);

    // format tarikh local YYYY-MM-DD (elak masalah timezone toISOString)
    function toISO(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function parseDate(str) {
        return str ? new Date(str + 'T00:00:00') : null;
    }

    function computeStay(checkInStr, checkOutStr, weekdayPrice, weekendPrice, ratePeriods) {
        let weekdayNights = 0, weekendNights = 0, specialNights = 0, total = 0;
        const rates = [];
        const labels = [];

        if (checkInStr && checkOutStr) {
            const cursor = parseDate(checkInStr);
            const end = parseDate(checkOutStr);
            while (cursor < end) {
                const nightDate = toISO(cursor);
                const specialRate = ratePeriods.find(p => nightDate >= p.start_date && nightDate <= p.end_date);
                let rate;
                if (specialRate) {
                    specialNights++;
                    rate = Number(specialRate.price);
                    if (specialRate.label && !labels.includes(specialRate.label)) labels.push(specialRate.label);
                } else if (cursor.getDay() === 5 || cursor.getDay() === 6) { // Jumaat & Sabtu malam = weekend
                    weekendNights++;
                    rate = weekendPrice;
                } else {
                    weekdayNights++;
                    rate = weekdayPrice;
                }
                total += rate;
                rates.push(rate);
                cursor.setDate(cursor.getDate() + 1);
            }
        }

        // takde tarikh sah lagi: tunjuk harga 1 malam sebagai preview
        if (weekdayNights + weekendNights + specialNights === 0) {
            weekdayNights = 1;
            total = weekdayPrice;
            rates.push(weekdayPrice);
        }
        return { weekdayNights, weekendNights, specialNights, total, rates, labels };
    }

    function updateSummary() {
        const opt = accommodationSelect.selectedOptions[0];
        const hasPackage = Boolean(opt && opt.value);
        const weekdayPrice = hasPackage ? Number(opt.dataset.price) : 0;
        const weekendPrice = hasPackage ? Number(opt.dataset.priceWeekend) : 0;
        let ratePeriods = [];
        try { ratePeriods = hasPackage ? JSON.parse(opt.dataset.ratePeriods || '[]') : []; } catch (e) { ratePeriods = []; }
        const capacity = hasPackage ? Number(opt.dataset.capacity) : 0;

        document.getElementById('summary-type').textContent = hasPackage ? opt.dataset.type : 'Villa';
        document.getElementById('summary-name').textContent = hasPackage ? opt.dataset.name : 'Select a package';
        document.getElementById('summary-capacity').textContent = capacity ? `Max ${capacity} guests` : '—';

        // had maksimum tetamu ikut capacity package
        if (capacity) { guestsInput.max = capacity; } else { guestsInput.removeAttribute('max'); }

        const thumb = document.getElementById('summary-thumb');
        const image = hasPackage ? opt.dataset.image : '';
        if (image) {
            thumb.style.backgroundImage = 'url("' + image.replace(/"/g, '%22') + '")';
            thumb.classList.add('has-image');
        } else {
            thumb.style.backgroundImage = '';
            thumb.classList.remove('has-image');
        }

        // had tarikh: check-in tak boleh lepas, check-out mesti sekurang-kurangnya sehari lepas check-in
        const todayISO = toISO(new Date());
        checkInInput.min = todayISO;
        const inDate = parseDate(checkInInput.value);
        if (inDate) {
            const minOut = new Date(inDate);
            minOut.setDate(minOut.getDate() + 1);
            checkOutInput.min = toISO(minOut);
        } else {
            checkOutInput.removeAttribute('min');
        }

        document.getElementById('s-name').textContent = document.getElementById('full-name').value || '—';
        document.getElementById('s-phone').textContent = document.getElementById('phone').value || '—';
        document.getElementById('s-email').textContent = document.getElementById('email').value || '—';
        document.getElementById('s-plate').textContent = (document.getElementById('plate').value || '—').toUpperCase();

        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;
        document.getElementById('s-checkin').textContent = checkIn || '—';
        document.getElementById('s-checkout').textContent = checkOut || '—';
        document.getElementById('s-guests').textContent = guestsInput.value || 1;

        const stay = computeStay(checkIn, checkOut, weekdayPrice, weekendPrice, ratePeriods);
        const totalNights = stay.weekdayNights + stay.weekendNights + stay.specialNights;
        const deposit = hasPackage && opt.dataset.type === 'Villa' ? BOOKING_DEPOSIT_AMOUNT : 0;
        document.getElementById('s-nights').textContent = totalNights;

        // label rate: satu harga, atau julat kalau harga berbeza (weekday/weekend/super peak)
        const minRate = Math.min(...stay.rates);
        const maxRate = Math.max(...stay.rates);
        let priceLabel = minRate === maxRate
            ? `${money(minRate)} / night`
            : `${money(minRate)} – ${money(maxRate)} / night`;
        if (stay.labels.length) priceLabel += ` (${stay.labels.join(', ')})`;

        const addonTotal = (addonBbq.checked ? ADDON_BBQ_PRICE : 0) + (addonMattress.checked ? ADDON_MATTRESS_PRICE : 0);
        const discount = (hasPackage && totalNights >= LONG_STAY_DISCOUNT_MIN_NIGHTS)
            ? Math.min(stay.total, LONG_STAY_DISCOUNT_AMOUNT)
            : 0;

        document.getElementById('s-price').textContent = priceLabel;
        document.getElementById('s-addons').textContent = money(addonTotal);
        document.getElementById('s-discount-row').hidden = discount <= 0;
        document.getElementById('s-discount').textContent = `-${money(discount)}`;
        document.getElementById('s-deposit').textContent = money(deposit);
        document.getElementById('s-total').textContent = money(stay.total + addonTotal + deposit - discount);
    }

    // ---------- validation kat browser (form ada novalidate) ----------
    function validate() {
        const errors = [];
        const opt = accommodationSelect.selectedOptions[0];
        const capacity = opt && opt.value ? Number(opt.dataset.capacity) : 0;
        const inDate = parseDate(checkInInput.value);
        const outDate = parseDate(checkOutInput.value);
        const guests = parseInt(guestsInput.value, 10);
        const today = parseDate(toISO(new Date()));

        if (!document.getElementById('full-name').value.trim()) errors.push('Full name is required.');
        const phone = document.getElementById('phone').value.trim();
        if (!phone) errors.push('Phone number is required.');
        else if (!/^[0-9+\-\s()]{8,16}$/.test(phone)) errors.push('Phone number is not valid.');
        const email = document.getElementById('email').value.trim();
        if (!email) errors.push('Email address is required.');
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Email address is not valid.');

        if (!inDate) errors.push('Check-in date is required.');
        else if (inDate < today) errors.push('Check-in date cannot be in the past.');
        if (!outDate) errors.push('Check-out date is required.');
        else if (inDate && outDate <= inDate) errors.push('Check-out must be after check-in.');

        if (!opt || !opt.value) errors.push('Please select an accommodation package.');
        if (!(guests >= 1)) errors.push('Number of guests must be at least 1.');
        else if (capacity && guests > capacity) errors.push(`This package allows up to ${capacity} guests only.`);

        if (!document.getElementById('agree-terms').checked) errors.push('You must agree to the booking terms.');
        return errors;
    }

    function showErrors(errors) {
        errList.innerHTML = '';
        errors.forEach(msg => {
            const li = document.createElement('li');
            li.textContent = msg; // textContent, selamat dari XSS
            errList.appendChild(li);
        });
        errBox.hidden = errors.length === 0;
        if (errors.length) errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ---------- preselect dari URL (?accommodation=Casa 1 / ?type=Villa) ----------
    // hanya bila belum ada pilihan, supaya tak override pilihan user lepas submit gagal
    if (!accommodationSelect.value) {
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
    }

    // bila check-in berubah dan check-out dah tak valid, auto betulkan
    checkInInput.addEventListener('change', function () {
        const inDate = parseDate(checkInInput.value);
        const outDate = parseDate(checkOutInput.value);
        if (inDate && outDate && outDate <= inDate) {
            const next = new Date(inDate);
            next.setDate(next.getDate() + 1);
            checkOutInput.value = toISO(next);
        }
    });

    form.addEventListener('input', updateSummary);
    form.addEventListener('change', updateSummary);
    form.addEventListener('submit', function (e) {
        const errors = validate();
        showErrors(errors);
        if (errors.length) e.preventDefault();
    });

    updateSummary();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>