<?php
/**
 * Streams the QR code PNG for one code (admin only). Nothing is written to disk.
 * Requires chillerlan/php-qrcode (composer install).
 *
 * GET: code, download=1 (optional, forces a file download)
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

if (!isLoggedIn()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Login required.\n");
}

$code = cleanCode($_GET['code'] ?? '');
if ($code === '' || !qrExists($code)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Unknown code.\n");
}

if (!class_exists(QRCode::class)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit("QR code library missing: run 'composer install' in the project directory.\n");
}

$options                   = new QROptions();
$options->outputInterface  = QRGdImagePNG::class;
$options->eccLevel         = EccLevel::H;
$options->scale            = 10;
$options->outputBase64     = false;
$options->imageTransparent = false;
$options->addQuietzone     = true;
$options->quietzoneSize    = 2;

$png = (new QRCode($options))->render($code);

$disposition = isset($_GET['download']) ? 'attachment' : 'inline';
header('Content-Type: image/png');
header('Cache-Control: private, no-store');
header('Content-Disposition: ' . $disposition . '; filename="casambi-' . $code . '.png"');
header('Content-Length: ' . strlen($png));
echo $png;
