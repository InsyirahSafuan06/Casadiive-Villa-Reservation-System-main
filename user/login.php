<?php
/**
 * Halaman log masuk untuk pengguna staf dan admin.
 * Halaman ini mengendalikan pengesahan dan ubah hala pengguna ke dashboard mereka selepas berjaya log masuk.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// dah login ke? kalau ya, takyah tunjuk borang, terus hantar ke dashboard yang betul
$existingUser = current_user();
if ($existingUser) {
    header('Location: ' . ($existingUser['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

$error = null;
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify()) {
        $error = 'Your session expired. Please try again.';
    } else {
        // attempt_login() check username/password kat table `user`, kalau betul,
        // dia terus simpan pengguna tu dalam session untuk kita
        $user = $oldUsername !== '' && $password !== '' ? attempt_login($pdo, $oldUsername, $password) : false;

        if ($user) {
            header('Location: ' . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
            exit;
        }

        // sengaja buat mesej error kabur — tak cakap "username salah" ke "password salah"
        // supaya orang jahat tak boleh guna page ni untuk teka username mana yang wujud
        $error = 'Invalid username or password.';
    }
}

$base = '../'; // page ni dalam folder user/, naik satu tahap untuk pergi root
// tambah "?v=" + masa fail last edit, supaya browser tak simpan CSS lama dalam cache
$loginCssHref = 'style/login.css?v=' . (is_file(__DIR__ . '/style/login.css') ? filemtime(__DIR__ . '/style/login.css') : time());

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/login.view.php';
