<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['manager']);

$currentUser = current_user();
$validTypes = ['Villa', 'Campsite'];
$validStatuses = ['available', 'unavailable', 'maintenance'];

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM accommodation WHERE accommodation_id = :id');
    $stmt->execute(['id' => $editId]);
    $editing = $stmt->fetch();
    if (!$editing) {
        header('Location: admin_dashboard.php');
        exit;
    }
}

$errors = [];
$old = [
    'accommodation_name' => $editing['accommodation_name'] ?? '',
    'accommodation_type' => $editing['accommodation_type'] ?? 'Villa',
    'price' => $editing['price'] ?? '',
    'price_weekend' => $editing['price_weekend'] ?? '',
    'price_holiday' => $editing['price_holiday'] ?? '',
    'capacity' => $editing['capacity'] ?? '',
    'pax_label' => $editing['pax_label'] ?? '',
    'features' => $editing['features'] ?? '',
    'description' => $editing['description'] ?? '',
    'status' => $editing['status'] ?? 'available',
    'door_code' => $editing['door_code'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($action === 'delete') {
        $targetId = filter_input(INPUT_POST, 'accommodation_id', FILTER_VALIDATE_INT);

        if (!$targetId) {
            $errors[] = 'Accommodation not found.';
        } else {
            try {
                $pdo->prepare('DELETE FROM accommodation WHERE accommodation_id = :id')->execute(['id' => $targetId]);
                header('Location: admin_dashboard.php?accdeleted=1');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'This package can\'t be deleted because it already has bookings against it. Set its status to "unavailable" instead.';
            }
        }
    } else {
        $old['accommodation_name'] = trim((string) ($_POST['accommodation_name'] ?? ''));
        $old['accommodation_type'] = (string) ($_POST['accommodation_type'] ?? '');
        $old['price'] = trim((string) ($_POST['price'] ?? ''));
        $old['price_weekend'] = trim((string) ($_POST['price_weekend'] ?? ''));
        $old['price_holiday'] = trim((string) ($_POST['price_holiday'] ?? ''));
        $old['capacity'] = trim((string) ($_POST['capacity'] ?? ''));
        $old['pax_label'] = trim((string) ($_POST['pax_label'] ?? ''));
        $old['features'] = trim((string) ($_POST['features'] ?? ''));
        $old['description'] = trim((string) ($_POST['description'] ?? ''));
        $old['status'] = (string) ($_POST['status'] ?? '');
        $old['door_code'] = trim((string) ($_POST['door_code'] ?? ''));

        if ($old['accommodation_name'] === '') {
            $errors[] = 'Package name is required.';
        }
        if (!in_array($old['accommodation_type'], $validTypes, true)) {
            $errors[] = 'Please select a valid type.';
        }
        $price = filter_var($old['price'], FILTER_VALIDATE_FLOAT);
        if ($price === false || $price < 0) {
            $errors[] = 'Price must be a positive number.';
        }
        $priceWeekend = null;
        if ($old['price_weekend'] !== '') {
            $priceWeekend = filter_var($old['price_weekend'], FILTER_VALIDATE_FLOAT);
            if ($priceWeekend === false || $priceWeekend < 0) {
                $errors[] = 'Weekend price must be a positive number, or left blank.';
            }
        }
        $priceHoliday = null;
        if ($old['price_holiday'] !== '') {
            $priceHoliday = filter_var($old['price_holiday'], FILTER_VALIDATE_FLOAT);
            if ($priceHoliday === false || $priceHoliday < 0) {
                $errors[] = 'Public holiday price must be a positive number, or left blank.';
            }
        }
        $capacity = filter_var($old['capacity'], FILTER_VALIDATE_INT);
        if ($capacity === false || $capacity < 1) {
            $errors[] = 'Capacity must be at least 1 guest.';
        }
        if (!in_array($old['status'], $validStatuses, true)) {
            $errors[] = 'Please select a valid status.';
        }

        if (!$errors) {
            $params = [
                'name' => $old['accommodation_name'],
                'type' => $old['accommodation_type'],
                'price' => $price,
                'price_weekend' => $priceWeekend,
                'price_holiday' => $priceHoliday,
                'capacity' => $capacity,
                'pax_label' => $old['pax_label'] !== '' ? $old['pax_label'] : null,
                'features' => $old['features'] !== '' ? $old['features'] : null,
                'description' => $old['description'] !== '' ? $old['description'] : null,
                'status' => $old['status'],
                'door_code' => $old['door_code'] !== '' ? $old['door_code'] : null,
            ];

            if ($editing) {
                $params['id'] = $editing['accommodation_id'];
                $stmt = $pdo->prepare(
                    'UPDATE accommodation SET accommodation_name = :name, accommodation_type = :type,
                     price = :price, price_weekend = :price_weekend, price_holiday = :price_holiday,
                     capacity = :capacity, pax_label = :pax_label, features = :features,
                     description = :description, status = :status, door_code = :door_code
                     WHERE accommodation_id = :id'
                );
                $stmt->execute($params);
                header('Location: admin_dashboard.php?accsaved=1');
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO accommodation
                 (accommodation_name, accommodation_type, price, price_weekend, price_holiday, capacity, pax_label, features, description, status, door_code)
                 VALUES (:name, :type, :price, :price_weekend, :price_holiday, :capacity, :pax_label, :features, :description, :status, :door_code)'
            );
            $stmt->execute($params);
            header('Location: admin_dashboard.php?acccreated=1');
            exit;
        }
    }
}

require __DIR__ . '/views/manage_accommodation.view.php';
