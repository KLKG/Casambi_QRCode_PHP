<?php
/**
 * General helpers: output escaping, input cleaning, session, CSRF, flash messages,
 * security headers and template rendering.
 */

declare(strict_types=1);

/** HTML-escape a value for output. */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Route / site names: lowercase letters only, max 10 characters. */
function cleanSite(mixed $input): string
{
    if (!is_string($input)) {
        return '';
    }
    return substr((string) preg_replace('/[^a-z]+/', '', strtolower($input)), 0, 10);
}

/** QR code identifiers: lowercase letters and digits, max 10 characters. */
function cleanCode(mixed $input): string
{
    if (!is_string($input)) {
        return '';
    }
    return substr((string) preg_replace('/[^a-z0-9]+/', '', strtolower($input)), 0, 10);
}

/** Integer within [$min, $max]; returns $default for anything else. */
function cleanInt(mixed $input, int $min, int $max, int $default): int
{
    if (is_int($input)) {
        $value = $input;
    } elseif (is_string($input) && trim($input) !== '') {
        $value = filter_var(trim($input), FILTER_VALIDATE_INT);
    } else {
        return $default;
    }
    if ($value === false || $value < $min || $value > $max) {
        return $default;
    }
    return $value;
}

/** Display names: trimmed UTF-8 text without control characters, limited length. */
function cleanName(mixed $input, int $maxLength = 60): string
{
    if (!is_string($input)) {
        return '';
    }
    $clean = (string) preg_replace('/[\p{C}]+/u', ' ', $input);
    $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));
    return mb_substr($clean, 0, $maxLength, 'UTF-8');
}

/** Usernames: letters, digits, dot, dash and underscore, max 20 characters. */
function cleanUsername(mixed $input): string
{
    if (!is_string($input)) {
        return '';
    }
    return substr((string) preg_replace('/[^A-Za-z0-9._-]+/', '', $input), 0, 20);
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function isHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

/** Redirect (303 See Other) and stop. */
function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------

function startSession(array $config = []): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('casambiqr');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isHttps(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();

    if (isset($config['session_lifetime'])) {
        sessionTimeout((int) $config['session_lifetime']);
    }
}

/** Idle timeout for the whole session (relevant for the admin login). */
function sessionTimeout(int $lifetime): void
{
    static $checked = false; // once per request, even if called with the config and the database value
    if ($checked || session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $checked = true;
    $now     = time();
    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > max(60, $lifetime)) {
        $lang     = $_SESSION['lang'] ?? null;
        $_SESSION = [];
        session_regenerate_id(true);
        if ($lang !== null) {
            $_SESSION['lang'] = $lang; // the language choice survives the timeout
        }
    }
    $_SESSION['last_activity'] = $now;
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------

function csrfToken(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function csrfValid(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf'])
        && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/** Abort the request unless the POSTed CSRF token is valid. */
function requireCsrf(): void
{
    if (!csrfValid($_POST['csrf'] ?? null)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Invalid or expired form token. Please go back and try again.\n");
    }
}

// ---------------------------------------------------------------------------
// Flash messages (shown once on the next page)
// ---------------------------------------------------------------------------

function flash(string $message, string $type = 'info'): void
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

/**
 * All pending messages, oldest first; empty when there are none.
 *
 * @return list<array{message: string, type: string}>
 */
function takeFlash(): array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (!is_array($flash)) {
        return [];
    }
    return array_values(array_filter($flash, static fn ($f): bool => is_array($f) && isset($f['message'], $f['type'])));
}

// ---------------------------------------------------------------------------
// Security headers
// ---------------------------------------------------------------------------

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
    // 'wasm-unsafe-eval' is required for the WebAssembly QR decoder on the scan page.
    header(
        "Content-Security-Policy: default-src 'self'; script-src 'self' 'wasm-unsafe-eval'; "
        . "style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; "
        . "worker-src 'self' blob:; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'"
    );
    if (isHttps()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

// ---------------------------------------------------------------------------
// Templates
// ---------------------------------------------------------------------------

/**
 * Asset URL with a version parameter derived from the file's modification time,
 * so browsers pick up changed CSS/JS despite long cache lifetimes.
 */
function asset(string $path): string
{
    $file  = APP_ROOT . '/public/' . ltrim($path, '/');
    $mtime = is_file($file) ? (int) filemtime($file) : 0;
    return $path . ($mtime > 0 ? '?v=' . $mtime : '');
}

/**
 * Render a template from src/templates inside the shared layout.
 *
 * Available in every template: $config, $title, $area ('index'|'admin'), $scripts, $flash
 * plus everything passed in $vars.
 */
function render(string $template, array $vars = []): void
{
    $vars += [
        'title'   => 'Casambi QR-Code Scanner',
        'area'    => 'index',
        'scripts' => [],
    ];
    $vars['config'] = $GLOBALS['config'];
    $vars['flash']  = takeFlash();

    extract($vars, EXTR_SKIP);

    require APP_ROOT . '/src/templates/layout/header.php';
    require APP_ROOT . '/src/templates/' . $template . '.php';
    require APP_ROOT . '/src/templates/layout/footer.php';
}
