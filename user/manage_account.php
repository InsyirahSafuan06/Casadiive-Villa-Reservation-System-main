<?php
# calling function require_once() untuk load fail db.php supaya boleh guna $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna current_user(), require_login(), csrf_verify()
require_once __DIR__ . '/../includes/auth.php';
# calling function require_login() untuk pastikan hanya manager je boleh buka page ni
require_login(['manager']);

# calling function current_user() that assign to variable name $currentUser untuk tahu siapa yang sedang login
$currentUser = current_user();
# assign array role yang valid ke $validRoles untuk dipakai semasa validate input
$validRoles = ['manager', 'staff'];
# assign array status akaun yang valid ke $validStatuses untuk dipakai semasa validate input
$validStatuses = ['active', 'inactive', 'suspended'];

# calling function filter_input() that assign to variable name $editId untuk ambil id user dari url (kalau mode edit), null kalau takde
$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
# assign value null ke $editing untuk simpan data akaun yang sedang diedit (default takde)
$editing = null;
# check kalau ada $editId (bermakna page ni dibuka dalam mode edit)
if ($editId) {
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil data user ikut id
    $stmt = $pdo->prepare('SELECT * FROM user WHERE user_id = :id');
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $editId
    $stmt->execute(['id' => $editId]);
    # calling method fetch() dari object $stmt that assign to variable name $editing untuk ambil data user yang nak diedit
    $editing = $stmt->fetch();
    # check kalau user yang nak diedit tak wujud
    if (!$editing) {
        # calling function header() untuk redirect balik ke admin dashboard sebab id tak wujud
        header('Location: admin_dashboard.php');
        exit;
    }
}
# assign value true/false ke $isSelfEdit untuk check user sedang edit akaun sendiri ke tidak
$isSelfEdit = $editing && (int) $editing['user_id'] === (int) $currentUser['user_id'];

# assign array kosong ke $errors untuk simpan senarai mesej error validation
$errors = [];
# assign array nilai lama/default form ke $old supaya form boleh isi semula bila ada error atau mode edit
$old = [
    'username' => $editing['username'] ?? '',
    'fullname' => $editing['fullname'] ?? '',
    'email' => $editing['email'] ?? '',
    'phone' => $editing['phone'] ?? '',
    'role' => $editing['role'] ?? 'staff',
    'status' => $editing['status'] ?? 'active',
];

# check kalau form dah disubmit guna method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    # ambil value $_POST['action'] that assign to variable name $action untuk tahu form ni nak buat apa (save/delete)
    $action = (string) ($_POST['action'] ?? '');

    # calling function csrf_verify() untuk check token csrf form ni sah ke tidak
    if (!csrf_verify()) {
        # assign mesej error ke dalam $errors sebab session dah expired
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($action === 'delete') {
        # calling function filter_input() that assign to variable name $targetId untuk ambil id akaun yang nak dipadam
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil data akaun yang nak dipadam
        $stmt = $pdo->prepare('SELECT * FROM user WHERE user_id = :id');
        # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $targetId
        $stmt->execute(['id' => $targetId]);
        # calling method fetch() dari object $stmt that assign to variable name $target untuk ambil data akaun yang nak dipadam
        $target = $stmt->fetch();

        # check kalau akaun yang nak dipadam tak wujud
        if (!$target) {
            $errors[] = 'Account not found.';
        } elseif ($targetId === (int) $currentUser['user_id']) {
            # check kalau user cuba padam akaun sendiri
            $errors[] = 'You cannot delete your own account.';
        } elseif ($target['role'] === 'manager' && $target['status'] === 'active') {
            # calling method query() dari object $pdo that assign to variable name $activeManagers untuk kira berapa manager active yang tinggal
            $activeManagers = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'manager' AND status = 'active'")->fetchColumn();
            # check kalau manager active cuma tinggal 1 orang je (tak boleh padam, kena ada sekurang-kurangnya 1)
            if ($activeManagers <= 1) {
                $errors[] = 'At least one active manager account must remain.';
            }
        }

        # check kalau takde error, teruskan padam akaun
        if (!$errors) {
            # calling method prepare() & execute() dari object $pdo untuk padam akaun user ikut id
            $pdo->prepare('DELETE FROM user WHERE user_id = :id')->execute(['id' => $targetId]);
            # calling function header() untuk redirect balik ke admin dashboard dengan flag deleted=1
            header('Location: admin_dashboard.php?deleted=1');
            exit;
        }
    } else {
        # calling function trim() that assign value ke $old['username'] untuk bersihkan input username dari form
        $old['username'] = trim((string) ($_POST['username'] ?? ''));
        # calling function trim() that assign value ke $old['fullname'] untuk bersihkan input nama penuh dari form
        $old['fullname'] = trim((string) ($_POST['fullname'] ?? ''));
        # calling function trim() that assign value ke $old['email'] untuk bersihkan input emel dari form
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        # calling function trim() that assign value ke $old['phone'] untuk bersihkan input no telefon dari form
        $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
        # ambil value $_POST['password'] that assign to variable name $password
        $password = (string) ($_POST['password'] ?? '');

        # check kalau user sedang edit akaun sendiri
        if ($isSelfEdit) {
            # assign value role sedia ada ke $old['role'] supaya user tak boleh tukar role sendiri
            $old['role'] = $editing['role'];
            # assign value status sedia ada ke $old['status'] supaya user tak boleh tukar status sendiri
            $old['status'] = $editing['status'];
        } else {
            # ambil value $_POST['role'] that assign ke $old['role']
            $old['role'] = (string) ($_POST['role'] ?? '');
            # ambil value $_POST['status'] that assign ke $old['status']
            $old['status'] = (string) ($_POST['status'] ?? '');
        }

        # calling function preg_match() untuk check format username sah (huruf, nombor, titik, underscore, 3-50 aksara)
        if ($old['username'] === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $old['username'])) {
            $errors[] = 'Username must be 3-50 characters (letters, numbers, dot, underscore only).';
        }
        # check kalau nama penuh kosong
        if ($old['fullname'] === '') {
            $errors[] = 'Full name is required.';
        }
        # calling function filter_var() untuk check format emel sah
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        # calling function in_array() untuk check role yang dipilih memang valid
        if (!in_array($old['role'], $validRoles, true)) {
            $errors[] = 'Please select a valid role.';
        }
        # calling function in_array() untuk check status yang dipilih memang valid
        if (!in_array($old['status'], $validStatuses, true)) {
            $errors[] = 'Please select a valid status.';
        }
        # calling function strlen() untuk check password minimum 8 aksara (untuk akaun baru)
        if (!$editing && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        # calling function strlen() untuk check password baru minimum 8 aksara kalau user isi password baru semasa edit
        if ($editing && $password !== '' && strlen($password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        # check kalau takde error setakat ni, sambung check username/emel duplicate pulak
        if (!$errors) {
            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query check username/emel dah dipakai orang lain
            $stmt = $pdo->prepare('SELECT user_id FROM user WHERE (username = :username OR email = :email) AND user_id != :self');
            # calling method execute() dari object $stmt untuk jalankan query check duplicate
            $stmt->execute([
                'username' => $old['username'],
                'email' => $old['email'],
                'self' => $editing['user_id'] ?? 0,
            ]);
            # calling method fetch() dari object $stmt untuk check ada row lain guna username/emel yang sama
            if ($stmt->fetch()) {
                $errors[] = 'That username or email is already in use by another account.';
            }
        }

        # check kalau takde error, dan akaun yang diedit tu manager active, pastikan bukan manager active terakhir yang ditukar
        if (!$errors && $editing && $editing['role'] === 'manager' && $editing['status'] === 'active') {
            # assign value true/false ke $willStillBeActiveManager untuk check lepas save dia masih manager active ke tidak
            $willStillBeActiveManager = $old['role'] === 'manager' && $old['status'] === 'active';
            # check kalau lepas save dia takkan jadi manager active lagi
            if (!$willStillBeActiveManager) {
                # calling method query() dari object $pdo that assign to variable name $activeManagers untuk kira berapa manager active yang tinggal
                $activeManagers = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'manager' AND status = 'active'")->fetchColumn();
                # check kalau manager active cuma tinggal 1 orang je (tak boleh tukar, kena ada sekurang-kurangnya 1)
                if ($activeManagers <= 1) {
                    $errors[] = 'At least one active manager account must remain — change another manager first.';
                }
            }
        }

        # check kalau takde error langsung sebelum simpan ke database
        if (!$errors) {
            # check kalau mode edit (akaun sedia ada)
            if ($editing) {
                # check kalau user isi password baru (nak tukar password)
                if ($password !== '') {
                    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query update user sekali dengan password baru
                    $stmt = $pdo->prepare(
                        'UPDATE user SET username = :username, fullname = :fullname, email = :email, phone = :phone,
                         role = :role, status = :status, password = :password WHERE user_id = :id'
                    );
                    # calling method execute() dari object $stmt untuk simpan perubahan akaun sekali dengan password baru yang di-hash
                    $stmt->execute([
                        'username' => $old['username'],
                        'fullname' => $old['fullname'],
                        'email' => $old['email'],
                        'phone' => $old['phone'] !== '' ? $old['phone'] : null,
                        'role' => $old['role'],
                        'status' => $old['status'],
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $editing['user_id'],
                    ]);
                } else {
                    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query update user tanpa tukar password
                    $stmt = $pdo->prepare(
                        'UPDATE user SET username = :username, fullname = :fullname, email = :email, phone = :phone,
                         role = :role, status = :status WHERE user_id = :id'
                    );
                    # calling method execute() dari object $stmt untuk simpan perubahan akaun tanpa tukar password
                    $stmt->execute([
                        'username' => $old['username'],
                        'fullname' => $old['fullname'],
                        'email' => $old['email'],
                        'phone' => $old['phone'] !== '' ? $old['phone'] : null,
                        'role' => $old['role'],
                        'status' => $old['status'],
                        'id' => $editing['user_id'],
                    ]);
                }
                # calling function header() untuk redirect balik ke admin dashboard dengan flag saved=1
                header('Location: admin_dashboard.php?saved=1');
                exit;
            }

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert akaun baru
            $stmt = $pdo->prepare(
                'INSERT INTO user (username, password, fullname, email, phone, role, status)
                 VALUES (:username, :password, :fullname, :email, :phone, :role, :status)'
            );
            # calling method execute() dari object $stmt untuk simpan akaun baru ke database, password di-hash guna password_hash()
            $stmt->execute([
                'username' => $old['username'],
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'fullname' => $old['fullname'],
                'email' => $old['email'],
                'phone' => $old['phone'] !== '' ? $old['phone'] : null,
                'role' => $old['role'],
                'status' => $old['status'],
            ]);
            # calling function header() untuk redirect balik ke admin dashboard dengan flag created=1
            header('Location: admin_dashboard.php?created=1');
            exit;
        }
    }
}

# calling function require() untuk load view manage_account.view.php dan papar form urus akaun
require __DIR__ . '/views/manage_account.view.php';
