<?php
/**
 * Admin authentication: password hashing, login throttling, session login state.
 */

declare(strict_types=1);

/** Valid bcrypt hash used to keep the response time constant for unknown usernames. */
const AUTH_DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

function isLoggedIn(): bool
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/** Abort with 403 unless an admin is logged in. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Login required.\n");
    }
}

function userCount(): int
{
    return (int) (dbFetchOne('SELECT COUNT(*) AS ctr FROM `user`')['ctr'] ?? 0);
}

/** Create a user or replace the password of an existing one. */
function createUser(string $username, string $password): void
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    dbExecute(
        'INSERT INTO `user` (username, password) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE password = VALUES(password), failed_logins = 0, locked_until = NULL',
        [$username, $hash]
    );
}

/**
 * Verify credentials and log the user in.
 *
 * Handles bcrypt hashes as well as the plaintext passwords of the 2022 schema
 * (which were stored lowercased, a-z0-9 only, max 10 characters); those are
 * re-hashed on the first successful login.
 */
function attemptLogin(string $username, string $password): bool
{
    $username = cleanUsername($username);
    if ($username === '' || $password === '' || strlen($password) > 1024) {
        return false;
    }

    $user = dbFetchOne(
        'SELECT username, password, failed_logins,
                (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked
         FROM `user` WHERE username = ?',
        [$username]
    );

    if ($user === null) {
        password_verify($password, AUTH_DUMMY_HASH); // same timing as for existing users
        return false;
    }
    if ((int) $user['is_locked'] === 1) {
        return false;
    }

    $stored = (string) $user['password'];
    $ok     = false;
    $rehash = false;

    if ((password_get_info($stored)['algo'] ?? null) !== null) {
        $ok     = password_verify($password, $stored);
        $rehash = $ok && password_needs_rehash($stored, PASSWORD_DEFAULT);
    } else {
        // Legacy plaintext password from schema v1
        $legacy = substr((string) preg_replace('/[^a-z0-9]+/', '', strtolower($password)), 0, 10);
        $ok     = hash_equals($stored, $password) || hash_equals($stored, $legacy);
        $rehash = $ok;
    }

    if (!$ok) {
        registerFailedLogin((string) $user['username'], (int) $user['failed_logins']);
        return false;
    }

    if ($rehash) {
        dbExecute('UPDATE `user` SET password = ? WHERE username = ?', [
            password_hash($password, PASSWORD_DEFAULT),
            $user['username'],
        ]);
    }
    dbExecute(
        'UPDATE `user` SET failed_logins = 0, locked_until = NULL, last_login = NOW() WHERE username = ?',
        [$user['username']]
    );

    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = (string) $user['username'];
    unset($_SESSION['csrf']); // fresh token for the authenticated session

    return true;
}

function registerFailedLogin(string $username, int $failedSoFar): void
{
    $config      = $GLOBALS['config'];
    $maxAttempts = max(1, (int) ($config['login_max_attempts'] ?? 5));
    $lockSeconds = max(1, (int) ($config['login_lockout_seconds'] ?? 900));
    $failed      = $failedSoFar + 1;

    if ($failed >= $maxAttempts) {
        dbExecute(
            'UPDATE `user` SET failed_logins = 0, locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE username = ?',
            [$lockSeconds, $username]
        );
    } else {
        dbExecute('UPDATE `user` SET failed_logins = ? WHERE username = ?', [$failed, $username]);
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
    }
    session_destroy();
}
