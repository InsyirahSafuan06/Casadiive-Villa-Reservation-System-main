<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/**
 * Verify credentials against the `user` table and, on success,
 * store a trimmed-down copy of the row (no password hash) in the session.
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
 * Redirects to the sibling login.php (this is only called from pages inside
 * user/) if there's no session, or the role isn't in $allowedRoles.
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
 * CSRF protection for POST forms. csrf_field() prints a hidden input;
 * csrf_verify() checks it and should be called before acting on any POST.
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
