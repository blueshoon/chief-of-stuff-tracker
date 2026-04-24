<?php
declare(strict_types=1);

const COS_CLIENT_PALETTE = [
    '#864322', '#a35a37', '#cf8160',
    '#5b6595', '#2c3870', '#000042',
    '#3d6b56', '#7a4f8e', '#b8893a',
];

function h_clients_index(): void {
    require_login();
    $clients = clients_list(true);
    view('clients_index', [
        'pageTitle' => 'Clients',
        'clients'   => $clients,
        'palette'   => COS_CLIENT_PALETTE,
    ]);
}

function h_clients_create(): void {
    require_login();
    csrf_check();

    $name  = trim((string) ($_POST['name'] ?? ''));
    $rate  = parse_dollars_to_cents((string) ($_POST['hourly_rate'] ?? ''));
    $color = (string) ($_POST['color'] ?? '#864322');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#864322';

    if ($name === '') {
        flash('error', 'Client name is required.');
        redirect('/clients');
    }
    if ($rate <= 0) {
        flash('error', 'Hourly rate must be greater than zero.');
        redirect('/clients');
    }

    $id = db_insert(
        'INSERT INTO clients (name, hourly_rate_cents, color) VALUES (?, ?, ?)',
        [$name, $rate, $color]
    );
    flash('success', "Added '$name'.");
    redirect('/clients/' . $id);
}

function h_clients_show(array $params): void {
    require_login();
    $id = (int) $params['id'];
    $client = db_one('SELECT * FROM clients WHERE id = ?', [$id]);
    if (!$client) {
        http_response_code(404);
        echo 'Client not found.';
        return;
    }

    [$mStart, $mEnd] = month_bounds();

    $monthStats = db_one(
        'SELECT COALESCE(SUM(minutes), 0) AS minutes
         FROM time_entries WHERE client_id = ? AND entry_date BETWEEN ? AND ?',
        [$id, $mStart, $mEnd]
    );
    $monthMinutes = (int) ($monthStats['minutes'] ?? 0);
    $monthEarnedCents = (int) round(($monthMinutes / 60) * (int) $client['hourly_rate_cents']);

    $monthBillable = (int) (db_val(
        'SELECT COALESCE(SUM(amount_cents), 0)
         FROM billable_expenses WHERE client_id = ? AND expense_date BETWEEN ? AND ?',
        [$id, $mStart, $mEnd]
    ) ?? 0);

    $allTimeMinutes = (int) (db_val(
        'SELECT COALESCE(SUM(minutes), 0) FROM time_entries WHERE client_id = ?',
        [$id]
    ) ?? 0);
    $allTimeEarnedCents = (int) round(($allTimeMinutes / 60) * (int) $client['hourly_rate_cents']);

    $recentTime = db_all(
        'SELECT id, entry_date, minutes, notes
         FROM time_entries WHERE client_id = ?
         ORDER BY entry_date DESC, id DESC LIMIT 8',
        [$id]
    );
    $recentBillable = db_all(
        'SELECT id, expense_date, amount_cents, description
         FROM billable_expenses WHERE client_id = ?
         ORDER BY expense_date DESC, id DESC LIMIT 8',
        [$id]
    );

    view('client_detail', [
        'pageTitle'          => $client['name'],
        'client'             => $client,
        'palette'            => COS_CLIENT_PALETTE,
        'monthMinutes'       => $monthMinutes,
        'monthEarnedCents'   => $monthEarnedCents,
        'monthBillable'      => $monthBillable,
        'allTimeMinutes'     => $allTimeMinutes,
        'allTimeEarnedCents' => $allTimeEarnedCents,
        'recentTime'         => $recentTime,
        'recentBillable'     => $recentBillable,
    ]);
}

function h_clients_update(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];

    $name  = trim((string) ($_POST['name'] ?? ''));
    $rate  = parse_dollars_to_cents((string) ($_POST['hourly_rate'] ?? ''));
    $color = (string) ($_POST['color'] ?? '#864322');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#864322';

    if ($name === '' || $rate <= 0) {
        flash('error', 'Name and a positive rate are required.');
        redirect('/clients/' . $id);
    }

    db_q(
        'UPDATE clients SET name = ?, hourly_rate_cents = ?, color = ? WHERE id = ?',
        [$name, $rate, $color, $id]
    );
    flash('success', 'Saved.');
    redirect('/clients/' . $id);
}

function h_clients_archive(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];
    $current = (int) (db_val('SELECT archived FROM clients WHERE id = ?', [$id]) ?? 0);
    db_q('UPDATE clients SET archived = ? WHERE id = ?', [$current ? 0 : 1, $id]);
    flash('success', $current ? 'Restored.' : 'Archived.');
    redirect('/clients');
}
