<?php
/**
 * Application bootstrap: error handling, configuration, helpers, session and security headers.
 * Every entry point in public/ (and bin/) requires this file first.
 */

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    // First start: hand over to the setup wizard (web) or point to the CLI setup.
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Configuration missing: run 'php bin/setup.php' or copy config/config.example.php to config/config.php.\n");
        exit(1);
    }
    header('Location: setup.php', true, 302);
    exit;
}

/** @var array<string, mixed> $config */
$config = require $configFile;

if (!empty($config['error_log'])) {
    ini_set('error_log', (string) $config['error_log']);
}

// require_once: bin/setup.php loads helpers.php before the config exists and then includes this file.
require_once APP_ROOT . '/src/helpers.php';
require_once APP_ROOT . '/src/db.php';
require_once APP_ROOT . '/src/auth.php';
require_once APP_ROOT . '/src/casambi.php';
require_once APP_ROOT . '/src/qrcodes.php';
require_once APP_ROOT . '/src/migrations.php';
require_once APP_ROOT . '/src/settings.php';
require_once APP_ROOT . '/src/i18n.php';
require_once APP_ROOT . '/src/icons.php';

// Composer autoloader (optional: only needed for QR image generation)
$autoload = APP_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo "Internal error. Details have been written to the error log.\n";
    exit(1);
});

if (PHP_SAPI !== 'cli') {
    startSession(); // the idle timeout is applied below, once the settings are known
    sendSecurityHeaders();
}

// Install or upgrade the database schema automatically.
try {
    $schemaSteps = ensureSchema();
    if ($schemaSteps !== [] && PHP_SAPI !== 'cli') {
        flash('Database updated: ' . implode(', ', $schemaSteps) . '.', 'success');
    }

    // Settings from the admin area override config.php
    settingsApply($config);
    if (PHP_SAPI !== 'cli') {
        sessionTimeout((int) ($config['session_lifetime'] ?? 1800));
        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            setLanguage($_GET['lang']);
        }
    }
} catch (mysqli_sql_exception $e) {
    // Connection problems (wrong host, credentials or database name)
    error_log('Database connection failed: ' . $e);
    $detail = (PHP_SAPI === 'cli' || isLoggedIn()) ? "\n\n" . $e->getMessage() : '';
    if (PHP_SAPI !== 'cli') {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit("Database connection failed. Check the 'db' settings in config/config.php\n"
        . "(or delete that file to run the setup wizard again). Details are in the error log." . $detail . "\n");
} catch (Throwable $e) {
    error_log('Database schema update failed: ' . $e);
    $detail = (PHP_SAPI === 'cli' || isLoggedIn()) ? "\n\n" . $e->getMessage() : '';
    if (PHP_SAPI !== 'cli') {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit("The database schema is outdated and could not be updated automatically.\n"
        . "Either grant the database user CREATE, ALTER, DROP, INDEX and REFERENCES rights and reload,\n"
        . "or run the files in database/ manually (see README, section Upgrading)." . $detail . "\n");
}
