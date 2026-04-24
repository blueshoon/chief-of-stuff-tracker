<?php
declare(strict_types=1);

function h_dashboard(): void {
    require_login();

    $today      = new DateTimeImmutable('today');
    $currentYm  = $today->format('Y-m');
    $selectedYm = (string) ($_GET['month'] ?? $currentYm);
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedYm)) {
        $selectedYm = $currentYm;
    }
    $isCurrentMonth = ($selectedYm === $currentYm);

    [$mStart, $mEnd] = month_bounds($selectedYm . '-01');
    $mStartDate = new DateTimeImmutable($mStart);

    $byClient = db_all(
        "SELECT c.id, c.name, c.color, c.hourly_rate_cents,
                COALESCE(SUM(te.minutes), 0) AS minutes
         FROM clients c
         LEFT JOIN time_entries te
           ON te.client_id = c.id AND te.entry_date BETWEEN ? AND ?
         WHERE c.archived = 0
         GROUP BY c.id
         ORDER BY minutes DESC, c.name",
        [$mStart, $mEnd]
    );

    $hoursMonth = 0;
    $earnedFromTime = 0;
    foreach ($byClient as &$row) {
        $row['minutes']      = (int) $row['minutes'];
        $row['earned_cents'] = (int) round(($row['minutes'] / 60) * (int) $row['hourly_rate_cents']);
        $hoursMonth     += $row['minutes'];
        $earnedFromTime += $row['earned_cents'];
    }
    unset($row);

    $billableByClient = db_all(
        "SELECT c.id, COALESCE(SUM(b.amount_cents), 0) AS billable_cents
         FROM clients c
         LEFT JOIN billable_expenses b
           ON b.client_id = c.id AND b.expense_date BETWEEN ? AND ?
         WHERE c.archived = 0
         GROUP BY c.id",
        [$mStart, $mEnd]
    );
    $billableByClientMap = [];
    $billableMonth = 0;
    foreach ($billableByClient as $b) {
        $billableByClientMap[(int) $b['id']] = (int) $b['billable_cents'];
        $billableMonth += (int) $b['billable_cents'];
    }
    foreach ($byClient as &$row) {
        $row['billable_cents'] = $billableByClientMap[(int) $row['id']] ?? 0;
        $row['total_cents']    = $row['earned_cents'] + $row['billable_cents'];
    }
    unset($row);

    $businessMonth = (int) (db_val(
        'SELECT COALESCE(SUM(amount_cents), 0) FROM business_expenses WHERE expense_date BETWEEN ? AND ?',
        [$mStart, $mEnd]
    ) ?? 0);

    $earnedMonth = $earnedFromTime + $billableMonth;
    $netMonth    = $earnedMonth - $businessMonth;

    // 14-day sparkline ending at the end of the selected month (or today, if current month)
    $sparkEnd = $isCurrentMonth
        ? $today
        : new DateTimeImmutable($mEnd);
    $sparkStart = $sparkEnd->modify('-13 days');

    $daily = db_all(
        "SELECT te.entry_date AS d,
                SUM(CAST(te.minutes AS REAL) / 60.0 * c.hourly_rate_cents) AS earned
         FROM time_entries te JOIN clients c ON c.id = te.client_id
         WHERE te.entry_date BETWEEN ? AND ?
         GROUP BY te.entry_date",
        [$sparkStart->format('Y-m-d'), $sparkEnd->format('Y-m-d')]
    );
    $earnedByDay = [];
    foreach ($daily as $row) $earnedByDay[$row['d']] = (int) round((float) $row['earned']);

    $billableDaily = db_all(
        "SELECT expense_date AS d, SUM(amount_cents) AS amt
         FROM billable_expenses WHERE expense_date BETWEEN ? AND ?
         GROUP BY expense_date",
        [$sparkStart->format('Y-m-d'), $sparkEnd->format('Y-m-d')]
    );
    foreach ($billableDaily as $row) {
        $earnedByDay[$row['d']] = ($earnedByDay[$row['d']] ?? 0) + (int) $row['amt'];
    }

    $spark = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = $sparkEnd->modify("-{$i} days")->format('Y-m-d');
        $spark[] = ['date' => $d, 'cents' => $earnedByDay[$d] ?? 0];
    }

    $thisWeek = array_sum(array_column(array_slice($spark, 7),  'cents'));
    $lastWeek = array_sum(array_column(array_slice($spark, 0, 7), 'cents'));
    $weekDelta = $lastWeek > 0
        ? ($thisWeek - $lastWeek) / $lastWeek
        : ($thisWeek > 0 ? 1.0 : 0.0);

    // Build month picker options: every month with any activity, plus current month
    $months = available_months($currentYm);

    $prevYm = $mStartDate->modify('-1 month')->format('Y-m');
    $nextYm = $mStartDate->modify('+1 month')->format('Y-m');
    $hasNextMonth = $nextYm <= $currentYm;

    // Un-invoiced totals for the selected month, for the "Create invoices" CTA
    $uninvoiced = uninvoiced_for_month($selectedYm);
    $uninvoicedTotal = (int) array_sum(array_column($uninvoiced, 'total_cents'));

    view('dashboard', [
        'pageTitle'      => 'Dashboard',
        'mStart'         => $mStart,
        'mEnd'           => $mEnd,
        'selectedYm'     => $selectedYm,
        'currentYm'      => $currentYm,
        'isCurrentMonth' => $isCurrentMonth,
        'months'         => $months,
        'prevYm'         => $prevYm,
        'nextYm'         => $nextYm,
        'hasNextMonth'   => $hasNextMonth,
        'earnedMonth'    => $earnedMonth,
        'earnedFromTime' => $earnedFromTime,
        'billableMonth'  => $billableMonth,
        'businessMonth'  => $businessMonth,
        'netMonth'       => $netMonth,
        'hoursMonth'     => $hoursMonth,
        'byClient'       => $byClient,
        'spark'          => $spark,
        'thisWeek'       => $thisWeek,
        'lastWeek'       => $lastWeek,
        'weekDelta'      => $weekDelta,
        'uninvoiced'      => $uninvoiced,
        'uninvoicedTotal' => $uninvoicedTotal,
    ]);
}

/**
 * Distinct YYYY-MM values that have any time entry / billable / business expense,
 * plus the current month, descending.
 */
function available_months(string $currentYm): array {
    $rows = db_all(
        "SELECT DISTINCT substr(entry_date, 1, 7) AS ym FROM time_entries
         UNION SELECT DISTINCT substr(expense_date, 1, 7) FROM billable_expenses
         UNION SELECT DISTINCT substr(expense_date, 1, 7) FROM business_expenses"
    );
    $months = array_column($rows, 'ym');
    $months[] = $currentYm;
    $months = array_values(array_unique(array_filter($months)));
    rsort($months);
    return $months;
}
