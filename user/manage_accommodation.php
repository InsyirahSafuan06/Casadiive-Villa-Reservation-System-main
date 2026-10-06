<?php
# calling function require_once() untuk load fail db.php supaya boleh guna $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna current_user(), require_login(), csrf_verify()
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
# calling function require_login() untuk pastikan hanya manager je boleh buka page ni
require_login(['manager']);

# calling function current_user() that assign to variable name $currentUser untuk tahu siapa yang sedang login
$currentUser = current_user();
# assign array jenis accommodation yang valid ke $validTypes untuk dipakai semasa validate input
$validTypes = ['Villa', 'Campsite'];
# assign array status accommodation yang valid ke $validStatuses untuk dipakai semasa validate input
$validStatuses = ['available', 'unavailable', 'maintenance'];
$validRateTypes = [
    'weekday' => 'Weekday override',
    'weekend' => 'Weekend override',
    'public_holiday' => 'Public holiday',
    'school_holiday' => 'School holiday',
    'ramadan' => 'Ramadan',
    'seasonal' => 'Seasonal',
    'super_peak_cny' => 'Super Peak - Chinese New Year',
    'super_peak_eid' => 'Super Peak - Eid al-Fitr and School Break',
    'custom' => 'Custom / other',
];

# calling function filter_input() that assign to variable name $editId untuk ambil id accommodation dari url (kalau mode edit), null kalau takde
$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
# assign value null ke $editing untuk simpan data accommodation yang sedang diedit (default takde)
$editing = null;
# check kalau ada $editId (bermakna page ni dibuka dalam mode edit)
if ($editId) {
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil data accommodation ikut id
    $stmt = $pdo->prepare('SELECT * FROM accommodation WHERE accommodation_id = :id');
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $editId
    $stmt->execute(['id' => $editId]);
    # calling method fetch() dari object $stmt that assign to variable name $editing untuk ambil data accommodation yang nak diedit
    $editing = $stmt->fetch();
    # check kalau accommodation yang nak diedit tak wujud
    if (!$editing) {
        # calling function header() untuk redirect balik ke admin dashboard sebab id tak wujud
        header('Location: admin_dashboard.php');
        exit;
    }
}

# assign array kosong ke $errors untuk simpan senarai mesej error validation
$errors = [];
$ratePeriods = [];
$ratePeriodsAvailable = accommodation_rate_period_categories_available($pdo);
if ($editing && $ratePeriodsAvailable) {
    $ratePeriods = fetch_accommodation_rate_periods($pdo, (int) $editing['accommodation_id']);
}
# assign array nilai lama/default form ke $old supaya form boleh isi semula bila ada error atau mode edit
$old = [
    'accommodation_name' => $editing['accommodation_name'] ?? '',
    'accommodation_type' => $editing['accommodation_type'] ?? 'Villa',
    'price' => $editing['price'] ?? '',
    'price_weekend' => $editing['price_weekend'] ?? '',
    'price_holiday' => $editing['price_holiday'] ?? '',
    'price_seasonal' => $editing['price_seasonal'] ?? '',
    'capacity' => $editing['capacity'] ?? '',
    'pax_label' => $editing['pax_label'] ?? '',
    'features' => $editing['features'] ?? '',
    'description' => $editing['description'] ?? '',
    'status' => $editing['status'] ?? 'available',
    'door_code' => $editing['door_code'] ?? '',
];

# check kalau form dah disubmit guna method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    # ambil value $_POST['action'] that assign to variable name $action untuk tahu form ni nak buat apa (save/delete)
    $action = (string) ($_POST['action'] ?? '');

    # calling function csrf_verify() untuk check token csrf form ni sah ke tidak
    if (!csrf_verify()) {
        # assign mesej error ke dalam $errors sebab session dah expired
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($action === 'delete_rate_period') {
        $ratePeriodId = filter_var($_POST['rate_period_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$ratePeriodsAvailable) {
            $errors[] = 'Date-specific rates are unavailable until the database pricing migration is installed.';
        } elseif (!$editing || !$ratePeriodId) {
            $errors[] = 'Rate period not found.';
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM accommodation_rate_period
                 WHERE rate_period_id = :rate_period_id AND accommodation_id = :accommodation_id'
            );
            $stmt->execute([
                'rate_period_id' => $ratePeriodId,
                'accommodation_id' => $editing['accommodation_id'],
            ]);
            header('Location: manage_accommodation.php?id=' . (int) $editing['accommodation_id']);
            exit;
        }
    } elseif (in_array($action, ['add_rate_period', 'update_rate_period'], true)) {
        $isUpdate = $action === 'update_rate_period';
        $ratePeriodId = filter_var($_POST['rate_period_id'] ?? '', FILTER_VALIDATE_INT);
        $label = trim((string) ($_POST['rate_label'] ?? ''));
        $rateType = (string) ($_POST['rate_type'] ?? '');
        $startDateRaw = trim((string) ($_POST['start_date'] ?? ''));
        $endDateRaw = trim((string) ($_POST['end_date'] ?? ''));
        $price = filter_var($_POST['rate_price'] ?? '', FILTER_VALIDATE_FLOAT);
        $startDate = DateTime::createFromFormat('!Y-m-d', $startDateRaw);
        $endDate = DateTime::createFromFormat('!Y-m-d', $endDateRaw);
        $startDateErrors = DateTime::getLastErrors();
        $endDateErrors = DateTime::getLastErrors();
        $validStartDate = $startDate && $startDate->format('Y-m-d') === $startDateRaw
            && (!$startDateErrors || ($startDateErrors['warning_count'] === 0 && $startDateErrors['error_count'] === 0));
        $validEndDate = $endDate && $endDate->format('Y-m-d') === $endDateRaw
            && (!$endDateErrors || ($endDateErrors['warning_count'] === 0 && $endDateErrors['error_count'] === 0));

        if (!$ratePeriodsAvailable) {
            $errors[] = 'Date-specific rates are unavailable until the database pricing migration is installed.';
        }
        if (!$editing || ($isUpdate && !$ratePeriodId)) {
            $errors[] = 'Save the accommodation before managing rate periods.';
        }
        if ($label === '' || strlen($label) > 120) {
            $errors[] = 'Enter a rate period label of up to 120 characters.';
        }
        if (!array_key_exists($rateType, $validRateTypes)) {
            $errors[] = 'Select a valid rate category.';
        }
        if (!$validStartDate || !$validEndDate || $startDateRaw > $endDateRaw) {
            $errors[] = 'Enter a valid date range with the end date on or after the start date.';
        }
        if ($price === false || $price <= 0) {
            $errors[] = 'The period price must be greater than zero.';
        }

        if (!$errors) {
            $overlapSql = 'SELECT 1 FROM accommodation_rate_period
                           WHERE accommodation_id = :accommodation_id AND rate_type = :rate_type
                             AND start_date <= :end_date AND end_date >= :start_date';
            if ($isUpdate) {
                $overlapSql .= ' AND rate_period_id <> :rate_period_id';
            }
            $overlapSql .= ' LIMIT 1';
            $stmt = $pdo->prepare($overlapSql);
            $overlapParams = [
                'accommodation_id' => $editing['accommodation_id'],
                'rate_type' => $rateType,
                'start_date' => $startDateRaw,
                'end_date' => $endDateRaw,
            ];
            if ($isUpdate) {
                $overlapParams['rate_period_id'] = $ratePeriodId;
            }
            $stmt->execute($overlapParams);

            if ($stmt->fetch()) {
                $errors[] = 'This date range overlaps another rate of the same category for this accommodation.';
            } else {
                if ($isUpdate) {
                    $stmt = $pdo->prepare(
                        'UPDATE accommodation_rate_period
                         SET label = :label, rate_type = :rate_type, start_date = :start_date, end_date = :end_date, price = :price
                         WHERE rate_period_id = :rate_period_id AND accommodation_id = :accommodation_id'
                    );
                    $stmt->execute([
                        'label' => $label,
                        'rate_type' => $rateType,
                        'start_date' => $startDateRaw,
                        'end_date' => $endDateRaw,
                        'price' => $price,
                        'rate_period_id' => $ratePeriodId,
                        'accommodation_id' => $editing['accommodation_id'],
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO accommodation_rate_period (accommodation_id, label, rate_type, start_date, end_date, price)
                         VALUES (:accommodation_id, :label, :rate_type, :start_date, :end_date, :price)'
                    );
                    $stmt->execute([
                        'accommodation_id' => $editing['accommodation_id'],
                        'label' => $label,
                        'rate_type' => $rateType,
                        'start_date' => $startDateRaw,
                        'end_date' => $endDateRaw,
                        'price' => $price,
                    ]);
                }
                header('Location: manage_accommodation.php?id=' . (int) $editing['accommodation_id']);
                exit;
            }
        }
    } elseif ($action === 'delete') {
        # calling function filter_input() that assign to variable name $targetId untuk ambil id accommodation yang nak dipadam
        $targetId = filter_input(INPUT_POST, 'accommodation_id', FILTER_VALIDATE_INT);

        # check kalau $targetId tak sah/takde
        if (!$targetId) {
            # assign mesej error ke dalam $errors sebab accommodation tak jumpa
            $errors[] = 'Accommodation not found.';
        } else {
            try {
                # calling method prepare() & execute() dari object $pdo untuk padam accommodation ikut id
                $pdo->prepare('DELETE FROM accommodation WHERE accommodation_id = :id')->execute(['id' => $targetId]);
                # calling function header() untuk redirect balik ke admin dashboard dengan flag accdeleted=1
                header('Location: admin_dashboard.php?accdeleted=1');
                exit;
            } catch (PDOException $e) {
                # assign mesej error ke dalam $errors sebab accommodation ni ada booking terikat, tak boleh dipadam
                $errors[] = 'This package can\'t be deleted because it already has bookings against it. Set its status to "unavailable" instead.';
            }
        }
    } else {
        # calling function trim() that assign value ke $old['accommodation_name'] untuk bersihkan input nama package dari form
        $old['accommodation_name'] = trim((string) ($_POST['accommodation_name'] ?? ''));
        # ambil value $_POST['accommodation_type'] that assign ke $old['accommodation_type']
        $old['accommodation_type'] = (string) ($_POST['accommodation_type'] ?? '');
        # calling function trim() that assign value ke $old['price'] untuk bersihkan input harga dari form
        $old['price'] = trim((string) ($_POST['price'] ?? ''));
        # calling function trim() that assign value ke $old['price_weekend'] untuk bersihkan input harga weekend dari form
        $old['price_weekend'] = trim((string) ($_POST['price_weekend'] ?? ''));
        # calling function trim() that assign value ke $old['price_holiday'] untuk bersihkan input harga cuti umum dari form
        $old['price_holiday'] = trim((string) ($_POST['price_holiday'] ?? ''));
        $old['price_seasonal'] = trim((string) ($_POST['price_seasonal'] ?? ''));
        # calling function trim() that assign value ke $old['capacity'] untuk bersihkan input kapasiti tetamu dari form
        $old['capacity'] = trim((string) ($_POST['capacity'] ?? ''));
        # calling function trim() that assign value ke $old['pax_label'] untuk bersihkan input label pax dari form
        $old['pax_label'] = trim((string) ($_POST['pax_label'] ?? ''));
        # calling function trim() that assign value ke $old['features'] untuk bersihkan input ciri-ciri package dari form
        $old['features'] = trim((string) ($_POST['features'] ?? ''));
        # calling function trim() that assign value ke $old['description'] untuk bersihkan input penerangan dari form
        $old['description'] = trim((string) ($_POST['description'] ?? ''));
        # ambil value $_POST['status'] that assign ke $old['status']
        $old['status'] = (string) ($_POST['status'] ?? '');
        # calling function trim() that assign value ke $old['door_code'] untuk bersihkan input kod pintu dari form
        $old['door_code'] = trim((string) ($_POST['door_code'] ?? ''));

        # check kalau nama package kosong
        if ($old['accommodation_name'] === '') {
            $errors[] = 'Package name is required.';
        }
        # calling function in_array() untuk check jenis accommodation yang dipilih memang valid
        if (!in_array($old['accommodation_type'], $validTypes, true)) {
            $errors[] = 'Please select a valid type.';
        }
        # calling function filter_var() that assign to variable name $price untuk sahkan harga adalah nombor
        $price = filter_var($old['price'], FILTER_VALIDATE_FLOAT);
        # check kalau harga tak sah atau negatif
        if ($price === false || $price < 0) {
            $errors[] = 'Price must be a positive number.';
        }
        # assign value null ke $priceWeekend sebagai default (harga weekend optional)
        $priceWeekend = null;
        # check kalau user memang isi harga weekend
        if ($old['price_weekend'] !== '') {
            # calling function filter_var() that assign to variable name $priceWeekend untuk sahkan harga weekend adalah nombor
            $priceWeekend = filter_var($old['price_weekend'], FILTER_VALIDATE_FLOAT);
            # check kalau harga weekend tak sah atau negatif
            if ($priceWeekend === false || $priceWeekend < 0) {
                $errors[] = 'Weekend price must be a positive number, or left blank.';
            }
        }
        # assign value null ke $priceHoliday sebagai default (harga cuti umum optional)
        $priceHoliday = null;
        # check kalau user memang isi harga cuti umum
        if ($old['price_holiday'] !== '') {
            # calling function filter_var() that assign to variable name $priceHoliday untuk sahkan harga cuti umum adalah nombor
            $priceHoliday = filter_var($old['price_holiday'], FILTER_VALIDATE_FLOAT);
            # check kalau harga cuti umum tak sah atau negatif
            if ($priceHoliday === false || $priceHoliday < 0) {
                $errors[] = 'Public holiday price must be a positive number, or left blank.';
            }
        }
        $priceSeasonal = null;
        if ($old['price_seasonal'] !== '') {
            $priceSeasonal = filter_var($old['price_seasonal'], FILTER_VALIDATE_FLOAT);
            if ($priceSeasonal === false || $priceSeasonal <= 0) {
                $errors[] = 'Ramadan / rainy season price must be greater than zero, or left blank.';
            }
        }
        # calling function filter_var() that assign to variable name $capacity untuk sahkan kapasiti adalah integer
        $capacity = filter_var($old['capacity'], FILTER_VALIDATE_INT);
        # check kalau kapasiti tak sah atau kurang dari 1
        if ($capacity === false || $capacity < 1) {
            $errors[] = 'Capacity must be at least 1 guest.';
        }
        # calling function in_array() untuk check status accommodation yang dipilih memang valid
        if (!in_array($old['status'], $validStatuses, true)) {
            $errors[] = 'Please select a valid status.';
        }

        # check kalau takde error langsung sebelum simpan ke database
        if (!$errors) {
            # assign array parameter query ke $params supaya senang pass masuk execute()
            $params = [
                'name' => $old['accommodation_name'],
                'type' => $old['accommodation_type'],
                'price' => $price,
                'price_weekend' => $priceWeekend,
                'price_holiday' => $priceHoliday,
                'price_seasonal' => $priceSeasonal,
                'capacity' => $capacity,
                'pax_label' => $old['pax_label'] !== '' ? $old['pax_label'] : null,
                'features' => $old['features'] !== '' ? $old['features'] : null,
                'description' => $old['description'] !== '' ? $old['description'] : null,
                'status' => $old['status'],
                'door_code' => $old['door_code'] !== '' ? $old['door_code'] : null,
            ];

            # check kalau mode edit (accommodation sedia ada)
            if ($editing) {
                # assign id accommodation yang diedit ke $params['id'] untuk klausa WHERE
                $params['id'] = $editing['accommodation_id'];
                # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query update accommodation
                $stmt = $pdo->prepare(
                    'UPDATE accommodation SET accommodation_name = :name, accommodation_type = :type,
                     price = :price, price_weekend = :price_weekend, price_holiday = :price_holiday,
                     price_seasonal = :price_seasonal, capacity = :capacity, pax_label = :pax_label, features = :features,
                     description = :description, status = :status, door_code = :door_code
                     WHERE accommodation_id = :id'
                );
                # calling method execute() dari object $stmt untuk simpan perubahan accommodation ke database
                $stmt->execute($params);
                # calling function header() untuk redirect balik ke admin dashboard dengan flag accsaved=1
                header('Location: admin_dashboard.php?accsaved=1');
                exit;
            }

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert accommodation baru
            $stmt = $pdo->prepare(
                'INSERT INTO accommodation
                 (accommodation_name, accommodation_type, price, price_weekend, price_holiday, price_seasonal, capacity, pax_label, features, description, status, door_code)
                 VALUES (:name, :type, :price, :price_weekend, :price_holiday, :price_seasonal, :capacity, :pax_label, :features, :description, :status, :door_code)'
            );
            # calling method execute() dari object $stmt untuk simpan accommodation baru ke database
            $stmt->execute($params);
            # calling function header() untuk redirect balik ke admin dashboard dengan flag acccreated=1
            header('Location: admin_dashboard.php?acccreated=1');
            exit;
        }
    }
}

# calling function require() untuk load view manage_accommodation.view.php dan papar form urus accommodation
require __DIR__ . '/views/manage_accommodation.view.php';
