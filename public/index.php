<?php
/**
 * Public front end: start page, QR scanner, manual code entry and the slider page.
 * Slider changes are sent to control.php via JavaScript.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$site = cleanSite($_GET['site'] ?? '');

switch ($site) {
    case 'scan':
        render('index/scan', ['title' => 'Scan Code', 'scripts' => ['scanner']]);
        break;

    case 'code':
        render('index/code', ['title' => 'Enter Code']);
        break;

    case 'control':
        $code  = cleanCode($_GET['code'] ?? '');
        $entry = qrGetCode($code);
        render('index/control', [
            'title'    => $entry !== null && $entry['name'] !== '' ? (string) $entry['name'] : 'Control ' . $code,
            'code'     => $code,
            'entry'    => $entry,
            'elements' => $entry !== null ? qrElements($code) : [],
        ]);
        break;

    default:
        render('index/start');
}
