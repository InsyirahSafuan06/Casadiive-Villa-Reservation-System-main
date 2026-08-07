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
    // Cari pengguna ikut username
    $stmt = $pdo->prepare('SELECT * FROM user WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    // Tolak jika tiada pengguna, akaun tidak aktif, atau kata laluan salah
    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
        return false;
    }

    unset($user['password']); // Buang hash kata laluan sebelum simpan dalam sesi
    session_regenerate_id(true); // Jana ID sesi baru untuk elak session fixation
    $_SESSION['user'] = $user; // Simpan pengguna log masuk dalam sesi

    return $user;
}

function current_user(): array|false
{
    // Pulangkan pengguna dalam sesi, atau false jika tiada sesi log masuk
    return $_SESSION['user'] ?? false;
}

/**
 * Ubah hala ke login.php (fungsi ini hanya dipanggil dari halaman dalam
 * folder user/) jika tiada sesi, atau peranan pengguna bukan dalam $allowedRoles.
 */
function require_login(array $allowedRoles = []): void
{
    $user = current_user();

    // Belum log masuk — hantar ke halaman login
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    // Sudah log masuk tapi peranan tidak dibenarkan — sekat akses
    if ($allowedRoles && !in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function logout(): void
{
    $_SESSION = []; // Kosongkan semua data sesi

    // Padam cookie sesi di pelayar
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000, // Tarikh luput lampau supaya cookie terus dipadam
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy(); // Musnahkan sesi di server
}

/**
 * Perlindungan CSRF untuk borang POST. csrf_field() memaparkan input tersembunyi;
 * csrf_verify() menyemaknya dan mesti dipanggil sebelum memproses sebarang POST.
 */
function csrf_token(): string
{
    // Jana token sekali sahaja setiap sesi, simpan untuk guna semula
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    // Cetak input tersembunyi yang mengandungi token CSRF untuk borang
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $submitted = (string) ($_POST['csrf_token'] ?? ''); // Token yang dihantar dari borang
    $expected = $_SESSION['csrf_token'] ?? ''; // Token sebenar yang disimpan dalam sesi

    // Bandingkan kedua-dua token — hash_equals elak timing attack
    return $submitted !== '' && $expected !== '' && hash_equals($expected, $submitted);
}
