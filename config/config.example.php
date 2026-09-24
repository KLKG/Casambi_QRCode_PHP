<?php
/**
 * Casambi QR-Code - configuration template.
 *
 * Copy this file to config/config.php and adjust the values.
 * config/config.php is listed in .gitignore and must never be committed.
 */

declare(strict_types=1);

return [
    // MySQL / MariaDB connection
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'user'     => 'lithernet',
        'password' => 'change-me',
        'database' => 'lithernet',
    ],

    // Lithernet Casambi Gateway (UDP broadcast target)
    'lithernet' => [
        'broadcast_ip' => '192.168.1.255',
        'port'         => 10009,
    ],

    // 'demo' = store slider values only, send no UDP packets
    // 'run'  = send commands to the gateway
    'operation_mode' => 'demo',

    // Admin session: idle timeout in seconds
    'session_lifetime' => 1800,

    // Admin login: lock the account for N seconds after M failed attempts
    'login_max_attempts'    => 5,
    'login_lockout_seconds' => 900,

    // Photo per QR code: maximum upload size in bytes and longest edge after downscaling
    'image_max_upload_bytes' => 8 * 1024 * 1024,
    'image_max_px'           => 1200,

    // PHP error log (empty string = use the server default)
    'error_log' => __DIR__ . '/../logs/php-error.log',
];
