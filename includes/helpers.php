<?php
declare(strict_types=1);

const ADDON_BBQ_PRICE = 20.00;
const ADDON_MATTRESS_PRICE = 10.00;
const BOOKING_DEPOSIT_AMOUNT = 50.00;

function booking_addon_total(bool $bbq, bool $mattress): float
{
    # kira jumlah harga addon, tambah harga bbq kalau dipilih, tambah harga tilam kalau dipilih
    return ($bbq ? ADDON_BBQ_PRICE : 0.0) + ($mattress ? ADDON_MATTRESS_PRICE : 0.0);
}

function accommodation_rate_periods_table_exists(PDO $pdo): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }

    try {
        $pdo->query('SELECT 1 FROM accommodation_rate_period LIMIT 0');
        return $exists = true;
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '42S02' || (int) ($e->errorInfo[1] ?? 0) === 1146) {
            return $exists = false;
        }
        throw $e;
    }
}

function accommodation_rate_period_categories_available(PDO $pdo): bool
{
    if (!accommodation_rate_periods_table_exists($pdo)) {
        return false;
    }

    try {
        $pdo->query('SELECT rate_type FROM accommodation_rate_period LIMIT 0');
        return true;
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '42S22' || (int) ($e->errorInfo[1] ?? 0) === 1054) {
            return false;
        }
        throw $e;
    }
}

function fetch_accommodation_rate_periods(
    PDO $pdo,
    ?int $accommodationId = null,
    bool $upcomingOnly = false,
    ?DateTimeInterface $checkIn = null,
    ?DateTimeInterface $checkOut = null
): array {
    if (!accommodation_rate_periods_table_exists($pdo)) {
        return [];
    }
    if (($checkIn === null) !== ($checkOut === null)) {
        throw new InvalidArgumentException('Both check-in and check-out dates are required.');
    }

    $conditions = [];
    $params = [];
    if ($accommodationId !== null) {
        $conditions[] = 'accommodation_id = :accommodation_id';
        $params['accommodation_id'] = $accommodationId;
    }
    if ($upcomingOnly) {
        $conditions[] = 'end_date >= CURDATE()';
    }
    if ($checkIn && $checkOut) {
        $conditions[] = 'start_date < :check_out AND end_date >= :check_in';
        $params['check_in'] = $checkIn->format('Y-m-d');
        $params['check_out'] = $checkOut->format('Y-m-d');
    }

    $rateTypeSelect = 'rate_type';
    try {
        $pdo->query('SELECT rate_type FROM accommodation_rate_period LIMIT 0');
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '42S22' || (int) ($e->errorInfo[1] ?? 0) === 1054) {
            $rateTypeSelect = "'custom' AS rate_type";
        } else {
            throw $e;
        }
    }

    $sql = 'SELECT accommodation_id, label, ' . $rateTypeSelect . ', start_date, end_date, price FROM accommodation_rate_period';
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY start_date';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function save_gallery_upload(PDO $pdo, ?array $file, string $caption, int $uploadedBy): bool|string
{
    # check kalau tiada fail, nama fail kosong, atau ada error semasa upload, tolak terus
    if (!$file || empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'Please choose an image to upload.';
    }

    # assign array extension yang dibenarkan ke $allowedExt
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    # calling function pathinfo() that assign to variable name $ext untuk ambil extension fail yang diupload
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    # assign value saiz maksimum fail (5MB) ke $maxSize
    $maxSize = 5 * 1024 * 1024;

    # check extension tak dibenarkan, saiz tak sah, atau bukan gambar sebenar - kalau salah satu true, tolak upload
    if (!in_array($ext, $allowedExt, true) || $file['size'] <= 0 || $file['size'] > $maxSize || @getimagesize($file['tmp_name']) === false) {
        return 'Please upload a valid image (JPG, PNG or WEBP, max 5MB).';
    }

    # assign value path folder simpan gambar gallery ke $destDir
    $destDir = __DIR__ . '/../assets/uploads/gallery/';
    # check kalau folder tu belum wujud, kena create dulu
    if (!is_dir($destDir)) {
        # calling function mkdir() untuk create folder simpan gambar gallery
        mkdir($destDir, 0755, true);
    }
    # calling function bin2hex() & random_bytes() that assign to variable name $filename untuk jana nama fail unik supaya tak clash
    $filename = 'gallery_' . bin2hex(random_bytes(4)) . '.' . $ext;

    # calling function move_uploaded_file() untuk pindah fail dari lokasi sementara ke folder gallery, check kalau gagal
    if (!move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
        return 'We could not save your uploaded image. Please try again.';
    }

    try {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert gambar gallery
        $stmt = $pdo->prepare(
            'INSERT INTO gallery (image_path, caption, uploaded_by) VALUES (:path, :caption, :uploaded_by)'
        );
        # calling method execute() dari object $stmt untuk simpan rekod gambar gallery baru dalam database
        $stmt->execute([
            'path' => 'assets/uploads/gallery/' . $filename,
            'caption' => $caption !== '' ? $caption : null,
            'uploaded_by' => $uploadedBy,
        ]);
    } catch (PDOException $e) {
        # calling function unlink() untuk padam balik fail yang dah diupload sbb simpan ke database gagal
        unlink($destDir . $filename);
        # calling function error_log() untuk simpan mesej error dalam log server
        error_log('Failed to save gallery image: ' . $e->getMessage());
        return 'Gallery is not available right now. Please try again later.';
    }

    return true;
}

function delete_gallery_image(PDO $pdo, int $galleryId): bool
{
    try {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari path gambar
        $stmt = $pdo->prepare('SELECT image_path FROM gallery WHERE gallery_id = :id');
        # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dgn $galleryId
        $stmt->execute(['id' => $galleryId]);
        # calling method fetchColumn() dari object $stmt that assign to variable name $imagePath untuk ambil path gambar tu je
        $imagePath = $stmt->fetchColumn();

        # check kalau rekod gambar tak jumpa, tak payah teruskan padam
        if ($imagePath === false) {
            return false;
        }

        # calling method prepare() & execute() untuk padam terus rekod gambar tu dari database
        $pdo->prepare('DELETE FROM gallery WHERE gallery_id = :id')->execute(['id' => $galleryId]);

        # assign value path penuh fail gambar kat server ke $fullPath
        $fullPath = __DIR__ . '/../' . $imagePath;
        # check kalau fail gambar tu memang wujud kat server, baru boleh padam
        if (is_file($fullPath)) {
            # calling function unlink() untuk padam fail gambar dari storage server
            unlink($fullPath);
        }

        return true;
    } catch (PDOException $e) {
        # calling function error_log() untuk simpan mesej error dalam log server
        error_log('Failed to delete gallery image: ' . $e->getMessage());
        return false;
    }
}

const LONG_STAY_DISCOUNT_MIN_NIGHTS = 3;
const LONG_STAY_DISCOUNT_AMOUNT = 50.00;

function booking_long_stay_discount(int $nights): float
{
    # check kalau bilangan malam cukup syarat diskaun, pulangkan jumlah diskaun, kalau tak pulangkan 0
    return $nights >= LONG_STAY_DISCOUNT_MIN_NIGHTS ? LONG_STAY_DISCOUNT_AMOUNT : 0.0;
}

function format_status(string $status): string
{
    # calling function ucwords() & str_replace() untuk tukar 'checked_in' jadi 'Checked In' senang dibaca
    return ucwords(str_replace('_', ' ', $status));
}

function format_booking_ref(int $bookingId): string
{
    # cantum prefix 'CDV' dgn id booking untuk bina rujukan booking
    return 'CDV' . $bookingId;
}

function parse_booking_ref(string $ref): int|false
{
    # calling function trim() that assign to variable name $ref untuk buang ruang kosong dekat depan/belakang
    $ref = trim($ref);
    # check kalau rujukan tu bermula dgn 'CDV', buang prefix tu untuk dapat nombor je
    if (stripos($ref, 'CDV') === 0) {
        # calling function substr() that assign to variable name $ref untuk potong prefix 'CDV' dari depan
        $ref = substr($ref, 3);
    }

    # calling function filter_var() untuk tukar $ref jadi integer sah, pulangkan false kalau bukan nombor
    return filter_var($ref, FILTER_VALIDATE_INT);
}

function fetch_booking_door_codes_plain(PDO $pdo, int $bookingId): string
{
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari door code accommodation dalam booking ni
    $stmt = $pdo->prepare(
        "SELECT DISTINCT a.accommodation_name, a.door_code
         FROM booking_item bi
         JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
         WHERE bi.booking_id = :id AND a.door_code IS NOT NULL AND a.door_code <> ''"
    );
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dgn $bookingId
    $stmt->execute(['id' => $bookingId]);
    # calling method fetchAll() dari object $stmt that assign to variable name $rows untuk ambil semua row hasil query
    $rows = $stmt->fetchAll();

    # check kalau cuma 1 accommodation ada door code, terus pulangkan kod tu je
    if (count($rows) === 1) {
        return $rows[0]['door_code'];
    }
    # check kalau lebih dari 1 accommodation ada door code, kena senaraikan semua
    if (count($rows) > 1) {
        # calling function array_map() & implode() untuk bina teks "nama: kod" bagi setiap row lalu cantum dgn koma
        return implode(', ', array_map(
            fn ($r) => $r['accommodation_name'] . ': ' . $r['door_code'],
            $rows
        ));
    }

    return 'Please contact the admin for your door lock code.';
}

function booking_grand_total(array $booking): float
{
    # kira jumlah keseluruhan booking, tambah total_amount dgn deposit_amount
    return (float) $booking['total_amount'] + (float) $booking['deposit_amount'];
}

function submit_booking_payment(PDO $pdo, int $bookingId, float $amount, string $paymentMethod, ?string $receipt = null): bool
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT booking_status FROM booking WHERE booking_id = :id FOR UPDATE');
        $stmt->execute(['id' => $bookingId]);
        $bookingStatus = $stmt->fetchColumn();
        if (!in_array($bookingStatus, ['pending', 'confirmed'], true)) {
            $pdo->commit();
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $bookingId]);
        $latestStatus = $stmt->fetchColumn();
        if (in_array($latestStatus, ['paid', 'pending'], true)) {
            $pdo->commit();
            return false;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_status, receipt)
             VALUES (:booking_id, :deposit_paid, :payment_method, 'pending', :receipt)"
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'deposit_paid' => $amount,
            'payment_method' => $paymentMethod,
            'receipt' => $receipt,
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function payment_needs_refund(string $bookingStatus, ?string $paymentStatus): bool
{
    # check kalau booking dah cancelled tapi payment tadi paid/partial, maknanya perlu refund
    return $bookingStatus === 'cancelled' && in_array($paymentStatus, ['paid', 'partial'], true);
}

function accommodation_rate_for_date(array $accommodation, DateTimeInterface $date, array $ratePeriods = []): array
{
    $nightDate = $date->format('Y-m-d');
    $isWeekend = in_array((int) $date->format('N'), [5, 6], true);
    $dayRate = null;
    $specialRate = null;
    $specialRatePriority = -1;
    $specialRatePriorities = [
        'seasonal' => 10,
        'ramadan' => 20,
        'school_holiday' => 30,
        'public_holiday' => 40,
        'custom' => 50,
        'super_peak_cny' => 60,
        'super_peak_eid' => 70,
    ];

    foreach ($ratePeriods as $ratePeriod) {
        if ($nightDate < $ratePeriod['start_date'] || $nightDate > $ratePeriod['end_date']) {
            continue;
        }

        $rateType = $ratePeriod['rate_type'] ?? 'custom';
        if ($rateType === 'weekday' && !$isWeekend) {
            $dayRate = ['price' => (float) $ratePeriod['price'], 'label' => 'Weekday', 'category' => 'weekday'];
        } elseif ($rateType === 'weekend' && $isWeekend) {
            $dayRate = ['price' => (float) $ratePeriod['price'], 'label' => 'Weekend', 'category' => 'weekend'];
        } elseif (!in_array($rateType, ['weekday', 'weekend'], true)) {
            $priority = $specialRatePriorities[$rateType] ?? $specialRatePriorities['custom'];
            if ($priority > $specialRatePriority) {
                $specialRatePriority = $priority;
                $specialRate = [
                    'price' => (float) $ratePeriod['price'],
                    'label' => $ratePeriod['label'],
                    'category' => 'special',
                ];
            }
        }
    }

    if ($specialRate !== null) {
        return $specialRate;
    }
    if ($dayRate !== null) {
        return $dayRate;
    }
    if ($isWeekend) {
        return [
            'price' => (float) ($accommodation['price_weekend'] ?? $accommodation['price']),
            'label' => 'Weekend',
            'category' => 'weekend',
        ];
    }

    return ['price' => (float) $accommodation['price'], 'label' => 'Weekday', 'category' => 'weekday'];
}

function resolve_accommodation_rate_date(?string $date): DateTimeImmutable
{
    if ($date !== null && $date !== '') {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            $parsed
            && $parsed->format('Y-m-d') === $date
            && (!$dateErrors || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        ) {
            return $parsed;
        }
    }

    return new DateTimeImmutable('today');
}

function compute_stay_price(float $weekdayPrice, ?float $weekendPrice, DateTime $checkIn, DateTime $checkOut, array $ratePeriods = []): array
{
    $accommodation = ['price' => $weekdayPrice, 'price_weekend' => $weekendPrice ?? $weekdayPrice];
    $weekdayNights = 0;
    $weekendNights = 0;
    $specialNights = 0;
    $total = 0.0;
    $cursor = clone $checkIn;

    while ($cursor < $checkOut) {
        $rate = accommodation_rate_for_date($accommodation, $cursor, $ratePeriods);
        if ($rate['category'] === 'special') {
            $specialNights++;
        } elseif ($rate['category'] === 'weekend') {
            $weekendNights++;
        } else {
            $weekdayNights++;
        }
        $total += $rate['price'];
        $cursor->modify('+1 day');
    }

    return [
        'weekday_nights' => $weekdayNights,
        'weekend_nights' => $weekendNights,
        'special_nights' => $specialNights,
        'total' => round($total, 2),
    ];
}

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

function lookup_bookings_by_phone(PDO $pdo, string $phone): array
{
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari 10 booking terbaru ikut phone
    $stmt = $pdo->prepare(
        'SELECT b.booking_id, b.check_in, b.check_out, b.total_guest, b.total_amount,
                b.deposit_amount, b.booking_status
         FROM booking b
         JOIN customer c ON c.customer_id = b.customer_id
         WHERE c.phone = :phone
         ORDER BY b.booking_id DESC
         LIMIT 10'
    );
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :phone
    $stmt->execute(['phone' => $phone]);
    # calling method fetchAll() dari object $stmt that assign to variable name $bookings untuk ambil semua row hasil query
    $bookings = $stmt->fetchAll();

    # check kalau tiada booking langsung jumpa untuk phone ni, terus pulangkan not found
    if (!$bookings) {
        return ['found' => false];
    }

    # assign array kosong ke $result untuk kumpul detail lengkap tiap-tiap booking
    $result = [];
    # loop setiap booking yang jumpa untuk lengkapkan detail accommodation & payment dia
    foreach ($bookings as $booking) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query senarai accommodation dalam booking ni
        $stmt = $pdo->prepare(
            'SELECT GROUP_CONCAT(a.accommodation_name SEPARATOR ", ") AS accommodations
             FROM booking_item bi JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id
        $stmt->execute(['id' => $booking['booking_id']]);
        # calling method fetchColumn() dari object $stmt that assign to variable name $accommodations, fallback '—' kalau takde
        $accommodations = $stmt->fetchColumn() ?: '—';

        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query status payment terkini
        $stmt = $pdo->prepare('SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1');
        # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id
        $stmt->execute(['id' => $booking['booking_id']]);
        # calling method fetchColumn() dari object $stmt that assign to variable name $paymentStatus, fallback null kalau takde
        $paymentStatus = $stmt->fetchColumn() ?: null;

        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query jumlah deposit yang dah paid
        $stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
        # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id
        $stmt->execute(['id' => $booking['booking_id']]);
        # calling method fetchColumn() dari object $stmt that assign to variable name $amountPaid, fallback 0 kalau takde
        $amountPaid = (float) ($stmt->fetchColumn() ?: 0);

        # assign array detail booking ni ke dalam $result untuk digabung sekali dgn booking lain
        $result[] = [
            'booking_ref' => format_booking_ref((int) $booking['booking_id']),
            'status' => format_status($booking['booking_status']),
            'check_in' => date('d/m/Y', strtotime($booking['check_in'])),
            'check_out' => date('d/m/Y', strtotime($booking['check_out'])),
            'total_guest' => (int) $booking['total_guest'],
            'accommodations' => $accommodations,
            'payment_status' => $paymentStatus ? format_status($paymentStatus) : null,
            'balance_due' => round(booking_grand_total($booking) - $amountPaid, 2),
        ];
    }

    return ['found' => true, 'bookings' => $result];
}

function recommend_accommodations(PDO $pdo, array $criteria): array
{
    # assign value $criteria['guests'] (dah cast ke int) ke $guests
    $guests = (int) $criteria['guests'];
    # ambil value $criteria['type'] kalau wujud, kalau tak wujud pulangkan null
    $type = $criteria['type'] ?? null;
    # ambil value $criteria['budget'] kalau wujud, kalau tak wujud pulangkan null
    $budget = $criteria['budget'] ?? null;
    # ambil value $criteria['check_in'] kalau wujud, kalau tak wujud pulangkan null
    $checkIn = $criteria['check_in'] ?? null;
    # ambil value $criteria['check_out'] kalau wujud, kalau tak wujud pulangkan null
    $checkOut = $criteria['check_out'] ?? null;

    # check kalau kedua-dua tarikh check-in & check-out ada dan check-out lepas check-in, baru guna filter tarikh
    $useDates = $checkIn && $checkOut && $checkOut > $checkIn;

    # assign query asas cari accommodation yang available & cukup kapasiti ke $sql
    $sql = 'SELECT * FROM accommodation a WHERE a.status = :status AND a.capacity >= :guests';
    $params = ['status' => 'available', 'guests' => $guests];

    # check kalau ada filter jenis accommodation, tambah syarat tu dalam query
    if ($type) {
        $sql .= ' AND a.accommodation_type = :type';
        $params['type'] = $type;
    }

    # check kalau perlu filter tarikh, tambah syarat elak accommodation yang dah dibooking dalam tempoh tu
    if ($useDates) {
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

    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari accommodation ikut kriteria
    $stmt = $pdo->prepare($sql);
    # calling method execute() dari object $stmt untuk jalankan query dgn semua placeholder dalam $params
    $stmt->execute($params);
    # calling method fetchAll() dari object $stmt that assign to variable name $candidates untuk ambil semua accommodation yang layak
    $candidates = $stmt->fetchAll();

    # assign array kosong ke $results untuk kumpul accommodation lengkap dgn skor & sebab cadangan
    $results = [];
    # loop setiap accommodation calon untuk kira skor kesesuaian dia
    foreach ($candidates as $row) {
        # assign value $row['capacity'] (dah cast ke int) ke $capacity
        $capacity = (int) $row['capacity'];
        # assign value $row['price'] (dah cast ke float) ke $price
        $price = (float) $row['price'];

        # kira skor kapasiti, nisbah bilangan tetamu berbanding kapasiti bilik (0 kalau kapasiti 0 elak divide by zero)
        $capacityScore = $capacity > 0 ? $guests / $capacity : 0;

        # check kalau tiada budget ditetapkan atau harga masih dalam budget
        $withinBudget = $budget === null || $price <= (float) $budget;
        # check kalau ada budget ditetapkan, kira skor gabungan kapasiti & harga
        if ($budget !== null) {
            # kira skor harga, penuh 1.0 kalau dalam budget, kurang ikut berapa jauh lebih dari budget
            $priceScore = $price <= (float) $budget
                ? 1.0
                : max(0.0, 1 - ($price - (float) $budget) / (float) $budget);
            $score = 0.6 * $capacityScore + 0.4 * $priceScore;
        } else {
            $score = $capacityScore;
        }

        # assign array sebab pertama (padan kapasiti tetamu) ke $reasons
        $reasons = ["Fits {$guests} guest" . ($guests !== 1 ? 's' : '') . " (capacity {$capacity})"];
        # check kalau ada budget, tambah sebab berkaitan harga sama ada dalam/luar budget
        if ($budget !== null) {
            $reasons[] = $withinBudget
                ? 'Within your RM ' . number_format((float) $budget, 2) . '/night budget'
                : 'RM ' . number_format($price - (float) $budget, 2) . ' above your budget';
        }
        # check kalau ada filter tarikh, tambah sebab tempoh tersedia
        if ($useDates) {
            $reasons[] = "Available for {$checkIn} to {$checkOut}";
        }

        # assign array accommodation ni berserta skor, status budget & sebab-sebab ke dalam $results
        $results[] = [
            'accommodation' => $row,
            'score' => $score,
            'within_budget' => $withinBudget,
            'reasons' => $reasons,
        ];
    }

    # calling function usort() untuk susun ikut dalam budget dulu, then skor tertinggi, then harga termurah
    usort($results, function ($a, $b) {
        # check kalau satu dalam budget & satu lagi tak, utamakan yang dalam budget
        if ($a['within_budget'] !== $b['within_budget']) {
            return $a['within_budget'] ? -1 : 1;
        }
        # check kalau skor dua-dua tak sama, utamakan skor lebih tinggi
        if ($a['score'] !== $b['score']) {
            return $a['score'] > $b['score'] ? -1 : 1;
        }
        return (float) $a['accommodation']['price'] <=> (float) $b['accommodation']['price'];
    });

    # calling function array_slice() untuk ambil 6 cadangan teratas je untuk dipulangkan
    return array_slice($results, 0, 6);
}

function load_current_occupancy(PDO $pdo, mixed $requestedDate): array
{
    $selectedDate = date('Y-m-d');
    if (is_string($requestedDate) && $requestedDate !== '') {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $requestedDate);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            $parsedDate
            && $parsedDate->format('Y-m-d') === $requestedDate
            && (!$dateErrors || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        ) {
            $selectedDate = $parsedDate->format('Y-m-d');
        }
    }

    $stmt = $pdo->prepare(
        "SELECT b.booking_id, b.booking_status, b.check_in, b.check_out, b.total_guest,
                c.full_name, c.phone, c.plate_num,
                a.accommodation_id, a.accommodation_name, a.accommodation_type, a.image
         FROM booking b
         JOIN customer c ON c.customer_id = b.customer_id
         JOIN booking_item bi ON bi.booking_id = b.booking_id
         JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
         WHERE b.booking_status NOT IN ('cancelled', 'pending')
           AND b.check_in <= :check_in_date
           AND b.check_out >= :check_out_date
         ORDER BY a.accommodation_name, b.check_in, b.booking_id"
    );
    $stmt->execute([
        'check_in_date' => $selectedDate,
        'check_out_date' => $selectedDate,
    ]);
    $bookings = $stmt->fetchAll();

    $rooms = [];
    foreach ($bookings as $booking) {
        $accommodationId = (int) $booking['accommodation_id'];
        $rooms[$accommodationId] ??= [
            'name' => $booking['accommodation_name'],
            'type' => $booking['accommodation_type'],
            'image' => $booking['image'],
            'bookings' => [],
        ];
        $rooms[$accommodationId]['bookings'][] = $booking;
    }

    return [
        'date' => $selectedDate,
        'rooms' => array_values($rooms),
        'bookings' => $bookings,
    ];
}
