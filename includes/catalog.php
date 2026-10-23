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
    $stmt = $db->prepare('SELECT ean, name, brand, quantity, category, packaging, uxb, uxc, image, source, source_url, revision FROM products WHERE ean = ?');
    $stmt->execute([$ean]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) return null;
    $product['revision'] = (int) $product['revision'];
    return $product;
}

function catalog_pack_value(mixed $value): string {
    if($value==='')return '';
    if(!is_string($value)||!preg_match('/^[1-9][0-9]{0,6}$/D',$value)||(int)$value>1000000)throw new CatalogException('UXB y UXC deben ser unidades enteras entre 1 y 1.000.000.',422);
    return (string)(int)$value;
}
function catalog_text($value, int $max): string {
    return is_string($value) ? mb_substr(trim($value), 0, $max) : '';
}
function catalog_approved_photo(PDO $db, string $ean): ?string {
    $direct=$db->prepare('SELECT object_key FROM catalog_photos WHERE ean=?');$direct->execute([$ean]);
    if($key=$direct->fetchColumn())return $key;
    $stmt = $db->prepare("SELECT p.object_key FROM report_photos p JOIN product_reports r ON r.id = p.report_id WHERE r.ean = ? AND r.status = 'approved' AND p.kind = 'product' ORDER BY r.reviewed_at DESC, r.id DESC LIMIT 1");
    $stmt->execute([$ean]); return $stmt->fetchColumn() ?: null;
}
function catalog_external_image(array $external): string {
    $image = catalog_text($external['image_front_small_url'] ?? '', 1000); $parts = parse_url($image);
    return ($parts['scheme'] ?? '') === 'https' && preg_match('/^(?:[a-z0-9-]+\.)*openfoodfacts\.org$/iD', $parts['host'] ?? '') ? $image : '';
}
function catalog_product_image(PDO $db, array $product, ?callable $fetcher = null): array {
    $ean = $product['ean'];
    if (catalog_approved_photo($db, $ean)) {
        $product['image'] = 'api/productos.php?ean=' . rawurlencode($ean) . '&photo=product';
        $product['image_source'] = 'repotools'; return $product;
    }
    if ($product['image']) { $product['image_source'] = 'openfoodfacts'; return $product; }
    $key = 'image:' . $ean; $now = time(); $token = bin2hex(random_bytes(16));
    $db->beginTransaction();
    try {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') $db->query('SELECT pg_advisory_xact_lock(72684622)');
        else $db->exec('DELETE FROM product_api_requests WHERE requested_at < 0');
        $stmt = $db->prepare('SELECT checked_at FROM product_lookup_state WHERE ean = ?'); $stmt->execute([$key]);
        $checked = $stmt->fetchColumn();
        $db->prepare('DELETE FROM product_api_requests WHERE requested_at <= ?')->execute([$now - 60]);
        if (($checked !== false && (int) $checked > $now - 86400) || (int) $db->query('SELECT COUNT(*) FROM product_api_requests')->fetchColumn() >= 15) { $db->commit(); return $product; }
        $db->prepare('INSERT INTO product_api_requests(requested_at) VALUES (?)')->execute([$now]);
        $db->prepare('INSERT INTO product_lookup_state(ean, state, checked_at, token) VALUES (?, ?, ?, ?) ON CONFLICT(ean) DO UPDATE SET checked_at = excluded.checked_at, token = excluded.token')->execute([$key, 'image', $now, $token]);
        $db->commit();
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
    try {
        $external = ($fetcher ?? 'fetch_external_product')($ean);
        if ($external !== null && (!isset($external['code']) || (string) $external['code'] === $ean)) {
            $image = catalog_external_image($external);
            if ($image !== '') $db->prepare("UPDATE products SET image = ? WHERE ean = ? AND image = ''")->execute([$image, $ean]);
        }
    } catch (Throwable $error) { /* A missing external photo never hides our product information. */ }
    $product = catalog_product($db, $ean) ?? $product;
    if (catalog_approved_photo($db, $ean)) { $product['image'] = 'api/productos.php?ean=' . rawurlencode($ean) . '&photo=product'; $product['image_source'] = 'repotools'; }
    elseif ($product['image']) $product['image_source'] = 'openfoodfacts';
    return $product;
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
    if (isset($product['code']) && (string) $product['code'] !== $ean) throw new CatalogException('No pudimos obtener los datos del producto. El EAN leído sigue disponible.');
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
    return in_array($stmt->fetchColumn(), ['manager', 'administrador', 'moderador'], true);
}

function edit_catalog_product(PDO $db, string $ean, array $fields, int $revision, int $userId, ?int $reviewedReportId = null, ?string $photoKey = null): array {
    if (!valid_product_ean($ean)) throw new CatalogException('El EAN no es válido.', 422);
    $values = [];
    foreach (['name' => 120, 'quantity' => 80, 'brand' => 160, 'category' => 240, 'packaging' => 240] as $field => $max) {
        if (!is_string($fields[$field] ?? null) || mb_strlen(trim($fields[$field])) > $max) throw new CatalogException('Revisa los datos del producto.', 422);
        $values[$field] = trim($fields[$field]);
    }
    if ($values['name'] === '') throw new CatalogException('Escribe el nombre del producto.', 422);
    $ownTransaction = !$db->inTransaction();
    if ($ownTransaction) $db->beginTransaction();
    try {
        $before = catalog_product($db, $ean);
        foreach(['uxb','uxc'] as $key)$values[$key]=catalog_pack_value($fields[$key]??($before[$key]??''));
        if ($before) {
            if ($before['revision'] !== $revision) throw new CatalogException('Otra persona modificó esta ficha. Revisa la versión actual antes de guardar.', 409);
            $stmt = $db->prepare('UPDATE products SET name = ?, quantity = ?, brand = ?, category = ?, packaging = ?, uxb = ?, uxc = ?, revision = revision + 1, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE ean = ? AND revision = ?');
            $stmt->execute([...array_values($values), $userId, $ean, $revision]);
        } else {
            if ($revision !== 0) throw new CatalogException('La ficha cambió. Revisa la versión actual.', 409);
            $stmt = $db->prepare('INSERT INTO products (name, quantity, brand, category, packaging, uxb, uxc, updated_by, ean) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(ean) DO NOTHING');
            $stmt->execute([...array_values($values), $userId, $ean]);
        }
        if ($stmt->rowCount() !== 1) throw new CatalogException('Otra persona modificó esta ficha. Revisa la versión actual antes de guardar.', 409);
        if($photoKey!==null)$db->prepare('INSERT INTO catalog_photos(ean,object_key) VALUES(?,?) ON CONFLICT(ean) DO UPDATE SET object_key=excluded.object_key')->execute([$ean,$photoKey]);
        $product = catalog_product($db, $ean);
        if($photoKey!==null)$product['photo_changed']=true;
        $db->prepare('INSERT INTO product_edits (ean, user_id, before_data, after_data) VALUES (?, ?, ?, ?)')->execute([$ean, $userId, json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), json_encode($reviewedReportId===null?$product:[...$product,'reviewed_report_id'=>$reviewedReportId], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        $db->prepare('DELETE FROM product_lookup_state WHERE ean = ?')->execute([$ean]);
        if($reviewedReportId===null){require_once __DIR__.'/audit.php';audit_event($db,$userId,'edited','catalog:'.$ean.':'.$product['revision'],['category'=>'movements','ean'=>$ean],$before??[],$product,'confirmed',null,1);}
        if ($ownTransaction) $db->commit();
        return $product;
    } catch (Throwable $error) { if ($ownTransaction && $db->inTransaction()) $db->rollBack(); throw $error; }
}

function catalog_direct_edit_condition(PDO $db, string $column='after_data'): string {
    $alias=str_contains($column,'.')?substr($column,0,strrpos($column,'.')):'product_edits';
    $origin=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql' ? "({$column}::jsonb ->> 'reviewed_report_id') IS NULL" : "json_extract({$column}, '$.reviewed_report_id') IS NULL";
    // Legacy reviews have no explicit origin: unify only an exact, unique audit match.
    return $origin." AND NOT EXISTS(SELECT 1 FROM product_reports legacy WHERE legacy.status='approved' AND legacy.ean={$alias}.ean AND legacy.reviewed_by={$alias}.user_id AND legacy.reviewed_at={$alias}.created_at AND ((legacy.original_data={$alias}.before_data AND (SELECT COUNT(*) FROM product_edits sibling WHERE sibling.ean={$alias}.ean AND sibling.user_id={$alias}.user_id AND sibling.created_at={$alias}.created_at AND sibling.before_data={$alias}.before_data)=1) OR (SELECT COUNT(*) FROM product_edits sibling WHERE sibling.ean={$alias}.ean AND sibling.user_id={$alias}.user_id AND sibling.created_at={$alias}.created_at)=(SELECT COUNT(*) FROM product_reports reviewed WHERE reviewed.status='approved' AND reviewed.ean={$alias}.ean AND reviewed.reviewed_by={$alias}.user_id AND reviewed.reviewed_at={$alias}.created_at)))";
}
