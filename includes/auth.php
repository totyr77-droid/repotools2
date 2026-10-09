<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_name('repotools_session');
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax']);
    session_start();
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
