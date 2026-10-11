<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/sessions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)DatabaseSessions::LIFETIME);
    session_name('repotools_session');
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (getenv('RENDER') === 'true');
    session_set_cookie_params(['lifetime' => DatabaseSessions::LIFETIME, 'path' => '/', 'httponly' => true, 'secure' => $secureCookie, 'samesite' => 'Lax']);
    try {
        session_set_save_handler(new DatabaseSessions(database()), true);
        if (!session_start()) throw new RuntimeException('Session unavailable');
    } catch (Throwable $error) {
        http_response_code(503);
        header('Retry-After: 30');
        header('Content-Type: text/plain; charset=UTF-8');
        exit('No pudimos acceder a tu sesión. Intenta nuevamente en unos momentos.');
    }
}

require_once __DIR__ . '/database.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function valid_csrf(): bool {
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals(csrf_token(), $_POST['csrf']);
}

function require_account(string $next = 'stock'): void {
    if (!current_user()) {
        header('Location: cuenta.php?next=' . (in_array($next, ['stock', 'tops', 'biblioteca'], true) ? $next : 'stock'));
        exit;
    }
}
