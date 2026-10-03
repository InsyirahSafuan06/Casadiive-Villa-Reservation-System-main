<?php
declare(strict_types=1);

const ADDON_BBQ_PRICE = 20.00;
const ADDON_MATTRESS_PRICE = 10.00;

function booking_addon_total(bool $bbq, bool $mattress): float
{
    # kira jumlah harga addon, tambah harga bbq kalau dipilih, tambah harga tilam kalau dipilih
    return ($bbq ? ADDON_BBQ_PRICE : 0.0) + ($mattress ? ADDON_MATTRESS_PRICE : 0.0);
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

function record_booking_payment(PDO $pdo, int $bookingId, float $amount, string $paymentMethod, ?string $receipt = null): bool
{
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query check payment sedia ada
    $stmt = $pdo->prepare("SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1");
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dgn $bookingId
    $stmt->execute(['id' => $bookingId]);
    # check kalau booking ni dah ada payment yang 'paid', tak payah rekod bayaran baru
    if ($stmt->fetch()) {
        return false;
    }

    # calling method beginTransaction() dari object $pdo untuk mula transaction supaya kedua-dua query jaya sama-sama atau gagal sama-sama
    $pdo->beginTransaction();
    try {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert rekod payment baru
        $stmt = $pdo->prepare(
            "INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_status, receipt)
             VALUES (:booking_id, :deposit_paid, :payment_method, 'paid', :receipt)"
        );
        # calling method execute() dari object $stmt untuk simpan rekod payment baru dalam database
        $stmt->execute([
            'booking_id' => $bookingId,
            'deposit_paid' => $amount,
            'payment_method' => $paymentMethod,
            'receipt' => $receipt,
        ]);

        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query kemaskini status booking jadi confirmed
        $stmt = $pdo->prepare(
            "UPDATE booking SET booking_status = 'confirmed' WHERE booking_id = :id AND booking_status = 'pending'"
        );
        # calling method execute() dari object $stmt untuk jalankan kemaskini status booking
        $stmt->execute(['id' => $bookingId]);

        # calling method commit() dari object $pdo untuk sahkan kedua-dua perubahan tadi disimpan betul-betul
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        # calling method rollBack() dari object $pdo untuk batalkan semua perubahan tadi sbb ada error
        $pdo->rollBack();
        throw $e;
    }
}

function payment_needs_refund(string $bookingStatus, ?string $paymentStatus): bool
{
    # check kalau booking dah cancelled tapi payment tadi paid/partial, maknanya perlu refund
    return $bookingStatus === 'cancelled' && in_array($paymentStatus, ['paid', 'partial'], true);
}

function compute_stay_price(float $weekdayPrice, ?float $weekendPrice, DateTime $checkIn, DateTime $checkOut): array
{
    # assign value $weekdayPrice ke $weekendPrice kalau $weekendPrice tak dibagi, guna harga sama je
    $weekendPrice ??= $weekdayPrice;
    # assign value 0 ke $weekdayNights untuk kira jumlah malam hari biasa
    $weekdayNights = 0;
    # assign value 0 ke $weekendNights untuk kira jumlah malam hujung minggu
    $weekendNights = 0;

    # calling clone that assign to variable name $cursor untuk salin tarikh check-in sbb nak gerak2 tanpa ubah asal
    $cursor = clone $checkIn;
    # loop dari tarikh check-in sampai sehari sebelum check-out, kira setiap malam
    while ($cursor < $checkOut) {
        # calling method format() dari object $cursor & in_array() that assign to variable name $isWeekend untuk check hari tu jumaat/sabtu ke tak
        $isWeekend = in_array((int) $cursor->format('N'), [5, 6], true);
        # check kalau hari tu hujung minggu, tambah kira weekend, kalau tak tambah kira weekday
        $isWeekend ? $weekendNights++ : $weekdayNights++;
        # calling method modify() dari object $cursor untuk gerak ke hari seterusnya
        $cursor->modify('+1 day');
    }

    # pulangkan array jumlah malam weekday, weekend, dan jumlah harga keseluruhan
    return [
        'weekday_nights' => $weekdayNights,
        'weekend_nights' => $weekendNights,
        'total' => round($weekdayNights * $weekdayPrice + $weekendNights * $weekendPrice, 2),
    ];
}

function lookup_booking_status(PDO $pdo, int $ref, string $phone): array
{
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari booking ikut ref & phone
    $stmt = $pdo->prepare(
        'SELECT b.booking_id, b.check_in, b.check_out, b.total_guest, b.total_amount,
                b.deposit_amount, b.booking_status
         FROM booking b
         JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :ref AND c.phone = :phone'
    );
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :ref & :phone
    $stmt->execute(['ref' => $ref, 'phone' => $phone]);
    # calling method fetch() dari object $stmt that assign to variable name $booking untuk ambil 1 row hasil query
    $booking = $stmt->fetch();

    # check kalau tiada booking jumpa ikut ref & phone tu, terus pulangkan not found
    if (!$booking) {
        return ['found' => false];
    }

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

    # pulangkan array detail booking lengkap dgn status, accommodation, dan baki bayaran
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
