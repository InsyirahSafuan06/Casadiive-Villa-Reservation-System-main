<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$existingUser = current_user();
if ($existingUser) {
    header('Location: ' . ($existingUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
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
        $user = $oldUsername !== '' && $password !== '' ? attempt_login($pdo, $oldUsername, $password) : false;

        if ($user) {
            header('Location: ' . ($user['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
            exit;
        }

        $error = 'Invalid username or password.';
    }
}

$base = '../';
$loginCssHref = 'style/login.css?v=' . (is_file(__DIR__ . '/style/login.css') ? filemtime(__DIR__ . '/style/login.css') : time());

require __DIR__ . '/views/login.view.php';
