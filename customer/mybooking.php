<?php
/**
 * Halaman carian MyBooking.
 * Pelanggan boleh cari tempahan mengikut nombor rujukan dan nombor telefon untuk lihat atau cetak resit.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
$reviewError = null;
$cancelError = null;

// tetamu boleh batalkan booking sendiri guna ref + phone yang sama macam lookup —
// cuma dibenarkan selagi booking tu belum check-in/check-out/dah cancelled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'cancel') {
    $cRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    $cPhone = trim((string) ($_POST['phone'] ?? ''));

    if (!csrf_verify()) {
        $cancelError = 'Your session expired. Please try again.';
    } elseif ($cRef === false || $cPhone === '') {
        $cancelError = 'Invalid booking reference.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status IN ('pending', 'confirmed')"
        );
        $stmt->execute(['ref' => $cRef, 'phone' => $cPhone]);
        if (!$stmt->fetch()) {
            $cancelError = 'This booking can no longer be cancelled.';
        } else {
            $stmt = $pdo->prepare("UPDATE booking SET booking_status = 'cancelled' WHERE booking_id = :id");
            $stmt->execute(['id' => $cRef]);
            send_status_email($pdo, $cRef, 'cancelled');
        }
    }

    if (!$cancelError) {
        // redirect balik supaya refresh page tak submit cancel dua kali
        header('Location: mybooking.php?ref=' . $cRef . '&phone=' . urlencode($cPhone));
        exit;
    }

    $ref = (string) $cRef;
    $phone = $cPhone;
}

// tetamu cuma boleh bagi review lepas dah checked_out, dan sekali je untuk setiap booking —
// dua-dua syarat ni kita check dalam query di bawah sebelum cuba INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'review') {
    $rRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    $rPhone = trim((string) ($_POST['phone'] ?? ''));
    $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if (!csrf_verify()) {
        $reviewError = 'Your session expired. Please try again.';
    } elseif ($rRef === false || $rPhone === '') {
        $reviewError = 'Invalid booking reference.';
    } elseif ($rating === false || $rating < 1 || $rating > 5) {
        $reviewError = 'Please choose a rating between 1 and 5 stars.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status = 'checked_out'"
        );
        $stmt->execute(['ref' => $rRef, 'phone' => $rPhone]);
        if (!$stmt->fetch()) {
            $reviewError = 'We could not verify that booking for a review.';
        } else {
            // gambar review — customer JS dah tapis kandungan (AI verification client-side)
            // sebelum submit, so field review_image ni sepatutnya cuma sampai kat sini kalau dah
            // lulus. Kat server kita cuma sahkan fail tu betul-betul gambar (bukan re-verify
            // kandungan — takde model AI kat server), sebagai lapisan keselamatan asas je.
            $imagePath = null;
            $imageStatus = 'not_applicable';
            $imageCategory = null;
            $imageConfidence = null;

            if (!empty($_FILES['review_image']['name']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['review_image'];
                $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $maxSize = 5 * 1024 * 1024; // 5MB

                if (in_array($ext, $allowedExt, true) && $file['size'] > 0 && $file['size'] <= $maxSize && @getimagesize($file['tmp_name']) !== false) {
                    $destDir = __DIR__ . '/../assets/uploads/reviews/';
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    $filename = 'review_' . $rRef . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                    if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                        $imagePath = 'assets/uploads/reviews/' . $filename;
                        $imageStatus = 'verified';
                        $imageCategory = trim((string) ($_POST['image_category'] ?? '')) ?: null;
                        $confidenceRaw = $_POST['image_confidence'] ?? null;
                        $imageConfidence = is_numeric($confidenceRaw) ? round((float) $confidenceRaw, 3) : null;
                    }
                }
            }

            try {
                // table `review` ada UNIQUE constraint kat booking_id, so kalau cuba review
                // kali kedua untuk booking yang sama, insert ni akan gagal dan masuk catch bawah
                $stmt = $pdo->prepare(
                    'INSERT INTO review (booking_id, rating, comment, image_path, image_verification_status, image_category, image_confidence)
                     VALUES (:booking_id, :rating, :comment, :image_path, :image_status, :image_category, :image_confidence)'
                );
                $stmt->execute([
                    'booking_id' => $rRef,
                    'rating' => $rating,
                    'comment' => $comment !== '' ? $comment : null,
                    'image_path' => $imagePath,
                    'image_status' => $imageStatus,
                    'image_category' => $imageCategory,
                    'image_confidence' => $imageConfidence,
                ]);
            } catch (Exception $e) {
                $reviewError = 'You have already reviewed this booking.';
                if ($imagePath && is_file(__DIR__ . '/../' . $imagePath)) {
                    // insert gagal (contoh: dah pernah review) — buang gambar yang dah terlanjur
                    // di-upload tu, elak fail terbiar kat cakera tanpa rekod DB
                    unlink(__DIR__ . '/../' . $imagePath);
                }
            }
        }
    }

    if (!$reviewError) {
        // redirect balik ke page ni juga supaya refresh tak submit review dua kali
        header('Location: mybooking.php?ref=' . $rRef . '&phone=' . urlencode($rPhone));
        exit;
    }

    $ref = (string) $rRef;
    $phone = $rPhone;
}

// booking dicari guna "no rujukan + no phone" yang digunakan masa booking — ni jadi macam
// "password" ringkas supaya tetamu tak boleh tengok booking orang lain just dengan teka ID
$lookupAttempted = $ref !== '' || $phone !== '';
$lookupError = null;
$booking = null;
$items = [];
$existingReview = null;
$latestPaymentStatus = null;

if ($lookupAttempted) {
    $refId = filter_var($ref, FILTER_VALIDATE_INT);

    if ($refId === false || $phone === '') {
        $lookupError = 'Please enter a valid booking reference and the phone number used to book.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT b.*, c.full_name, c.phone, c.plate_num
             FROM booking b
             JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone'
        );
        $stmt->execute(['ref' => $refId, 'phone' => $phone]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $lookupError = 'No booking found for that reference number and phone number. Please double-check and try again.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT bi.quantity, bi.price, a.accommodation_name, a.accommodation_type,
                        a.price AS nightly_price, a.price_weekend AS nightly_price_weekend
                 FROM booking_item bi
                 JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
                 WHERE bi.booking_id = :id'
            );
            $stmt->execute(['id' => $booking['booking_id']]);
            $items = $stmt->fetchAll();

            $stmt = $pdo->prepare('SELECT rating, comment, image_path, review_date FROM review WHERE booking_id = :id');
            $stmt->execute(['id' => $booking['booking_id']]);
            $existingReview = $stmt->fetch() ?: null; // ada review sedia ada ke tak untuk booking ni

            $stmt = $pdo->prepare('SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1');
            $stmt->execute(['id' => $booking['booking_id']]);
            $latestPaymentStatus = $stmt->fetchColumn() ?: null; // untuk papar status refund kalau booking dah cancel
        }
    }
}

$nights = 1;
$stay = ['weekday_nights' => 1, 'weekend_nights' => 0];
if ($booking) {
    $checkIn = new DateTime($booking['check_in']);
    $checkOut = new DateTime($booking['check_out']);
    $nights = max(1, $checkOut->diff($checkIn)->days);
    // trick sikit ni — kita hantar 1/1 sebagai harga sebab kita bukan nak jumlah harga,
    // kita cuma nak tau berapa malam weekday vs weekend untuk bina pecahan harga kat bawah
    $stay = compute_stay_price(1, 1, $checkIn, $checkOut);
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = 'mybooking'; // untuk highlight menu "MyBooking" kat navbar
$pageTitle = 'MyBooking — Casadive Villa';
$pageCss = 'style/mybooking.css';
include __DIR__ . '/../includes/header.php';
?>

  <!-- PAGE TITLE -->
  <div class="container page-title">
    <h1>MyBooking</h1>
    <p>Enter your booking reference and the phone number you booked with to view or print your receipt.</p>
  </div>

  <?php if ($booking): ?>

  <!-- RECEIPT -->
  <section class="receipt-section">
    <div class="container">
      <?php if ($reviewError && $booking['booking_status'] !== 'checked_out'): ?>
        <!-- A review was submitted (e.g. via the footer form) for a booking that isn't
             checked_out yet — the review section below is hidden in that case, so without
             this the rejection would happen silently and look like the button did nothing. -->
        <p class="lookup-error" style="max-width:700px;margin:0 auto 24px;"><?= htmlspecialchars($reviewError) ?></p>
      <?php endif; ?>
      <?php if ($cancelError): ?>
        <p class="lookup-error" style="max-width:700px;margin:0 auto 24px;"><?= htmlspecialchars($cancelError) ?></p>
      <?php endif; ?>
      <div class="receipt-card">
        <div class="receipt-head">
          <div>
            <p class="brand">Casadive Villa</p>
            <p class="receipt-ref">Booking Reference #<?= (int) $booking['booking_id'] ?> &middot; Booked on <?= htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))) ?></p>
          </div>
          <span class="status-badge status-<?= htmlspecialchars($booking['booking_status']) ?>"><?= htmlspecialchars(format_status($booking['booking_status'])) ?></span>
        </div>

        <?php if ($booking['booking_status'] === 'cancelled' && $latestPaymentStatus): ?>
        <div class="receipt-block">
          <h3>Deposit Refund</h3>
          <?php if ($latestPaymentStatus === 'refunded'): ?>
            <p>Your deposit of RM <?= number_format((float) $booking['deposit_amount'], 2) ?> has been refunded.</p>
          <?php elseif (in_array($latestPaymentStatus, ['paid', 'partial'], true)): ?>
            <p>Your deposit of RM <?= number_format((float) $booking['deposit_amount'], 2) ?> is being processed for refund. Since refunds are handled manually (bank transfer/cash), please allow a few business days, or contact us directly if you need it sooner.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="receipt-block">
          <h3>Guest Details</h3>
          <dl class="receipt-grid">
            <div><dt>Full Name</dt><dd><?= htmlspecialchars($booking['full_name']) ?></dd></div>
            <div><dt>Phone Number</dt><dd><?= htmlspecialchars($booking['phone']) ?></dd></div>
            <div><dt>Car Plate Number</dt><dd><?= htmlspecialchars($booking['plate_num'] ?: '—') ?></dd></div>
            <div><dt>Number of Guests</dt><dd><?= (int) $booking['total_guest'] ?></dd></div>
          </dl>
        </div>

        <div class="receipt-block">
          <h3>Stay Details</h3>
          <dl class="receipt-grid">
            <div><dt>Check-in</dt><dd><?= htmlspecialchars($booking['check_in']) ?></dd></div>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars($booking['check_out']) ?></dd></div>
            <div><dt>Nights</dt><dd><?= $nights ?></dd></div>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
          </dl>
        </div>

        <?php if (!empty($booking['special_request'])): ?>
        <div class="receipt-block">
          <h3>Special Request</h3>
          <p class="receipt-request"><?= nl2br(htmlspecialchars($booking['special_request'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="receipt-block">
          <h3>Price Breakdown</h3>
          <div class="receipt-rows">
            <?php foreach ($items as $item):
              // tunjuk pecahan weekday/weekend cuma kalau kadar dia memang lain, kalau sama je tunjuk simple
              $weekendRate = $item['nightly_price_weekend'] !== null ? (float) $item['nightly_price_weekend'] : (float) $item['nightly_price'];
              $rateLabel = $stay['weekend_nights'] > 0 && $weekendRate !== (float) $item['nightly_price']
                  ? "{$stay['weekday_nights']} weekday night" . ($stay['weekday_nights'] !== 1 ? 's' : '') . " &times; RM " . number_format((float) $item['nightly_price'], 2)
                    . " + {$stay['weekend_nights']} weekend night" . ($stay['weekend_nights'] !== 1 ? 's' : '') . " &times; RM " . number_format($weekendRate, 2)
                  : "RM " . number_format((float) $item['nightly_price'], 2) . " &times; {$nights} night" . ($nights > 1 ? 's' : '');
            ?>
              <div class="receipt-row">
                <span><?= htmlspecialchars($item['accommodation_name']) ?> (<?= $rateLabel ?>)</span>
                <span>RM <?= number_format((float) $item['price'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="receipt-row total">
              <span>Total Price</span>
              <span>RM <?= number_format((float) $booking['total_amount'], 2) ?></span>
            </div>
            <div class="receipt-row">
              <span>Deposit</span>
              <span>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></span>
            </div>
            <div class="receipt-row balance">
              <span>Balance Due</span>
              <span>RM <?= number_format((float) $booking['total_amount'] - (float) $booking['deposit_amount'], 2) ?></span>
            </div>
          </div>
        </div>

        <div class="receipt-actions">
          <button type="button" class="receipt-print" onclick="window.print()">Print Receipt</button>
          <a href="mybooking.php" class="receipt-back">Look Up Another Booking</a>
          <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'], true)): ?>
            <form method="post" onsubmit="return confirm('Are you sure you want to cancel this booking?');" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="form" value="cancel">
              <input type="hidden" name="ref" value="<?= (int) $booking['booking_id'] ?>">
              <input type="hidden" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>">
              <button type="submit" class="receipt-back" style="color:#c0392b;border-color:#c0392b;">Cancel Booking</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($booking['booking_status'] === 'checked_out'): ?>
      <div class="receipt-card review-card">
        <?php if ($existingReview): ?>
          <h3>Your Review</h3>
          <div class="stars" aria-label="<?= (int) $existingReview['rating'] ?> out of 5 stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg viewBox="0 0 20 19" class="<?= $i <= (int) $existingReview['rating'] ? '' : 'star-empty' ?>"><polygon points="10,0 12.5,7 20,7 14,11.5 16,19 10,14.5 4,19 6,11.5 0,7 7.5,7"/></svg>
            <?php endfor; ?>
          </div>
          <?php if ($existingReview['comment']): ?>
            <p class="review-comment"><?= nl2br(htmlspecialchars($existingReview['comment'])) ?></p>
          <?php endif; ?>
          <?php if ($existingReview['image_path']): ?>
            <img class="review-photo" src="../<?= htmlspecialchars($existingReview['image_path']) ?>" alt="Room photo shared by the guest">
          <?php endif; ?>
          <p class="review-date">Reviewed on <?= htmlspecialchars(date('d M Y', strtotime($existingReview['review_date']))) ?></p>
        <?php else: ?>
          <h3>Leave a Review</h3>
          <?php if ($reviewError): ?>
            <p class="lookup-error"><?= htmlspecialchars($reviewError) ?></p>
          <?php endif; ?>
          <form method="post" class="review-form" id="review-form" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="review">
            <input type="hidden" name="ref" value="<?= (int) $booking['booking_id'] ?>">
            <input type="hidden" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>">
            <div class="rating-picker" role="radiogroup" aria-label="Rating">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="rating-<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                <label for="rating-<?= $i ?>" title="<?= $i ?> stars">&#9733;</label>
              <?php endfor; ?>
            </div>
            <textarea name="comment" rows="3" placeholder="Tell us about your stay (optional)"></textarea>

            <div class="review-photo-section">
              <p class="review-photo-label">Add a room photo (optional) &mdash; our AI checks it's actually a photo of the room before it's attached.</p>
              <div class="review-photo-controls">
                <button type="button" class="photo-btn" id="review-upload-btn">Upload Image</button>
                <button type="button" class="photo-btn" id="review-camera-btn">Open Camera</button>
              </div>
              <input type="file" id="review-file-input" name="review_image" accept="image/*" hidden>
              <input type="hidden" id="review-image-category" name="image_category">
              <input type="hidden" id="review-image-confidence" name="image_confidence">
              <img id="review-image-preview" class="review-photo-preview" hidden alt="Selected room photo preview">
              <p id="review-verify-status" class="review-verify-status" hidden></p>
            </div>

            <button type="submit" class="lookup-submit">Submit Review</button>
          </form>

          <!-- CAMERA MODAL -->
          <div class="camera-modal" id="camera-modal">
            <div class="camera-modal-inner">
              <video id="camera-video" autoplay playsinline muted></video>
              <canvas id="camera-canvas" hidden></canvas>
              <div class="camera-modal-actions">
                <button type="button" class="photo-btn" id="camera-capture-btn">Capture</button>
                <button type="button" class="photo-btn photo-btn-outline" id="camera-cancel-btn">Cancel</button>
              </div>
            </div>
          </div>

          <script>
          (function () {
            var form = document.getElementById('review-form');
            if (!form) return;

            // Client-side room-photo check: TensorFlow.js + COCO-SSD (free, no API key, loaded
            // from CDN only when the customer actually clicks Upload/Camera). COCO-SSD's 80
            // object classes give clean signals for "bed" (accept), "person" (selfie reject),
            // vehicles and food (reject) — it has no class for bathroom/pool/garden/screenshot/
            // logo, so anything else lands as "uncertain" rather than a confidently-wrong guess.
            var uploadBtn = document.getElementById('review-upload-btn');
            var cameraBtn = document.getElementById('review-camera-btn');
            var fileInput = document.getElementById('review-file-input');
            var preview = document.getElementById('review-image-preview');
            var statusEl = document.getElementById('review-verify-status');
            var categoryField = document.getElementById('review-image-category');
            var confidenceField = document.getElementById('review-image-confidence');

            var cameraModal = document.getElementById('camera-modal');
            var cameraVideo = document.getElementById('camera-video');
            var cameraCanvas = document.getElementById('camera-canvas');
            var captureBtn = document.getElementById('camera-capture-btn');
            var cancelBtn = document.getElementById('camera-cancel-btn');

            var CONFIDENCE_THRESHOLD = 0.5;
            var cocoModel = null;
            var modelLoading = null;
            var cameraStream = null;

            var VEHICLE_CLASSES = ['car', 'motorcycle', 'bus', 'truck', 'bicycle', 'train', 'airplane', 'boat'];
            var FOOD_CLASSES = ['banana', 'apple', 'sandwich', 'orange', 'broccoli', 'carrot', 'hot dog', 'pizza', 'donut', 'cake', 'bowl'];

            function setStatus(text, kind) {
              statusEl.hidden = false;
              statusEl.textContent = text;
              statusEl.className = 'review-verify-status ' + (kind || '');
            }

            function loadScript(src) {
              return new Promise(function (resolve, reject) {
                var s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
              });
            }

            function loadModel() {
              if (cocoModel) return Promise.resolve(cocoModel);
              if (modelLoading) return modelLoading;

              setStatus('Loading AI model…', 'processing');
              modelLoading = loadScript('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4/dist/tf.min.js')
                .then(function () { return loadScript('https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2/dist/coco-ssd.min.js'); })
                .then(function () { return window.cocoSsd.load(); })
                .then(function (model) { cocoModel = model; return model; });
              return modelLoading;
            }

            function classify(imgEl) {
              return loadModel().then(function (model) {
                setStatus('AI is checking your image…', 'processing');
                return model.detect(imgEl);
              }).then(function (predictions) {
                var best = null;
                predictions.forEach(function (p) {
                  if (p.score >= CONFIDENCE_THRESHOLD && (!best || p.score > best.score)) best = p;
                });

                if (!best) {
                  return { is_valid: false, category: 'uncertain', confidence: predictions[0] ? predictions[0].score : 0 };
                }
                if (best.class === 'bed') {
                  return { is_valid: true, category: 'bedroom', confidence: best.score };
                }
                if (best.class === 'person') {
                  return { is_valid: false, category: 'selfie', confidence: best.score };
                }
                if (VEHICLE_CLASSES.indexOf(best.class) !== -1) {
                  return { is_valid: false, category: 'vehicle', confidence: best.score };
                }
                if (FOOD_CLASSES.indexOf(best.class) !== -1) {
                  return { is_valid: false, category: 'food', confidence: best.score };
                }
                return { is_valid: false, category: 'uncertain', confidence: best.score };
              });
            }

            function assignFile(file) {
              var dt = new DataTransfer();
              dt.items.add(file);
              fileInput.files = dt.files;
            }

            function clearFile() {
              fileInput.value = '';
              categoryField.value = '';
              confidenceField.value = '';
            }

            function handleFile(file) {
              clearFile();
              var url = URL.createObjectURL(file);
              var img = new Image();
              img.onload = function () {
                preview.src = url;
                preview.hidden = false;
                classify(img).then(function (result) {
                  if (result.is_valid) {
                    setStatus('✓ Image verified. Your room photo is suitable for this review.', 'success');
                    categoryField.value = result.category;
                    confidenceField.value = result.confidence.toFixed(3);
                    assignFile(file);
                  } else if (result.category === 'uncertain') {
                    setStatus('Please upload a clearer photo showing the villa room.', 'uncertain');
                  } else {
                    setStatus('✕ Image rejected. Please upload a clear photo of the villa room only.', 'reject');
                  }
                }).catch(function () {
                  setStatus('Could not load the AI model right now — please try again, or submit your review without a photo.', 'reject');
                });
              };
              img.src = url;
            }

            uploadBtn.addEventListener('click', function () { fileInput.click(); });

            fileInput.addEventListener('change', function () {
              if (fileInput.files && fileInput.files[0]) handleFile(fileInput.files[0]);
            });

            function stopCamera() {
              if (cameraStream) {
                cameraStream.getTracks().forEach(function (t) { t.stop(); });
                cameraStream = null;
              }
              cameraModal.classList.remove('is-open');
            }

            cameraBtn.addEventListener('click', function () {
              setStatus('Requesting camera access…', 'processing');
              navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (stream) {
                cameraStream = stream;
                cameraVideo.srcObject = stream;
                cameraModal.classList.add('is-open');
                statusEl.hidden = true;
              }).catch(function () {
                setStatus('Camera access was denied or unavailable. Please use Upload Image instead.', 'reject');
              });
            });

            cancelBtn.addEventListener('click', stopCamera);

            captureBtn.addEventListener('click', function () {
              cameraCanvas.width = cameraVideo.videoWidth;
              cameraCanvas.height = cameraVideo.videoHeight;
              cameraCanvas.getContext('2d').drawImage(cameraVideo, 0, 0);
              cameraCanvas.toBlob(function (blob) {
                var file = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
                stopCamera();
                handleFile(file);
              }, 'image/jpeg', 0.9);
            });
          })();
          </script>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <?php else: ?>

  <!-- LOOKUP FORM -->
  <section class="lookup-section">
    <div class="container">
      <div class="lookup-card">
        <?php if ($lookupError): ?>
          <p class="lookup-error"><?= htmlspecialchars($lookupError) ?></p>
        <?php endif; ?>

        <form method="get">
          <div class="form-field">
            <label for="ref">Booking Reference</label>
            <input type="text" id="ref" name="ref" placeholder="e.g. 12" value="<?= htmlspecialchars($ref) ?>" required>
          </div>
          <div class="form-field">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="The phone number used to book" value="<?= htmlspecialchars($phone) ?>" required>
          </div>
          <button type="submit" class="lookup-submit">View My Booking</button>
        </form>

        <p class="lookup-hint">Your booking reference was shown after you completed a booking. Don't have one yet? <a href="villa.php" style="color:var(--orange-deep);font-weight:600;">Browse packages</a>.</p>
      </div>
    </div>
  </section>

  <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
