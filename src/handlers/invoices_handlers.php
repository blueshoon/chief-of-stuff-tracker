<?php
declare(strict_types=1);

function h_invoices_index(): void {
    require_login();

    $today = new DateTimeImmutable('today');
    $defaultYm = $today->modify('first day of last month')->format('Y-m');
    $selectedYm = (string) ($_GET['month'] ?? $defaultYm);
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedYm)) {
        $selectedYm = $defaultYm;
    }

    $uninvoiced = uninvoiced_for_month($selectedYm);

    $invoices = db_all(
        "SELECT i.*, c.name AS client_name, c.color AS client_color,
                COALESCE((SELECT SUM(amount_cents) FROM invoice_payments WHERE invoice_id = i.id), 0) AS paid_cents
         FROM invoices i JOIN clients c ON c.id = i.client_id
         ORDER BY i.issue_date DESC, i.id DESC
         LIMIT 100"
    );

    // group invoices by issue month for display
    $grouped = [];
    foreach ($invoices as $inv) {
        $ym = substr($inv['issue_date'], 0, 7);
        $grouped[$ym][] = $inv;
    }

    $months = available_months($today->format('Y-m'));

    view('invoices_index', [
        'pageTitle'    => 'Invoices',
        'selectedYm'   => $selectedYm,
        'uninvoiced'   => $uninvoiced,
        'grouped'      => $grouped,
        'months'       => $months,
    ]);
}

function h_invoices_create_for_month(): void {
    require_login();
    csrf_check();

    $ym = (string) ($_POST['month'] ?? '');
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) {
        flash('error', 'Invalid month.');
        redirect('/invoices');
    }

    $clientIds = $_POST['client_ids'] ?? [];
    if (!is_array($clientIds)) $clientIds = [];
    $clientIds = array_map('intval', $clientIds);
    $clientIds = array_values(array_filter($clientIds, fn($id) => $id > 0));

    if (empty($clientIds)) {
        flash('error', 'Pick at least one client to invoice.');
        redirect('/invoices?month=' . $ym);
    }

    $created = 0;
    foreach ($clientIds as $cid) {
        if (create_invoice_for_client_month($cid, $ym) !== null) $created++;
    }

    if ($created === 0) {
        flash('error', 'Nothing to invoice for those clients in that month.');
        redirect('/invoices?month=' . $ym);
    }
    flash('success', $created === 1 ? '1 invoice created.' : "$created invoices created.");
    redirect('/invoices?month=' . $ym);
}

function h_invoice_show(array $params): void {
    require_login();
    $inv = load_invoice_full((int) $params['id']);
    if (!$inv) { http_response_code(404); echo 'Invoice not found.'; return; }
    $settings = business_settings();

    view('invoice_show', [
        'pageTitle' => $inv['invoice_number'],
        'inv'       => $inv,
        'settings'  => $settings,
    ]);
}

function h_invoice_update(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];

    $invoiceNumber = trim((string) ($_POST['invoice_number'] ?? ''));
    $issueDate     = (string) ($_POST['issue_date'] ?? today());
    $dueDate       = trim((string) ($_POST['due_date'] ?? ''));
    $notes         = (string) ($_POST['notes'] ?? '');

    if ($invoiceNumber === '') {
        flash('error', 'Invoice number is required.');
        redirect('/invoices/' . $id);
    }
    foreach ([$issueDate, $dueDate] as $d) {
        if ($d !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            flash('error', 'Dates must be YYYY-MM-DD.');
            redirect('/invoices/' . $id);
        }
    }

    try {
        db_q(
            "UPDATE invoices
             SET invoice_number = ?, issue_date = ?, due_date = ?, notes = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$invoiceNumber, $issueDate, $dueDate !== '' ? $dueDate : null, $notes, $id]
        );
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'UNIQUE')) {
            flash('error', 'That invoice number is already in use.');
            redirect('/invoices/' . $id);
        }
        throw $e;
    }
    flash('success', 'Saved.');
    redirect('/invoices/' . $id);
}

function h_invoice_delete(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];

    db()->beginTransaction();
    try {
        // un-invoice the source rows so they're billable again
        db_q('UPDATE time_entries      SET invoice_id = NULL WHERE invoice_id = ?', [$id]);
        db_q('UPDATE billable_expenses SET invoice_id = NULL WHERE invoice_id = ?', [$id]);
        // invoice_line_items + invoice_payments cascade-delete
        db_q('DELETE FROM invoices WHERE id = ?', [$id]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    flash('success', 'Invoice deleted.');
    redirect('/invoices');
}

function h_invoice_set_status(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];
    $status = (string) ($_POST['status'] ?? '');
    if (!in_array($status, INVOICE_STATUSES, true)) {
        flash('error', 'Unknown status.');
        redirect('/invoices/' . $id);
    }
    db_q('UPDATE invoices SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$status, $id]);
    flash('success', 'Marked ' . invoice_status_label($status) . '.');
    redirect('/invoices/' . $id);
}

function h_invoice_line_create(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];

    $kind        = ((string) ($_POST['kind'] ?? 'expense')) === 'time' ? 'time' : 'expense';
    $description = trim((string) ($_POST['description'] ?? ''));
    $lineDate    = trim((string) ($_POST['line_date'] ?? ''));
    $quantity    = (float) ($_POST['quantity'] ?? 1);
    $unit        = parse_dollars_to_cents((string) ($_POST['unit'] ?? '0'));
    $amount      = (int) round($quantity * $unit);
    if ($amount === 0) {
        $amount = parse_dollars_to_cents((string) ($_POST['amount'] ?? '0'));
    }

    if ($description === '' || $amount === 0) {
        flash('error', 'Description and a non-zero amount are required.');
        redirect('/invoices/' . $id);
    }

    $sort = (int) (db_val('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM invoice_line_items WHERE invoice_id = ?', [$id]) ?? 0);

    db_q(
        "INSERT INTO invoice_line_items
         (invoice_id, kind, description, line_date, quantity, unit_cents, amount_cents, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$id, $kind, $description, $lineDate !== '' ? $lineDate : null, $quantity, $unit, $amount, $sort]
    );
    recompute_invoice_totals($id);
    maybe_mark_paid($id);
    flash('success', 'Line added.');
    redirect('/invoices/' . $id);
}

function h_invoice_line_update(array $params): void {
    require_login();
    csrf_check();
    $id     = (int) $params['id'];
    $lineId = (int) $params['lineId'];

    $line = db_one('SELECT id FROM invoice_line_items WHERE id = ? AND invoice_id = ?', [$lineId, $id]);
    if (!$line) { http_response_code(404); return; }

    $description = trim((string) ($_POST['description'] ?? ''));
    $lineDate    = trim((string) ($_POST['line_date'] ?? ''));
    $quantity    = (float) ($_POST['quantity'] ?? 1);
    $unit        = parse_dollars_to_cents((string) ($_POST['unit'] ?? '0'));
    $amount      = (int) round($quantity * $unit);

    if ($description === '' || $amount === 0) {
        flash('error', 'Description and a non-zero amount are required.');
        redirect('/invoices/' . $id);
    }

    db_q(
        "UPDATE invoice_line_items
         SET description = ?, line_date = ?, quantity = ?, unit_cents = ?, amount_cents = ?
         WHERE id = ?",
        [$description, $lineDate !== '' ? $lineDate : null, $quantity, $unit, $amount, $lineId]
    );
    recompute_invoice_totals($id);
    maybe_mark_paid($id);
    flash('success', 'Line updated.');
    redirect('/invoices/' . $id);
}

function h_invoice_line_delete(array $params): void {
    require_login();
    csrf_check();
    $id     = (int) $params['id'];
    $lineId = (int) $params['lineId'];

    $line = db_one('SELECT * FROM invoice_line_items WHERE id = ? AND invoice_id = ?', [$lineId, $id]);
    if (!$line) { http_response_code(404); return; }

    db()->beginTransaction();
    try {
        // un-invoice any source row tied to this line
        if ($line['time_entry_id']) {
            db_q('UPDATE time_entries      SET invoice_id = NULL WHERE id = ?', [(int) $line['time_entry_id']]);
        }
        if ($line['billable_expense_id']) {
            db_q('UPDATE billable_expenses SET invoice_id = NULL WHERE id = ?', [(int) $line['billable_expense_id']]);
        }
        db_q('DELETE FROM invoice_line_items WHERE id = ?', [$lineId]);
        recompute_invoice_totals($id);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    flash('success', 'Line deleted.');
    redirect('/invoices/' . $id);
}

function h_invoice_payment_create(array $params): void {
    require_login();
    csrf_check();
    $id = (int) $params['id'];

    $amount    = parse_dollars_to_cents((string) ($_POST['amount'] ?? ''));
    $date      = (string) ($_POST['payment_date'] ?? today());
    $method    = trim((string) ($_POST['method'] ?? ''));
    $reference = trim((string) ($_POST['reference'] ?? ''));
    $notes     = trim((string) ($_POST['notes'] ?? ''));

    if ($amount <= 0) {
        flash('error', 'Payment amount must be greater than zero.');
        redirect('/invoices/' . $id);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = today();

    db_q(
        "INSERT INTO invoice_payments (invoice_id, amount_cents, payment_date, method, reference, notes)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$id, $amount, $date, $method !== '' ? $method : null,
         $reference !== '' ? $reference : null, $notes !== '' ? $notes : null]
    );
    maybe_mark_paid($id);
    flash('success', 'Payment recorded.');
    redirect('/invoices/' . $id);
}

function h_invoice_payment_delete(array $params): void {
    require_login();
    csrf_check();
    $id    = (int) $params['id'];
    $payId = (int) $params['payId'];
    db_q('DELETE FROM invoice_payments WHERE id = ? AND invoice_id = ?', [$payId, $id]);
    flash('success', 'Payment removed.');
    redirect('/invoices/' . $id);
}

function h_invoice_print(array $params): void {
    require_login();
    $inv = load_invoice_full((int) $params['id']);
    if (!$inv) { http_response_code(404); echo 'Not found'; return; }
    $settings = business_settings();
    render_invoice_html($inv, $settings, false);
}

function h_invoice_pdf(array $params): void {
    require_login();
    $inv = load_invoice_full((int) $params['id']);
    if (!$inv) { http_response_code(404); echo 'Not found'; return; }
    $settings = business_settings();

    ob_start();
    render_invoice_html($inv, $settings, true);
    $html = ob_get_clean();

    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled'    => false,
        'isHtml5ParserEnabled' => true,
        'defaultFont'        => 'DejaVu Sans',
    ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    $filename = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $inv['invoice_number']) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
}

function render_invoice_html(array $inv, array $settings, bool $forPdf): void {
    extract([
        'inv'      => $inv,
        'settings' => $settings,
        'forPdf'   => $forPdf,
    ], EXTR_SKIP);
    require COS_VIEWS_DIR . '/invoice_print.php';
}

function business_settings(): array {
    $row = db_one('SELECT * FROM business_settings WHERE id = 1');
    return $row ?: ['business_name' => 'Chief of Stuff'];
}
