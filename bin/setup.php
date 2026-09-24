#!/usr/bin/env php
<?php
/**
 * Interactive first-run setup from the command line (alternative to public/setup.php):
 * asks for database and gateway settings, tests the connection, writes config/config.php,
 * installs the schema and creates the first admin account.
 *
 *   php bin/setup.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("This script can only be run from the command line.\n");
}

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/src/helpers.php';
require APP_ROOT . '/src/setup.php';

function ask(string $label, string $default = '', bool $hidden = false): string
{
    $suffix = $default !== '' ? " [{$default}]" : '';
    fwrite(STDOUT, $label . $suffix . ': ');
    $line = fgets(STDIN);
    if ($line === false) {
        fwrite(STDERR, "\nInput closed, aborting.\n");
        exit(1);
    }
    $line = rtrim($line, "\r\n");
    return $line === '' ? $default : $line;
}

function fail(string $message): never
{
    fwrite(STDERR, 'Error: ' . $message . "\n");
    exit(1);
}

if (is_file(APP_ROOT . '/config/config.php')) {
    fail('config/config.php already exists. Delete it to run the setup again.');
}

echo "Casambi QR-Code setup\n=====================\n\n";

echo "Requirements:\n";
$requirements = setupRequirements();
foreach ($requirements as $r) {
    printf("  [%s] %-30s %s\n", $r['ok'] ? 'ok' : ($r['warn'] ? '! ' : 'XX'), $r['label'], $r['detail']);
}
if (!setupRequirementsMet($requirements)) {
    fail('Not all requirements are met.');
}
echo "\n";

$d     = setupDefaults();
$input = [
    'db_host'     => ask('Database host', (string) $d['db_host']),
    'db_port'     => ask('Database port', (string) $d['db_port']),
    'db_user'     => ask('Database user', (string) $d['db_user']),
    'db_password' => ask('Database password'),
    'db_database' => ask('Database name', (string) $d['db_database']),
];
$input['create_database'] = strtolower(ask('Create the database if it does not exist? (y/n)', 'y')) === 'y' ? '1' : '';
$input['gw_ip']   = ask('Gateway broadcast address', (string) $d['gw_ip']);
$input['gw_port'] = ask('Gateway UDP port', (string) $d['gw_port']);
$input['mode']    = ask('Operation mode (demo = send nothing, run = send to gateway)', (string) $d['mode']);

$validated = setupValidate($input);
if ($validated['errors'] !== []) {
    fail(implode(' ', $validated['errors']));
}
$values = $validated['values'];

echo "\nTesting database connection... ";
$test = setupTestDatabase($values);
echo $test['message'], "\n";
if (!$test['ok']) {
    exit(1);
}

setupWriteConfig(setupRenderConfig($values));
echo "Configuration written to config/config.php.\n\n";

// From here on the normal bootstrap applies: it installs or upgrades the schema.
require APP_ROOT . '/src/bootstrap.php';
echo "Database schema is at version " . SCHEMA_VERSION . ".\n\n";

if (userCount() === 0) {
    echo "Create the first admin account:\n";
    $username = cleanUsername(ask('Admin username', 'admin'));
    if ($username === '') {
        fail('Invalid username (letters, digits, dot, dash, underscore).');
    }
    $password = ask('Admin password (min. 8 characters)');
    $repeat   = ask('Repeat password');
    if (strlen($password) < 8 || $password !== $repeat) {
        fail('Passwords do not match or are shorter than 8 characters. Run "php bin/create-admin.php <username>" later.');
    }
    createUser($username, $password);
    echo "Admin account '{$username}' created.\n";
}

echo "\nSetup complete. Open admin.php in the browser to create QR codes.\n";
