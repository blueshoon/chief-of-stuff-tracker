<?php
declare(strict_types=1);

const COS_LOGIN_LOCKOUT_THRESHOLD = 5;
const COS_LOGIN_LOCKOUT_WINDOW    = 300;

function current_user(): ?array {
    cos_start_session();
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) return null;
    return db_one('SELECT id, username FROM users WHERE id = ?', [$uid]);
}

function require_login(): array {
    $u = current_user();
    if ($u) return $u;
    $next = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: /login?next=' . rawurlencode($next));
    exit;
}

function login_attempt(string $username, string $password): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (login_is_locked($ip)) {
        flash('error', 'Too many failed attempts. Try again in a few minutes.');
        return false;
    }

    $row = db_one('SELECT id, username, password_hash FROM users WHERE username = ?', [$username]);
    if (!$row || !password_verify($password, $row['password_hash'])) {
        login_record_failure($ip);
        flash('error', 'Invalid username or password.');
        return false;
    }

    cos_start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['_csrf']   = bin2hex(random_bytes(32));
    login_clear_failures($ip);
    return true;
}

function logout(): void {
    cos_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function login_attempts_path(): string {
    return COS_DATA_DIR . '/login_attempts.json';
}

function login_attempts_read(): array {
    $p = login_attempts_path();
    if (!is_file($p)) return [];
    $raw = @file_get_contents($p);
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function login_attempts_write(array $data): void {
    @file_put_contents(login_attempts_path(), json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function login_is_locked(string $ip): bool {
    $data = login_attempts_read();
    $now = time();
    $entry = $data[$ip] ?? ['count' => 0, 'first' => $now];
    if (($now - $entry['first']) > COS_LOGIN_LOCKOUT_WINDOW) return false;
    return $entry['count'] >= COS_LOGIN_LOCKOUT_THRESHOLD;
}

function login_record_failure(string $ip): void {
    $data = login_attempts_read();
    $now = time();
    $entry = $data[$ip] ?? ['count' => 0, 'first' => $now];
    if (($now - $entry['first']) > COS_LOGIN_LOCKOUT_WINDOW) {
        $entry = ['count' => 0, 'first' => $now];
    }
    $entry['count']++;
    $data[$ip] = $entry;
    login_attempts_write($data);
}

function login_clear_failures(string $ip): void {
    $data = login_attempts_read();
    unset($data[$ip]);
    login_attempts_write($data);
}
