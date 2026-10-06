<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php # ternary check $editing untuk tentukan title page (Edit ke Add Accommodation) ?>
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

      <?php # check array $errors ada isi ke tak untuk papar list mesej ralat validation ?>
      <?php if ($errors): ?>
        <div style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;max-width:560px;">
          <ul style="margin-left:18px;">
            <?php # loop setiap mesej error dalam $errors untuk papar sebagai list item ?>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <section class="dash-section" style="max-width:560px;">
        <form method="post" style="display:flex;flex-direction:column;gap:18px;font-family:'Raleway',sans-serif;">
          <?php # calling function csrf_field() untuk letak token keselamatan dalam form accommodation ?>
          <?= csrf_field() ?>
          <?php # ternary check $editing untuk tentukan value hidden input action (update ke create) ?>
          <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Package Name</label>
            <input type="text" name="accommodation_name" value="<?= htmlspecialchars($old['accommodation_name']) ?>" placeholder="e.g. Villa Package 5" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
          </div>

          <div class="form-row-2">
            <div>
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Type</label>
              <select name="accommodation_type" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                <?php # loop setiap type dalam $validTypes untuk bina dropdown pilihan (Villa/Campsite) ?>
                <?php foreach ($validTypes as $type): ?>
                  <option value="<?= $type ?>" <?= $old['accommodation_type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Status</label>
              <select name="status" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                <?php # loop setiap status dalam $validStatuses untuk bina dropdown pilihan status accommodation ?>
                <?php foreach ($validStatuses as $status): ?>
                  <option value="<?= $status ?>" <?= $old['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row-2">
            <div>
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Weekday Base Price / Night (RM)</label>
              <input type="number" name="price" value="<?= htmlspecialchars((string) $old['price']) ?>" min="0" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
              <p style="font-size:12px;color:#999;margin-top:4px;">This is the rate actually used for booking totals.</p>
            </div>
            <div>
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Capacity (guests)</label>
              <input type="number" name="capacity" value="<?= htmlspecialchars((string) $old['capacity']) ?>" min="1" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
            </div>
          </div>

          <div class="form-row-2">
            <div>
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Weekend Base Price / Night (RM, optional)</label>
              <input type="number" name="price_weekend" value="<?= htmlspecialchars((string) $old['price_weekend']) ?>" min="0" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
            </div>
            <div>
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Public Holiday Price / Night (RM, optional)</label>
              <input type="number" name="price_holiday" value="<?= htmlspecialchars((string) $old['price_holiday']) ?>" min="0" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
            </div>
          </div>
          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Ramadan / Rainy Season Price (RM, optional)</label>
            <input type="number" name="price_seasonal" value="<?= htmlspecialchars((string) $old['price_seasonal']) ?>" min="0.01" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
            <p style="font-size:12px;color:#999;margin-top:4px;">Set the amount here, then add the annual date range under Date-specific rates.</p>
          </div>
          <p style="font-size:12px;color:#999;margin-top:-10px;">Weekday and weekend base rates apply automatically. To charge a different rate on public or school holidays, Ramadan / rainy season, or Super Peak dates, add a dated rate below. Dated rates are used in booking totals for both villas and campsites.</p>

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
            <?php # ternary check $editing untuk tukar label butang submit (Save Changes ke Create Package) ?>
            <button type="submit" class="btn btn-primary" style="flex:1;"><?= $editing ? 'Save Changes' : 'Create Package' ?></button>
            <a href="admin_dashboard.php" class="btn btn-outline" style="flex:1;text-align:center;">Cancel</a>
          </div>
        </form>

        <?php if ($editing && $ratePeriodsAvailable): ?>
          <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);">
            <h2 style="font-size:20px;color:var(--brown-price);margin-bottom:8px;">Date-specific rates</h2>
            <p style="font-size:13px;color:#777;margin-bottom:18px;">Add one dated rate for each applicable period and accommodation. Weekday/weekend rates apply only to matching nights in the selected range. Holiday, seasonal, and Super Peak rates override them; higher-priority categories win when special periods overlap.</p>

            <?php if ($ratePeriods): ?>
              <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:22px;">
                <?php foreach ($ratePeriods as $ratePeriod): ?>
                  <div style="display:flex;align-items:flex-end;gap:10px;padding:12px 0;border-bottom:1px solid var(--border);">
                    <form method="post" style="display:flex;flex:1;flex-wrap:wrap;align-items:flex-end;gap:10px;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update_rate_period">
                      <input type="hidden" name="rate_period_id" value="<?= (int) $ratePeriod['rate_period_id'] ?>">
                      <div style="flex:1;min-width:150px;">
                        <label style="display:block;font-size:12px;color:#777;margin-bottom:4px;">Label</label>
                        <input type="text" name="rate_label" maxlength="120" value="<?= htmlspecialchars($ratePeriod['label']) ?>" style="width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
                      </div>
                      <div style="flex:1;min-width:150px;">
                        <label style="display:block;font-size:12px;color:#777;margin-bottom:4px;">Category</label>
                        <select name="rate_type" style="width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                          <?php foreach ($validRateTypes as $rateType => $rateTypeLabel): ?>
                            <option value="<?= htmlspecialchars($rateType) ?>" <?= ($ratePeriod['rate_type'] ?? 'custom') === $rateType ? 'selected' : '' ?>><?= htmlspecialchars($rateTypeLabel) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div>
                        <label style="display:block;font-size:12px;color:#777;margin-bottom:4px;">Start</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($ratePeriod['start_date']) ?>" style="padding:9px 8px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
                      </div>
                      <div>
                        <label style="display:block;font-size:12px;color:#777;margin-bottom:4px;">End</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($ratePeriod['end_date']) ?>" style="padding:9px 8px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
                      </div>
                      <div style="width:120px;">
                        <label style="display:block;font-size:12px;color:#777;margin-bottom:4px;">Rate / night (RM)</label>
                        <input type="number" name="rate_price" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $ratePeriod['price']) ?>" style="width:100%;padding:9px 8px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
                      </div>
                      <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                    <form method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete_rate_period">
                      <input type="hidden" name="rate_period_id" value="<?= (int) $ratePeriod['rate_period_id'] ?>">
                      <button type="submit" class="btn btn-outline" aria-label="Delete <?= htmlspecialchars($ratePeriod['label']) ?> rate">Delete</button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p style="font-size:13px;color:#777;margin-bottom:18px;">No date-specific rates have been added.</p>
            <?php endif; ?>

            <form method="post" style="display:flex;flex-direction:column;gap:14px;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add_rate_period">
              <div>
                <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Rate label</label>
                <input type="text" name="rate_label" maxlength="120" placeholder="e.g. Ramadan / rainy season 2027" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
              </div>
              <div>
                <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Rate category</label>
                <select name="rate_type" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                  <?php foreach ($validRateTypes as $rateType => $rateTypeLabel): ?>
                    <option value="<?= htmlspecialchars($rateType) ?>"><?= htmlspecialchars($rateTypeLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-row-2">
                <div>
                  <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Start date</label>
                  <input type="date" name="start_date" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
                </div>
                <div>
                  <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">End date</label>
                  <input type="date" name="end_date" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
                </div>
              </div>
              <div>
                <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Rate per night (RM)</label>
                <input type="number" name="rate_price" min="0.01" step="0.01" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
              </div>
              <button type="submit" class="btn btn-primary">Add Date-specific Rate</button>
            </form>
          </div>
        <?php elseif ($editing): ?>
          <p class="notice-info" style="margin-top:28px;">Date-specific rates are unavailable until the database pricing migration is installed.</p>
        <?php endif; ?>
      </section>
    </div>
  </main>

</body>
</html>
