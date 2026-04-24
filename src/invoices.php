<?php
declare(strict_types=1);

const INVOICE_STATUSES = ['draft', 'sent', 'paid', 'void'];

function invoice_status_label(string $status): string {
    return match ($status) {
        'draft' => 'Draft',
        'sent'  => 'Sent',
        'paid'  => 'Paid',
        'void'  => 'Void',
        default => ucfirst($status),
    };
}

function invoice_status_chip_class(string $status): string {
    return match ($status) {
        'draft' => 'bg-navy-100 text-navy-500',
        'sent'  => 'bg-rust-100 text-rust-700',
        'paid'  => 'bg-rust-500 text-cream',
        'void'  => 'bg-navy-300 text-navy-600 line-through',
        default => 'bg-navy-100 text-navy-500',
    };
}

/**
 * Generates the next invoice number scoped to the issue year, e.g. "INV-2026-0001".
 */
function next_invoice_number(string $issueDate): string {
    $year = substr($issueDate, 0, 4);
    $count = (int) (db_val(
        "SELECT COUNT(*) FROM invoices WHERE substr(issue_date, 1, 4) = ?",
        [$year]
    ) ?? 0);
    return sprintf('INV-%s-%04d', $year, $count + 1);
}

/**
 * Per-client snapshot of un-invoiced earnings and reimbursable expenses for a month.
 * Returns a list of rows: client + minutes/earned/billable/total uninvoiced.
 */
function uninvoiced_for_month(string $ym): array {
    [$mStart, $mEnd] = month_bounds($ym . '-01');

    $rows = db_all(
        "SELECT c.id, c.name, c.color, c.hourly_rate_cents,
                COALESCE((SELECT SUM(te.minutes)
                          FROM time_entries te
                          WHERE te.client_id = c.id
                            AND te.entry_date BETWEEN ? AND ?
                            AND te.invoice_id IS NULL), 0) AS minutes,
                COALESCE((SELECT SUM(b.amount_cents)
                          FROM billable_expenses b
                          WHERE b.client_id = c.id
                            AND b.expense_date BETWEEN ? AND ?
                            AND b.invoice_id IS NULL), 0) AS billable_cents
         FROM clients c
         WHERE c.archived = 0
         ORDER BY c.name COLLATE NOCASE",
        [$mStart, $mEnd, $mStart, $mEnd]
    );

    $out = [];
    foreach ($rows as $r) {
        $minutes = (int) $r['minutes'];
        $billable = (int) $r['billable_cents'];
        $earned = (int) round(($minutes / 60) * (int) $r['hourly_rate_cents']);
        if ($minutes === 0 && $billable === 0) continue;
        $out[] = [
            'client_id'         => (int) $r['id'],
            'client_name'       => $r['name'],
            'client_color'      => $r['color'],
            'hourly_rate_cents' => (int) $r['hourly_rate_cents'],
            'minutes'           => $minutes,
            'earned_cents'      => $earned,
            'billable_cents'    => $billable,
            'total_cents'       => $earned + $billable,
        ];
    }
    return $out;
}

/**
 * Creates a draft invoice for one client covering all un-invoiced time entries
 * and billable expenses in the given YYYY-MM month. Snapshots them as line items.
 * Returns the new invoice id, or null if there was nothing to invoice.
 */
function create_invoice_for_client_month(int $clientId, string $ym, ?string $issueDate = null): ?int {
    [$mStart, $mEnd] = month_bounds($ym . '-01');
    $issueDate = $issueDate ?? today();

    $client = db_one('SELECT id, name, hourly_rate_cents FROM clients WHERE id = ?', [$clientId]);
    if (!$client) return null;

    $time = db_all(
        "SELECT id, entry_date, minutes, notes
         FROM time_entries
         WHERE client_id = ?
           AND entry_date BETWEEN ? AND ?
           AND invoice_id IS NULL
         ORDER BY entry_date ASC, id ASC",
        [$clientId, $mStart, $mEnd]
    );
    $expenses = db_all(
        "SELECT id, expense_date, amount_cents, description
         FROM billable_expenses
         WHERE client_id = ?
           AND expense_date BETWEEN ? AND ?
           AND invoice_id IS NULL
         ORDER BY expense_date ASC, id ASC",
        [$clientId, $mStart, $mEnd]
    );

    if (empty($time) && empty($expenses)) return null;

    $monthLabel = (new DateTimeImmutable($mStart))->format('F Y');
    $rate = (int) $client['hourly_rate_cents'];

    db()->beginTransaction();
    try {
        $invoiceNumber = next_invoice_number($issueDate);
        $dueDate = (new DateTimeImmutable($issueDate))->modify('+30 days')->format('Y-m-d');

        $invoiceId = db_insert(
            "INSERT INTO invoices
             (client_id, invoice_number, period_start, period_end,
              issue_date, due_date, notes, status,
              hours_minutes_total, hours_amount_cents, expenses_amount_cents, total_cents)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', 0, 0, 0, 0)",
            [$clientId, $invoiceNumber, $mStart, $mEnd, $issueDate, $dueDate,
             "Services rendered for {$monthLabel}."]
        );

        $sort = 0;
        foreach ($time as $t) {
            $minutes = (int) $t['minutes'];
            $hours   = $minutes / 60;
            $amount  = (int) round($hours * $rate);
            $desc    = $t['notes'] !== null && $t['notes'] !== ''
                ? $t['notes']
                : 'Services on ' . $t['entry_date'];
            db_q(
                "INSERT INTO invoice_line_items
                 (invoice_id, kind, description, line_date, quantity, unit_cents, amount_cents,
                  time_entry_id, sort_order)
                 VALUES (?, 'time', ?, ?, ?, ?, ?, ?, ?)",
                [$invoiceId, $desc, $t['entry_date'], $hours, $rate, $amount, (int) $t['id'], $sort++]
            );
            db_q('UPDATE time_entries SET invoice_id = ? WHERE id = ?', [$invoiceId, (int) $t['id']]);
        }

        foreach ($expenses as $x) {
            $amount = (int) $x['amount_cents'];
            db_q(
                "INSERT INTO invoice_line_items
                 (invoice_id, kind, description, line_date, quantity, unit_cents, amount_cents,
                  billable_expense_id, sort_order)
                 VALUES (?, 'expense', ?, ?, 1, ?, ?, ?, ?)",
                [$invoiceId, $x['description'], $x['expense_date'], $amount, $amount, (int) $x['id'], $sort++]
            );
            db_q('UPDATE billable_expenses SET invoice_id = ? WHERE id = ?', [$invoiceId, (int) $x['id']]);
        }

        recompute_invoice_totals($invoiceId);

        db()->commit();
        return $invoiceId;
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

/**
 * Recomputes hours_minutes_total / hours_amount_cents / expenses_amount_cents / total_cents
 * for an invoice from its line items. Touches updated_at.
 */
function recompute_invoice_totals(int $invoiceId): void {
    $sums = db_one(
        "SELECT
            COALESCE(SUM(CASE WHEN kind = 'time'    THEN ROUND(quantity * 60) END), 0) AS minutes,
            COALESCE(SUM(CASE WHEN kind = 'time'    THEN amount_cents END), 0)         AS hours_amount,
            COALESCE(SUM(CASE WHEN kind = 'expense' THEN amount_cents END), 0)         AS expenses_amount,
            COALESCE(SUM(amount_cents), 0)                                             AS total
         FROM invoice_line_items WHERE invoice_id = ?",
        [$invoiceId]
    );
    db_q(
        "UPDATE invoices
         SET hours_minutes_total   = ?,
             hours_amount_cents    = ?,
             expenses_amount_cents = ?,
             total_cents           = ?,
             updated_at            = CURRENT_TIMESTAMP
         WHERE id = ?",
        [(int) $sums['minutes'], (int) $sums['hours_amount'],
         (int) $sums['expenses_amount'], (int) $sums['total'], $invoiceId]
    );
}

function invoice_total_paid(int $invoiceId): int {
    return (int) (db_val(
        'SELECT COALESCE(SUM(amount_cents), 0) FROM invoice_payments WHERE invoice_id = ?',
        [$invoiceId]
    ) ?? 0);
}

/**
 * Auto-promote a 'sent' invoice to 'paid' once payments cover the total.
 * Conservative: never demotes, never touches drafts or void.
 */
function maybe_mark_paid(int $invoiceId): void {
    $row = db_one('SELECT status, total_cents FROM invoices WHERE id = ?', [$invoiceId]);
    if (!$row) return;
    if (!in_array($row['status'], ['sent', 'draft'], true)) return;
    if ((int) $row['total_cents'] <= 0) return;
    $paid = invoice_total_paid($invoiceId);
    if ($paid >= (int) $row['total_cents']) {
        db_q('UPDATE invoices SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
             ['paid', $invoiceId]);
    }
}

function load_invoice_full(int $invoiceId): ?array {
    $inv = db_one(
        'SELECT i.*, c.name AS client_name, c.color AS client_color, c.hourly_rate_cents
         FROM invoices i JOIN clients c ON c.id = i.client_id WHERE i.id = ?',
        [$invoiceId]
    );
    if (!$inv) return null;
    $inv['lines'] = db_all(
        'SELECT * FROM invoice_line_items WHERE invoice_id = ? ORDER BY sort_order, id',
        [$invoiceId]
    );
    $inv['payments'] = db_all(
        'SELECT * FROM invoice_payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC',
        [$invoiceId]
    );
    $inv['paid_cents']    = invoice_total_paid($invoiceId);
    $inv['balance_cents'] = (int) $inv['total_cents'] - $inv['paid_cents'];
    return $inv;
}
