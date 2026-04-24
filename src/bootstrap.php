<?php
declare(strict_types=1);

if (defined('COS_BOOTED')) return;
define('COS_BOOTED', true);

define('COS_ROOT',       dirname(__DIR__));
define('COS_DATA_DIR',   COS_ROOT . '/data');
define('COS_VIEWS_DIR',  COS_ROOT . '/src/views');
define('COS_DB_PATH',    COS_DATA_DIR . '/chief.sqlite');
define('COS_MIGRATIONS', COS_ROOT . '/migrations');

if (!is_dir(COS_DATA_DIR)) {
    mkdir(COS_DATA_DIR, 0775, true);
}

cos_load_env(COS_ROOT . '/.env');

date_default_timezone_set($_ENV['TIMEZONE'] ?? 'America/Chicago');

$appEnv = $_ENV['APP_ENV'] ?? 'development';
if ($appEnv === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

function cos_load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, "\"'");
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}

function cos_start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $isHttps = ($_SERVER['HTTPS'] ?? '') === 'on'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_name($_ENV['SESSION_NAME'] ?? 'cos_session');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
