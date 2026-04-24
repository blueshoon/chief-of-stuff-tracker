<?php
declare(strict_types=1);

function h_time_index(): void {
    require_login();
    $clients = clients_list(false);
    $timer   = active_timer();

    $entries = db_all(
        "SELECT te.id, te.client_id, te.entry_date, te.minutes, te.notes, te.invoice_id,
                c.name AS client_name, c.color AS client_color, c.hourly_rate_cents,
                i.invoice_number
         FROM time_entries te
         JOIN clients c ON c.id = te.client_id
         LEFT JOIN invoices i ON i.id = te.invoice_id
         ORDER BY te.entry_date DESC, te.id DESC
         LIMIT 60"
    );

    view('time_index', [
        'pageTitle' => 'Time',
        'clients'   => $clients,
        'timer'     => $timer,
        'entries'   => $entries,
    ]);
}

function h_time_create(): void {
    require_login();
    csrf_check();

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $date     = (string) ($_POST['entry_date'] ?? today());
    $hours    = (string) ($_POST['hours'] ?? '');
    $notes    = trim((string) ($_POST['notes'] ?? ''));

    if ($clientId <= 0) {
        flash('error', 'Pick a client.');
        redirect('/time');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = today();
    }

    $minutes = parse_hours_to_minutes($hours);
    if ($minutes <= 0) {
        flash('error', 'Hours must be greater than zero (e.g. 1.5 or 1:30).');
        redirect('/time');
    }

    db_q(
        'INSERT INTO time_entries (client_id, entry_date, minutes, notes) VALUES (?, ?, ?, ?)',
        [$clientId, $date, $minutes, $notes !== '' ? $notes : null]
    );
    flash('success', 'Time logged.');
    redirect('/time');
}

function h_time_delete(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];
    $row = db_one('SELECT invoice_id FROM time_entries WHERE id = ?', [$id]);
    if ($row && $row['invoice_id']) {
        flash('error', 'Cannot delete an entry that has been invoiced. Delete the invoice first.');
        redirect('/time');
    }
    db_q('DELETE FROM time_entries WHERE id = ?', [$id]);
    flash('success', 'Entry deleted.');
    redirect('/time');
}

function h_timer_start(): void {
    require_login();
    csrf_check();

    if (active_timer()) {
        flash('error', 'A timer is already running.');
        redirect('/time');
    }

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $notes    = trim((string) ($_POST['notes'] ?? ''));
    if ($clientId <= 0) {
        flash('error', 'Pick a client to start a timer.');
        redirect('/time');
    }

    $startedAt = gmdate('Y-m-d H:i:s'); // UTC; JS reads as UTC
    db_q(
        'INSERT INTO active_timer (id, client_id, started_at, notes) VALUES (1, ?, ?, ?)',
        [$clientId, $startedAt, $notes !== '' ? $notes : null]
    );
    flash('success', 'Timer started.');
    redirect('/time');
}

function h_timer_stop(): void {
    require_login();
    csrf_check();

    $timer = active_timer();
    if (!$timer) redirect('/time');

    $startedTs = strtotime($timer['started_at'] . ' UTC');
    $minutes   = max(1, (int) round((time() - $startedTs) / 60));
    $entryDate = date('Y-m-d'); // local date (the day the work was done)

    db()->beginTransaction();
    try {
        db_q(
            'INSERT INTO time_entries (client_id, started_at, ended_at, minutes, entry_date, notes)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                (int) $timer['client_id'],
                $timer['started_at'],
                gmdate('Y-m-d H:i:s'),
                $minutes,
                $entryDate,
                $timer['notes'] ?: null,
            ]
        );
        db_q('DELETE FROM active_timer WHERE id = 1');
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    flash('success', 'Logged ' . format_minutes_as_hours($minutes) . '.');
    redirect('/time');
}
