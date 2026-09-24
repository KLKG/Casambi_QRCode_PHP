<?php
/**
 * Streams the photo stored for a QR code (public, like the control page).
 *
 * GET: code
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$code  = cleanCode($_GET['code'] ?? '');
$image = qrGetImage($code);

if ($image === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit("No image.\n");
}

$etag = '"' . md5($code . '|' . $image['updated_at'] . '|' . strlen($image['data'])) . '"';
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $image['mime']);
header('Content-Length: ' . strlen($image['data']));
header('Cache-Control: public, max-age=3600');
header('ETag: ' . $etag);
header('Content-Disposition: inline; filename="casambi-' . $code . '.jpg"');
echo $image['data'];
