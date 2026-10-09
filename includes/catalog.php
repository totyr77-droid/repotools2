<?php
declare(strict_types=1);

class CatalogException extends RuntimeException {
    public int $status;
    public function __construct(string $message, int $status = 503) { parent::__construct($message); $this->status = $status; }
}

function valid_product_ean(string $ean): bool {
    if (!preg_match('/^(?:[0-9]{8}|[0-9]{13})$/D', $ean) || preg_match('/^0+$/D', $ean)) return false;
    $sum = 0;
    for ($i = strlen($ean) - 2, $weight = 3; $i >= 0; $i--, $weight = 4 - $weight) $sum += (int) $ean[$i] * $weight;
    return (10 - $sum % 10) % 10 === (int) $ean[strlen($ean) - 1];
}

function catalog_product(PDO $db, string $ean): ?array {
    $stmt = $db->prepare('SELECT ean, name, brand, quantity, category, packaging, image, source, source_url, revision FROM products WHERE ean = ?');
    $stmt->execute([$ean]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) return null;
    $product['revision'] = (int) $product['revision'];
    return $product;
}

function catalog_text($value, int $max): string {
    return is_string($value) ? mb_substr(trim($value), 0, $max) : '';
}

function fetch_external_product(string $ean): ?array {
    if (!function_exists('curl_init')) throw new CatalogException('No pudimos consultar el producto. Intenta más tarde.');
    $fields = 'code,product_name,product_name_es,brands,quantity,categories,packaging,image_front_small_url';
    $handle = curl_init('https://world.openfoodfacts.org/api/v2/product/' . rawurlencode($ean) . '.json?fields=' . $fields);
    $body = '';
    curl_setopt_array($handle, [CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => false, CURLOPT_USERAGENT => 'Repotools/1.0 (https://github.com/totyr77-droid/repotools2)', CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
        if (strlen($body) + strlen($chunk) > 1048576) return 0;
        $body .= $chunk;
        return strlen($chunk);
    }]);
    $success = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    if ($success === false || !in_array($status, [200, 404], true)) throw new CatalogException('No pudimos consultar el producto. Intenta nuevamente.');
    try { $result = json_decode($body, true, 32, JSON_THROW_ON_ERROR); }
    catch (JsonException $error) { throw new CatalogException('La información del producto no está disponible.'); }
    if (!is_array($result)) throw new CatalogException('La información del producto no está disponible.');
    if (($result['status'] ?? null) === 0) return null;
    if (($result['status'] ?? null) !== 1 || !is_array($result['product'] ?? null)) throw new CatalogException('La información del producto no está disponible.');
    $product = $result['product'];
    if (isset($product['code']) && (string) $product['code'] !== $ean) throw new CatalogException('La información recibida no corresponde al EAN.');
    return $product;
}

function lookup_product(PDO $db, string $ean, ?callable $fetcher = null): ?array {
    if (!valid_product_ean($ean)) throw new CatalogException('El EAN no es válido.', 422);
    if ($product = catalog_product($db, $ean)) return $product;
    $token = bin2hex(random_bytes(16));
    $now = time();
    // Reserva corta: no mantiene una transacción abierta mientras consulta la API.
    $db->beginTransaction();
    try {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') $db->query('SELECT pg_advisory_xact_lock(72684622)');
        else $db->exec('DELETE FROM product_api_requests WHERE requested_at < 0'); // Adquiere el bloqueo de escritura en SQLite.
        if ($product = catalog_product($db, $ean)) { $db->commit(); return $product; }
        $stmt = $db->prepare('SELECT state, checked_at FROM product_lookup_state WHERE ean = ?');
        $stmt->execute([$ean]);
        $lookup = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($lookup && $lookup['state'] === 'missing' && (int) $lookup['checked_at'] > $now - 86400) { $db->commit(); return null; }
        if ($lookup && $lookup['state'] === 'pending' && (int) $lookup['checked_at'] > $now - 15) throw new CatalogException('Este producto se está consultando. Reintenta en unos segundos.');
        $db->prepare('DELETE FROM product_api_requests WHERE requested_at <= ?')->execute([$now - 60]);
        if ((int) $db->query('SELECT COUNT(*) FROM product_api_requests')->fetchColumn() >= 15) throw new CatalogException('Muchas consultas nuevas. Reintenta en un minuto.', 429);
        $db->prepare('INSERT INTO product_api_requests (requested_at) VALUES (?)')->execute([$now]);
        $db->prepare('INSERT INTO product_lookup_state (ean, state, checked_at, token) VALUES (?, ?, ?, ?) ON CONFLICT(ean) DO UPDATE SET state = excluded.state, checked_at = excluded.checked_at, token = excluded.token')->execute([$ean, 'pending', $now, $token]);
        $db->commit();
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
    try {
        $external = ($fetcher ?? 'fetch_external_product')($ean);
        if ($external === null) {
            $db->prepare('UPDATE product_lookup_state SET state = ? WHERE ean = ? AND token = ?')->execute(['missing', $ean, $token]);
            return catalog_product($db, $ean);
        }
        $name = catalog_text($external['product_name_es'] ?? '', 120) ?: catalog_text($external['product_name'] ?? '', 120);
        $image = catalog_text($external['image_front_small_url'] ?? '', 1000);
        $imageParts = parse_url($image);
        if (($imageParts['scheme'] ?? '') !== 'https' || !preg_match('/^(?:[a-z0-9-]+\.)*openfoodfacts\.org$/iD', $imageParts['host'] ?? '')) $image = '';
        $sourceUrl = 'https://world.openfoodfacts.org/product/' . $ean;
        // DO NOTHING conserva las correcciones de una ficha creada mientras llegaba la API.
        $db->prepare('INSERT INTO products (ean, name, brand, quantity, category, packaging, image, source, source_url, external_payload) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(ean) DO NOTHING')->execute([$ean, $name, catalog_text($external['brands'] ?? '', 160), catalog_text($external['quantity'] ?? '', 80), catalog_text($external['categories'] ?? '', 240), catalog_text($external['packaging'] ?? '', 240), $image, 'openfoodfacts', $sourceUrl, json_encode($external, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        $db->prepare('DELETE FROM product_lookup_state WHERE ean = ? AND token = ?')->execute([$ean, $token]);
        return catalog_product($db, $ean);
    } catch (Throwable $error) {
        $db->prepare('DELETE FROM product_lookup_state WHERE ean = ? AND token = ?')->execute([$ean, $token]);
        throw $error;
    }
}

function can_edit_catalog(PDO $db, ?array $user): bool {
    if (!$user) return false;
    $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    return in_array($stmt->fetchColumn(), ['administrador', 'moderador'], true);
}

function edit_catalog_product(PDO $db, string $ean, array $fields, int $revision, int $userId): array {
    if (!valid_product_ean($ean)) throw new CatalogException('El EAN no es válido.', 422);
    $values = [];
    foreach (['name' => 120, 'quantity' => 80, 'brand' => 160, 'category' => 240, 'packaging' => 240] as $field => $max) {
        if (!is_string($fields[$field] ?? null) || mb_strlen(trim($fields[$field])) > $max) throw new CatalogException('Revisa los datos del producto.', 422);
        $values[$field] = trim($fields[$field]);
    }
    if ($values['name'] === '') throw new CatalogException('Escribe el nombre del producto.', 422);
    $db->beginTransaction();
    try {
        $before = catalog_product($db, $ean);
        if ($before) {
            if ($before['revision'] !== $revision) throw new CatalogException('Otra persona modificó esta ficha. Revisa la versión actual antes de guardar.', 409);
            $stmt = $db->prepare('UPDATE products SET name = ?, quantity = ?, brand = ?, category = ?, packaging = ?, revision = revision + 1, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE ean = ? AND revision = ?');
            $stmt->execute([...array_values($values), $userId, $ean, $revision]);
        } else {
            if ($revision !== 0) throw new CatalogException('La ficha cambió. Revisa la versión actual.', 409);
            $stmt = $db->prepare('INSERT INTO products (name, quantity, brand, category, packaging, updated_by, ean) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT(ean) DO NOTHING');
            $stmt->execute([...array_values($values), $userId, $ean]);
        }
        if ($stmt->rowCount() !== 1) throw new CatalogException('Otra persona modificó esta ficha. Revisa la versión actual antes de guardar.', 409);
        $product = catalog_product($db, $ean);
        $db->prepare('INSERT INTO product_edits (ean, user_id, before_data, after_data) VALUES (?, ?, ?, ?)')->execute([$ean, $userId, json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), json_encode($product, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        $db->prepare('DELETE FROM product_lookup_state WHERE ean = ?')->execute([$ean]);
        $db->commit();
        return $product;
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
}
