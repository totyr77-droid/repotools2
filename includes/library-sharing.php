<?php
declare(strict_types=1);

function library_share_preview(PDO $db, string $token): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
    $stmt = $db->prepare('SELECT source_library_id, owner_id, payload FROM library_shares WHERE token = ?');
    $stmt->execute([$token]);
    $share = $stmt->fetch();
    if (!$share) return null;
    $share['collection'] = json_decode($share['payload'], true, 512, JSON_THROW_ON_ERROR);
    unset($share['payload']);
    return $share;
}

function library_share_create(PDO $db, int $userId, int $libraryId): string {
    $stmt = $db->prepare('SELECT name FROM libraries WHERE id = ? AND user_id = ?');
    $stmt->execute([$libraryId, $userId]);
    $name = $stmt->fetchColumn();
    if ($name === false) throw new DomainException('No encontramos esa colección.');
    $stmt = $db->prepare('SELECT ean, name FROM library_products WHERE library_id = ? ORDER BY id');
    $stmt->execute([$libraryId]);
    $products = $stmt->fetchAll();
    if (count($products) < 1) throw new DomainException('Agrega un producto para compartir.');
    $token = bin2hex(random_bytes(32));
    $payload = json_encode(['name' => $name, 'products' => $products], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $db->prepare('INSERT INTO library_shares (token, source_library_id, owner_id, payload) VALUES (?, ?, ?, ?) ON CONFLICT(source_library_id) DO UPDATE SET payload = excluded.payload')->execute([$token, $libraryId, $userId, $payload]);
    $stmt = $db->prepare('SELECT token FROM library_shares WHERE source_library_id = ?');
    $stmt->execute([$libraryId]);
    return $stmt->fetchColumn();
}

function library_share_import(PDO $db, int $userId, string $token): int {
    $db->beginTransaction();
    try {
        // Serializa importaciones de la misma cuenta, también con dos pestañas abiertas.
        $db->prepare('UPDATE users SET name = name WHERE id = ?')->execute([$userId]);
        $share = library_share_preview($db, $token);
        if (!$share) throw new DomainException('El enlace no está disponible.');
        if ((int) $share['owner_id'] === $userId) throw new DomainException('Esta colección ya es tuya.');
        $stmt = $db->prepare('SELECT library_id FROM library_imports WHERE user_id = ? AND source_library_id = ?');
        $stmt->execute([$userId, $share['source_library_id']]);
        if ($stmt->fetchColumn()) throw new DomainException('Ya agregaste esta colección a tu biblioteca.');
        $collection = $share['collection'];
        // Mantiene colecciones existentes y elige un nombre libre para la copia.
        $name = $collection['name']; $suffix = 1;
        $stmt = $db->prepare('SELECT id FROM libraries WHERE user_id = ? AND name = ?');
        while (true) {
            $stmt->execute([$userId, $name]);
            if (!$stmt->fetchColumn()) break;
            $name = mb_substr($collection['name'], 0, 65) . ' (' . ++$suffix . ')';
        }
        $stmt = $db->prepare('INSERT INTO libraries (user_id, name) VALUES (?, ?) RETURNING id');
        $stmt->execute([$userId, $name]); $id = (int) $stmt->fetchColumn(); $stmt->closeCursor();
        $stmt = $db->prepare('INSERT INTO library_products (library_id, ean, name) VALUES (?, ?, ?) ON CONFLICT(library_id, ean) DO NOTHING');
        foreach ($collection['products'] as $product) $stmt->execute([$id, $product['ean'], $product['name']]);
        $db->prepare('INSERT INTO library_imports (user_id, source_library_id, library_id) VALUES (?, ?, ?)')->execute([$userId, $share['source_library_id'], $id]);
        $db->commit(); return $id;
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}
