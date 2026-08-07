<?php
/**
 * Halaman butiran penginapan.
 * Halaman ini memaparkan butiran penuh, ciri-ciri, dan kadar bagi pakej vila atau khemah yang dipilih.
 */
require_once __DIR__ . '/../includes/db.php';

// ?id=123 dalam URL memberitahu kita pakej vila/khemah mana yang perlu dipaparkan.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$accommodation = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM accommodation WHERE accommodation_id = :id');
    $stmt->execute(['id' => $id]);
    $accommodation = $stmt->fetch();
}

// Ciri-ciri bilik disimpan sebagai satu baris teks bagi setiap ciri (lihat manage_accommodation.php),
// jadi pecahkan semula menjadi senarai di sini, buang mana-mana baris kosong.
$features = [];
if ($accommodation && !empty($accommodation['features'])) {
    $features = array_filter(array_map('trim', explode("\n", $accommodation['features'])));
}

$base = '../';
$active = $accommodation && $accommodation['accommodation_type'] === 'Campsite' ? 'campsite' : 'villa';
$pageTitle = ($accommodation ? $accommodation['accommodation_name'] : 'Package not found') . ' — Casadive Villa';
$pageCss = 'style/detail.css';
include __DIR__ . '/../includes/header.php';
?>

  <section class="detail-section">
    <div class="container">
      <?php if (!$accommodation): ?>

        <div class="not-found">
          <h1>Package not found</h1>
          <p>The accommodation you're looking for doesn't exist or may have been removed.</p>
          <br>
          <a href="villa.php" class="detail-book" style="display:inline-block;">Browse Packages</a>
        </div>

      <?php else:
        // Hantar "Back to Packages" / "Browse More" ke halaman senarai yang betul mengikut jenis pakej.
        $backHref = $accommodation['accommodation_type'] === 'Campsite' ? 'campsite.php' : 'villa.php';
        // Guna label khas admin (contoh: "4-5 PAX") jika ditetapkan, jika tidak bina dari nombor kapasiti.
        $paxLabel = $accommodation['pax_label'] ?: ('Max ' . (int) $accommodation['capacity'] . ' guests');
      ?>

        <a href="<?= $backHref ?>" class="detail-back">&larr; Back to <?= htmlspecialchars($accommodation['accommodation_type']) ?> Packages</a>

        <div class="detail-card">

          <?php if ($accommodation['image']): ?>
            <div class="detail-media">
              <img src="<?= htmlspecialchars($accommodation['image']) ?>" alt="<?= htmlspecialchars($accommodation['accommodation_name']) ?>" loading="lazy">
            </div>
          <?php endif; ?>

          <div class="detail-head">
            <h1 class="detail-name"><?= htmlspecialchars($accommodation['accommodation_name']) ?></h1>
            <span class="detail-pax"><?= htmlspecialchars($paxLabel) ?></span>
          </div>

          <p class="detail-description"><?= nl2br(htmlspecialchars($accommodation['description'] ?: 'No additional description available for this package.')) ?></p>

          <hr class="detail-divider">

          <?php if ($features): ?>
          <h2 class="detail-heading">Room Features</h2>
          <div class="detail-features">
            <?php foreach ($features as $feature): ?>
              <div class="detail-feature">
                <svg viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                <span><?= htmlspecialchars($feature) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <h2 class="detail-heading">Room Rates</h2>
          <div class="rates-card">
            <div class="rates-row">
              <span class="rates-label">Weekday</span>
              <span class="rates-value">RM <?= number_format((float) $accommodation['price'], 0) ?> /night</span>
            </div>
            <?php if ($accommodation['price_weekend'] !== null): ?>
            <div class="rates-row">
              <span class="rates-label">Weekend</span>
              <span class="rates-value">RM <?= number_format((float) $accommodation['price_weekend'], 0) ?> /night</span>
            </div>
            <?php endif; ?>
            <?php if ($accommodation['price_holiday'] !== null): ?>
            <div class="rates-row">
              <span class="rates-label">Public Holiday</span>
              <span class="rates-value">RM <?= number_format((float) $accommodation['price_holiday'], 0) ?> /night</span>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($accommodation['status'] !== 'available'): ?>
            <p class="detail-unavailable">This package is currently <?= htmlspecialchars($accommodation['status']) ?> and not open for booking right now. Please check back later or browse other packages.</p>
          <?php endif; ?>

          <div class="detail-actions">
            <?php if ($accommodation['status'] === 'available'): ?>
              <a href="bookingform.php?accommodation=<?= urlencode($accommodation['accommodation_name']) ?>" class="detail-book">Book Now</a>
            <?php endif; ?>
            <a href="<?= $backHref ?>" class="detail-browse">Browse More</a>
          </div>

        </div>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
