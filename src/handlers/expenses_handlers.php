<?php
declare(strict_types=1);

function h_expenses_index(): void {
    require_login();
    $clients = clients_list(false);

    $billable = db_all(
        "SELECT b.id, b.expense_date, b.amount_cents, b.description, b.client_id, b.invoice_id,
                c.name AS client_name, c.color AS client_color,
                i.invoice_number
         FROM billable_expenses b
         JOIN clients c ON c.id = b.client_id
         LEFT JOIN invoices i ON i.id = b.invoice_id
         ORDER BY b.expense_date DESC, b.id DESC
         LIMIT 60"
    );
    $business = db_all(
        "SELECT id, expense_date, amount_cents, description, category
         FROM business_expenses
         ORDER BY expense_date DESC, id DESC
         LIMIT 60"
    );

    [$mStart, $mEnd] = month_bounds();
    $billableMonth = (int) (db_val(
        'SELECT COALESCE(SUM(amount_cents), 0) FROM billable_expenses WHERE expense_date BETWEEN ? AND ?',
        [$mStart, $mEnd]
    ) ?? 0);
    $businessMonth = (int) (db_val(
        'SELECT COALESCE(SUM(amount_cents), 0) FROM business_expenses WHERE expense_date BETWEEN ? AND ?',
        [$mStart, $mEnd]
    ) ?? 0);

    view('expenses_index', [
        'pageTitle'     => 'Expenses',
        'clients'       => $clients,
        'billable'      => $billable,
        'business'      => $business,
        'billableMonth' => $billableMonth,
        'businessMonth' => $businessMonth,
    ]);
}

function h_expenses_billable_create(): void {
    require_login();
    csrf_check();

    $clientId    = (int) ($_POST['client_id'] ?? 0);
    $amount      = parse_dollars_to_cents((string) ($_POST['amount'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $date        = (string) ($_POST['expense_date'] ?? today());
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = today();

    if ($clientId <= 0 || $amount <= 0 || $description === '') {
        flash('error', 'Client, amount, and description are required.');
        redirect('/expenses#billable');
    }

    db_q(
        'INSERT INTO billable_expenses (client_id, amount_cents, description, expense_date)
         VALUES (?, ?, ?, ?)',
        [$clientId, $amount, $description, $date]
    );
    flash('success', 'Billable expense added.');
    redirect('/expenses#billable');
}

function h_expenses_business_create(): void {
    require_login();
    csrf_check();

    $amount      = parse_dollars_to_cents((string) ($_POST['amount'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $category    = trim((string) ($_POST['category'] ?? ''));
    $date        = (string) ($_POST['expense_date'] ?? today());
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = today();

    if ($amount <= 0 || $description === '') {
        flash('error', 'Amount and description are required.');
        redirect('/expenses#business');
    }

    db_q(
        'INSERT INTO business_expenses (amount_cents, description, category, expense_date)
         VALUES (?, ?, ?, ?)',
        [$amount, $description, $category !== '' ? $category : null, $date]
    );
    flash('success', 'Business expense added.');
    redirect('/expenses#business');
}

function h_expenses_delete(array $params): void {
    require_login();
    csrf_check();

    $type = $params['type'] ?? '';
    $id   = (int) ($params['id'] ?? 0);
    $table = match ($type) {
        'billable' => 'billable_expenses',
        'business' => 'business_expenses',
        default    => null,
    };
    if (!$table) {
        http_response_code(404);
        return;
    }
    if ($type === 'billable') {
        $row = db_one('SELECT invoice_id FROM billable_expenses WHERE id = ?', [$id]);
        if ($row && $row['invoice_id']) {
            flash('error', 'Cannot delete an expense that has been invoiced. Delete the invoice first.');
            redirect('/expenses#billable');
        }
    }
    db_q("DELETE FROM $table WHERE id = ?", [$id]);
    flash('success', 'Expense deleted.');
    redirect('/expenses#' . $type);
}
