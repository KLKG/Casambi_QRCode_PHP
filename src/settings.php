<?php
/**
 * Runtime settings edited in the admin area.
 *
 * Values are stored in the `settings` table and override the corresponding entries of
 * config/config.php, which stays the fallback (and the only place for the database access).
 */

declare(strict_types=1);

/**
 * Editable settings: type, limits, where the value lives in $config.
 *
 * @return array<string, array<string, mixed>>
 */
function settingsDefinitions(): array
{
    return [
        'gateway_ip' => [
            'label' => t('Gateway broadcast address'),
            'help'  => t('Broadcast address of the network segment the gateway is in, e.g. 192.168.1.255.'),
            'type'  => 'ip',
            'path'  => ['lithernet', 'broadcast_ip'],
            'group' => t('Gateway'),
        ],
        'gateway_port' => [
            'label' => t('Gateway UDP port'),
            'help'  => t('Default 10009.'),
            'type'  => 'int', 'min' => 1, 'max' => 65535,
            'path'  => ['lithernet', 'port'],
            'group' => t('Gateway'),
        ],
        'operation_mode' => [
            'label'   => t('Operation mode'),
            'help'    => t('demo stores slider values only; run sends UDP commands to the gateway.'),
            'type'    => 'select',
            'options' => ['demo' => t('demo (send nothing)'), 'run' => t('run (send to gateway)')],
            'path'    => ['operation_mode'],
            'group'   => t('Gateway'),
        ],
        'default_language' => [
            'label' => t('Default language'),
            'help'  => t('Used when the browser language matches none of the enabled languages.'),
            'type'  => 'language',
            'path'  => ['default_language'],
            'group' => t('Interface'),
        ],
        'session_lifetime' => [
            'label' => t('Admin session timeout [s]'),
            'help'  => t('Idle time after which an admin has to log in again. 60-86400 seconds.'),
            'type'  => 'int', 'min' => 60, 'max' => 86400,
            'path'  => ['session_lifetime'],
            'group' => t('Security'),
        ],
        'login_max_attempts' => [
            'label' => t('Failed logins before lockout'),
            'help'  => t('1-50 attempts.'),
            'type'  => 'int', 'min' => 1, 'max' => 50,
            'path'  => ['login_max_attempts'],
            'group' => t('Security'),
        ],
        'login_lockout_seconds' => [
            'label' => t('Lockout duration [s]'),
            'help'  => t('30-86400 seconds.'),
            'type'  => 'int', 'min' => 30, 'max' => 86400,
            'path'  => ['login_lockout_seconds'],
            'group' => t('Security'),
        ],
        'image_max_upload_mb' => [
            'label' => t('Photo upload limit [MB]'),
            'help'  => t('1-64 MB; upload_max_filesize and post_max_size in php.ini must allow it.'),
            'type'  => 'int', 'min' => 1, 'max' => 64,
            'path'  => ['image_max_upload_bytes'],
            'scale' => 1048576,
            'group' => t('Photos'),
        ],
        'image_max_px' => [
            'label' => t('Photo size after downscaling [px]'),
            'help'  => t('Longest edge, 200-4000 pixels.'),
            'type'  => 'int', 'min' => 200, 'max' => 4000,
            'path'  => ['image_max_px'],
            'group' => t('Photos'),
        ],
    ];
}

/** @return array<string, string> name => stored value ([] when the table is not available) */
function settingsLoad(): array
{
    try {
        $rows = dbFetchAll('SELECT name, value FROM settings');
    } catch (Throwable $e) {
        return [];
    }
    $out = [];
    foreach ($rows as $row) {
        $out[(string) $row['name']] = (string) $row['value'];
    }
    return $out;
}

/** Read a value from $config by path. */
function configGet(array $config, array $path, mixed $default = null): mixed
{
    $cursor = $config;
    foreach ($path as $key) {
        if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
            return $default;
        }
        $cursor = $cursor[$key];
    }
    return $cursor;
}

function configSet(array &$config, array $path, mixed $value): void
{
    $cursor = &$config;
    foreach ($path as $key) {
        if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
            $cursor[$key] = [];
        }
        $cursor = &$cursor[$key];
    }
    $cursor = $value;
}

/** Merge the stored settings into $config (database wins over config.php). */
function settingsApply(array &$config): void
{
    $stored = settingsLoad();
    $config['default_language'] = $config['default_language'] ?? 'en';
    foreach (settingsDefinitionsRaw() as $name => $def) {
        if (!array_key_exists($name, $stored)) {
            continue;
        }
        $value = $stored[$name];
        if ($def['type'] === 'int') {
            $value = (int) $value * (int) ($def['scale'] ?? 1);
        }
        configSet($config, $def['path'], $value);
    }
}

/**
 * Definitions without translated labels (safe to call before the language is known).
 *
 * @return array<string, array<string, mixed>>
 */
function settingsDefinitionsRaw(): array
{
    return [
        'gateway_ip'            => ['type' => 'ip', 'path' => ['lithernet', 'broadcast_ip']],
        'gateway_port'          => ['type' => 'int', 'path' => ['lithernet', 'port']],
        'operation_mode'        => ['type' => 'select', 'path' => ['operation_mode']],
        'default_language'      => ['type' => 'language', 'path' => ['default_language']],
        'session_lifetime'      => ['type' => 'int', 'path' => ['session_lifetime']],
        'login_max_attempts'    => ['type' => 'int', 'path' => ['login_max_attempts']],
        'login_lockout_seconds' => ['type' => 'int', 'path' => ['login_lockout_seconds']],
        'image_max_upload_mb'   => ['type' => 'int', 'path' => ['image_max_upload_bytes'], 'scale' => 1048576],
        'image_max_px'          => ['type' => 'int', 'path' => ['image_max_px']],
    ];
}

/**
 * Effective values for the settings form.
 *
 * @return array<string, array{value: string, source: string}>
 */
function settingsEffective(): array
{
    $config = $GLOBALS['config'];
    $stored = settingsLoad();
    $out    = [];
    foreach (settingsDefinitions() as $name => $def) {
        $raw = configGet($config, $def['path'], '');
        if ($def['type'] === 'int' && isset($def['scale'])) {
            $raw = (int) round(((int) $raw) / (int) $def['scale']);
        }
        $out[$name] = [
            'value'  => (string) $raw,
            'source' => array_key_exists($name, $stored) ? 'database' : 'config',
        ];
    }
    return $out;
}

/**
 * Validate and store the submitted settings.
 *
 * @param array<string, mixed> $input
 * @return list<string> errors (empty on success)
 */
function settingsSave(array $input): array
{
    $errors = [];
    $values = [];
    foreach (settingsDefinitions() as $name => $def) {
        $raw = $input[$name] ?? null;
        switch ($def['type']) {
            case 'ip':
                $raw = is_string($raw) ? trim($raw) : '';
                if (filter_var($raw, FILTER_VALIDATE_IP) === false) {
                    $errors[] = t('{field}: must be an IP address.', ['field' => $def['label']]);
                    continue 2;
                }
                break;
            case 'int':
                $int = cleanInt($raw, (int) $def['min'], (int) $def['max'], PHP_INT_MIN);
                if ($int === PHP_INT_MIN) {
                    $errors[] = t('{field}: must be between {min} and {max}.', ['field' => $def['label'], 'min' => $def['min'], 'max' => $def['max']]);
                    continue 2;
                }
                $raw = (string) $int;
                break;
            case 'select':
                if (!is_string($raw) || !array_key_exists($raw, $def['options'])) {
                    $errors[] = t('{field}: invalid choice.', ['field' => $def['label']]);
                    continue 2;
                }
                break;
            case 'language':
                if (!is_string($raw) || !in_array($raw, availableLanguageCodes(), true)) {
                    $errors[] = t('{field}: language is not enabled.', ['field' => $def['label']]);
                    continue 2;
                }
                break;
        }
        $values[$name] = $raw;
    }
    if ($errors !== []) {
        return $errors;
    }
    dbTransaction(static function () use ($values): void {
        foreach ($values as $name => $value) {
            dbExecute(
                'INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
                [$name, $value]
            );
        }
    });
    return [];
}

/** Remove all stored settings so config/config.php applies again. */
function settingsReset(): void
{
    dbExecute('DELETE FROM settings');
}
