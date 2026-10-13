<?php
declare(strict_types=1);

// Optional scan evidence is short-lived and never part of the public catalogue.
function relay_uploaded_photo(): ?string {
    if (!isset($_FILES['photo'])) return null;
    $file = $_FILES['photo'];
    if (!is_array($file) || ($file['error'] ?? -1) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name']) || ($file['size'] ?? 0) > 1048576) {
        throw new RuntimeException('La foto no es válida. Toma otra imagen.');
    }
    $bytes = file_get_contents($file['tmp_name']);
    $image = $bytes !== false ? @getimagesizefromstring($bytes) : false;
    if (!$image || $image[2] !== IMAGETYPE_JPEG || $image[0] < 1 || $image[1] < 1 || max($image[0], $image[1]) > 1280 || strlen($bytes) > 1048576) {
        throw new RuntimeException('La foto no es válida. Toma otra imagen.');
    }
    return base64_encode($bytes);
}

function relay_drop_photo(PDO $db, int $uid): void {
    $db->prepare('DELETE FROM scan_relay_photos WHERE user_id=?')->execute([$uid]);
}
