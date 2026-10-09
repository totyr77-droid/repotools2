<?php
declare(strict_types=1);
require_once __DIR__ . '/catalog.php';

function report_storage_config(): array {
    $endpoint = rtrim(getenv('AWS_ENDPOINT_URL_S3') ?: '', '/');
    $parts = parse_url($endpoint);
    $key = getenv('AWS_ACCESS_KEY_ID') ?: '';
    $secret = getenv('AWS_SECRET_ACCESS_KEY') ?: '';
    $region = getenv('AWS_REGION') ?: '';
    if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || !preg_match('/\.storage\.[a-z0-9.-]+\.neon\.tech$/iD', $parts['host']) || isset($parts['user']) || isset($parts['query']) || !$key || !$secret || !preg_match('/^[a-z0-9-]+$/D', $region)) throw new CatalogException('Las fotos todavía no están habilitadas. Puedes enviar el reporte sin fotos.');
    return compact('endpoint', 'key', 'secret', 'region');
}
function report_storage_ready(): bool { try { report_storage_config(); return true; } catch (CatalogException $error) { return false; } }

function report_object(string $method, string $objectKey, string $body = ''): string {
    if (!preg_match('~^reports/[a-f0-9-]{36}/[a-f0-9]{24}-(ean|product)\.jpg$~D', $objectKey)) throw new CatalogException('Foto no válida.', 422);
    $config = report_storage_config();
    $url = $config['endpoint'] . '/report-evidence/' . implode('/', array_map('rawurlencode', explode('/', $objectKey)));
    $curl = curl_init($url);
    $result = '';
    $headers = ['Content-Type: image/jpeg', 'Cache-Control: private, no-store', 'x-amz-content-sha256: ' . hash('sha256', $body)];
    $options = [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_AWS_SIGV4 => 'aws:amz:' . $config['region'] . ':s3', CURLOPT_USERPWD => $config['key'] . ':' . $config['secret'], CURLOPT_HTTPHEADER => $headers, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => false, CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$result): int { if (strlen($result) + strlen($chunk) > 1048576) return 0; $result .= $chunk; return strlen($chunk); }];
    if ($method === 'PUT') $options[CURLOPT_POSTFIELDS] = $body;
    curl_setopt_array($curl, $options);
    $success = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $transportError = curl_errno($curl);
    curl_close($curl);
    if ($success === false || $status < 200 || $status >= 300) {
        error_log('Repotools report storage error: http=' . $status . ' curl=' . $transportError);
        throw new CatalogException('No pudimos acceder a la foto. Intenta nuevamente.');
    }
    return $result;
}

function prepare_report_photo(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name']) || ($file['size'] ?? 0) > 6 * 1024 * 1024) throw new CatalogException('Usa una foto JPG, PNG o WebP de hasta 6 MB.', 422);
    $bytes = file_get_contents($file['tmp_name']);
    $info = $bytes === false ? false : @getimagesizefromstring($bytes);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] * $info[1] > 16000000) throw new CatalogException('La foto no es válida. Usa JPG, PNG o WebP.', 422);
    if (!function_exists('imagecreatefromstring')) throw new CatalogException('No pudimos procesar la foto.');
    $image = @imagecreatefromstring($bytes);
    if (!$image) throw new CatalogException('No pudimos leer la foto.', 422);
    // Redibuja la imagen y elimina metadatos y cualquier contenido añadido al archivo.
    $ratio = min(1, 1280 / max($info[0], $info[1]));
    $width = max(1, (int) round($info[0] * $ratio)); $height = max(1, (int) round($info[1] * $ratio));
    $safe = imagecreatetruecolor($width, $height);
    imagefill($safe, 0, 0, imagecolorallocate($safe, 255, 255, 255));
    imagecopyresampled($safe, $image, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
    ob_start(); imagejpeg($safe, null, 80); $jpeg = ob_get_clean();
    imagedestroy($safe); imagedestroy($image);
    if (!is_string($jpeg) || strlen($jpeg) > 1024 * 1024) throw new CatalogException('La foto es demasiado grande. Acércate al producto e intenta otra vez.', 422);
    return $jpeg;
}
