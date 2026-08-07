<?php
/**
 * Fungsi bantuan pengesahan untuk log masuk, log keluar, semakan sesi, dan perlindungan CSRF.
 * Fungsi-fungsi ini membantu memastikan laman web selamat dan menguruskan status pengguna yang log masuk.
 */
declare(strict_types=1); // Aktifkan typing ketat

// Elak mulakan sesi dua kali
if (session_status() === PHP_SESSION_NONE) {
    // Cookie tak boleh dibaca JavaScript & tak dihantar merentasi tapak lain
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);

    // Mulakan sesi PHP
    session_start();
}

/**
 * Sahkan kelayakan berdasarkan jadual `user`, dan jika berjaya,
 * simpan salinan ringkas rekod tersebut (tanpa hash kata laluan) dalam sesi.
 */
function attempt_login(PDO $pdo, string $username, string $password): array|false
{
    $stmt = $pdo->prepare('SELECT * FROM user WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
        return false;
    }

    unset($user['password']);
    session_regenerate_id(true);
    $_SESSION['user'] = $user;

    return $user;
}

function current_user(): array|false
{
    return $_SESSION['user'] ?? false;
}

/**
 * Ubah hala ke login.php (fungsi ini hanya dipanggil dari halaman dalam
 * folder user/) jika tiada sesi, atau peranan pengguna bukan dalam $allowedRoles.
 */
function require_login(array $allowedRoles = []): void
{
    $user = current_user();

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    if ($allowedRoles && !in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Perlindungan CSRF untuk borang POST. csrf_field() memaparkan input tersembunyi;
 * csrf_verify() menyemaknya dan mesti dipanggil sebelum memproses sebarang POST.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    $expected = $_SESSION['csrf_token'] ?? '';

    return $submitted !== '' && $expected !== '' && hash_equals($expected, $submitted);
}
