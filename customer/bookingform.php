<?php
# calling function require_once() untuk load fail db.php supaya dapat object $pdo untuk connect database
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna fungsi csrf_verify()
require_once __DIR__ . '/../includes/auth.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna fungsi pengiraan harga
require_once __DIR__ . '/../includes/helpers.php';

# assign array kosong ke $errors untuk simpan mesej error validation
$errors = [];

# assign array default value borang ke $old supaya field borang tak kosong bila reload/error
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

# calling method query() & fetchAll() dari object $pdo that assign to variable name $accommodations untuk ambil semua accommodation yang available
$accommodations = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, price_weekend, capacity, image
     FROM accommodation
     WHERE status = 'available'
     ORDER BY accommodation_type, accommodation_id"
)->fetchAll();
# assign array kosong ke $accommodationsById untuk simpan accommodation ikut id sebagai key, senang nak cari balik
$accommodationsById = [];
# loop setiap accommodation untuk bina array $accommodationsById ikut id
foreach ($accommodations as $acc) {
    $accommodationsById[(int) $acc['accommodation_id']] = $acc;
}
$ratePeriodsByAccommodation = [];
$ratePeriodRows = $pdo->query(
    'SELECT accommodation_id, label, start_date, end_date, price
     FROM accommodation_rate_period
     ORDER BY start_date'
)->fetchAll();
foreach ($ratePeriodRows as $ratePeriod) {
    $ratePeriodsByAccommodation[(int) $ratePeriod['accommodation_id']][] = $ratePeriod;
}

# check kalau request bukan POST (means page baru dibuka), ambil value dari query string untuk pre-fill borang
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    # ambil value $_GET['check_in'] then trim, assign to variable name $qbCheckIn
    $qbCheckIn = trim((string) ($_GET['check_in'] ?? ''));
    # ambil value $_GET['check_out'] then trim, assign to variable name $qbCheckOut
    $qbCheckOut = trim((string) ($_GET['check_out'] ?? ''));
    # ambil value $_GET['guests'] then trim, assign to variable name $qbGuests
    $qbGuests = trim((string) ($_GET['guests'] ?? ''));
    # ambil value $_GET['accommodation'] then trim, assign to variable name $qbAccommodation
    $qbAccommodation = trim((string) ($_GET['accommodation'] ?? ''));
    # ambil value $_GET['type'] then trim, assign to variable name $qbType
    $qbType = trim((string) ($_GET['type'] ?? ''));

    # calling function DateTime::createFromFormat() untuk check $qbCheckIn ialah tarikh yang valid
    if (DateTime::createFromFormat('Y-m-d', $qbCheckIn)) {
        $old['check_in'] = $qbCheckIn;
    }
    # calling function DateTime::createFromFormat() untuk check $qbCheckOut ialah tarikh yang valid
    if (DateTime::createFromFormat('Y-m-d', $qbCheckOut)) {
        $old['check_out'] = $qbCheckOut;
    }
    # calling function filter_var() untuk check $qbGuests ialah integer valid dan sekurang-kurangnya 1
    if (filter_var($qbGuests, FILTER_VALIDATE_INT) !== false && (int) $qbGuests >= 1) {
        $old['total_guest'] = $qbGuests;
    }

    # check kalau nama accommodation dari query string diisi, cari accommodation yang sepadan
    if ($qbAccommodation !== '') {
        # loop setiap accommodation untuk padankan nama dgn $qbAccommodation
        foreach ($accommodations as $acc) {
            # calling function strcasecmp() untuk banding nama accommodation dgn $qbAccommodation, abaikan huruf besar/kecil
            if (strcasecmp($acc['accommodation_name'], $qbAccommodation) === 0) {
                $old['accommodation_id'] = (string) $acc['accommodation_id'];
                break;
            }
        }
    }

    # check kalau accommodation belum dipilih lagi tapi ada type dari query string, cari ikut type
    if ($old['accommodation_id'] === '' && $qbType !== '') {
        # loop setiap accommodation untuk padankan type dgn $qbType
        foreach ($accommodations as $acc) {
            # calling function strcasecmp() untuk banding type accommodation dgn $qbType, abaikan huruf besar/kecil
            if (strcasecmp($acc['accommodation_type'], $qbType) === 0) {
                $old['accommodation_id'] = (string) $acc['accommodation_id'];
                break;
            }
        }
    }
}

# check request method POST, means customer submit borang booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    # ambil semua value dari $_POST then trim, assign balik ke dalam array $old untuk simpan input customer
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
    # calling function isset() untuk check checkbox addon_bbq ditick ke tidak
    $old['addon_bbq'] = isset($_POST['addon_bbq']);
    # calling function isset() untuk check checkbox addon_mattress ditick ke tidak
    $old['addon_mattress'] = isset($_POST['addon_mattress']);
    # calling function isset() untuk check checkbox agree_terms ditick ke tidak
    $old['agree_terms'] = isset($_POST['agree_terms']);
    # calling function isset() untuk check checkbox whatsapp_optin ditick ke tidak
    $old['whatsapp_optin'] = isset($_POST['whatsapp_optin']);

    # calling function csrf_verify() untuk pastikan borang ni betul-betul dihantar dari page kita sendiri, bukan attacker
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please review your details and submit again.';
    }

    # check kalau nama penuh tak diisi
    if ($old['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    # check kalau nombor phone tak diisi
    if ($old['phone'] === '') {
        $errors[] = 'Phone number is required.';
    }
    # check kalau email tak diisi atau format email tak valid
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required so we can send your booking confirmation and check-in reminder.';
    }
    # check kalau IC/passport tak diisi
    if ($old['ic_passport'] === '') {
        $errors[] = 'IC / Passport Number is required.';
    }
    # check kalau customer tak tick setuju terma & syarat
    if (!$old['agree_terms']) {
        $errors[] = 'Please agree to the Terms & Conditions to continue.';
    }

    # calling function DateTime::createFromFormat() that assign to variable name $checkIn untuk tukar text tarikh jadi object DateTime
    $checkIn = DateTime::createFromFormat('Y-m-d', $old['check_in']) ?: null;
    # calling function DateTime::createFromFormat() that assign to variable name $checkOut untuk tukar text tarikh jadi object DateTime
    $checkOut = DateTime::createFromFormat('Y-m-d', $old['check_out']) ?: null;
    # check kalau tarikh check-in atau check-out tak valid
    if (!$checkIn || !$checkOut) {
        $errors[] = 'Please provide valid check-in and check-out dates.';
    } elseif ($checkOut <= $checkIn) {
        # check tarikh check-out kena lepas tarikh check-in
        $errors[] = 'Check-out date must be after check-in date.';
    }

    # calling function filter_var() that assign to variable name $totalGuest untuk validate bilangan tetamu adalah integer
    $totalGuest = filter_var($old['total_guest'], FILTER_VALIDATE_INT);
    # check kalau bilangan tetamu tak valid atau kurang dari 1
    if ($totalGuest === false || $totalGuest < 1) {
        $errors[] = 'Number of guests must be at least 1.';
    }

    # calling function filter_var() that assign to variable name $accommodationId untuk validate id accommodation adalah integer
    $accommodationId = filter_var($old['accommodation_id'], FILTER_VALIDATE_INT);
    # check $accommodationId valid then cari data accommodation dalam $accommodationsById, assign to variable name $selectedAccommodation
    $selectedAccommodation = $accommodationId !== false ? ($accommodationsById[$accommodationId] ?? null) : null;
    # check kalau accommodation yang dipilih tak wujud/tak valid
    if (!$selectedAccommodation) {
        $errors[] = 'Please select a valid accommodation package.';
    } elseif ($totalGuest !== false && $totalGuest > (int) $selectedAccommodation['capacity']) {
        # check bilangan tetamu melebihi kapasiti maksimum package yang dipilih
        $errors[] = "This package can only host up to {$selectedAccommodation['capacity']} guests.";
    } elseif ($checkIn && $checkOut) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query check tarikh bertindih dgn booking sedia ada
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
        # calling method execute() dari object $stmt untuk jalankan query check tarikh bertindih
        $stmt->execute([
            'accommodation_id' => $accommodationId,
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
        ]);
        # calling method fetch() dari object $stmt untuk check ada row hasil, means tarikh dah ditempah orang lain
        if ($stmt->fetch()) {
            $errors[] = "{$selectedAccommodation['accommodation_name']} is already booked for part of those dates. Please choose different dates or a different package.";
        }
    }

    # check takde error validation langsung baru boleh proceed simpan booking
    if (!$errors) {
        # assign harga weekday accommodation ke $weekdayPrice
        $weekdayPrice = (float) $selectedAccommodation['price'];
        # check ada harga weekend khas ke tidak, assign to variable name $weekendPrice
        $weekendPrice = $selectedAccommodation['price_weekend'] !== null ? (float) $selectedAccommodation['price_weekend'] : null;
                $rateStmt = $pdo->prepare(
                        'SELECT label, start_date, end_date, price
                         FROM accommodation_rate_period
                         WHERE accommodation_id = :accommodation_id
                             AND start_date < :check_out AND end_date >= :check_in
                         ORDER BY start_date'
                );
                $rateStmt->execute([
                        'accommodation_id' => $accommodationId,
                        'check_in' => $checkIn->format('Y-m-d'),
                        'check_out' => $checkOut->format('Y-m-d'),
                ]);
                $ratePeriods = $rateStmt->fetchAll();
                $stay = compute_stay_price($weekdayPrice, $weekendPrice, $checkIn, $checkOut, $ratePeriods);
                $nights = $stay['weekday_nights'] + $stay['weekend_nights'] + $stay['special_nights'];
        # calling function booking_long_stay_discount() & min() that assign to variable name $discountAmount untuk kira diskaun ikut lama tinggal
        $discountAmount = min($stay['total'], booking_long_stay_discount($nights));
        # calling function booking_addon_total() that assign to variable name $addonAmount untuk kira jumlah harga addon bbq/mattress
        $addonAmount = booking_addon_total($old['addon_bbq'], $old['addon_mattress']);
        # kira jumlah keseluruhan (stay + addon - discount), assign to $totalAmount
        $totalAmount = $stay['total'] + $addonAmount - $discountAmount;
        # set deposit tempahan bagi setiap villa
        $depositAmount = $selectedAccommodation['accommodation_type'] === 'Villa'
            ? BOOKING_DEPOSIT_AMOUNT
            : 0.0;

        try {
            # calling method beginTransaction() dari object $pdo untuk mula transaction, supaya semua insert berjaya sekali atau gagal sekali
            $pdo->beginTransaction();

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert data customer baru
        $rateStmt = $pdo->prepare(
            'SELECT label, start_date, end_date, price
             FROM accommodation_rate_period
             WHERE accommodation_id = :accommodation_id
               AND start_date < :check_out AND end_date >= :check_in
             ORDER BY start_date'
        );
        $rateStmt->execute([
            'accommodation_id' => $accommodationId,
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
        ]);
        $ratePeriods = $rateStmt->fetchAll();
        $stay = compute_stay_price($weekdayPrice, $weekendPrice, $checkIn, $checkOut, $ratePeriods);
        $nights = $stay['weekday_nights'] + $stay['weekend_nights'] + $stay['special_nights'];
            $customerId = (int) $pdo->lastInsertId();

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert booking baru
            $stmt = $pdo->prepare(
                'INSERT INTO booking (customer_id, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, special_request, addon_bbq, addon_mattress, discount_amount)
                 VALUES (:customer_id, :check_in, :check_out, :total_guest, :deposit_amount, :total_amount, "pending", :special_request, :addon_bbq, :addon_mattress, :discount_amount)'
            );
            # calling method execute() dari object $stmt untuk simpan data booking ke database
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
            # calling method lastInsertId() dari object $pdo that assign to variable name $bookingId untuk dapatkan id booking yang baru insert
            $bookingId = (int) $pdo->lastInsertId();

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert item booking (accommodation yang dipilih)
            $stmt = $pdo->prepare(
                'INSERT INTO booking_item (booking_id, accommodation_id, quantity, price)
                 VALUES (:booking_id, :accommodation_id, 1, :price)'
            );
            # calling method execute() dari object $stmt untuk simpan data booking item ke database
            $stmt->execute([
                'booking_id' => $bookingId,
                'accommodation_id' => $accommodationId,
                'price' => $stay['total'],
            ]);

            # calling method commit() dari object $pdo untuk sahkan semua insert tadi jadi permanent dalam database
            $pdo->commit();
        } catch (Exception $e) {
            # calling method rollBack() dari object $pdo untuk batalkan semua insert kalau ada error masa transaction
            $pdo->rollBack();
            $errors[] = 'Something went wrong while saving your booking. Please try again.';
        }

        # check takde error lepas transaction, terus redirect ke page payment
        if (!$errors) {
            # calling function header() untuk redirect browser ke page payment.php dengan bawa id booking
            header('Location: payment.php?booking_id=' . $bookingId);
            exit;
        }
    }
}

# assign value '../' ke variable $base untuk set path relative balik ke root folder
$base = '../';
# assign string kosong ke $active sbb page ni takde nav item yang kena highlight
$active = '';
# assign value title page ke $pageTitle untuk papar kat tag <title> dan header
$pageTitle = 'Booking Details — Casadive Villa';
# assign path css khas untuk page ni ke $pageCss
$pageCss = 'style/bookingform.css';

# calling function require() untuk load fail view bookingform supaya papar html page ni
require __DIR__ . '/views/bookingform.view.php';
