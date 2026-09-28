<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$errors = [];

$old = [
    'full_name' => '',
    'phone' => '',
    'email' => '',
    'ic_passport' => '',
    'plate_num' => '',
    'location' => '',
    'check_in' => '',
    'check_out' => '',
    'total_guest' => '1',
    'accommodation_id' => '',
    'special_request' => '',
    'addon_bbq' => false,
    'addon_mattress' => false,
    'agree_terms' => false,
    'whatsapp_optin' => false,
];

$accommodations = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, price_weekend, capacity, image
     FROM accommodation
     WHERE status = 'available'
     ORDER BY accommodation_type, accommodation_id"
)->fetchAll();
$accommodationsById = [];
foreach ($accommodations as $acc) {
    $accommodationsById[(int) $acc['accommodation_id']] = $acc;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $qbCheckIn = trim((string) ($_GET['check_in'] ?? ''));
    $qbCheckOut = trim((string) ($_GET['check_out'] ?? ''));
    $qbGuests = trim((string) ($_GET['guests'] ?? ''));
    $qbAccommodation = trim((string) ($_GET['accommodation'] ?? ''));
    $qbType = trim((string) ($_GET['type'] ?? ''));

    if (DateTime::createFromFormat('Y-m-d', $qbCheckIn)) {
        $old['check_in'] = $qbCheckIn;
    }
    if (DateTime::createFromFormat('Y-m-d', $qbCheckOut)) {
        $old['check_out'] = $qbCheckOut;
    }
    if (filter_var($qbGuests, FILTER_VALIDATE_INT) !== false && (int) $qbGuests >= 1) {
        $old['total_guest'] = $qbGuests;
    }

    if ($qbAccommodation !== '') {
        foreach ($accommodations as $acc) {
            if (strcasecmp($acc['accommodation_name'], $qbAccommodation) === 0) {
                $old['accommodation_id'] = (string) $acc['accommodation_id'];
                break;
            }
        }
    }

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
    $old['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $old['email'] = trim((string) ($_POST['email'] ?? ''));
    $old['ic_passport'] = trim((string) ($_POST['ic_passport'] ?? ''));
    $old['plate_num'] = trim((string) ($_POST['plate_num'] ?? ''));
    $old['location'] = trim((string) ($_POST['location'] ?? ''));
    $old['check_in'] = trim((string) ($_POST['check_in'] ?? ''));
    $old['check_out'] = trim((string) ($_POST['check_out'] ?? ''));
    $old['total_guest'] = trim((string) ($_POST['total_guest'] ?? '1'));
    $old['accommodation_id'] = trim((string) ($_POST['accommodation_id'] ?? ''));
    $old['special_request'] = trim((string) ($_POST['special_request'] ?? ''));
    $old['addon_bbq'] = isset($_POST['addon_bbq']);
    $old['addon_mattress'] = isset($_POST['addon_mattress']);
    $old['agree_terms'] = isset($_POST['agree_terms']);
    $old['whatsapp_optin'] = isset($_POST['whatsapp_optin']);

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please review your details and submit again.';
    }

    if ($old['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($old['phone'] === '') {
        $errors[] = 'Phone number is required.';
    }
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required so we can send your booking confirmation and check-in reminder.';
    }
    if ($old['ic_passport'] === '') {
        $errors[] = 'IC / Passport Number is required.';
    }
    if (!$old['agree_terms']) {
        $errors[] = 'Please agree to the Terms & Conditions to continue.';
    }

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

    if (!$errors) {
        $weekdayPrice = (float) $selectedAccommodation['price'];
        $weekendPrice = $selectedAccommodation['price_weekend'] !== null ? (float) $selectedAccommodation['price_weekend'] : null;
        $stay = compute_stay_price($weekdayPrice, $weekendPrice, $checkIn, $checkOut);
        $nights = $stay['weekday_nights'] + $stay['weekend_nights'];
        $discountAmount = min($stay['total'], booking_long_stay_discount($nights));
        $addonAmount = booking_addon_total($old['addon_bbq'], $old['addon_mattress']);
        $totalAmount = $stay['total'] + $addonAmount - $discountAmount;
        $depositAmount = 1.00;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin)
                 VALUES (:full_name, :phone, :email, :plate_num, :location, :ic_passport, :whatsapp_optin)'
            );
            $stmt->execute([
                'full_name' => $old['full_name'],
                'phone' => $old['phone'],
                'email' => $old['email'],
                'plate_num' => $old['plate_num'] !== '' ? $old['plate_num'] : null,
                'location' => $old['location'] !== '' ? $old['location'] : null,
                'ic_passport' => $old['ic_passport'],
                'whatsapp_optin' => $old['whatsapp_optin'] ? 1 : 0,
            ]);
            $customerId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO booking (customer_id, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, special_request, addon_bbq, addon_mattress, discount_amount)
                 VALUES (:customer_id, :check_in, :check_out, :total_guest, :deposit_amount, :total_amount, "pending", :special_request, :addon_bbq, :addon_mattress, :discount_amount)'
            );
            $stmt->execute([
                'customer_id' => $customerId,
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'total_guest' => $totalGuest,
                'deposit_amount' => $depositAmount,
                'total_amount' => $totalAmount,
                'special_request' => $old['special_request'] !== '' ? $old['special_request'] : null,
                'addon_bbq' => $old['addon_bbq'] ? 1 : 0,
                'addon_mattress' => $old['addon_mattress'] ? 1 : 0,
                'discount_amount' => $discountAmount,
            ]);
            $bookingId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO booking_item (booking_id, accommodation_id, quantity, price)
                 VALUES (:booking_id, :accommodation_id, 1, :price)'
            );
            $stmt->execute([
                'booking_id' => $bookingId,
                'accommodation_id' => $accommodationId,
                'price' => $stay['total'],
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while saving your booking. Please try again.';
        }

        if (!$errors) {
            header('Location: payment.php?booking_id=' . $bookingId);
            exit;
        }
    }
}

$base = '../';
$active = '';
$pageTitle = 'Booking Details — Casadive Villa';
$pageCss = 'style/bookingform.css';

require __DIR__ . '/views/bookingform.view.php';
