<?php
/**
 * Halaman borang tempahan.
 * Pelanggan isikan butiran mereka di sini, dan sistem simpan tempahan sebelum ubah hala ke pembayaran.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$errors = []; // simpan semua mesej error kat sini untuk papar balik kat pelanggan

// simpan apa yang pelanggan taip, supaya kalau ada error borang tak kosong balik —
// diisi dari $_POST kalau submit gagal, atau dari $_GET kalau datang dari bar booking pantas homepage
$old = [
    'full_name' => '',
    'phone' => '',
    'email' => '',
    'plate_num' => '',
    'location' => '',
    'check_in' => '',
    'check_out' => '',
    'total_guest' => '1',
    'accommodation_id' => '',
    'special_request' => '',
];

// pelanggan cuma boleh pilih pakej yang admin dah tandakan "available"
$accommodations = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, price_weekend, capacity, image
     FROM accommodation
     WHERE status = 'available'
     ORDER BY accommodation_type, accommodation_id"
)->fetchAll();
// senarai sama, tapi diindeks ikut ID supaya senang cari "pakej mana yang dia pilih tu" nanti
$accommodationsById = [];
foreach ($accommodations as $acc) {
    $accommodationsById[(int) $acc['accommodation_id']] = $acc;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // page baru buka (bukan submit) — cuba pra-isi dari bar booking pantas homepage / link pakej
    $qbCheckIn = trim((string) ($_GET['check_in'] ?? ''));
    $qbCheckOut = trim((string) ($_GET['check_out'] ?? ''));
    $qbGuests = trim((string) ($_GET['guests'] ?? ''));
    $qbAccommodation = trim((string) ($_GET['accommodation'] ?? ''));
    $qbType = trim((string) ($_GET['type'] ?? ''));

    // isi cuma kalau format tarikh tu betul, jangan terima sampah dari URL
    if (DateTime::createFromFormat('Y-m-d', $qbCheckIn)) {
        $old['check_in'] = $qbCheckIn;
    }
    if (DateTime::createFromFormat('Y-m-d', $qbCheckOut)) {
        $old['check_out'] = $qbCheckOut;
    }
    if (filter_var($qbGuests, FILTER_VALIDATE_INT) !== false && (int) $qbGuests >= 1) {
        $old['total_guest'] = $qbGuests;
    }

    // padan nama pakej dari URL dengan senarai pakej available, cari yang sama nama je
    if ($qbAccommodation !== '') {
        foreach ($accommodations as $acc) {
            if (strcasecmp($acc['accommodation_name'], $qbAccommodation) === 0) {
                $old['accommodation_id'] = (string) $acc['accommodation_id'];
                break;
            }
        }
    }

    // kalau tak jumpa nama pakej yang sama, cuba padan ikut jenis je (Villa/Campsite)
    if ($old['accommodation_id'] === '' && $qbType !== '') {
        foreach ($accommodations as $acc) {
            if (strcasecmp($acc['accommodation_type'], $qbType) === 0) {
                $old['accommodation_id'] = (string) $acc['accommodation_id'];
                break;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // pelanggan submit borang — ambil semua data dari $_POST dulu
    $old['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $old['email'] = trim((string) ($_POST['email'] ?? ''));
    $old['plate_num'] = trim((string) ($_POST['plate_num'] ?? ''));
    $old['location'] = trim((string) ($_POST['location'] ?? ''));
    $old['check_in'] = trim((string) ($_POST['check_in'] ?? ''));
    $old['check_out'] = trim((string) ($_POST['check_out'] ?? ''));
    $old['total_guest'] = trim((string) ($_POST['total_guest'] ?? '1'));
    $old['accommodation_id'] = trim((string) ($_POST['accommodation_id'] ?? ''));
    $old['special_request'] = trim((string) ($_POST['special_request'] ?? ''));

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please review your details and submit again.';
    }

    // semakan asas — medan wajib takboleh kosong
    if ($old['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($old['phone'] === '') {
        $errors[] = 'Phone number is required.';
    }
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required so we can send your booking confirmation and check-in reminder.';
    }

    // pastikan tarikh check-in/out betul format dan check-out kena lepas check-in
    $checkIn = DateTime::createFromFormat('Y-m-d', $old['check_in']) ?: null;
    $checkOut = DateTime::createFromFormat('Y-m-d', $old['check_out']) ?: null;
    if (!$checkIn || !$checkOut) {
        $errors[] = 'Please provide valid check-in and check-out dates.';
    } elseif ($checkOut <= $checkIn) {
        $errors[] = 'Check-out date must be after check-in date.';
    }

    $totalGuest = filter_var($old['total_guest'], FILTER_VALIDATE_INT);
    if ($totalGuest === false || $totalGuest < 1) {
        $errors[] = 'Number of guests must be at least 1.';
    }

    $accommodationId = filter_var($old['accommodation_id'], FILTER_VALIDATE_INT);
    $selectedAccommodation = $accommodationId !== false ? ($accommodationsById[$accommodationId] ?? null) : null;
    if (!$selectedAccommodation) {
        $errors[] = 'Please select a valid accommodation package.';
    } elseif ($totalGuest !== false && $totalGuest > (int) $selectedAccommodation['capacity']) {
        $errors[] = "This package can only host up to {$selectedAccommodation['capacity']} guests.";
    } elseif ($checkIn && $checkOut) {
        // check double-booking: cari tempahan lain (yang tak cancel) untuk unit yang sama
        // yang tarikhnya bertindih dengan tarikh yang pelanggan minta ni
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM booking_item bi
             JOIN booking b ON b.booking_id = bi.booking_id
             WHERE bi.accommodation_id = :accommodation_id
               AND b.booking_status != 'cancelled'
               AND b.check_in < :check_out
               AND b.check_out > :check_in
             LIMIT 1"
        );
        $stmt->execute([
            'accommodation_id' => $accommodationId,
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
        ]);
        if ($stmt->fetch()) {
            $errors[] = "{$selectedAccommodation['accommodation_name']} is already booked for part of those dates. Please choose different dates or a different package.";
        }
    }

    // baru sentuh database kalau semua semakan atas tu lepas takde error
    if (!$errors) {
        $weekdayPrice = (float) $selectedAccommodation['price'];
        $weekendPrice = $selectedAccommodation['price_weekend'] !== null ? (float) $selectedAccommodation['price_weekend'] : null;
        $stay = compute_stay_price($weekdayPrice, $weekendPrice, $checkIn, $checkOut); // kira jumlah harga ikut malam weekday/weekend
        $totalAmount = $stay['total'];
        $depositAmount = 50.00; // deposit tetap RM50 untuk confirm mana-mana tempahan

        try {
            // customer + booking + booking_item kena simpan sekali gus — bungkus dalam
            // satu transaction, kalau mana-mana insert gagal, semua rollback (tak simpan separuh-separuh)
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO customer (full_name, phone, email, plate_num, location) VALUES (:full_name, :phone, :email, :plate_num, :location)'
            );
            $stmt->execute([
                'full_name' => $old['full_name'],
                'phone' => $old['phone'],
                'email' => $old['email'],
                'plate_num' => $old['plate_num'] !== '' ? $old['plate_num'] : null,
                'location' => $old['location'] !== '' ? $old['location'] : null,
            ]);
            $customerId = (int) $pdo->lastInsertId(); // id customer baru yang kita baru insert

            $stmt = $pdo->prepare(
                'INSERT INTO booking (customer_id, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, special_request)
                 VALUES (:customer_id, :check_in, :check_out, :total_guest, :deposit_amount, :total_amount, "pending", :special_request)'
            );
            $stmt->execute([
                'customer_id' => $customerId,
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'total_guest' => $totalGuest,
                'deposit_amount' => $depositAmount,
                'total_amount' => $totalAmount,
                'special_request' => $old['special_request'] !== '' ? $old['special_request'] : null,
            ]);
            $bookingId = (int) $pdo->lastInsertId(); // id booking baru, kita perlukan untuk booking_item & redirect

            $stmt = $pdo->prepare(
                'INSERT INTO booking_item (booking_id, accommodation_id, quantity, price)
                 VALUES (:booking_id, :accommodation_id, 1, :price)'
            );
            $stmt->execute([
                'booking_id' => $bookingId,
                'accommodation_id' => $accommodationId,
                'price' => $totalAmount,
            ]);

            $pdo->commit(); // semua ok, confirm simpan
        } catch (Exception $e) {
            $pdo->rollBack(); // ada masalah, undur balik semua insert tadi
            $errors[] = 'Something went wrong while saving your booking. Please try again.';
        }

        if (!$errors) {
            // booking dah simpan, terus hantar ke page bayar deposit
            header('Location: payment.php?booking_id=' . $bookingId);
            exit;
        }
    }
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Booking Details — Casadive Villa';
$pageCss = 'style/bookingform.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/bookingform.view.php';
