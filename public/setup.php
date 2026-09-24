<?php
/**
 * First-run setup wizard. Only available while config/config.php does not exist:
 * checks requirements, tests the database connection (optionally creating the database),
 * writes config/config.php and hands over to admin.php, which installs the schema and
 * asks for the first admin account.
 *
 * Optional protection: if config/setup.key exists, its content must be entered first.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (is_dir(APP_ROOT . '/logs') && is_writable(APP_ROOT . '/logs')) {
    ini_set('error_log', APP_ROOT . '/logs/php-error.log');
}

require APP_ROOT . '/src/helpers.php';
require APP_ROOT . '/src/icons.php';
require APP_ROOT . '/src/i18n.php';   // t() works without a database: English only
require APP_ROOT . '/src/setup.php';

$GLOBALS['config'] = [];

if (is_file(APP_ROOT . '/config/config.php')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Setup has already been completed. To run it again, delete config/config.php.\n");
}

startSession([]);
sendSecurityHeaders();

$requiredKey = setupRequiredKey();
$locked      = $requiredKey !== '' && empty($_SESSION['setup_unlocked']);
$errors      = [];
$testResult  = null;
$values      = is_array($_SESSION['setup_values'] ?? null) ? $_SESSION['setup_values'] : setupDefaults();
$values     += setupDefaults();

if (isPost()) {
    requireCsrf();

    if ($locked) {
        if (hash_equals($requiredKey, (string) ($_POST['setup_key'] ?? ''))) {
            $_SESSION['setup_unlocked'] = true;
        } else {
            flash('Wrong setup key.', 'error');
        }
        redirect('setup.php');
    }

    $validated = setupValidate($_POST);
    $values    = $validated['values'] + setupDefaults();
    $errors    = $validated['errors'];
    $_SESSION['setup_values'] = array_diff_key($values, ['db_password' => 1]); // never keep the password in the session

    $action = (string) ($_POST['action'] ?? 'test');

    if ($errors === []) {
        $testResult = setupTestDatabase($values);
        if ($action === 'install' && $testResult['ok']) {
            if (!setupRequirementsMet(setupRequirements())) {
                $errors[] = 'Not all requirements are met, see the list above.';
            } else {
                try {
                    setupWriteConfig(setupRenderConfig($values));
                    unset($_SESSION['setup_values'], $_SESSION['setup_unlocked']);
                    flash('Configuration written to config/config.php. Now create the admin account.', 'success');
                    redirect('admin.php');
                } catch (RuntimeException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }
}

$values['db_password'] = $values['db_password'] ?? '';

render('setup/wizard', [
    'title'        => 'Setup',
    'area'         => 'setup',
    'locked'       => $locked,
    'requirements' => setupRequirements(),
    'values'       => $values,
    'errors'       => $errors,
    'testResult'   => $testResult,
]);
