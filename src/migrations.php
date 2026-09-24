<?php
/**
 * Automatic database schema installation and upgrades.
 *
 * On every request ensureSchema() compares the schema version stored in
 * `schema_migrations` with SCHEMA_VERSION. An empty database gets
 * database/schema.sql, an older database gets the migrate-*.sql files in order.
 * Databases created before the version table existed are detected by their
 * structure (v1: plain-text passwords, v2: fixed code types, v3: elements).
 */

declare(strict_types=1);

const SCHEMA_VERSION = 5;

/** SQL files that bring the schema from version N to N+1. Version 0 = empty database. */
function schemaMigrationFiles(): array
{
    return [
        1 => 'migrate-v1-to-v2.sql',   // applied when the detected version is 1 (result: 2)
        2 => 'migrate-v2-to-v3.sql',   // applied when the detected version is 2 (result: 3)
        3 => 'migrate-v3-to-v4.sql',   // applied when the detected version is 3 (result: 4)
        4 => 'migrate-v4-to-v5.sql',   // applied when the detected version is 4 (result: 5)
    ];
}

function schemaTableExists(string $table): bool
{
    return dbFetchOne(
        'SELECT 1 AS found FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
        [$table]
    ) !== null;
}

function schemaColumnExists(string $table, string $column): bool
{
    return dbFetchOne(
        'SELECT 1 AS found FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        [$table, $column]
    ) !== null;
}

/** Current schema version: from schema_migrations, or detected from the table structure. */
function schemaDetectVersion(): int
{
    try {
        $row = dbFetchOne('SELECT MAX(version) AS v FROM schema_migrations');
        if ($row !== null && $row['v'] !== null) {
            return (int) $row['v'];
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() !== 1146) { // 1146 = table does not exist
            throw $e;
        }
    }

    // Legacy databases without a version table
    if (schemaTableExists('qrcode_elements')) {
        // v3 created before the version table existed: record it now so the
        // structure check is not repeated on every request (v4 always has the table).
        try {
            schemaRecordVersion(3);
        } catch (mysqli_sql_exception $e) {
            error_log('Database: cannot create schema_migrations table: ' . $e->getMessage());
        }
        return 3;
    }
    if (!schemaTableExists('qrcodes')) {
        return 0;
    }
    if (schemaColumnExists('user', 'failed_logins')) {
        return 2;
    }
    return 1;
}

function schemaRecordVersion(int $version): void
{
    dbExecute(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version    SMALLINT UNSIGNED NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    dbExecute('INSERT IGNORE INTO schema_migrations (version) VALUES (?)', [$version]);
}

/**
 * Execute an SQL file statement by statement.
 * Comment lines (--) are removed; statements end with ";" at a line end.
 *
 * @throws RuntimeException naming the failing statement
 */
function schemaRunSqlFile(string $file): void
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Cannot read ' . basename($file));
    }
    $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];

    $link = db();
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        try {
            $link->query($statement);
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException(
                basename($file) . ': ' . $e->getMessage() . ' [statement: ' . mb_substr(preg_replace('/\s+/', ' ', $statement) ?? '', 0, 120) . '…]',
                0,
                $e
            );
        }
    }
}

/**
 * Install or upgrade the schema if necessary. Returns the list of steps performed.
 *
 * @return list<string>
 * @throws RuntimeException when a step fails (the database user may lack CREATE/ALTER/DROP rights)
 */
function ensureSchema(): array
{
    $version = schemaDetectVersion();
    if ($version >= SCHEMA_VERSION) {
        return [];
    }

    $link = db();
    $lock = $link->query("SELECT GET_LOCK('casambiqr_schema', 30) AS got")->fetch_assoc();
    if ((int) ($lock['got'] ?? 0) !== 1) {
        throw new RuntimeException('Another request is updating the database schema; please retry in a moment.');
    }

    $steps = [];
    try {
        $version = schemaDetectVersion(); // re-check under the lock
        $dir     = APP_ROOT . '/database/';

        if ($version === 0) {
            schemaRunSqlFile($dir . 'schema.sql');
            schemaRecordVersion(SCHEMA_VERSION);
            $steps[] = 'installed schema v' . SCHEMA_VERSION;
            $version = SCHEMA_VERSION;
        }

        while ($version < SCHEMA_VERSION) {
            $file = schemaMigrationFiles()[$version] ?? null;
            if ($file === null) {
                throw new RuntimeException('No migration from schema v' . $version . ' available.');
            }
            schemaRunSqlFile($dir . $file);
            $version++;
            schemaRecordVersion($version);
            $steps[] = 'migrated to schema v' . $version . ' (' . $file . ')';
        }
    } finally {
        $link->query("SELECT RELEASE_LOCK('casambiqr_schema')");
    }

    foreach ($steps as $step) {
        error_log('Database: ' . $step);
    }
    return $steps;
}
