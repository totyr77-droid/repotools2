<?php
// Public presentation only: never use profile details or arbitrary query strings.
function repotools_page_metadata(?string $title = null, ?string $description = null): array
{
    $origin = 'https://repotools.online';
    $configured = parse_url(getenv('APP_URL') ?: $origin);
    if (($configured['scheme'] ?? '') === 'https' && in_array($configured['host'] ?? '', ['repotools.online', 'www.repotools.online'], true)) {
        $origin = 'https://' . $configured['host'];
    }
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/');
    $path = $uri['path'] ?? '/';
    $allowed = ['/', '/index.php', '/index.html', '/stock.php', '/biblioteca.php', '/tops.php', '/reportes.php', '/cuenta.php', '/palet.php', '/stock-compartido.php'];
    $short = preg_match('~^/q/[0-9]{8}/?$~D', $path) || preg_match('~^/[psbl]/[A-Za-z0-9]{8}/?$~D', $path) || preg_match('~^/s/(?:[A-Za-z0-9_-]{22}|[a-f0-9]{64})/?$~D', $path);
    if (!in_array($path, $allowed, true) && !$short) $path = '/';
    if (in_array($path, ['/index.php', '/index.html'], true)) $path = '/';
    parse_str($uri['query'] ?? '', $query);
    $suffix = '';
    if ($path === '/stock-compartido.php' && is_string($query['token'] ?? null) && preg_match('/^(?:[A-Za-z0-9_-]{22}|[a-f0-9]{64})$/D', $query['token'])) {
        $suffix = '?token=' . rawurlencode($query['token']);
    } elseif ($path === '/palet.php' && is_string($query['codigo'] ?? null) && preg_match('/^[0-9]{8}$/D', $query['codigo'])) {
        $suffix = '?codigo=' . $query['codigo'];
    } elseif ($path === '/tops.php' && in_array($query['categoria'] ?? '', ['reportes', 'aportes'], true)) {
        $suffix = '?categoria=' . $query['categoria'];
    }
    return [
        'title' => $title ?? 'Repotools - Herramientas para repositores',
        'description' => $description ?? 'Escaneá productos, controlá stock y organizá tu trabajo con Repotools.',
        'url' => $origin . $path . $suffix,
        'image' => $origin . '/img/repotools-og-preview.png',
    ];
}
