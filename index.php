<?php
/**
 * Halaman utama sistem tempahan Casadive Villa.
 * Halaman ini memaparkan laman pendaratan utama, borang tempahan pantas, kemudahan, dan ulasan tetamu terkini.
 */
require_once __DIR__ . '/includes/db.php'; // sambung ke database dulu, dapat $pdo

// ambil 6 ulasan terbaru je untuk letak kat homepage, susun dari yang paling baru
$reviews = $pdo->query(
    "SELECT r.rating, r.comment, r.review_date, c.full_name
     FROM review r
     JOIN booking b ON b.booking_id = r.booking_id
     JOIN customer c ON c.customer_id = b.customer_id
     ORDER BY r.review_date DESC
     LIMIT 6"
)->fetchAll();

// data sebenar dari accommodation table untuk AI Assistant guna terus (tanpa round-trip) —
// senarai + julat harga untuk jawab "Villa Facilities"/harga. Untuk cadangan ikut tarikh/budget/
// bilangan tetamu (data yang berubah ikut input customer), chatbot panggil
// customer/chatbot_recommend.php yang guna recommend_accommodations() (includes/helpers.php).
$accommodationRows = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity,
            pax_label, features, description
     FROM accommodation WHERE status = 'available' ORDER BY accommodation_type, accommodation_id"
)->fetchAll();

$chatbotAccommodations = [];
$chatbotPriceRanges = [];
foreach ($accommodationRows as $row) {
    // takde column khas "ada pool/wifi/bbq" dalam DB, so kita cari perkataan tu dalam
    // features/description — teknik sama macam villa.php guna untuk icon kemudahan
    $searchText = strtolower(($row['features'] ?? '') . ' ' . ($row['description'] ?? ''));
    $chatbotAccommodations[] = [
        'id' => (int) $row['accommodation_id'],
        'name' => $row['accommodation_name'],
        'type' => $row['accommodation_type'],
        'price' => (float) $row['price'],
        'capacity' => (int) $row['capacity'],
        'pax_label' => $row['pax_label'] ?: ('Max ' . (int) $row['capacity'] . ' guests'),
        'has_pool' => strpos($searchText, 'pool') !== false,
        'has_wifi' => strpos($searchText, 'wifi') !== false,
        'has_bbq' => strpos($searchText, 'bbq') !== false,
    ];

    $type = $row['accommodation_type'];
    $price = (float) $row['price'];
    if (!isset($chatbotPriceRanges[$type])) {
        $chatbotPriceRanges[$type] = ['min' => $price, 'max' => $price];
    } else {
        $chatbotPriceRanges[$type]['min'] = min($chatbotPriceRanges[$type]['min'], $price);
        $chatbotPriceRanges[$type]['max'] = max($chatbotPriceRanges[$type]['max'], $price);
    }
}

$chatbotData = [
    'accommodations' => $chatbotAccommodations,
    'priceRanges' => $chatbotPriceRanges,
    // alamat sebenar, sama seperti dipaparkan di contact_us.php — bukan direka
    'address' => 'PT 195, Kg Baru, Kampung Pulau Sayak, 08500 Kota Kuala Muda, Kedah',
    'whatsapp' => '60103851892',
];

$base = ''; // kita kat root folder, so path takyah naik satu tahap
$active = 'home'; // untuk highlight menu "Home" kat navbar
$pageTitle = 'Casadive Villa — Spend your Dream Holidays with us';
$pageCss = 'style/index.css'; // CSS khas untuk page ni
include __DIR__ . '/includes/header.php'; // papar navbar + head html
?>

  <!-- HERO -->
  <section class="hero" id="home" style="background-image:linear-gradient(0deg, rgba(0,0,0,.35), rgba(0,0,0,.15)), url('assets/images/beach-villa-bright.jpg');background-size:cover;background-position:center;">
    <div class="container">
      <p class="hero-eyebrow">Casadive Villa</p>
      <h1 class="hero-title">Spend your Dream Holidays with us</h1>
      <p class="hero-sub">Every moment feels like the first time in Casadive Villa</p>
      <button class="play-btn" type="button" aria-label="Watch video">
        <span class="play-circle">
          <svg viewBox="0 0 12 17" xmlns="http://www.w3.org/2000/svg"><path d="M0 0 L12 8.5 L0 17 Z" fill="#fff"/></svg>
        </span>
        <span>Watch Video</span>
      </button>
    </div>
  </section>

  <!-- QUICK BOOKING BAR -->
  <div class="booking-wrap">
    <div class="container">
      <form class="booking-bar" method="get" action="customer/villa.php" id="quick-booking-form">
        <div class="booking-field">
          <svg viewBox="0 0 20 23" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="3" width="18" height="19" rx="2" fill="none" stroke="#1C1C1C" stroke-width="1.6"/><line x1="1" y1="9" x2="19" y2="9" stroke="#1C1C1C" stroke-width="1.6"/><line x1="5" y1="1" x2="5" y2="5" stroke="#1C1C1C" stroke-width="1.6"/><line x1="15" y1="1" x2="15" y2="5" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-checkin">Check in</label>
            <input class="field-value" type="date" id="qb-checkin" name="check_in" required>
          </span>
        </div>
        <div class="booking-field">
          <svg viewBox="0 0 20 23" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="3" width="18" height="19" rx="2" fill="none" stroke="#1C1C1C" stroke-width="1.6"/><line x1="1" y1="9" x2="19" y2="9" stroke="#1C1C1C" stroke-width="1.6"/><line x1="5" y1="1" x2="5" y2="5" stroke="#1C1C1C" stroke-width="1.6"/><line x1="15" y1="1" x2="15" y2="5" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-checkout">Check out</label>
            <input class="field-value" type="date" id="qb-checkout" name="check_out" required>
          </span>
        </div>
        <div class="booking-field">
          <svg viewBox="0 0 20 23" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="6" r="4.2" fill="none" stroke="#1C1C1C" stroke-width="1.6"/><path d="M2 22c0-5 4-8 8-8s8 3 8 8" fill="none" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-guests">Person</label>
            <input class="field-value" type="number" id="qb-guests" name="guests" min="1" value="1" required>
          </span>
        </div>
        <div class="booking-field">
          <svg viewBox="0 0 22 23" xmlns="http://www.w3.org/2000/svg"><path d="M2 12 L11 3 L20 12 L20 21 L2 21 Z" fill="none" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-type">Type</label>
            <select class="field-value" id="qb-type" name="type">
              <option value="Villa">Villa</option>
              <option value="Campsite">Campsite</option>
            </select>
          </span>
        </div>
        <button type="submit" class="booking-cta">Check Availability</button>
      </form>
    </div>
  </div>

  <!-- WELCOME -->
  <section class="welcome" id="villa">
    <div class="container">
      <div class="welcome-photos" aria-hidden="true">
        <div class="photo-a" style="background-image:url('assets/images/beach-casa3.jpg');background-size:cover;background-position:center;"></div>
        <div class="photo-b" style="background-image:url('assets/images/bedroom-modern-fan.jpg');background-size:cover;background-position:center;"></div>
      </div>
      <div class="welcome-copy">
        <p class="welcome-script">Welcome</p>
        <h2 class="welcome-heading">to Casadive Villa</h2>
        <p class="welcome-text">
          The perfect destination for a relaxing getaway. Take a break from your daily routine and enjoy a peaceful environment with beautiful surroundings. Our villa provides a comfortable place for you and your loved ones to rest, relax, and enjoy unforgettable moments together. Whether it is a family vacation or a short escape with friends, Casadive Villa is ready to give you a memorable experience filled with comfort and happiness.
        </p>
        <a href="customer/gallery.php" class="btn btn-primary btn-round">View More</a>
      </div>
    </div>
  </section>

  <!-- FACILITIES -->
  <section class="facilities" id="facilities">
    <div class="container">
      <h2>Our Facilities</h2>
      <p class="subtitle">Everything you need for a comfortable and memorable stay</p>
      <div class="facility-grid">
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><path d="M4 34c3 0 3-4 6-4s3 4 6 4 3-4 6-4 3 4 6 4 3-4 6-4 3 4 6 4v6H4v-6z"/><circle cx="18" cy="14" r="6"/><path d="M6 26h20v6H6z" opacity=".5"/></svg>
          <span>Swimming pool</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><path d="M24 36a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM8 20c9-9 23-9 32 0l-4 4c-7-7-17-7-24 0l-4-4zM14 26c6-6 14-6 20 0l-4 4c-4-4-8-4-12 0l-4-4zM20 32c3-3 5-3 8 0l-4 4-4-4z"/></svg>
          <span>Wifi</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><rect x="8" y="6" width="32" height="36" rx="4" fill="none" stroke="currentColor" stroke-width="3"/><path d="M18 14h8a6 6 0 0 1 0 12h-8V14zm0 12v10" fill="none" stroke="currentColor" stroke-width="3"/></svg>
          <span>Parking space</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><circle cx="24" cy="24" r="16" fill="none" stroke="currentColor" stroke-width="3"/><path d="M14 24h20M24 14v20" stroke="currentColor" stroke-width="3"/></svg>
          <span>Bbq set</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><path d="M6 40c6-4 10-4 16 0s10 4 16 0" fill="none" stroke="currentColor" stroke-width="3"/><path d="M24 6c4 8 4 16 0 24" fill="none" stroke="currentColor" stroke-width="3"/><circle cx="24" cy="6" r="2.5"/></svg>
          <span>Beach</span>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section class="testimonials">
    <div class="container">
      <h2>What Our Guests Say</h2>

      <?php if ($reviews): ?>
      <div class="testimonial-track">
        <?php foreach ($reviews as $review): ?>
        <article class="testimonial-card">
          <div class="stars" data-rating="<?= (int) $review['rating'] ?>" aria-label="<?= (int) $review['rating'] ?> out of 5 stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star">&#9733;</span>
            <?php endfor; ?>
          </div>
          <p class="testimonial-date"><?= htmlspecialchars(date('j M. Y', strtotime($review['review_date']))) ?></p>
          <p class="testimonial-quote"><?= htmlspecialchars($review['comment'] ?: 'Great stay!') ?></p>
          <div class="testimonial-person">
            <div class="avatar"></div>
            <span><?= htmlspecialchars($review['full_name']) ?></span>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="no-reviews">No reviews yet — be the first to stay with us and share your experience!</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- AI ASSISTANT (scripted, rule-based — answers pulled from real accommodation data) -->
  <div class="chatbot-widget">
    <button type="button" class="chatbot-launcher" id="chatbot-launcher" aria-label="Open AI Assistant" aria-expanded="false">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v12H7l-3 3V4z" fill="none" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><circle cx="9" cy="10" r="1" fill="#fff"/><circle cx="12" cy="10" r="1" fill="#fff"/><circle cx="15" cy="10" r="1" fill="#fff"/></svg>
      <span>Casadive Villa Assistant</span>
    </button>

    <div class="chatbot-panel" id="chatbot-panel">
      <div class="chatbot-header">
        <span>Casadive Villa Assistant</span>
        <button type="button" class="chatbot-close" id="chatbot-close" aria-label="Close chat">&times;</button>
      </div>
      <div class="chatbot-log" id="chatbot-log" aria-live="polite"></div>
      <div class="chatbot-quickreplies" id="chatbot-quickreplies">
        <button type="button" class="chip" data-action="check_availability">Check Availability</button>
        <button type="button" class="chip" data-action="recommend">Recommend a Villa</button>
        <button type="button" class="chip" data-action="facilities">Villa Facilities</button>
        <button type="button" class="chip" data-action="booking">Booking</button>
        <button type="button" class="chip" data-action="payment">Payment</button>
        <button type="button" class="chip" data-action="checkinout">Check-in / Check-out</button>
        <button type="button" class="chip" data-action="rules">House Rules</button>
        <button type="button" class="chip" data-action="contact">Contact Staff</button>
      </div>
      <form class="chatbot-input-row" id="chatbot-form">
        <input type="text" id="chatbot-input" placeholder="Ask a question..." autocomplete="off" aria-label="Type your question">
        <button type="submit">Send</button>
      </form>
    </div>
  </div>

<script>
  // butang "Check Availability" kena bawa pelanggan ke page Villa ke Campsite,
  // ikut apa yang dia pilih dekat dropdown Type — tukar action borang sebelum submit
  var quickBookingForm = document.getElementById('quick-booking-form');
  var quickBookingType = document.getElementById('qb-type');
  function updateQuickBookingAction() {
    quickBookingForm.action = quickBookingType.value === 'Campsite' ? 'customer/campsite.php' : 'customer/villa.php';
  }
  quickBookingType.addEventListener('change', updateQuickBookingAction);
  updateQuickBookingAction(); // set betul-betul dari awal, ikut pilihan default pun

  // PHP dah papar 5 bintang penuh untuk setiap ulasan, JS ni je yang "padamkan" warna
  // bintang lepas rating sebenar (contoh: rating 3 bintang, so bintang ke-4 & ke-5 jadi pudar)
  document.querySelectorAll('.stars[data-rating]').forEach(function (starsEl) {
    var rating = parseInt(starsEl.dataset.rating, 10) || 0; // rating ulasan ni berapa bintang
    starsEl.querySelectorAll('.star').forEach(function (star, index) {
      if (index >= rating) {
        star.classList.add('star-empty'); // bintang lepas rating, buat pudar
      }
    });
  });

  // ---------- AI ASSISTANT ----------
  // Scripted/rule-based assistant — keyword matching + a small multi-turn slot-filling state
  // machine, NOT a real LLM (no external AI API call, no per-message cost). Villa facts/prices
  // come from CHATBOT_DATA (real accommodation rows, built server-side in index.php).
  // Live availability/recommendation and booking-status lookups call real DB-backed endpoints
  // (customer/chatbot_recommend.php, customer/chatbot_booking_status.php) — never invented.
  var CHATBOT_DATA = <?= json_encode($chatbotData) ?>;
  var WHATSAPP_NUMBER = CHATBOT_DATA.whatsapp;
  var FALLBACK_EN = "Sorry, I don't have that information right now. Please contact CasaDive Villa staff for more details.";
  var FALLBACK_MS = 'Maaf, saya tidak mempunyai maklumat tersebut buat masa ini. Sila hubungi staff CasaDive Villa untuk maklumat lanjut.';

  var chatbotLauncher = document.getElementById('chatbot-launcher');
  var chatbotPanel = document.getElementById('chatbot-panel');
  var chatbotClose = document.getElementById('chatbot-close');
  var chatbotLog = document.getElementById('chatbot-log');
  var chatbotForm = document.getElementById('chatbot-form');
  var chatbotInput = document.getElementById('chatbot-input');
  var chatbotQuickreplies = document.getElementById('chatbot-quickreplies');
  var chatbotGreeted = false;

  var chatLang = 'en'; // 'en' | 'ms' — flips based on detected language of free-typed messages
  var chatIntent = null; // null | 'reco_guests' | 'reco_dates' | 'booking_id' | 'booking_phone'
  var recoSlots = { guests: null, checkIn: null, checkOut: null, budget: null, facility: null };
  var bookingSlots = { ref: null };

  function T(en, ms) { return chatLang === 'ms' ? ms : en; }

  function addChatMessage(html, sender) {
    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + sender;
    bubble.innerHTML = html;
    chatbotLog.appendChild(bubble);
    chatbotLog.scrollTop = chatbotLog.scrollHeight;
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function showTyping() {
    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble bot typing';
    bubble.id = 'chatbot-typing';
    bubble.innerHTML = '<span></span><span></span><span></span>';
    chatbotLog.appendChild(bubble);
    chatbotLog.scrollTop = chatbotLog.scrollHeight;
  }

  function hideTyping() {
    var el = document.getElementById('chatbot-typing');
    if (el) el.remove();
  }

  // papar SATU indicator "typing...", lepas tu letak semua bubble bot sekali gus —
  // elak typing indicator berkelip-kelip untuk jawapan yang ada banyak bubble
  function botSayMulti(htmlList, delay) {
    showTyping();
    return new Promise(function (resolve) {
      setTimeout(function () {
        hideTyping();
        htmlList.forEach(function (html) { addChatMessage(html, 'bot'); });
        resolve();
      }, delay || 550);
    });
  }

  function botSay(html, delay) {
    return botSayMulti([html], delay);
  }

  function whatsappChip(question) {
    var text = encodeURIComponent(question || "Hi, I'd like some help with a booking.");
    return '<a href="https://wa.me/' + WHATSAPP_NUMBER + '?text=' + text + '" target="_blank" rel="noopener" class="chip">' + T('Chat on WhatsApp', 'Chat di WhatsApp') + '</a>';
  }

  function priceRangeText(type) {
    var facts = CHATBOT_DATA.priceRanges[type];
    if (!facts) {
      return T("Sorry, we don't have any " + type.toLowerCase() + " packages available right now.",
                'Maaf, tiada pakej ' + type.toLowerCase() + ' yang tersedia buat masa ini.');
    }
    return T(
      'Our ' + type.toLowerCase() + ' packages range from RM ' + facts.min.toFixed(0) + ' to RM ' + facts.max.toFixed(0) + ' per night.',
      'Pakej ' + type.toLowerCase() + ' kami bermula dari RM ' + facts.min.toFixed(0) + ' hingga RM ' + facts.max.toFixed(0) + ' semalam.'
    );
  }

  // ---- bahasa: kira perkataan penanda Melayu vs Inggeris, tukar chatLang kalau jelas menang ----
  var MS_MARKERS = ['ada','boleh','nak','saya','awak','anda','tak','tidak','macam','berapa','bila','apa','sila','kosong','bilik','malam','orang','dewasa','kanak','tarikh','esok','cadang','sesuai','tempahan','tempah','bayar','deposit','batal','hubungi','kemudahan','peraturan','lokasi','mana','bawa','pukul','hari','ini','itu','saya','dgn','dengan','untuk'];
  var EN_MARKERS = ['the','is','are','can','want','room','night','please','thank','available','book','price','when','how','what','where','guest','recommend','staying','cancel'];

  function detectLanguage(text) {
    var msg = ' ' + text.toLowerCase() + ' ';
    var msScore = 0, enScore = 0;
    MS_MARKERS.forEach(function (w) { if (msg.indexOf(' ' + w + ' ') !== -1) msScore++; });
    EN_MARKERS.forEach(function (w) { if (msg.indexOf(' ' + w + ' ') !== -1) enScore++; });
    if (msScore > enScore) return 'ms';
    if (enScore > msScore) return 'en';
    return null;
  }

  // ---- extraction helpers (scoped pattern matching, not general NLU) ----
  function extractNumber(text) {
    var m = text.match(/\d+/);
    return m ? parseInt(m[0], 10) : null;
  }

  function extractBudget(text) {
    var m = text.match(/rm\s?(\d+(?:\.\d+)?)/i) || text.match(/budget\D{0,10}(\d+(?:\.\d+)?)/i);
    return m ? parseFloat(m[1]) : null;
  }

  function extractFacility(text) {
    var msg = text.toLowerCase();
    if (msg.indexOf('pool') !== -1 || msg.indexOf('kolam') !== -1) return 'pool';
    if (msg.indexOf('wifi') !== -1) return 'wifi';
    if (msg.indexOf('bbq') !== -1 || msg.indexOf('barbeku') !== -1) return 'bbq';
    return null;
  }

  var MONTH_NAMES = {
    jan: 0, januari: 0, january: 0, feb: 1, februari: 1, february: 1, mac: 2, mar: 2, march: 2,
    apr: 3, april: 3, mei: 4, may: 4, jun: 5, june: 5, jul: 6, julai: 6, july: 6,
    ogos: 7, aug: 7, august: 7, sep: 8, sept: 8, september: 8, okt: 9, oct: 9, october: 9,
    nov: 10, november: 10, dis: 11, dec: 11, disember: 11, december: 11
  };

  function pad2(n) { return n < 10 ? '0' + n : '' + n; }
  function toIsoDate(d) { return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()); }

  function inferYear(month, day) {
    var now = new Date();
    var candidate = new Date(now.getFullYear(), month, day);
    return candidate < now ? now.getFullYear() + 1 : now.getFullYear();
  }

  function parseOneDate(str) {
    str = str.trim();
    var m = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (m) return new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));

    m = str.match(/^(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{4}))?$/);
    if (m) {
      var day = parseInt(m[1], 10), month = parseInt(m[2], 10) - 1;
      var year = m[3] ? parseInt(m[3], 10) : inferYear(month, day);
      return new Date(year, month, day);
    }

    m = str.match(/^(\d{1,2})\s+([a-zA-Z]+)\s*(\d{4})?$/);
    if (m && MONTH_NAMES.hasOwnProperty(m[2].toLowerCase())) {
      var day2 = parseInt(m[1], 10), month2 = MONTH_NAMES[m[2].toLowerCase()];
      var year2 = m[3] ? parseInt(m[3], 10) : inferYear(month2, day2);
      return new Date(year2, month2, day2);
    }
    return null;
  }

  var DATE_TOKEN_RE = /(\d{4}-\d{1,2}-\d{1,2})|(\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{4})?)|(\d{1,2}\s+[a-zA-Z]+(?:\s+\d{4})?)/g;

  function extractDateRange(text) {
    var raw = text.match(DATE_TOKEN_RE) || [];
    var dates = [];
    for (var i = 0; i < raw.length && dates.length < 2; i++) {
      var d = parseOneDate(raw[i]);
      if (d) dates.push(d);
    }
    if (dates.length < 2 || dates[1] <= dates[0]) return null;
    return { checkIn: toIsoDate(dates[0]), checkOut: toIsoDate(dates[1]) };
  }

  // ---- static FAQ content (grounded in real data; ungrounded topics use the required fallback) ----
  function showFacilities() {
    var htmls = [T('Here are our available villas & campsites:', 'Berikut senarai villa & campsite yang tersedia:')];
    CHATBOT_DATA.accommodations.forEach(function (a) {
      var bits = [];
      if (a.has_pool) bits.push(T('pool', 'kolam renang'));
      if (a.has_wifi) bits.push('wifi');
      if (a.has_bbq) bits.push('BBQ');
      htmls.push(
        '<strong>' + escapeHtml(a.name) + '</strong> (' + a.type + ', ' + escapeHtml(a.pax_label) + ') &mdash; RM ' + a.price.toFixed(0) + '/' + T('night', 'malam') +
        (bits.length ? '<br>' + T('Facilities', 'Kemudahan') + ': ' + bits.join(', ') : '')
      );
    });
    return botSayMulti(htmls);
  }

  function showPaymentInfo() {
    chatIntent = 'booking_id';
    return botSayMulti([
      T(
        'We accept payment via QR code or online banking. A deposit confirms your booking (status changes to "Confirmed"); the remaining balance is settled at check-in.',
        'Kami menerima pembayaran melalui QR code atau online banking. Deposit akan mengesahkan tempahan anda (status bertukar kepada "Confirmed"); baki bayaran diselesaikan semasa check-in.'
      ),
      T('Want to check the payment status of a specific booking? Just tell me your Booking ID.', 'Nak semak status pembayaran tempahan tertentu? Beritahu saya Booking ID anda.')
    ]);
  }

  function showCheckInOut() {
    return botSayMulti([
      T('Check-in is after 3.00 PM, and check-out is before 12.00 PM.', 'Waktu check-in adalah selepas 3.00 PTG, dan check-out sebelum 12.00 TGH.'),
      T('For early check-in / late check-out requests: ' + FALLBACK_EN, 'Untuk permintaan early check-in / late check-out: ' + FALLBACK_MS)
    ]);
  }

  function showLocation() {
    var mapUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent('Casadive Villa, ' + CHATBOT_DATA.address);
    return botSayMulti([
      T('CasaDive Villa is located at: ' + CHATBOT_DATA.address, 'CasaDive Villa terletak di: ' + CHATBOT_DATA.address),
      '<a href="' + mapUrl + '" target="_blank" rel="noopener" class="chip">' + T('Get Directions', 'Dapatkan Arah') + '</a>',
      T('For nearby attractions or restaurants: ' + FALLBACK_EN, 'Untuk tempat menarik atau restoran berdekatan: ' + FALLBACK_MS)
    ]);
  }

  function showHouseRules() {
    return botSayMulti([
      T('A BBQ area/set is available as one of our facilities.', 'Kawasan/set BBQ tersedia sebagai salah satu kemudahan kami.'),
      T('For other house rules (smoking, pets, parties, noise, maximum guests): ' + FALLBACK_EN, 'Untuk peraturan lain (merokok, haiwan peliharaan, majlis, kebisingan, had tetamu maksimum): ' + FALLBACK_MS)
    ]);
  }

  function showHowToBook() {
    return botSay(T(
      "Booking is easy: pick a villa or campsite package, fill in the booking form with your dates and details, then pay the deposit. You'll get a booking reference to check your status anytime.",
      'Tempahan sangat mudah: pilih pakej villa atau campsite, isi borang tempahan dengan tarikh dan butiran anda, kemudian bayar deposit. Anda akan menerima nombor rujukan untuk semak status bila-bila masa.'
    ));
  }

  function showCancelInfo() {
    return botSayMulti([
      T(
        'You can cancel a booking yourself: go to MyBooking, look it up with your booking reference and phone number, then click "Cancel Booking". Deposit refunds are then handled manually by our team.',
        'Anda boleh batalkan tempahan sendiri: pergi ke MyBooking, cari dengan nombor rujukan dan telefon anda, kemudian klik "Cancel Booking". Refund deposit akan diuruskan secara manual oleh pasukan kami selepas itu.'
      ),
      '<a href="customer/mybooking.php" class="chip">' + T('Open MyBooking', 'Buka MyBooking') + '</a>'
    ]);
  }

  function showBookingMenu() {
    return botSayMulti([
      T('Sure — what would you like to do?', 'Baik — apa yang anda ingin lakukan?'),
      '<button type="button" class="chip" data-action="how_to_book">' + T('How to Book', 'Cara Tempah') + '</button> ' +
      '<button type="button" class="chip" data-action="check_booking">' + T('Check My Booking', 'Semak Tempahan Saya') + '</button> ' +
      '<button type="button" class="chip" data-action="cancel_booking">' + T('Cancel a Booking', 'Batal Tempahan') + '</button>'
    ]);
  }

  function contactStaff() {
    return botSayMulti([
      T("Sure — I'll connect you to our team on WhatsApp.", 'Baik — saya akan hubungkan anda dengan pasukan kami di WhatsApp.'),
      whatsappChip(T('Hi, I need help from CasaDive Villa staff.', 'Hai, saya perlukan bantuan daripada staff CasaDive Villa.'))
    ]);
  }

  // ---- booking status flow (Booking ID + phone = same shared-secret gate as MyBooking) ----
  function startBookingStatus() {
    bookingSlots = { ref: null };
    chatIntent = 'booking_id';
    return botSay(T('Sure! What is your Booking ID / reference number?', 'Baik! Apakah nombor rujukan/Booking ID tempahan anda?'));
  }

  function handleBookingId(text) {
    var ref = extractNumber(text);
    if (!ref) {
      return botSay(T('Please enter a valid Booking ID (numbers only), e.g. 12.', 'Sila masukkan Booking ID yang sah (nombor sahaja), cth: 12.'));
    }
    bookingSlots.ref = ref;
    chatIntent = 'booking_phone';
    return botSay(T('Thanks! Now, what is the phone number you used when booking?', 'Baik! Sekarang, apakah nombor telefon yang anda gunakan semasa membuat tempahan?'));
  }

  function handleBookingPhone(text) {
    var phone = text.replace(/[^\d]/g, '');
    chatIntent = null;
    showTyping();
    return fetch('customer/chatbot_booking_status.php?ref=' + encodeURIComponent(bookingSlots.ref) + '&phone=' + encodeURIComponent(phone))
      .then(function (res) { return res.json(); })
      .then(function (data) {
        hideTyping();
        if (!data.found) {
          addChatMessage(T(
            "I couldn't find a booking with that reference and phone number. Please double-check and try again, or contact our staff.",
            'Maaf, saya tidak jumpa tempahan dengan rujukan dan nombor telefon tersebut. Sila semak semula, atau hubungi staff kami.'
          ), 'bot');
          addChatMessage(whatsappChip(T('Hi, I need help checking my booking status.', 'Hai, saya perlukan bantuan menyemak status tempahan saya.')), 'bot');
          return;
        }
        var balanceLine = data.balance_due > 0
          ? (T(' (Balance due: RM ', ' (Baki: RM ') + data.balance_due.toFixed(2) + ')')
          : '';
        addChatMessage(
          T('Booking #', 'Tempahan #') + data.booking_id + ': <strong>' + data.status + '</strong><br>' +
          T('Villa/Campsite', 'Villa/Campsite') + ': ' + escapeHtml(data.accommodations) + '<br>' +
          T('Check-in', 'Check-in') + ': ' + data.check_in + ' &middot; ' + T('Check-out', 'Check-out') + ': ' + data.check_out + '<br>' +
          T('Guests', 'Bilangan Tetamu') + ': ' + data.total_guest + '<br>' +
          T('Payment', 'Pembayaran') + ': ' + (data.payment_status || T('No record yet', 'Belum ada rekod')) + balanceLine,
          'bot'
        );
      })
      .catch(function () {
        hideTyping();
        addChatMessage(T('Sorry, something went wrong. Please try again later.', 'Maaf, ada masalah teknikal. Sila cuba lagi kemudian.'), 'bot');
      });
  }

  // ---- recommendation / availability flow ----
  function startRecommendation(text) {
    recoSlots = { guests: null, checkIn: null, checkOut: null, budget: null, facility: extractFacility(text) };
    var guests = extractNumber(text);
    if (guests) {
      recoSlots.guests = guests;
      chatIntent = 'reco_dates';
      return botSay(T(
        'Great, ' + guests + ' guests. Could you share your check-in and check-out dates? (e.g. 20/09/2026 - 22/09/2026). Type "skip" if you don\'t have dates yet.',
        'Baik, ' + guests + ' orang. Boleh berikan tarikh check-in dan check-out? (cth: 20/09/2026 - 22/09/2026). Taip "skip" jika belum ada tarikh.'
      ));
    }
    chatIntent = 'reco_guests';
    return botSay(T('Sure! How many guests will be staying?', 'Baik! Berapa orang tetamu semua?'));
  }

  function startAvailability(text) {
    recoSlots = { guests: extractNumber(text) || 1, checkIn: null, checkOut: null, budget: null, facility: extractFacility(text) };
    var range = extractDateRange(text);
    if (range) {
      recoSlots.checkIn = range.checkIn;
      recoSlots.checkOut = range.checkOut;
      chatIntent = null;
      return runRecommendation();
    }
    chatIntent = 'reco_dates';
    return botSay(T(
      'Sure! What check-in and check-out dates would you like to check? (e.g. 20/09/2026 - 22/09/2026)',
      'Baik! Apakah tarikh check-in dan check-out yang anda ingin semak? (cth: 20/09/2026 - 22/09/2026)'
    ));
  }

  function handleRecoGuests(text) {
    var guests = extractNumber(text);
    if (!guests || guests < 1) {
      return botSay(T('Please enter a valid number of guests, e.g. 5.', 'Sila masukkan bilangan tetamu yang sah, cth: 5.'));
    }
    recoSlots.guests = guests;
    var facility = extractFacility(text);
    if (facility) recoSlots.facility = facility;
    chatIntent = 'reco_dates';
    return botSay(T(
      'Got it — ' + guests + ' guests. Do you have check-in and check-out dates? (e.g. 20/09/2026 - 22/09/2026). Type "skip" if not yet.',
      'Baik — ' + guests + ' orang. Ada tarikh check-in dan check-out? (cth: 20/09/2026 - 22/09/2026). Taip "skip" jika belum ada.'
    ));
  }

  function handleRecoDates(text) {
    var skip = /\bskip\b|\btak tau\b|\bbelum\b|\btiada\b/i.test(text);
    var range = skip ? null : extractDateRange(text);
    if (!skip && !range) {
      return botSay(T(
        'Sorry, I could not read those dates. Please use a format like 20/09/2026 - 22/09/2026, or type "skip".',
        'Maaf, saya tak dapat baca tarikh tersebut. Sila guna format seperti 20/09/2026 - 22/09/2026, atau taip "skip".'
      ));
    }
    if (range) {
      recoSlots.checkIn = range.checkIn;
      recoSlots.checkOut = range.checkOut;
    }
    var budget = extractBudget(text);
    if (budget) recoSlots.budget = budget;
    var facility = extractFacility(text);
    if (facility) recoSlots.facility = facility;

    chatIntent = null;
    return runRecommendation();
  }

  function runRecommendation() {
    showTyping();
    var params = new URLSearchParams();
    params.set('guests', recoSlots.guests);
    if (recoSlots.checkIn) params.set('check_in', recoSlots.checkIn);
    if (recoSlots.checkOut) params.set('check_out', recoSlots.checkOut);
    if (recoSlots.budget) params.set('budget', recoSlots.budget);
    if (recoSlots.facility) params.set('facility', recoSlots.facility);

    return fetch('customer/chatbot_recommend.php?' + params.toString())
      .then(function (res) { return res.json(); })
      .then(function (data) {
        hideTyping();
        if (!data.results || !data.results.length) {
          addChatMessage(T(
            "Sorry, I couldn't find a package that fits those requirements right now. Try adjusting the guest count, budget, or dates, or contact our staff.",
            'Maaf, tiada pakej yang sesuai dengan keperluan tersebut buat masa ini. Cuba ubah bilangan tetamu, budget atau tarikh, atau hubungi staff kami.'
          ), 'bot');
          addChatMessage(whatsappChip(T('Hi, I need help finding a suitable villa.', 'Hai, saya perlukan bantuan mencari villa yang sesuai.')), 'bot');
          return;
        }
        addChatMessage(T('Here are my top picks for you:', 'Ini pilihan terbaik untuk anda:'), 'bot');
        data.results.forEach(function (r) {
          var facilityBits = [];
          if (r.has_pool) facilityBits.push(T('pool', 'kolam renang'));
          if (r.has_wifi) facilityBits.push('wifi');
          if (r.has_bbq) facilityBits.push('BBQ');
          var bookLink = 'customer/bookingform.php?accommodation=' + encodeURIComponent(r.name) + '&guests=' + recoSlots.guests +
            (recoSlots.checkIn ? '&check_in=' + recoSlots.checkIn + '&check_out=' + recoSlots.checkOut : '');
          var html =
            '<strong>' + escapeHtml(r.name) + '</strong> (' + r.type + ', ' + escapeHtml(r.pax_label) + ')<br>' +
            'RM ' + r.price.toFixed(0) + ' ' + T('per night', 'semalam') +
            (facilityBits.length ? '<br>' + T('Facilities', 'Kemudahan') + ': ' + facilityBits.join(', ') : '') +
            '<br><small>' + r.reasons.join(' &middot; ') + '</small><br>' +
            '<a href="customer/detail.php?id=' + r.id + '" class="chip">' + T('View Detail', 'Lihat Butiran') + '</a> ' +
            '<a href="' + bookLink + '" class="chip">' + T('Book Now', 'Tempah Sekarang') + '</a>';
          addChatMessage(html, 'bot');
        });
      })
      .catch(function () {
        hideTyping();
        addChatMessage(T('Sorry, something went wrong. Please try again later.', 'Maaf, ada masalah teknikal. Sila cuba lagi kemudian.'), 'bot');
      });
  }

  // ---- top-level keyword router (used for free-typed text with no pending intent) ----
  function botReply(text) {
    var msg = text.toLowerCase();

    if (/\bcancel\b|\bbatal\b/.test(msg)) return showCancelInfo();
    if (/refund|deposit balik|bayar balik|pulang(kan)? deposit/.test(msg)) {
      return botSayMulti([
        T("If you cancel a confirmed booking, your deposit is refunded manually by our team (bank transfer/cash) — it isn't instant.", 'Jika anda batalkan tempahan yang disahkan, deposit akan direfund secara manual oleh pasukan kami (bank transfer/tunai) — ia tidak serta-merta.'),
        whatsappChip(T('Hi, I have a question about my deposit refund.', 'Hai, saya ada soalan tentang refund deposit saya.'))
      ]);
    }
    if (/\bpayment\b|\bbayar\b|\bdeposit\b|\bbayaran\b/.test(msg)) return showPaymentInfo();
    if (/check-?in|check-?out|checkin|checkout/.test(msg)) return showCheckInOut();
    if (/\bhouse rule|\bperaturan|\bsmok|\brokok|\bpet\b|\bhaiwan\b|\bparty|\bparties|\bnoise|\bbising/.test(msg)) return showHouseRules();
    if (/location|lokasi|\baddress\b|\balamat\b|\barah\b|direction/.test(msg)) return showLocation();
    if (/villa mana|bilik mana|mana (yang )?sesuai|\bsesuai\b|\brecommend|\bsuggest|\bcadang|nak villa|cari villa|\d+\s*(orang|org|pax|dewasa|adult|guest)/.test(msg)) return startRecommendation(text);
    if (/\bavailable\b|ada kosong|\bkosong\b|boleh (book|tempah)/.test(msg)) return startAvailability(text);
    if (/facilit|kemudahan|swimming pool|\bkolam\b|\bwifi\b|\bbbq\b/.test(msg)) return showFacilities();
    if (/\bbook\b|\bbooking\b|\btempah/.test(msg)) return showBookingMenu();
    if (/\bvilla\b/.test(msg)) return botSay(priceRangeText('Villa'));
    if (/campsite/.test(msg)) return botSay(priceRangeText('Campsite'));
    if (/\bprice\b|\bharga\b|\bcost\b|\brm\b/.test(msg)) return botSay(priceRangeText('Villa') + ' ' + priceRangeText('Campsite'));
    if (/contact|human|agent|staff|talk|hubungi/.test(msg)) return contactStaff();

    return botSayMulti([
      T("Sorry, I'm not sure about that one — let me connect you to our team on WhatsApp instead.", 'Maaf, saya kurang pasti tentang itu — biar saya hubungkan anda dengan pasukan kami di WhatsApp.'),
      whatsappChip(text)
    ]);
  }

  // ---- master entry point: routes to the pending slot-filling step, or the keyword router ----
  function handleUserMessage(text) {
    addChatMessage(escapeHtml(text), 'user');
    var detected = detectLanguage(text);
    if (detected) chatLang = detected;

    if (chatIntent === 'reco_guests') return handleRecoGuests(text);
    if (chatIntent === 'reco_dates') return handleRecoDates(text);
    if (chatIntent === 'booking_id') return handleBookingId(text);
    if (chatIntent === 'booking_phone') return handleBookingPhone(text);

    return botReply(text);
  }

  // ---- quick-reply / in-chat action buttons (data-action) — separate from real <a class="chip"> links ----
  function runAction(action, label) {
    addChatMessage(escapeHtml(label), 'user');
    // klik quick-reply = topik baru — batalkan mana-mana flow bertanya (reco/booking status)
    // yang tergantung, supaya balasan seterusnya tak "tersasar" pergi state lama. Fungsi yang
    // memang nak mula flow baru (startAvailability, showPaymentInfo, dll.) set chatIntent
    // semula lepas ni, so ni selamat.
    chatIntent = null;
    switch (action) {
      case 'check_availability': return startAvailability('');
      case 'recommend': return startRecommendation('');
      case 'facilities': return showFacilities();
      case 'booking': return showBookingMenu();
      case 'payment': return showPaymentInfo();
      case 'checkinout': return showCheckInOut();
      case 'rules': return showHouseRules();
      case 'contact': return contactStaff();
      case 'how_to_book': return showHowToBook();
      case 'check_booking': return startBookingStatus();
      case 'cancel_booking': return showCancelInfo();
    }
  }

  function chipActionHandler(e) {
    var btn = e.target.closest('button[data-action]');
    if (!btn) return;
    runAction(btn.dataset.action, btn.textContent);
  }
  chatbotQuickreplies.addEventListener('click', chipActionHandler);
  chatbotLog.addEventListener('click', chipActionHandler);

  function openChatbot() {
    chatbotPanel.classList.add('is-open');
    chatbotLauncher.setAttribute('aria-expanded', 'true');
    if (!chatbotGreeted) {
      chatbotGreeted = true;
      addChatMessage("Assalamualaikum 👋 Selamat datang ke CasaDive Villa! Saya AI Assistant CasaDive. Saya boleh bantu anda mencari villa yang sesuai, semak availability, booking, pembayaran, kemudahan dan peraturan villa. Apa yang boleh saya bantu hari ini?", 'bot');
    }
  }

  function closeChatbot() {
    chatbotPanel.classList.remove('is-open');
    chatbotLauncher.setAttribute('aria-expanded', 'false');
  }

  chatbotLauncher.addEventListener('click', function () {
    chatbotPanel.classList.contains('is-open') ? closeChatbot() : openChatbot();
  });
  chatbotClose.addEventListener('click', closeChatbot);

  chatbotForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = chatbotInput.value.trim();
    if (!text) return;
    chatbotInput.value = '';
    handleUserMessage(text);
  });
</script>

<?php
$showWhatsapp = true; // homepage je yang ada butang WhatsApp terapung
include __DIR__ . '/includes/footer.php';
