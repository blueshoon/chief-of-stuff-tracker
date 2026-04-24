<?php
/** @var array $inv */
/** @var array $settings */
/** @var bool $forPdf */

$periodLabel = (new DateTimeImmutable($inv['period_start']))->format('M j, Y')
             . ' – ' . (new DateTimeImmutable($inv['period_end']))->format('M j, Y');
$balance = (int) $inv['balance_cents'];
$paid    = (int) $inv['paid_cents'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($inv['invoice_number']) ?> · <?= e($settings['business_name'] ?? 'Invoice') ?></title>
    <style>
        @page { margin: 0.6in 0.55in; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #000042;
            font-size: 11pt;
            line-height: 1.45;
            margin: 0;
            background: #ffffff;
        }
        h1 { font-size: 28pt; margin: 0 0 4pt 0; color: #000042; font-weight: 700; letter-spacing: -0.5pt; }
        h2 { font-size: 9pt; margin: 0 0 4pt 0; color: #864322; text-transform: uppercase; letter-spacing: 1.5pt; font-weight: 700; }
        .muted { color: #5b6595; }
        .small { font-size: 9pt; }
        .right { text-align: right; }
        .accent { color: #864322; }

        table.layout { width: 100%; border-collapse: collapse; }
        table.layout > tbody > tr > td { vertical-align: top; }

        .header-rule {
            border-top: 3px solid #864322;
            margin: 18pt 0 14pt 0;
        }

        .meta-table { width: 100%; border-collapse: collapse; margin-top: 6pt; }
        .meta-table td { padding: 3pt 0; vertical-align: top; }
        .meta-table .label { color: #5b6595; font-size: 9pt; text-transform: uppercase; letter-spacing: 1pt; padding-right: 12pt; white-space: nowrap; }

        .bill-to {
            background: #fbf7f2;
            border-left: 3px solid #864322;
            padding: 10pt 12pt;
            border-radius: 4px;
        }

        table.lines { width: 100%; border-collapse: collapse; margin-top: 14pt; }
        table.lines th {
            text-align: left;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 1pt;
            color: #5b6595;
            border-bottom: 2px solid #000042;
            padding: 6pt 6pt 6pt 0;
            font-weight: 600;
        }
        table.lines td {
            border-bottom: 1px solid #d4d8e8;
            padding: 8pt 6pt 8pt 0;
            vertical-align: top;
        }
        table.lines td.num, table.lines th.num { text-align: right; padding-right: 0; }
        table.lines tr:last-child td { border-bottom: none; }

        .totals { width: 60%; margin-left: 40%; margin-top: 14pt; border-collapse: collapse; }
        .totals td { padding: 4pt 0; }
        .totals td.label { color: #5b6595; }
        .totals td.amount { text-align: right; font-variant-numeric: tabular-nums; }
        .totals tr.total td { border-top: 2px solid #000042; padding-top: 8pt; font-size: 14pt; font-weight: 700; color: #000042; }
        .totals tr.balance td { border-top: 1px solid #d4d8e8; padding-top: 6pt; font-weight: 700; color: #864322; }

        .notes {
            margin-top: 22pt;
            padding-top: 14pt;
            border-top: 1px solid #d4d8e8;
            font-size: 10pt;
            color: #2c3870;
        }
        .notes h2 { margin-bottom: 4pt; }

        .pay-box {
            margin-top: 14pt;
            background: #000042;
            color: #fbf7f2;
            padding: 12pt 14pt;
            border-radius: 4px;
            font-size: 10pt;
        }
        .pay-box h2 { color: #fbf7f2; margin-bottom: 4pt; }

        .status-badge {
            display: inline-block;
            padding: 2pt 8pt;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1pt;
            border-radius: 999px;
            background: #864322;
            color: #fbf7f2;
        }
        .status-badge.paid { background: #864322; }
        .status-badge.draft { background: #d4d8e8; color: #2c3870; }
        .status-badge.void { background: #9aa1c2; color: #ffffff; text-decoration: line-through; }

        .footer {
            margin-top: 28pt;
            text-align: center;
            font-size: 8pt;
            color: #5b6595;
        }

        @media print {
            .toolbar { display: none !important; }
            body { background: #ffffff; }
        }

        .toolbar {
            position: sticky;
            top: 0;
            background: #000042;
            color: #fbf7f2;
            padding: 8pt 12pt;
            margin-bottom: 14pt;
            display: flex;
            gap: 8pt;
            justify-content: space-between;
            align-items: center;
            font-size: 10pt;
            border-radius: 4px;
        }
        .toolbar a, .toolbar button {
            background: #864322;
            color: #fbf7f2;
            text-decoration: none;
            padding: 5pt 10pt;
            border: 0;
            border-radius: 4px;
            font-size: 10pt;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php if (!$forPdf): ?>
<div class="toolbar">
    <span>Preview · <?= e($inv['invoice_number']) ?></span>
    <span>
        <a href="/invoices/<?= (int) $inv['id'] ?>/pdf">Download PDF</a>
        <button onclick="window.print()">Print</button>
        <a href="/invoices/<?= (int) $inv['id'] ?>" style="background: transparent; border: 1px solid #fbf7f2;">Back</a>
    </span>
</div>
<?php endif; ?>

<table class="layout">
    <tr>
        <td style="width: 60%;">
            <h1><?= e($settings['business_name'] ?? 'Chief of Stuff') ?></h1>
            <?php if (!empty($settings['contact_name'])): ?>
                <div><?= e($settings['contact_name']) ?></div>
            <?php endif; ?>
            <?php if (!empty($settings['address'])): ?>
                <div class="small muted" style="white-space: pre-line;"><?= e($settings['address']) ?></div>
            <?php endif; ?>
            <?php if (!empty($settings['email']) || !empty($settings['phone'])): ?>
                <div class="small muted">
                    <?= e($settings['email'] ?? '') ?>
                    <?php if (!empty($settings['email']) && !empty($settings['phone'])): ?> · <?php endif; ?>
                    <?= e($settings['phone'] ?? '') ?>
                </div>
            <?php endif; ?>
        </td>
        <td class="right" style="width: 40%;">
            <h2>Invoice</h2>
            <div style="font-size: 18pt; font-weight: 700; color: #864322;">
                <?= e($inv['invoice_number']) ?>
            </div>
            <div style="margin-top: 6pt;">
                <span class="status-badge <?= e($inv['status']) ?>">
                    <?= e(invoice_status_label($inv['status'])) ?>
                </span>
            </div>
        </td>
    </tr>
</table>

<div class="header-rule"></div>

<table class="layout">
    <tr>
        <td style="width: 55%;">
            <h2>Bill to</h2>
            <div class="bill-to">
                <div style="font-size: 13pt; font-weight: 700;"><?= e($inv['client_name']) ?></div>
                <div class="small muted">For services rendered</div>
            </div>
        </td>
        <td style="width: 45%; padding-left: 20pt;">
            <table class="meta-table">
                <tr>
                    <td class="label">Issue date</td>
                    <td><?= e((new DateTimeImmutable($inv['issue_date']))->format('M j, Y')) ?></td>
                </tr>
                <?php if (!empty($inv['due_date'])): ?>
                <tr>
                    <td class="label">Due date</td>
                    <td><?= e((new DateTimeImmutable($inv['due_date']))->format('M j, Y')) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="label">Period</td>
                    <td><?= e($periodLabel) ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="lines">
    <thead>
        <tr>
            <th style="width: 50%;">Description</th>
            <th style="width: 15%;">Date</th>
            <th class="num" style="width: 10%;">Qty</th>
            <th class="num" style="width: 12%;">Rate</th>
            <th class="num" style="width: 13%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($inv['lines'])): ?>
            <tr><td colspan="5" class="muted small">No line items.</td></tr>
        <?php else: foreach ($inv['lines'] as $line): ?>
            <tr>
                <td>
                    <div style="font-weight: 600;"><?= e($line['description']) ?></div>
                    <?php if ($line['kind'] === 'time'): ?>
                        <div class="small muted">Time</div>
                    <?php elseif ($line['kind'] === 'expense'): ?>
                        <div class="small muted">Reimbursable expense</div>
                    <?php endif; ?>
                </td>
                <td class="small"><?= $line['line_date'] ? e((new DateTimeImmutable($line['line_date']))->format('M j')) : '' ?></td>
                <td class="num small">
                    <?= $line['kind'] === 'time'
                        ? number_format((float) $line['quantity'], 2)
                        : (((float) $line['quantity']) == 1.0 ? '' : number_format((float) $line['quantity'], 2)) ?>
                </td>
                <td class="num small">
                    <?= $line['kind'] === 'time' ? e(dollars((int) $line['unit_cents'])) : '' ?>
                </td>
                <td class="num"><strong><?= e(dollars((int) $line['amount_cents'])) ?></strong></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="label">Hours <span class="small">(<?= e(format_minutes_as_hours((int) $inv['hours_minutes_total'])) ?>)</span></td>
        <td class="amount"><?= e(dollars((int) $inv['hours_amount_cents'])) ?></td>
    </tr>
    <tr>
        <td class="label">Reimbursable expenses</td>
        <td class="amount"><?= e(dollars((int) $inv['expenses_amount_cents'])) ?></td>
    </tr>
    <tr class="total">
        <td>Total</td>
        <td class="amount"><?= e(dollars((int) $inv['total_cents'])) ?></td>
    </tr>
    <?php if ($paid > 0): ?>
        <tr>
            <td class="label">Paid</td>
            <td class="amount">−<?= e(dollars($paid)) ?></td>
        </tr>
        <tr class="balance">
            <td><?= $balance <= 0 ? 'Settled' : 'Balance due' ?></td>
            <td class="amount"><?= e(dollars(max(0, $balance))) ?></td>
        </tr>
    <?php endif; ?>
</table>

<?php if (!empty($inv['notes'])): ?>
    <div class="notes">
        <h2>Notes</h2>
        <div style="white-space: pre-line;"><?= e($inv['notes']) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($settings['payment_instructions'])): ?>
    <div class="pay-box">
        <h2>How to pay</h2>
        <div style="white-space: pre-line;"><?= e($settings['payment_instructions']) ?></div>
    </div>
<?php endif; ?>

<div class="footer">
    Thank you for your business.
</div>

</body>
</html>
