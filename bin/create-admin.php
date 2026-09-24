#!/usr/bin/env php
<?php
/**
 * Create an admin account or reset its password from the command line.
 *
 *   php bin/create-admin.php <username>
 *
 * The password is read from standard input so that it does not end up in the shell history.
 * (The admin.php setup form does the same on the first visit when no user exists yet.)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("This script can only be run from the command line.\n");
}

require __DIR__ . '/../src/bootstrap.php';

$username = cleanUsername($argv[1] ?? '');
if ($username === '') {
    fwrite(STDERR, "Usage: php bin/create-admin.php <username>\n");
    fwrite(STDERR, "Allowed characters: letters, digits, dot, dash, underscore (max 20).\n");
    exit(1);
}

fwrite(STDOUT, "Password for '{$username}' (min. 8 characters): ");
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must have at least 8 characters.\n");
    exit(1);
}

fwrite(STDOUT, 'Repeat password: ');
$repeat = rtrim((string) fgets(STDIN), "\r\n");
if ($repeat !== $password) {
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}

createUser($username, $password);
fwrite(STDOUT, "User '{$username}' created or password updated.\n");
