<?php
/**
 * Halaman pengurusan penginapan.
 * Admin boleh tambah, kemas kini, atau buang pakej vila dan khemah dari halaman ini.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

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
                // Pangkalan data sendiri menghalang padam ini (foreign key constraint) apabila
                // masih ada tempahan yang merujuk pakej ini, jadi kita papar mesej yang lebih mesra.
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

        // Array $params yang sama digunakan semula untuk UPDATE dan INSERT di bawah —
        // hanya kenyataan SQL (dan sama ada :id diperlukan) yang berbeza.
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $editing ? 'Edit Accommodation' : 'Add Accommodation' ?> — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Mulish:wght@700;800&family=Poppins:wght@400;500;600&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style/dashboard.css">
</head>
<body>

  <header class="dash-topbar">
    <div class="container">
      <div class="brand">Casadive Villa</div>
      <div class="dash-user">
        <span class="who">Hi, <strong><?= htmlspecialchars($currentUser['fullname']) ?></strong><span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span></span>
        <a href="admin_dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
      </div>
    </div>
  </header>

  <main class="dash-main">
    <div class="container">
      <h1 class="dash-heading"><?= $editing ? 'Edit Accommodation' : 'Add Accommodation' ?></h1>
      <p class="dash-subheading">Villa rooms and campsite packages shown on the public site are managed here.</p>

      <?php if ($errors): ?>
        <div style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;max-width:560px;">
          <ul style="margin-left:18px;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <section class="dash-section" style="max-width:560px;">
        <form method="post" style="display:flex;flex-direction:column;gap:18px;font-family:'Raleway',sans-serif;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Package Name</label>
            <input type="text" name="accommodation_name" value="<?= htmlspecialchars($old['accommodation_name']) ?>" placeholder="e.g. Villa Package 5" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
          </div>

          <div style="display:flex;gap:18px;">
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Type</label>
              <select name="accommodation_type" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                <?php foreach ($validTypes as $type): ?>
                  <option value="<?= $type ?>" <?= $old['accommodation_type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Status</label>
              <select name="status" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                <?php foreach ($validStatuses as $status): ?>
                  <option value="<?= $status ?>" <?= $old['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div style="display:flex;gap:18px;">
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Weekday Price / Night (RM)</label>
              <input type="number" name="price" value="<?= htmlspecialchars((string) $old['price']) ?>" min="0" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
              <p style="font-size:12px;color:#999;margin-top:4px;">This is the rate actually used for booking totals.</p>
            </div>
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Capacity (guests)</label>
              <input type="number" name="capacity" value="<?= htmlspecialchars((string) $old['capacity']) ?>" min="1" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
            </div>
          </div>

          <div style="display:flex;gap:18px;">
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Weekend Price / Night (RM, optional)</label>
              <input type="number" name="price_weekend" value="<?= htmlspecialchars((string) $old['price_weekend']) ?>" min="0" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
            </div>
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Public Holiday Price / Night (RM, optional)</label>
              <input type="number" name="price_holiday" value="<?= htmlspecialchars((string) $old['price_holiday']) ?>" min="0" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
            </div>
          </div>
          <p style="font-size:12px;color:#999;margin-top:-10px;">Weekend/holiday rates are shown on the package detail page only — booking totals always use the weekday price above.</p>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">PAX Label (optional, e.g. "4-5 PAX")</label>
            <input type="text" name="pax_label" value="<?= htmlspecialchars((string) $old['pax_label']) ?>" placeholder="Falls back to &quot;Max N guests&quot; if left blank" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
          </div>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Door Lock Code (optional)</label>
            <input type="text" name="door_code" value="<?= htmlspecialchars((string) $old['door_code']) ?>" placeholder="e.g. 1745" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
            <p style="font-size:12px;color:#999;margin-top:4px;">Sent to the guest in the check-in reminder message. Leave blank for packages with no door lock (e.g. campsite sites).</p>
          </div>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Room Features (one per line)</label>
            <textarea name="features" rows="6" placeholder="1 Queen Bed&#10;Bathroom&#10;Aircond&#10;Wifi" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;resize:vertical;"><?= htmlspecialchars((string) $old['features']) ?></textarea>
          </div>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Description / Subtitle</label>
            <textarea name="description" rows="3" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;resize:vertical;"><?= htmlspecialchars((string) $old['description']) ?></textarea>
          </div>

          <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary" style="flex:1;"><?= $editing ? 'Save Changes' : 'Create Package' ?></button>
            <a href="admin_dashboard.php" class="btn btn-outline" style="flex:1;text-align:center;">Cancel</a>
          </div>
        </form>
      </section>
    </div>
  </main>

</body>
</html>
