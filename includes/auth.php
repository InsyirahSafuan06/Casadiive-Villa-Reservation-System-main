<?php
# declare strict_types=1 untuk php check jenis data dgn ketat dalam fail ni
declare(strict_types=1);

# calling function session_status() untuk check status session semasa, banding dengan PHP_SESSION_NONE
if (session_status() === PHP_SESSION_NONE) {
    #httponly : true sbb nk elak XSS , penting sbb nk elak session cookie dicuri
    #samsite : lax sbb nk elak CSRF, penting sbb kita ada guna payment gateway means sambungan luar website
    # calling function session_set_cookie_params() untuk set setting cookie session sebelum session start
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);

    # calling function session_start() untuk mulakan/sambung session
    session_start();
}

#fungsi unttuk semak login
function attempt_login(PDO $pdo, string $username, string $password): array|false
{
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari user ikut username
    $stmt = $pdo->prepare('SELECT * FROM user WHERE username = :username LIMIT 1');
    #guna object operator untuk jalan execute query
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :username dgn value sebenar
    $stmt->execute(['username' => $username]);
    # calling method fetch() dari object $stmt that assign to variable name $user untuk ambil 1 row hasil query
    $user = $stmt->fetch();

    # check user tak wujud, status bukan active, atau password tak match - kalau salah satu true, tolak login
    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
        return false;
    }

    # calling function unset() untuk buang key 'password' dari array $user
    unset($user['password']);
    # calling function session_regenerate_id() untuk jana session id baru, elak session fixation attack
    session_regenerate_id(true);
    # assign value $user ke variable session $_SESSION['user'] untuk simpan data user yang dah login
    $_SESSION['user'] = $user;

    return $user;
}

function current_user(): array|false
{
    # ambil value $_SESSION['user'] kalau wujud, kalau tak wujud pulangkan false
    return $_SESSION['user'] ?? false;
}

function require_login(array $allowedRoles = []): void
{
    # calling function current_user() that assign to variable name $user untuk tahu siapa yang sedang login
    $user = current_user();

    if (!$user) {
        # calling function header() untuk redirect browser ke page login.php
        header('Location: login.php');
        exit;
    }

    # calling function in_array() untuk check role $user ada dalam list $allowedRoles ke tidak
    if ($allowedRoles && !in_array($user['role'], $allowedRoles, true)) {
        # calling function http_response_code() untuk set response code 403 (forbidden)
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

#hapuskan cookies session supaya data session tidak boleh diakses lagi, dan destroy session
function logout(): void
{
    # assign array kosong ke $_SESSION untuk kosongkan semua data session
    $_SESSION = [];

    # calling function ini_get() untuk check setting php sama ada guna session cookies
    if (ini_get('session.use_cookies')) {
        # calling function sesion_get_cokie_param() that assign to variable name $params untuk jalan session cookie
        $params = session_get_cookie_params();
        # calling function setcookie() untuk padam cookie session (set value kosong & masa lampau)
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

    # calling function session_destroy() untuk padam semua data session kat server
    session_destroy();
}

function csrf_token(): string
{
    # calling function empty() untuk check token csrf dah wujud dlm session ke belum
    if (empty($_SESSION['csrf_token'])) {
        # calling function bin2hex() & random_bytes() that assign value ke $_SESSION['csrf_token'] untuk jana token rawak baru
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}
#keselamatan data
function csrf_field(): string
{
    # calling function csrf_token() & htmlspecialchars() untuk bina hidden input html yang bawa token csrf
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    # ambil value $_POST['csrf_token'] that assign to variable name $submitted
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    # ambil value $_SESSION['csrf_token'] that assign to variable name $expected
    $expected = $_SESSION['csrf_token'] ?? '';

    # calling function hash_equals() untuk banding $submitted dgn $expected, pulangkan true/false
    return $submitted !== '' && $expected !== '' && hash_equals($expected, $submitted);
}
