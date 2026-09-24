<?php
/**
 * Interface languages and translations.
 *
 * English is the source language: every fixed string in the code is written in English
 * and wrapped in t('...'). Additional languages are managed in the admin area; their
 * translations live in the `translations` table, keyed by the md5 of the English text.
 * The list of translatable strings is derived from the source files (translationCatalogue()).
 */

declare(strict_types=1);

const I18N_SOURCE_LANGUAGE = 'en';

function i18nValidCode(string $code): bool
{
    return (bool) preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $code);
}

/**
 * Additional languages from the database (empty when the table is not available yet).
 *
 * @return list<array{code: string, name: string, enabled: int}>
 */
function languagesList(bool $enabledOnly = false): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        if (function_exists('dbFetchAll')) {
            try {
                $cache = dbFetchAll('SELECT code, name, enabled FROM languages ORDER BY name');
            } catch (Throwable $e) {
                $cache = [];
            }
        }
    }
    if (!$enabledOnly) {
        return $cache;
    }
    return array_values(array_filter($cache, static fn (array $l): bool => (int) $l['enabled'] === 1));
}

/** @return array{code: string, name: string, enabled: int}|null */
function languageGet(string $code): ?array
{
    foreach (languagesList() as $l) {
        if ($l['code'] === $code) {
            return $l;
        }
    }
    return null;
}

/** Codes that may be selected: English plus every enabled language. */
function availableLanguageCodes(): array
{
    return array_merge([I18N_SOURCE_LANGUAGE], array_column(languagesList(true), 'code'));
}

function languageName(string $code): string
{
    if ($code === I18N_SOURCE_LANGUAGE) {
        return 'English';
    }
    return languageGet($code)['name'] ?? $code;
}

/**
 * Language for this request: explicit choice (session) > browser preference >
 * default_language setting > English. Only English and enabled languages are used.
 */
function currentLanguage(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $available  = availableLanguageCodes();
    $candidates = [];
    if (!empty($_SESSION['lang']) && is_string($_SESSION['lang'])) {
        $candidates[] = $_SESSION['lang'];
    }
    foreach (explode(',', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) as $part) {
        $tag = strtolower(trim(explode(';', $part)[0]));
        if ($tag !== '') {
            $candidates[] = $tag;
            $candidates[] = substr($tag, 0, 2);
        }
    }
    $candidates[] = (string) ($GLOBALS['config']['default_language'] ?? I18N_SOURCE_LANGUAGE);

    foreach ($candidates as $c) {
        if (in_array($c, $available, true)) {
            return $lang = $c;
        }
    }
    return $lang = I18N_SOURCE_LANGUAGE;
}

/** Remember an explicit language choice for this session (ignored when not available). */
function setLanguage(string $code): void
{
    $code = strtolower(trim($code));
    if (in_array($code, availableLanguageCodes(), true)) {
        $_SESSION['lang'] = $code;
    }
}

/**
 * Translations of one language: source_hash => translation (non-empty only).
 *
 * @return array<string, string>
 */
function translationsFor(string $lang): array
{
    static $cache = [];
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }
    $map = [];
    if ($lang !== I18N_SOURCE_LANGUAGE && function_exists('dbFetchAll')) {
        try {
            foreach (dbFetchAll('SELECT source_hash, translation FROM translations WHERE lang = ? AND translation <> ""', [$lang]) as $row) {
                $map[(string) $row['source_hash']] = (string) $row['translation'];
            }
        } catch (Throwable $e) {
            $map = [];
        }
    }
    return $cache[$lang] = $map;
}

/**
 * Translate a fixed interface string. Placeholders are written as {name}.
 *
 * @param array<string, string|int|float> $params
 */
function t(string $text, array $params = []): string
{
    $lang = currentLanguage();
    if ($lang !== I18N_SOURCE_LANGUAGE) {
        $text = translationsFor($lang)[md5($text)] ?? $text;
    }
    if ($params !== []) {
        $replace = [];
        foreach ($params as $key => $value) {
            $replace['{' . $key . '}'] = (string) $value;
        }
        $text = strtr($text, $replace);
    }
    return $text;
}

/**
 * All translatable strings, collected from the t('...') calls in the source files.
 *
 * @return array<string, list<string>>  source text => files it appears in
 */
function translationCatalogue(): array
{
    static $catalogue = null;
    if ($catalogue !== null) {
        return $catalogue;
    }
    $files = array_merge(
        glob(APP_ROOT . '/src/*.php') ?: [],
        glob(APP_ROOT . '/src/templates/*/*.php') ?: [],
        glob(APP_ROOT . '/public/*.php') ?: [],
        glob(APP_ROOT . '/bin/*.php') ?: []
    );
    $pattern   = '/(?<![A-Za-z0-9_$>\\\\])t\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\$]|\\\\.)*")/';
    $catalogue = [];
    foreach ($files as $file) {
        $code = file_get_contents($file);
        if ($code === false || !preg_match_all($pattern, $code, $matches)) {
            continue;
        }
        foreach ($matches[1] as $literal) {
            $body   = substr($literal, 1, -1);
            $source = $literal[0] === "'"
                ? str_replace(['\\\\', "\\'"], ['\\', "'"], $body)
                : stripcslashes($body);
            if ($source === '') {
                continue;
            }
            $catalogue[$source][] = basename($file);
        }
    }
    foreach ($catalogue as &$list) {
        $list = array_values(array_unique($list));
    }
    unset($list);
    ksort($catalogue, SORT_NATURAL | SORT_FLAG_CASE);
    return $catalogue;
}

/** Number of catalogue strings that have a non-empty translation in $lang. */
function translationProgress(string $lang): array
{
    $catalogue  = translationCatalogue();
    $translated = translationsFor($lang);
    $done       = 0;
    foreach (array_keys($catalogue) as $source) {
        if (isset($translated[md5($source)])) {
            $done++;
        }
    }
    return ['done' => $done, 'total' => count($catalogue)];
}

/**
 * Store the translations submitted for one language: hash => text.
 * Empty texts remove the row (the English source is used again).
 *
 * @param array<string, mixed> $input
 */
function translationsSave(string $lang, array $input): int
{
    $catalogue = translationCatalogue();
    $saved     = 0;
    dbTransaction(static function () use ($lang, $input, $catalogue, &$saved): void {
        foreach (array_keys($catalogue) as $source) {
            $hash = md5($source);
            if (!array_key_exists($hash, $input) || !is_string($input[$hash])) {
                continue;
            }
            $text = trim(str_replace("\r\n", "\n", $input[$hash]));
            if ($text === '') {
                dbExecute('DELETE FROM translations WHERE lang = ? AND source_hash = ?', [$lang, $hash]);
            } else {
                dbExecute(
                    'INSERT INTO translations (lang, source_hash, source, translation) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE source = VALUES(source), translation = VALUES(translation)',
                    [$lang, $hash, $source, mb_substr($text, 0, 2000)]
                );
                $saved++;
            }
        }
    });
    return $saved;
}

function languageAdd(string $code, string $name): void
{
    dbExecute('INSERT INTO languages (code, name, enabled) VALUES (?, ?, 1)', [$code, $name]);
}

function languageSetEnabled(string $code, bool $enabled): void
{
    dbExecute('UPDATE languages SET enabled = ? WHERE code = ?', [$enabled ? 1 : 0, $code]);
}

function languageRename(string $code, string $name): void
{
    dbExecute('UPDATE languages SET name = ? WHERE code = ?', [$name, $code]);
}

function languageDelete(string $code): void
{
    dbExecute('DELETE FROM languages WHERE code = ?', [$code]); // translations follow via FK
}

/** URL of the current page with the lang parameter replaced (for the language switcher). */
function langSwitchUrl(string $code): string
{
    $uri   = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');
    $path  = (string) (parse_url($uri, PHP_URL_PATH) ?: 'index.php');
    $query = [];
    parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
    $query['lang'] = $code;
    return basename($path) . '?' . http_build_query($query);
}
