<?php
/**
 * First-run setup: requirement checks, database connection test and writing config/config.php.
 * Used by public/setup.php (web wizard) and bin/setup.php (CLI). Works without a config file.
 */

declare(strict_types=1);

/** Default values for the setup form. */
function setupDefaults(): array
{
    return [
        'db_host'         => 'localhost',
        'db_port'         => 3306,
        'db_user'         => 'lithernet',
        'db_password'     => '',
        'db_database'     => 'lithernet',
        'create_database' => false,
        'gw_ip'           => '192.168.1.255',
        'gw_port'         => 10009,
        'mode'            => 'demo',
    ];
}

/**
 * Requirement checks for the setup page.
 *
 * @return list<array{label: string, ok: bool, warn: bool, detail: string}>
 */
function setupRequirements(): array
{
    $req = [];
    $add = static function (string $label, bool $ok, string $detail, bool $warn = false) use (&$req): void {
        $req[] = ['label' => $label, 'ok' => $ok, 'warn' => $warn, 'detail' => $detail];
    };

    $add('PHP 8.2 or newer', PHP_VERSION_ID >= 80200, 'PHP ' . PHP_VERSION);
    foreach (['mysqli' => 'database', 'sockets' => 'UDP commands to the gateway', 'gd' => 'QR images and photos', 'mbstring' => 'text handling'] as $ext => $use) {
        $add('Extension ' . $ext, extension_loaded($ext), $use);
    }
    $add('config/ writable', is_writable(APP_ROOT . '/config'), 'config.php is written here');
    $logsOk = is_dir(APP_ROOT . '/logs') && is_writable(APP_ROOT . '/logs');
    $add('logs/ writable', $logsOk, $logsOk ? 'error log goes to logs/php-error.log' : 'errors will use the server default log', true);
    $vendorOk = is_file(APP_ROOT . '/vendor/autoload.php');
    $add('Composer packages installed', $vendorOk, $vendorOk ? 'vendor/autoload.php found' : 'run "composer install --no-dev" for QR image generation', true);
    $https = isHttps();
    $add('HTTPS', $https, $https ? 'secure connection' : 'camera scanning in the browser requires HTTPS', true);

    return $req;
}

/** True when every non-warning requirement is met. */
function setupRequirementsMet(array $requirements): bool
{
    foreach ($requirements as $r) {
        if (!$r['ok'] && !$r['warn']) {
            return false;
        }
    }
    return true;
}

/**
 * Validate and normalise the submitted setup values.
 *
 * @return array{values: array<string, mixed>, errors: list<string>}
 */
function setupValidate(array $input): array
{
    $d      = setupDefaults();
    $errors = [];
    $v      = [];

    $v['db_host'] = trim((string) ($input['db_host'] ?? $d['db_host']));
    if ($v['db_host'] === '' || preg_match('/[\s\'"\\\\]/', $v['db_host'])) {
        $errors[] = 'Database host is invalid.';
    }
    $v['db_port'] = cleanInt($input['db_port'] ?? null, 1, 65535, -1);
    if ($v['db_port'] < 0) {
        $errors[] = 'Database port must be between 1 and 65535.';
    }
    $v['db_user'] = trim((string) ($input['db_user'] ?? ''));
    if ($v['db_user'] === '' || strlen($v['db_user']) > 80 || preg_match('/[\s\'"\\\\]/', $v['db_user'])) {
        $errors[] = 'Database user is invalid.';
    }
    $v['db_password'] = (string) ($input['db_password'] ?? '');
    if (strlen($v['db_password']) > 200) {
        $errors[] = 'Database password is too long.';
    }
    $v['db_database'] = trim((string) ($input['db_database'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $v['db_database'])) {
        $errors[] = 'Database name may only contain letters, digits, _ $ and -.';
    }
    $v['create_database'] = !empty($input['create_database']);

    $v['gw_ip'] = trim((string) ($input['gw_ip'] ?? $d['gw_ip']));
    if (filter_var($v['gw_ip'], FILTER_VALIDATE_IP) === false) {
        $errors[] = 'Gateway broadcast address must be an IP address, e.g. 192.168.1.255.';
    }
    $v['gw_port'] = cleanInt($input['gw_port'] ?? null, 1, 65535, -1);
    if ($v['gw_port'] < 0) {
        $errors[] = 'Gateway port must be between 1 and 65535.';
    }
    $v['mode'] = ($input['mode'] ?? 'demo') === 'run' ? 'run' : 'demo';

    return ['values' => $v, 'errors' => $errors];
}

/**
 * Try the database connection; optionally create the database.
 *
 * @return array{ok: bool, message: string, ddl: bool}
 */
function setupTestDatabase(array $v): array
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $link = new mysqli((string) $v['db_host'], (string) $v['db_user'], (string) $v['db_password'], '', (int) $v['db_port']);
        $link->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        return ['ok' => false, 'ddl' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
    }

    $db      = (string) $v['db_database'];
    $created = false;
    try {
        $stmt = $link->prepare('SELECT 1 FROM information_schema.schemata WHERE schema_name = ?');
        $stmt->execute([$db]);
        $exists = $stmt->get_result()->num_rows > 0;

        if (!$exists) {
            if (empty($v['create_database'])) {
                return ['ok' => false, 'ddl' => false, 'message' => 'Database "' . $db . '" does not exist. Tick "create database" or create it first.'];
            }
            $link->query('CREATE DATABASE `' . str_replace('`', '``', $db) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $created = true;
        }
        $link->select_db($db);
    } catch (mysqli_sql_exception $e) {
        return ['ok' => false, 'ddl' => false, 'message' => 'Database "' . $db . '": ' . $e->getMessage()];
    }

    // Can the user create tables? Decides whether the schema installs itself.
    $ddl = true;
    try {
        $link->query('CREATE TABLE IF NOT EXISTS `__casambiqr_setup_probe` (id INT) ENGINE=InnoDB');
        $link->query('DROP TABLE `__casambiqr_setup_probe`');
    } catch (mysqli_sql_exception $e) {
        $ddl = false;
    }

    $message = 'Connected to ' . $link->server_info . ', database "' . $db . '"' . ($created ? ' created' : ' found') . '. ';
    $message .= $ddl
        ? 'The user may create tables, the schema will be installed automatically.'
        : 'Warning: the user may not create tables; import database/schema.sql manually.';
    $link->close();

    return ['ok' => true, 'ddl' => $ddl, 'message' => $message];
}

/** Render the content of config/config.php from validated values. */
function setupRenderConfig(array $v): string
{
    $c = [
        'host'     => var_export((string) $v['db_host'], true),
        'port'     => (int) $v['db_port'],
        'user'     => var_export((string) $v['db_user'], true),
        'password' => var_export((string) $v['db_password'], true),
        'database' => var_export((string) $v['db_database'], true),
        'gw_ip'    => var_export((string) $v['gw_ip'], true),
        'gw_port'  => (int) $v['gw_port'],
        'mode'     => var_export((string) $v['mode'], true),
        'date'     => date('Y-m-d H:i'),
    ];

    return <<<PHP
<?php
/**
 * Casambi QR-Code - local configuration.
 * Written by the setup wizard on {$c['date']}. Not committed (see .gitignore).
 * All options with explanations: config/config.example.php
 */

declare(strict_types=1);

return [
    // MySQL / MariaDB connection
    'db' => [
        'host'     => {$c['host']},
        'port'     => {$c['port']},
        'user'     => {$c['user']},
        'password' => {$c['password']},
        'database' => {$c['database']},
    ],

    // Lithernet Casambi Gateway (UDP broadcast target)
    'lithernet' => [
        'broadcast_ip' => {$c['gw_ip']},
        'port'         => {$c['gw_port']},
    ],

    // 'demo' = store slider values only, send no UDP packets; 'run' = send commands to the gateway
    'operation_mode' => {$c['mode']},

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

PHP;
}

/**
 * Write config/config.php atomically. Refuses to overwrite an existing file.
 *
 * @throws RuntimeException
 */
function setupWriteConfig(string $content): void
{
    $target = APP_ROOT . '/config/config.php';
    if (is_file($target)) {
        throw new RuntimeException('config/config.php already exists.');
    }
    $tmp = APP_ROOT . '/config/config.php.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $content, LOCK_EX) === false) {
        throw new RuntimeException('config/ is not writable.');
    }
    @chmod($tmp, 0640);
    if (!rename($tmp, $target)) {
        @unlink($tmp);
        throw new RuntimeException('Could not move the configuration into place.');
    }
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($target, true);
    }
}

/** Optional protection: content of config/setup.key, or '' when the file does not exist. */
function setupRequiredKey(): string
{
    $file = APP_ROOT . '/config/setup.key';
    return is_file($file) ? trim((string) file_get_contents($file)) : '';
}
