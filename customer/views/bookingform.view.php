<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$errors = $errors ?? [];
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
        <?php # papar error dari server (kalau ada) ?>
        <?php if ($errors): ?>
            <div style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;">
                <ul style="margin-left:18px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php # error dari JavaScript (client-side) papar kat sini ?>
        <div id="client-errors" role="alert" hidden
             style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;">
            <ul id="client-errors-list" style="margin-left:18px;"></ul>
        </div>

        <form id="booking-form" method="post" novalidate>
            <?php # token CSRF ?>
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
                                <?php # loop semua accommodation untuk dropdown ?>
                                <?php foreach ($accommodations as $acc): ?>
                                    <option value="<?= (int) $acc['accommodation_id'] ?>"
                                            data-name="<?= htmlspecialchars($acc['accommodation_name']) ?>"
                                            data-type="<?= htmlspecialchars($acc['accommodation_type']) ?>"
                                            data-price="<?= (float) $acc['price'] ?>"
                                            data-price-weekend="<?= $acc['price_weekend'] !== null ? (float) $acc['price_weekend'] : (float) $acc['price'] ?>"
                                            data-capacity="<?= (int) $acc['capacity'] ?>"
                                            data-image="<?= htmlspecialchars($acc['image'] ?? '') ?>"
                                            data-rate-periods="<?= htmlspecialchars(json_encode($ratePeriodsByAccommodation[(int) $acc['accommodation_id']] ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $old['accommodation_id'] !== '' && (int) $old['accommodation_id'] === (int) $acc['accommodation_id'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($acc['accommodation_name']) ?> — RM <?= number_format((float) $acc['price'], 2) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-field">
                        <label>Add-ons (optional)</label>
                        <div class="addon-options">
                            <label class="addon-option">
                                <input type="checkbox" id="addon-bbq" name="addon_bbq" <?= !empty($old['addon_bbq']) ? 'checked' : '' ?>> BBQ Set <span class="addon-price">+RM <?= number_format(ADDON_BBQ_PRICE, 2) ?></span>
                            </label>
                            <label class="addon-option">
                                <input type="checkbox" id="addon-mattress" name="addon_mattress" <?= !empty($old['addon_mattress']) ? 'checked' : '' ?>> Extra Mattress <span class="addon-price">+RM <?= number_format(ADDON_MATTRESS_PRICE, 2) ?></span>
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
                        <div class="summary-row"><span>Add-ons</span><span id="s-addons">—</span></div>
                        <div class="summary-row"><span>Total</span><span id="s-total">RM 0.00</span></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Proceed to Payment</button>
                </aside>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    'use strict';

    // ---------- Config ----------
    var ADDON_BBQ_PRICE      = <?= json_encode((float) ADDON_BBQ_PRICE) ?>;
    var ADDON_MATTRESS_PRICE = <?= json_encode((float) ADDON_MATTRESS_PRICE) ?>;
    // Malam yang kira weekend rate: 0=Ahad, 1=Isnin ... 5=Jumaat, 6=Sabtu
    // (Jumaat & Sabtu malam = weekend. Tukar kalau hotel kau lain.)
    var WEEKEND_NIGHTS = [5, 6];

    // ---------- Elements ----------
    var form      = document.getElementById('booking-form');
    var fullName  = document.getElementById('full-name');
    var phone     = document.getElementById('phone');
    var email     = document.getElementById('email');
    var plate     = document.getElementById('plate');
    var checkIn   = document.getElementById('check-in');
    var checkOut  = document.getElementById('check-out');
    var guests    = document.getElementById('guests');
    var accSelect = document.getElementById('accommodation');
    var addonBbq  = document.getElementById('addon-bbq');
    var addonMat  = document.getElementById('addon-mattress');

    var el = function (id) { return document.getElementById(id); };
    var sThumb = el('summary-thumb'), sType = el('summary-type'), sNameBox = el('summary-name'), sCap = el('summary-capacity');
    var sName = el('s-name'), sPhone = el('s-phone'), sEmail = el('s-email'), sPlate = el('s-plate');
    var sIn = el('s-checkin'), sOut = el('s-checkout'), sGuests = el('s-guests'), sNights = el('s-nights');
    var sPrice = el('s-price'), sAddons = el('s-addons'), sTotal = el('s-total');
    var errBox = el('client-errors'), errList = el('client-errors-list');

    // ---------- Helpers ----------
    function money(n) {
        return 'RM ' + Number(n).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Parse "YYYY-MM-DD" sebagai tarikh LOCAL (elak masalah timezone UTC)
    function parseDate(str) {
        if (!str) return null;
        var p = str.split('-');
        if (p.length !== 3) return null;
        var d = new Date(+p[0], +p[1] - 1, +p[2]);
        return isNaN(d.getTime()) ? null : d;
    }

    function toISO(d) {
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    function addDays(d, n) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
    }

    function formatDate(d) {
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function diffNights(a, b) {
        return Math.round((b - a) / 86400000);
    }

    function today() {
        var t = new Date();
        return new Date(t.getFullYear(), t.getMonth(), t.getDate());
    }

    function setText(node, value) {
        node.textContent = (value === '' || value == null) ? '—' : value;
    }

    function getSelected() {
        var opt = accSelect.options[accSelect.selectedIndex];
        if (!opt || !opt.value) return null;
        var periods = [];
        try { periods = JSON.parse(opt.dataset.ratePeriods || '[]'); } catch (e) { periods = []; }
        return {
            id: opt.value,
            name: opt.dataset.name || '',
            type: opt.dataset.type || '',
            price: parseFloat(opt.dataset.price) || 0,
            priceWeekend: parseFloat(opt.dataset.priceWeekend) || 0,
            capacity: parseInt(opt.dataset.capacity, 10) || 0,
            image: opt.dataset.image || '',
            periods: Array.isArray(periods) ? periods : []
        };
    }

    // Cari rate period yang cover tarikh malam tu
    function findPeriod(acc, d) {
        var iso = toISO(d);
        for (var i = 0; i < acc.periods.length; i++) {
            var p = acc.periods[i];
            if (p.start_date && p.end_date && iso >= p.start_date && iso <= p.end_date) return p;
        }
        return null;
    }

    function nightRate(acc, d) {
        var weekend = WEEKEND_NIGHTS.indexOf(d.getDay()) !== -1;
        var period = findPeriod(acc, d);
        var base = acc.price, wk = acc.priceWeekend;
        if (period) {
            if (period.price != null && period.price !== '') base = parseFloat(period.price);
            wk = (period.price_weekend != null && period.price_weekend !== '')
                ? parseFloat(period.price_weekend)
                : base;
        }
        return weekend ? wk : base;
    }

    // ---------- Main update ----------
    function update() {
        // maklumat peribadi
        setText(sName, fullName.value.trim());
        setText(sPhone, phone.value.trim());
        setText(sEmail, email.value.trim());
        setText(sPlate, plate.value.trim().toUpperCase());

        var acc = getSelected();
        var inDate = parseDate(checkIn.value);
        var outDate = parseDate(checkOut.value);

        // had tarikh: check-out mesti lepas check-in
        checkIn.min = toISO(today());
        if (inDate) {
            checkOut.min = toISO(addDays(inDate, 1));
        } else {
            checkOut.min = toISO(addDays(today(), 1));
        }

        setText(sIn, inDate ? formatDate(inDate) : '');
        setText(sOut, outDate ? formatDate(outDate) : '');

        var g = parseInt(guests.value, 10);
        sGuests.textContent = (g > 0) ? g : 1;

        // package info
        if (acc) {
            sNameBox.textContent = acc.name;
            sType.textContent = acc.type;
            sCap.textContent = acc.capacity > 0 ? 'Up to ' + acc.capacity + ' guests' : '—';
            guests.max = acc.capacity > 0 ? acc.capacity : '';
            if (acc.image) {
                sThumb.style.backgroundImage = 'url("' + acc.image.replace(/"/g, '%22') + '")';
                sThumb.style.backgroundSize = 'cover';
                sThumb.style.backgroundPosition = 'center';
            } else {
                sThumb.style.backgroundImage = '';
            }
        } else {
            sNameBox.textContent = 'Select a package';
            sType.textContent = 'Villa';
            sCap.textContent = '—';
            guests.removeAttribute('max');
            sThumb.style.backgroundImage = '';
        }

        // kira malam & harga
        var nights = 0, roomTotal = 0, rateText = money(0);
        if (inDate && outDate && outDate > inDate) {
            nights = diffNights(inDate, outDate);
        }
        sNights.textContent = nights > 0 ? nights : 1;

        if (acc && nights > 0) {
            var min = Infinity, max = -Infinity;
            for (var i = 0; i < nights; i++) {
                var r = nightRate(acc, addDays(inDate, i));
                roomTotal += r;
                if (r < min) min = r;
                if (r > max) max = r;
            }
            rateText = (min === max)
                ? money(min) + ' / night'
                : money(min) + ' – ' + money(max) + ' / night';
        } else if (acc) {
            rateText = money(acc.price) + ' / night';
        }
        sPrice.textContent = rateText;

        // add-ons (kira sekali sahaja setiap booking)
        var addonLabels = [], addonTotal = 0;
        if (addonBbq.checked) { addonLabels.push('BBQ Set'); addonTotal += ADDON_BBQ_PRICE; }
        if (addonMat.checked) { addonLabels.push('Extra Mattress'); addonTotal += ADDON_MATTRESS_PRICE; }
        setText(sAddons, addonLabels.length ? addonLabels.join(', ') + ' (' + money(addonTotal) + ')' : '');

        sTotal.textContent = money(roomTotal + addonTotal);
    }

    // ---------- Validation (client-side, sebab form ada novalidate) ----------
    function validate() {
        var errors = [];
        var inDate = parseDate(checkIn.value);
        var outDate = parseDate(checkOut.value);
        var acc = getSelected();
        var g = parseInt(guests.value, 10);

        if (!fullName.value.trim()) errors.push('Full name is required.');
        if (!phone.value.trim()) {
            errors.push('Phone number is required.');
        } else if (!/^[0-9+\-\s()]{8,16}$/.test(phone.value.trim())) {
            errors.push('Phone number is not valid.');
        }
        if (!email.value.trim()) {
            errors.push('Email address is required.');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            errors.push('Email address is not valid.');
        }
        if (!inDate) {
            errors.push('Check-in date is required.');
        } else if (inDate < today()) {
            errors.push('Check-in date cannot be in the past.');
        }
        if (!outDate) {
            errors.push('Check-out date is required.');
        } else if (inDate && outDate <= inDate) {
            errors.push('Check-out must be after check-in.');
        }
        if (!acc) errors.push('Please select an accommodation package.');
        if (!(g >= 1)) {
            errors.push('Number of guests must be at least 1.');
        } else if (acc && acc.capacity > 0 && g > acc.capacity) {
            errors.push('This package allows up to ' + acc.capacity + ' guests only.');
        }
        return errors;
    }

    function showErrors(errors) {
        errList.innerHTML = '';
        errors.forEach(function (msg) {
            var li = document.createElement('li');
            li.textContent = msg; // textContent, selamat dari XSS
            errList.appendChild(li);
        });
        errBox.hidden = errors.length === 0;
        if (errors.length) errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ---------- Events ----------
    ['input', 'change'].forEach(function (evt) {
        form.addEventListener(evt, update);
    });

    // bila check-in berubah, auto betulkan check-out kalau dah tak valid
    checkIn.addEventListener('change', function () {
        var inDate = parseDate(checkIn.value);
        var outDate = parseDate(checkOut.value);
        if (inDate && outDate && outDate <= inDate) {
            checkOut.value = toISO(addDays(inDate, 1));
        }
        update();
    });

    form.addEventListener('submit', function (e) {
        var errors = validate();
        showErrors(errors);
        if (errors.length) e.preventDefault();
    });

    update(); // run sekali masa page load (untuk old values)
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>