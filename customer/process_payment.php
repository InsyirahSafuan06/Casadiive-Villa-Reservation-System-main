<?php
/**
 * Halaman pemprosesan.
 * Rekodkan pembayaran deposit bagi satu tempahan, kemudian papar animasi "processing"
 * yang ringkas sebelum diserahkan ke sucess_payment.php.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];

$bookingId = filter_var($_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$method = $_GET['method'] ?? '';
$bank = trim((string) ($_GET['bank'] ?? ''));
$booking = null;
$errors = [];
$paid = false;

// check balik lagi sekali kaedah & bank tu sah — sama macam payment_method.php
if (!in_array($method, ['qr', 'online_banking'], true)) {
    $method = '';
}
if ($method !== 'online_banking' || !in_array($bank, $allowedBanks, true)) {
    $bank = '';
}

if ($bookingId !== false) {
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id'
    );
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();

    if ($booking) {
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();
    }
}

if (!$booking) {
    $errors[] = 'We couldn\'t find that booking. Please start again from the booking form.';
} elseif ($method === '') {
    $errors[] = 'Missing payment method. Please choose a payment method again.';
}

// sini baru betul-betul rekod deposit — page sebelum ni (payment.php, payment_method.php)
// cuma pilih kaedah & confirm je, tak sentuh table payment/booking
if ($booking && !$paid && !$errors) {
    try {
        // insert rekod payment + tukar status booking jadi "confirmed" sekali gus dalam
        // satu transaction, elak jadi satu simpan tapi satu lagi tak simpan
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_status)
             VALUES (:booking_id, :deposit_paid, :payment_method, 'paid')"
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'deposit_paid' => $booking['deposit_amount'],
            'payment_method' => $method,
        ]);

        $stmt = $pdo->prepare(
            "UPDATE booking SET booking_status = 'confirmed' WHERE booking_id = :id AND booking_status = 'pending'"
        );
        $stmt->execute(['id' => $bookingId]);

        $pdo->commit(); // ok semua berjaya
        $paid = true;
    } catch (Exception $e) {
        $pdo->rollBack(); // ada masalah, undur balik
        $errors[] = 'Something went wrong while recording your payment. Please try again.';
    }

    // hantar emel confirm cuma lepas payment betul-betul dah simpan, dan letak luar
    // try/catch atas tu supaya kalau emel gagal, tak nampak macam payment yang gagal
    if ($paid) {
        send_status_email($pdo, $bookingId, 'confirmed');
    }
}

$redirectUrl = $booking
    ? 'sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['phone'])
    : '';

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Processing Payment — Casadive Villa';
$pageCss = 'style/process_payment.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/process_payment.view.php';
