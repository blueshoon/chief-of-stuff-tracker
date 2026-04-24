<?php
declare(strict_types=1);

function e(mixed $v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null): ?string {
    cos_start_session();
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['_flash'][$key] ?? null;
    if ($msg !== null) unset($_SESSION['_flash'][$key]);
    return $msg;
}

function flash_all(): array {
    cos_start_session();
    $all = $_SESSION['_flash'] ?? [];
    $_SESSION['_flash'] = [];
    return $all;
}

function view(string $page, array $data = []): void {
    $data['__page'] = $page;
    extract($data, EXTR_SKIP);
    require COS_VIEWS_DIR . '/layout.php';
}

function view_partial(string $page, array $data = []): void {
    extract($data, EXTR_SKIP);
    require COS_VIEWS_DIR . '/pages/' . $page . '.php';
}

function dollars(int $cents): string {
    $sign = $cents < 0 ? '-' : '';
    $abs = abs($cents);
    return $sign . '$' . number_format($abs / 100, 2);
}

function dollars_short(int $cents): string {
    $abs = abs($cents);
    if ($abs >= 100000) {
        return ($cents < 0 ? '-' : '') . '$' . number_format($abs / 100000, 1) . 'k';
    }
    return dollars($cents);
}

function parse_dollars_to_cents(string $input): int {
    $clean = preg_replace('/[^0-9.\-]/', '', $input);
    if ($clean === '' || $clean === '-' || $clean === '.') return 0;
    return (int) round(((float) $clean) * 100);
}

function format_minutes_as_hours(int $minutes): string {
    if ($minutes <= 0) return '0h';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h === 0) return "{$m}m";
    if ($m === 0) return "{$h}h";
    return "{$h}h {$m}m";
}

function parse_hours_to_minutes(string $input): int {
    $input = trim($input);
    if ($input === '') return 0;
    if (preg_match('/^(\d+):(\d{1,2})$/', $input, $m)) {
        return ((int) $m[1]) * 60 + min(59, (int) $m[2]);
    }
    return (int) round(((float) $input) * 60);
}

function today(): string {
    return (new DateTimeImmutable('now'))->format('Y-m-d');
}

function month_bounds(?string $ymd = null): array {
    $d = new DateTimeImmutable($ymd ?? 'now');
    $start = $d->modify('first day of this month')->format('Y-m-d');
    $end   = $d->modify('last day of this month')->format('Y-m-d');
    return [$start, $end];
}

function active_timer(): ?array {
    return db_one('SELECT t.*, c.name AS client_name, c.color AS client_color
                   FROM active_timer t JOIN clients c ON c.id = t.client_id
                   WHERE t.id = 1');
}

function clients_list(bool $includeArchived = false): array {
    $sql = 'SELECT id, name, hourly_rate_cents, color, archived FROM clients';
    if (!$includeArchived) $sql .= ' WHERE archived = 0';
    $sql .= ' ORDER BY archived ASC, name COLLATE NOCASE ASC';
    return db_all($sql);
}
