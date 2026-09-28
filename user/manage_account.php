<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['manager']);

$currentUser = current_user();
$validRoles = ['manager', 'staff'];
$validStatuses = ['active', 'inactive', 'suspended'];

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM user WHERE user_id = :id');
    $stmt->execute(['id' => $editId]);
    $editing = $stmt->fetch();
    if (!$editing) {
        header('Location: admin_dashboard.php');
        exit;
    }
}
$isSelfEdit = $editing && (int) $editing['user_id'] === (int) $currentUser['user_id'];

$errors = [];
$old = [
    'username' => $editing['username'] ?? '',
    'fullname' => $editing['fullname'] ?? '',
    'email' => $editing['email'] ?? '',
    'phone' => $editing['phone'] ?? '',
    'role' => $editing['role'] ?? 'staff',
    'status' => $editing['status'] ?? 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($action === 'delete') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare('SELECT * FROM user WHERE user_id = :id');
        $stmt->execute(['id' => $targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            $errors[] = 'Account not found.';
        } elseif ($targetId === (int) $currentUser['user_id']) {
            $errors[] = 'You cannot delete your own account.';
        } elseif ($target['role'] === 'manager' && $target['status'] === 'active') {
            $activeManagers = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'manager' AND status = 'active'")->fetchColumn();
            if ($activeManagers <= 1) {
                $errors[] = 'At least one active manager account must remain.';
            }
        }

        if (!$errors) {
            $pdo->prepare('DELETE FROM user WHERE user_id = :id')->execute(['id' => $targetId]);
            header('Location: admin_dashboard.php?deleted=1');
            exit;
        }
    } else {
        $old['username'] = trim((string) ($_POST['username'] ?? ''));
        $old['fullname'] = trim((string) ($_POST['fullname'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($isSelfEdit) {
            $old['role'] = $editing['role'];
            $old['status'] = $editing['status'];
        } else {
            $old['role'] = (string) ($_POST['role'] ?? '');
            $old['status'] = (string) ($_POST['status'] ?? '');
        }

        if ($old['username'] === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $old['username'])) {
            $errors[] = 'Username must be 3-50 characters (letters, numbers, dot, underscore only).';
        }
        if ($old['fullname'] === '') {
            $errors[] = 'Full name is required.';
        }
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (!in_array($old['role'], $validRoles, true)) {
            $errors[] = 'Please select a valid role.';
        }
        if (!in_array($old['status'], $validStatuses, true)) {
            $errors[] = 'Please select a valid status.';
        }
        if (!$editing && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($editing && $password !== '' && strlen($password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT user_id FROM user WHERE (username = :username OR email = :email) AND user_id != :self');
            $stmt->execute([
                'username' => $old['username'],
                'email' => $old['email'],
                'self' => $editing['user_id'] ?? 0,
            ]);
            if ($stmt->fetch()) {
                $errors[] = 'That username or email is already in use by another account.';
            }
        }

        if (!$errors && $editing && $editing['role'] === 'manager' && $editing['status'] === 'active') {
            $willStillBeActiveManager = $old['role'] === 'manager' && $old['status'] === 'active';
            if (!$willStillBeActiveManager) {
                $activeManagers = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'manager' AND status = 'active'")->fetchColumn();
                if ($activeManagers <= 1) {
                    $errors[] = 'At least one active manager account must remain — change another manager first.';
                }
            }
        }

        if (!$errors) {
            if ($editing) {
                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE user SET username = :username, fullname = :fullname, email = :email, phone = :phone,
                         role = :role, status = :status, password = :password WHERE user_id = :id'
                    );
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
                    $stmt = $pdo->prepare(
                        'UPDATE user SET username = :username, fullname = :fullname, email = :email, phone = :phone,
                         role = :role, status = :status WHERE user_id = :id'
                    );
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
                header('Location: admin_dashboard.php?saved=1');
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO user (username, password, fullname, email, phone, role, status)
                 VALUES (:username, :password, :fullname, :email, :phone, :role, :status)'
            );
            $stmt->execute([
                'username' => $old['username'],
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'fullname' => $old['fullname'],
                'email' => $old['email'],
                'phone' => $old['phone'] !== '' ? $old['phone'] : null,
                'role' => $old['role'],
                'status' => $old['status'],
            ]);
            header('Location: admin_dashboard.php?created=1');
            exit;
        }
    }
}

require __DIR__ . '/views/manage_account.view.php';
