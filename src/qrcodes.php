<?php
/**
 * QR code repository: codes, their control elements and the optional image.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Codes
// ---------------------------------------------------------------------------

/** @return array<string, mixed>|null */
function qrGetCode(string $code): ?array
{
    if ($code === '') {
        return null;
    }
    return dbFetchOne(
        'SELECT q.code, q.name, q.lithernet_id, q.created_at, q.updated_at,
                (SELECT COUNT(*) FROM qrcode_images i WHERE i.code = q.code) AS has_image,
                (SELECT COUNT(*) FROM qrcode_elements e WHERE e.code = q.code) AS element_count
         FROM qrcodes q WHERE q.code = ?',
        [$code]
    );
}

/** @return list<array<string, mixed>> */
function qrListCodes(): array
{
    return dbFetchAll(
        'SELECT q.code, q.name, q.lithernet_id,
                (SELECT COUNT(*) FROM qrcode_images i WHERE i.code = q.code) AS has_image,
                (SELECT COUNT(*) FROM qrcode_elements e WHERE e.code = q.code) AS element_count,
                (SELECT GROUP_CONCAT(e.element_type ORDER BY e.position, e.id SEPARATOR ",")
                   FROM qrcode_elements e WHERE e.code = q.code) AS element_types
         FROM qrcodes q ORDER BY q.name, q.code'
    );
}

function qrExists(string $code): bool
{
    if ($code === '') {
        return false;
    }
    return dbFetchOne('SELECT 1 AS found FROM qrcodes WHERE code = ?', [$code]) !== null;
}

/** Cryptographically random 10-character code that is not in use yet. */
function qrGenerateCode(): string
{
    do {
        $code = bin2hex(random_bytes(5));
    } while (qrExists($code));

    return $code;
}

function qrCreate(string $code, string $name, int $lithernetId): void
{
    dbExecute('INSERT INTO qrcodes (code, name, lithernet_id) VALUES (?, ?, ?)', [$code, $name, $lithernetId]);
}

function qrUpdateMeta(string $code, string $name, int $lithernetId): void
{
    dbExecute('UPDATE qrcodes SET name = ?, lithernet_id = ? WHERE code = ?', [$name, $lithernetId, $code]);
}

function qrDelete(string $code): void
{
    // Elements and image are removed by the foreign keys (ON DELETE CASCADE).
    dbExecute('DELETE FROM qrcodes WHERE code = ?', [$code]);
}

// ---------------------------------------------------------------------------
// Elements
// ---------------------------------------------------------------------------

/** @return list<array<string, mixed>> */
function qrElements(string $code): array
{
    return dbFetchAll(
        'SELECT * FROM qrcode_elements WHERE code = ? ORDER BY position, id',
        [$code]
    );
}

/** @return array<string, mixed>|null */
function qrGetElement(int $id, string $code): ?array
{
    if ($id <= 0 || $code === '') {
        return null;
    }
    return dbFetchOne('SELECT * FROM qrcode_elements WHERE id = ? AND code = ?', [$id, $code]);
}

/** Append a new element of the given type with sensible defaults; returns its id. */
function qrAddElement(string $code, string $type, string $name): int
{
    $def = elementTypeDef($type);
    if ($def === null) {
        throw new InvalidArgumentException('Unknown element type ' . $type);
    }
    $position = (int) (dbFetchOne('SELECT COALESCE(MAX(position), 0) + 1 AS p FROM qrcode_elements WHERE code = ?', [$code])['p'] ?? 1);
    $template = [
        'element_type' => $type,
        'target_type'  => $def['scene'] ? TARGET_SCENE_ALL : TARGET_BROADCAST,
        'target_id'    => 0,
        'fade_ms'      => 0,
        'param_min'    => 2700,
        'param_max'    => 6500,
        'param_level'  => 254,
        'state'        => null,
    ];
    $state = json_encode(elementDefaultState($template), JSON_THROW_ON_ERROR);

    dbExecute(
        'INSERT INTO qrcode_elements
            (code, position, name, element_type, target_type, target_id, fade_ms, param_min, param_max, param_level, state)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $code, $position, $name !== '' ? $name : $def['label'], $type,
            $template['target_type'], $template['target_id'], $template['fade_ms'],
            $template['param_min'], $template['param_max'], $template['param_level'], $state,
        ]
    );
    return (int) db()->insert_id;
}

/**
 * Update the editable settings of an element.
 *
 * @param array{position: int, name: string, target_type: int, target_id: int, fade_ms: int,
 *              param_min: int, param_max: int, param_level: int} $f
 */
function qrUpdateElement(int $id, string $code, array $f): void
{
    dbExecute(
        'UPDATE qrcode_elements
            SET position = ?, name = ?, target_type = ?, target_id = ?, fade_ms = ?,
                param_min = ?, param_max = ?, param_level = ?
          WHERE id = ? AND code = ?',
        [
            $f['position'], $f['name'], $f['target_type'], $f['target_id'], $f['fade_ms'],
            $f['param_min'], $f['param_max'], $f['param_level'], $id, $code,
        ]
    );
}

function qrDeleteElement(int $id, string $code): void
{
    dbExecute('DELETE FROM qrcode_elements WHERE id = ? AND code = ?', [$id, $code]);
}

/** Renumber positions 1..n in the current order. */
function qrNormalizePositions(string $code): void
{
    $position = 1;
    foreach (qrElements($code) as $element) {
        dbExecute('UPDATE qrcode_elements SET position = ? WHERE id = ?', [$position++, (int) $element['id']]);
    }
}

/** @param array<string, int> $state */
function qrUpdateElementState(int $id, array $state): void
{
    dbExecute('UPDATE qrcode_elements SET state = ? WHERE id = ?', [json_encode($state, JSON_THROW_ON_ERROR), $id]);
}

// ---------------------------------------------------------------------------
// Image
// ---------------------------------------------------------------------------

/** @return array{mime: string, width: int, height: int, data: string, updated_at: string}|null */
function qrGetImage(string $code): ?array
{
    if ($code === '') {
        return null;
    }
    return dbFetchOne('SELECT mime, width, height, data, updated_at FROM qrcode_images WHERE code = ?', [$code]);
}

function qrSetImage(string $code, string $mime, int $width, int $height, string $data): void
{
    dbExecute(
        'INSERT INTO qrcode_images (code, mime, width, height, data) VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE mime = VALUES(mime), width = VALUES(width), height = VALUES(height), data = VALUES(data)',
        [$code, $mime, $width, $height, $data]
    );
}

function qrDeleteImage(string $code): void
{
    dbExecute('DELETE FROM qrcode_images WHERE code = ?', [$code]);
}

/**
 * Validate an uploaded photo, downscale it and re-encode it as JPEG.
 * Re-encoding drops metadata and anything that is not pixel data.
 *
 * @return array{mime: string, width: int, height: int, data: string}
 * @throws RuntimeException with a user-readable message
 */
function processUploadedImage(string $tmpFile, int $size): array
{
    $config   = $GLOBALS['config'];
    $maxBytes = (int) ($config['image_max_upload_bytes'] ?? 8388608);
    $maxPx    = max(200, (int) ($config['image_max_px'] ?? 1200));

    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('The image must be between 1 byte and ' . round($maxBytes / 1048576) . ' MB.');
    }
    $info = @getimagesize($tmpFile);
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
        throw new RuntimeException('Only JPEG, PNG, GIF or WebP images are accepted.');
    }
    $raw = file_get_contents($tmpFile);
    if ($raw === false) {
        throw new RuntimeException('Upload could not be read.');
    }
    $src = @imagecreatefromstring($raw);
    if ($src === false) {
        throw new RuntimeException('The file is not a valid image.');
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1.0, $maxPx / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white); // flatten transparency onto white
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    ob_start();
    imagejpeg($dst, null, 82);
    $data = (string) ob_get_clean();
    imagedestroy($dst);

    if ($data === '') {
        throw new RuntimeException('Image could not be encoded.');
    }
    return ['mime' => 'image/jpeg', 'width' => $nw, 'height' => $nh, 'data' => $data];
}
