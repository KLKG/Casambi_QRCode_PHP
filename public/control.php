<?php
/**
 * JSON endpoint for the control page: stores the new values of one element
 * and sends the matching command to the gateway.
 *
 * POST fields: csrf, code, element (id), then either
 *   - the slider fields of the element (level, tc, red, ...), or
 *   - action=<button index> for button elements
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jsonExit(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

if (!isPost()) {
    jsonExit(405, ['ok' => false, 'error' => 'POST required.']);
}
if (!csrfValid($_POST['csrf'] ?? null)) {
    jsonExit(403, ['ok' => false, 'error' => t('Session expired, please reload the page.')]);
}

$code    = cleanCode($_POST['code'] ?? '');
$entry   = qrGetCode($code);
$element = $entry !== null ? qrGetElement(cleanInt($_POST['element'] ?? null, 1, PHP_INT_MAX, 0), $code) : null;

if ($entry === null || $element === null) {
    jsonExit(404, ['ok' => false, 'error' => t('Unknown code or element.')]);
}

$def   = elementTypeDef((string) $element['element_type']);
$state = elementState($element);

if ($def === null) {
    jsonExit(500, ['ok' => false, 'error' => t('Element type not supported.')]);
}

$index = null;
if ($def['kind'] === 'slider') {
    foreach (elementFields($element) as $field => $f) {
        $state[$field] = cleanInt($_POST[$field] ?? null, $f['min'], $f['max'], $state[$field]);
    }
} else {
    $buttons = elementButtons($element);
    $index   = cleanInt($_POST['action'] ?? null, 0, count($buttons) - 1, -1);
    if ($index < 0) {
        jsonExit(400, ['ok' => false, 'error' => t('Unknown button.')]);
    }
    $button = $buttons[$index];
    if ($button['field'] !== null && $button['value'] !== null) {
        $state[$button['field']] = (int) $button['value'];
    }
}

try {
    if ($state !== []) {
        qrUpdateElementState((int) $element['id'], $state);
    }
    $message = buildElementCommand($element, $state, (int) $entry['lithernet_id'], $index);
    $sent    = sendCasambiCommand($message);
} catch (Throwable $e) {
    error_log((string) $e);
    jsonExit(500, ['ok' => false, 'error' => t('Command could not be sent.')]);
}

if (!$sent) {
    jsonExit(502, ['ok' => false, 'error' => t('Gateway did not accept the command.')]);
}

jsonExit(200, [
    'ok'      => true,
    'demo'    => isDemoMode(),
    'element' => (int) $element['id'],
    'values'  => $state,
]);
