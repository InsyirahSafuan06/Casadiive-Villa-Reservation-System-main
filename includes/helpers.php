<?php
/**
 * Fungsi bantuan untuk format dan pengiraan harga.
 * Fungsi-fungsi kecil ini memudahkan pengurusan dan paparan proses tempahan.
 */
declare(strict_types=1);

// Harga add-on tetap (bukan dari DB) — sama macam deposit RM1, senang je so tak perlukan
// jadual/pengurusan admin berasingan buat masa ni. Tukar nilai kat SINI je kalau harga berubah;
// booking yang dah wujud takkan terjejas sebab harga dah "dibekukan" dalam booking.total_amount.
const ADDON_BBQ_PRICE = 20.00;
const ADDON_MATTRESS_PRICE = 10.00;

/** Jumlah harga add-on yang dipilih — dipanggil masa booking dicipta DAN masa papar resit. */
function booking_addon_total(bool $bbq, bool $mattress): float
{
    return ($bbq ? ADDON_BBQ_PRICE : 0.0) + ($mattress ? ADDON_MATTRESS_PRICE : 0.0);
}

/**
 * Sahkan + simpan satu gambar gallery yang di-upload manager/staff dari dashboard, dan
 * rekod dalam DB. Dikongsi oleh admin_dashboard.php dan staff_dashboard.php (dulu logik
 * yang sama disalin dua tempat — sekarang satu je, senang nak selenggara/betulkan).
 * Pulangkan true bila berjaya, atau mesej error (string) untuk papar kat customer/staff.
 */
function save_gallery_upload(PDO $pdo, ?array $file, string $caption, int $uploadedBy): bool|string
{
    if (!$file || empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'Please choose an image to upload.';
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($ext, $allowedExt, true) || $file['size'] <= 0 || $file['size'] > $maxSize || @getimagesize($file['tmp_name']) === false) {
        return 'Please upload a valid image (JPG, PNG or WEBP, max 5MB).';
    }

    $destDir = __DIR__ . '/../assets/uploads/gallery/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $filename = 'gallery_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
        return 'We could not save your uploaded image. Please try again.';
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO gallery (image_path, caption, uploaded_by) VALUES (:path, :caption, :uploaded_by)'
        );
        $stmt->execute([
            'path' => 'assets/uploads/gallery/' . $filename,
            'caption' => $caption !== '' ? $caption : null,
            'uploaded_by' => $uploadedBy,
        ]);
    } catch (PDOException $e) {
        // table `gallery` mungkin belum wujud lagi kat production (belum di-migrate) — buang
        // fail yang dah terlanjur di-upload tu, elak fail terbiar tanpa rekod DB
        unlink($destDir . $filename);
        error_log('Failed to save gallery image: ' . $e->getMessage());
        return 'Gallery is not available right now. Please try again later.';
    }

    return true;
}

/**
 * Padam satu gambar gallery (rekod DB + fail di cakera). Pulangkan true kalau memang ada
 * sesuatu yang dipadam, false kalau id tak wujud atau query gagal (contoh: jadual belum wujud).
 */
function delete_gallery_image(PDO $pdo, int $galleryId): bool
{
    try {
        $stmt = $pdo->prepare('SELECT image_path FROM gallery WHERE gallery_id = :id');
        $stmt->execute(['id' => $galleryId]);
        $imagePath = $stmt->fetchColumn();

        if ($imagePath === false) {
            return false;
        }

        $pdo->prepare('DELETE FROM gallery WHERE gallery_id = :id')->execute(['id' => $galleryId]);

        $fullPath = __DIR__ . '/../' . $imagePath;
        if (is_file($fullPath)) {
            unlink($fullPath);
        }

        return true;
    } catch (PDOException $e) {
        error_log('Failed to delete gallery image: ' . $e->getMessage());
        return false;
    }
}

// Diskaun automatik untuk tempahan lama (3 malam ke atas) — RM tetap, bukan peratus, sama
// prinsip macam harga add-on kat atas: tukar nilai kat SINI je, booking yang dah wujud takkan
// terjejas sebab jumlah diskaun dah "dibekukan" dalam booking.discount_amount masa tempahan dibuat.
const LONG_STAY_DISCOUNT_MIN_NIGHTS = 3;
const LONG_STAY_DISCOUNT_AMOUNT = 50.00;

/** Jumlah diskaun untuk tempahan sepanjang $nights malam — 0 kalau tak capai ambang. */
function booking_long_stay_discount(int $nights): float
{
    return $nights >= LONG_STAY_DISCOUNT_MIN_NIGHTS ? LONG_STAY_DISCOUNT_AMOUNT : 0.0;
}

/** Tukar nilai enum snake_case seperti 'checked_in' kepada 'Checked In' untuk paparan. */
function format_status(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/** Bina rujukan tempahan yang tetamu nampak/guna, cth. booking_id 16 jadi "CDV16". */
function format_booking_ref(int $bookingId): string
{
    return 'CDV' . $bookingId;
}

/**
 * Songsangkan format_booking_ref() — terima input tetamu ("CDV16", "cdv16", atau "16" je)
 * dan pulangkan booking_id sebagai int, atau false kalau format tak sah.
 */
function parse_booking_ref(string $ref): int|false
{
    $ref = trim($ref);
    if (stripos($ref, 'CDV') === 0) {
        $ref = substr($ref, 3);
    }

    return filter_var($ref, FILTER_VALIDATE_INT);
}

/**
 * Jumlah besar SEBENAR yang perlu dibayar customer = kos penginapan (total_amount) +
 * deposit tempahan (deposit_amount). Deposit di sini caj TAMBAHAN atas kos penginapan,
 * bukan sebahagian/potongan daripadanya — pakai function ni di MANA-MANA tempat yang papar
 * atau caj "jumlah besar"/"Total Price" supaya konsisten seluruh sistem.
 */
function booking_grand_total(array $booking): float
{
    return (float) $booking['total_amount'] + (float) $booking['deposit_amount'];
}

/**
 * Rekod satu bayaran penuh yang berjaya untuk satu booking, dan sahkan booking tu
 * (pending -> confirmed) dalam satu transaction — dikongsi oleh flow simulasi online
 * banking (process_payment.php) dan flow ToyyibPay sebenar (toyyibpay_return.php), supaya
 * dua-dua guna logik "rekod bayaran" yang sama, tak duplicate.
 * Pulangkan false kalau booking dah ada rekod 'paid' sebelum ni (elak rekod bayaran dua kali).
 */
function record_booking_payment(PDO $pdo, int $bookingId, float $amount, string $paymentMethod, ?string $receipt = null): bool
{
    $stmt = $pdo->prepare("SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1");
    $stmt->execute(['id' => $bookingId]);
    if ($stmt->fetch()) {
        return false; // dah bayar sebelum ni, jangan rekod/confirm lagi
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_status, receipt)
             VALUES (:booking_id, :deposit_paid, :payment_method, 'paid', :receipt)"
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'deposit_paid' => $amount,
            'payment_method' => $paymentMethod,
            'receipt' => $receipt,
        ]);

        $stmt = $pdo->prepare(
            "UPDATE booking SET booking_status = 'confirmed' WHERE booking_id = :id AND booking_status = 'pending'"
        );
        $stmt->execute(['id' => $bookingId]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Booking yang dibatalkan tapi deposit dia masih 'paid'/'partial' (belum ditandakan
 * 'refunded') bermakna staff/admin masih terhutang refund kat tetamu tu. Sistem ni
 * takde payment gateway automatik, so refund sebenar diuruskan manual (bank transfer/tunai)
 * — function ni cuma tentukan bila nak papar amaran "Refund Due" kat dashboard.
 */
function payment_needs_refund(string $bookingStatus, ?string $paymentStatus): bool
{
    return $bookingStatus === 'cancelled' && in_array($paymentStatus, ['paid', 'partial'], true);
}

/**
 * Bahagikan tempoh penginapan kepada malam hari biasa vs. hujung minggu (Jumaat & Sabtu
 * dikira sebagai hujung minggu) dan kira harga setiap malam mengikutnya. $weekendPrice
 * akan guna $weekdayPrice jika null. Harga cuti umum tidak digunakan di sini — itu
 * memerlukan kalendar cuti yang sistem ini tiada; price_holiday pada jadual
 * `accommodation` hanya untuk paparan sahaja buat masa ini.
 */
function compute_stay_price(float $weekdayPrice, ?float $weekendPrice, DateTime $checkIn, DateTime $checkOut): array
{
    $weekendPrice ??= $weekdayPrice; // kalau tak set harga weekend, guna harga weekday je
    $weekdayNights = 0; // kira jumlah malam hari biasa
    $weekendNights = 0; // kira jumlah malam hujung minggu

    $cursor = clone $checkIn; // kita "jalan" dari tarikh check-in, hari demi hari
    while ($cursor < $checkOut) {
        $isWeekend = in_array((int) $cursor->format('N'), [5, 6], true); // Jumaat, Sabtu
        $isWeekend ? $weekendNights++ : $weekdayNights++; // tambah kira ikut hari tu weekend ke tak
        $cursor->modify('+1 day'); // pergi ke hari seterusnya
    }

    // pulangkan berapa malam setiap jenis, dan jumlah harga keseluruhan
    return [
        'weekday_nights' => $weekdayNights,
        'weekend_nights' => $weekendNights,
        'total' => round($weekdayNights * $weekdayPrice + $weekendNights * $weekendPrice, 2),
    ];
}

/**
 * Cari status + ringkasan pembayaran satu booking guna "ref + phone" (macam mybooking.php).
 * Digunakan oleh customer/chatbot_booking_status.php — diasingkan dari situ supaya query
 * ni senang diuji/diguna semula kalau perlu di tempat lain kelak.
 *
 * @return array{found: bool, booking_ref?: string, status?: string, check_in?: string,
 *     check_out?: string, total_guest?: int, accommodations?: string, payment_status?: ?string,
 *     balance_due?: float}
 */
function lookup_booking_status(PDO $pdo, int $ref, string $phone): array
{
    $stmt = $pdo->prepare(
        'SELECT b.booking_id, b.check_in, b.check_out, b.total_guest, b.total_amount,
                b.deposit_amount, b.booking_status
         FROM booking b
         JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :ref AND c.phone = :phone'
    );
    $stmt->execute(['ref' => $ref, 'phone' => $phone]);
    $booking = $stmt->fetch();

    if (!$booking) {
        return ['found' => false];
    }

    $stmt = $pdo->prepare(
        'SELECT GROUP_CONCAT(a.accommodation_name SEPARATOR ", ") AS accommodations
         FROM booking_item bi JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
         WHERE bi.booking_id = :id'
    );
    $stmt->execute(['id' => $booking['booking_id']]);
    $accommodations = $stmt->fetchColumn() ?: '—';

    $stmt = $pdo->prepare('SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1');
    $stmt->execute(['id' => $booking['booking_id']]);
    $paymentStatus = $stmt->fetchColumn() ?: null;

    $stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
    $stmt->execute(['id' => $booking['booking_id']]);
    $amountPaid = (float) ($stmt->fetchColumn() ?: 0);

    return [
        'found' => true,
        'booking_ref' => format_booking_ref((int) $booking['booking_id']),
        'status' => format_status($booking['booking_status']),
        'check_in' => $booking['check_in'],
        'check_out' => $booking['check_out'],
        'total_guest' => (int) $booking['total_guest'],
        'accommodations' => $accommodations,
        'payment_status' => $paymentStatus ? format_status($paymentStatus) : null,
        'balance_due' => round(booking_grand_total($booking) - $amountPaid, 2),
    ];
}

/**
 * "Smart Recommendation" — padankan accommodation yang available dengan keperluan pelanggan
 * (bilangan tetamu, tarikh, budget, jenis). Ni BUKAN LLM/AI sebenar — cuma scoring rules based
 * on data DB sebenar, supaya sebab-sebab yang dipaparkan (reasons) memang tepat dan tak
 * mengarang nombor. $criteria: guests(int, required), type(?string 'Villa'|'Campsite'),
 * check_in(?string), check_out(?string), budget(?float, RM/malam).
 *
 * @return array<int, array{accommodation: array, score: float, within_budget: bool, reasons: string[]}>
 */
function recommend_accommodations(PDO $pdo, array $criteria): array
{
    $guests = (int) $criteria['guests'];
    $type = $criteria['type'] ?? null;
    $budget = $criteria['budget'] ?? null;
    $checkIn = $criteria['check_in'] ?? null;
    $checkOut = $criteria['check_out'] ?? null;

    // tarikh cuma digunakan untuk filter availability kalau DUA-DUA sah dan check_out lepas check_in —
    // kalau tak, kita skip terus filter tarikh (customer mungkin belum tau tarikh lagi)
    $useDates = $checkIn && $checkOut && $checkOut > $checkIn;

    $sql = 'SELECT * FROM accommodation a WHERE a.status = :status AND a.capacity >= :guests';
    $params = ['status' => 'available', 'guests' => $guests];

    if ($type) {
        $sql .= ' AND a.accommodation_type = :type';
        $params['type'] = $type;
    }

    if ($useDates) {
        // logik sama macam overlap-check dalam bookingform.php — cuma cancelled je yang
        // "clear" tarikh tu, status lain (termasuk pending) tetap dikira sebagai occupied
        $sql .= ' AND NOT EXISTS (
            SELECT 1 FROM booking_item bi
            JOIN booking b ON b.booking_id = bi.booking_id
            WHERE bi.accommodation_id = a.accommodation_id
              AND b.booking_status != :cancelled
              AND b.check_in < :check_out AND b.check_out > :check_in
        )';
        $params['cancelled'] = 'cancelled';
        $params['check_in'] = $checkIn;
        $params['check_out'] = $checkOut;
    }

    $sql .= ' ORDER BY a.accommodation_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidates = $stmt->fetchAll();

    $results = [];
    foreach ($candidates as $row) {
        $capacity = (int) $row['capacity'];
        $price = (float) $row['price'];

        // scoring: berapa padan capacity dengan bilangan tetamu (dekat dengan 1 = paling padan,
        // sebab kita dah filter capacity >= guests kat SQL atas, so max value dia memang 1)
        $capacityScore = $capacity > 0 ? $guests / $capacity : 0;

        $withinBudget = $budget === null || $price <= (float) $budget;
        if ($budget !== null) {
            $priceScore = $price <= (float) $budget
                ? 1.0
                : max(0.0, 1 - ($price - (float) $budget) / (float) $budget);
            $score = 0.6 * $capacityScore + 0.4 * $priceScore;
        } else {
            $score = $capacityScore;
        }

        $reasons = ["Fits {$guests} guest" . ($guests !== 1 ? 's' : '') . " (capacity {$capacity})"];
        if ($budget !== null) {
            $reasons[] = $withinBudget
                ? 'Within your RM ' . number_format((float) $budget, 2) . '/night budget'
                : 'RM ' . number_format($price - (float) $budget, 2) . ' above your budget';
        }
        if ($useDates) {
            $reasons[] = "Available for {$checkIn} to {$checkOut}";
        }

        $results[] = [
            'accommodation' => $row,
            'score' => $score,
            'within_budget' => $withinBudget,
            'reasons' => $reasons,
        ];
    }

    // urutkan: dalam budget dulu, lepas tu score tertinggi, lepas tu harga termurah
    usort($results, function ($a, $b) {
        if ($a['within_budget'] !== $b['within_budget']) {
            return $a['within_budget'] ? -1 : 1;
        }
        if ($a['score'] !== $b['score']) {
            return $a['score'] > $b['score'] ? -1 : 1;
        }
        return (float) $a['accommodation']['price'] <=> (float) $b['accommodation']['price'];
    });

    return array_slice($results, 0, 6);
}
