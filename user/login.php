<?php
# calling function require_once() untuk load fail db.php supaya boleh guna $pdo (sambungan database)
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna current_user(), attempt_login(), csrf_verify()
require_once __DIR__ . '/../includes/auth.php';

# calling function current_user() that assign to variable name $existingUser untuk check kalau dah ada user login
$existingUser = current_user();
# check kalau $existingUser wujud (user memang dah login), terus halau dia keluar dari page login
if ($existingUser) {
    # calling function header() untuk redirect user yang dah login terus ke dashboard ikut role dia
    header('Location: ' . ($existingUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

# assign value null ke $error untuk simpan mesej error (takde error lagi buat masa ni)
$error = null;
# assign value string kosong ke $oldUsername untuk simpan balik username yang ditaip kalau login gagal
$oldUsername = '';

# check kalau form login dah disubmit guna method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    # calling function trim() that assign to variable name $oldUsername untuk ambil & bersihkan input username dari form
    $oldUsername = trim((string) ($_POST['username'] ?? ''));
    # assign value dari $_POST['password'] ke $password untuk ambil input password dari form
    $password = (string) ($_POST['password'] ?? '');

    # calling function csrf_verify() untuk check token csrf form ni sah ke tidak
    if (!csrf_verify()) {
        # assign mesej error ke $error sebab session dah expired/token csrf tak sah
        $error = 'Your session expired. Please try again.';
    } else {
        # calling function attempt_login() that assign to variable name $user untuk cuba login guna username & password yang ditaip
        $user = $oldUsername !== '' && $password !== '' ? attempt_login($pdo, $oldUsername, $password) : false;

        # check kalau $user berjaya login (bukan false)
        if ($user) {
            # calling function header() untuk redirect user yang berjaya login ke dashboard ikut role dia
            header('Location: ' . ($user['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
            exit;
        }

        # assign mesej error ke $error sebab username/password yang ditaip salah
        $error = 'Invalid username or password.';
    }
}

# assign value '../' ke $base untuk path relatif balik ke root folder
$base = '../';
# calling function is_file() & filemtime() that assign to variable name $loginCssHref untuk elak browser guna cache css lama
$loginCssHref = 'style/login.css?v=' . (is_file(__DIR__ . '/style/login.css') ? filemtime(__DIR__ . '/style/login.css') : time());

# calling function require() untuk load view login.view.php dan papar html page login
require __DIR__ . '/views/login.view.php';
