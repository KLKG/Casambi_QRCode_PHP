<?php
/**
 * Database access: one shared mysqli connection and small prepared-statement helpers.
 * All queries in the application go through these functions; no SQL string concatenation.
 */

declare(strict_types=1);

function db(): mysqli
{
    static $link = null;
    if ($link instanceof mysqli) {
        return $link;
    }

    $c    = $GLOBALS['config']['db'];
    $link = new mysqli(
        (string) $c['host'],
        (string) $c['user'],
        (string) $c['password'],
        (string) $c['database'],
        (int) ($c['port'] ?? 3306)
    );
    $link->set_charset('utf8mb4');

    return $link;
}

/** Prepare and execute a statement with positional parameters. */
function dbQuery(string $sql, array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** @return array<string, mixed>|null */
function dbFetchOne(string $sql, array $params = []): ?array
{
    $result = dbQuery($sql, $params)->get_result();
    $row    = $result->fetch_assoc();
    $result->free();
    return $row ?: null;
}

/** @return list<array<string, mixed>> */
function dbFetchAll(string $sql, array $params = []): array
{
    $result = dbQuery($sql, $params)->get_result();
    $rows   = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;
}

/** Execute INSERT / UPDATE / DELETE and return the number of affected rows. */
function dbExecute(string $sql, array $params = []): int
{
    return (int) dbQuery($sql, $params)->affected_rows;
}

/** Run $fn inside a transaction; rolls back and rethrows on any exception. */
function dbTransaction(callable $fn): mixed
{
    $link = db();
    $link->begin_transaction();
    try {
        $result = $fn($link);
        $link->commit();
        return $result;
    } catch (Throwable $e) {
        $link->rollback();
        throw $e;
    }
}
