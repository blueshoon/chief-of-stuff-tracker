<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/db.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the CLI.\n");
    exit(1);
}

function prompt(string $label, bool $hidden = false): string {
    fwrite(STDOUT, $label);
    if ($hidden && stripos(PHP_OS, 'WIN') !== 0) {
        system('stty -echo');
        $value = rtrim((string) fgets(STDIN), "\r\n");
        system('stty echo');
        fwrite(STDOUT, "\n");
        return $value;
    }
    return rtrim((string) fgets(STDIN), "\r\n");
}

$username = trim(prompt("Username (e.g. 'mari'): "));
if ($username === '') {
    fwrite(STDERR, "Username required.\n");
    exit(1);
}

$password = prompt("Password: ", true);
$confirm  = prompt("Confirm:  ", true);

if ($password === '' || $password !== $confirm) {
    fwrite(STDERR, "Passwords empty or do not match.\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$existing = db_one('SELECT id FROM users WHERE username = ?', [$username]);
if ($existing) {
    db_q('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $existing['id']]);
    echo "Updated password for '$username'.\n";
} else {
    db_q('INSERT INTO users (username, password_hash) VALUES (?, ?)', [$username, $hash]);
    echo "Created user '$username'.\n";
}
