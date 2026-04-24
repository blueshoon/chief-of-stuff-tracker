<?php
/** @var array $inv */
/** @var array $settings */

$periodLabel = (new DateTimeImmutable($inv['period_start']))->format('M j');
$periodLabel .= ' – ' . (new DateTimeImmutable($inv['period_end']))->format('M j, Y');
$balance = (int) $inv['balance_cents'];
?>
<a href="/invoices" class="inline-flex items-center gap-1 text-cream/70 hover:text-cream text-sm mb-4">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
    All invoices
</a>

<div class="flex items-start justify-between gap-3 mb-1">
    <h1 class="font-display text-2xl"><?= e($inv['invoice_number']) ?></h1>
    <span class="chip <?= e(invoice_status_chip_class($inv['status'])) ?>">
        <?= e(invoice_status_label($inv['status'])) ?>
    </span>
</div>
<p class="text-cream/70 text-sm mb-4">
    <a href="/clients/<?= (int) $inv['client_id'] ?>" class="hover:text-cream">
        <?= e($inv['client_name']) ?>
    </a>
    · <?= e($periodLabel) ?>
</p>

<div class="grid grid-cols-3 gap-2 mb-4">
    <a href="/invoices/<?= (int) $inv['id'] ?>/pdf"
       class="btn-primary text-sm py-2.5">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
        PDF
    </a>
    <a href="/invoices/<?= (int) $inv['id'] ?>/print" target="_blank"
       class="btn-ghost text-sm py-2.5 border border-cream/20">
        Preview
    </a>
    <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/delete"
          onsubmit="return confirm('Delete this invoice? Source time/expense rows will be unmarked.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn-danger text-sm py-2.5 w-full">Delete</button>
    </form>
</div>

<!-- Status controls -->
<div class="card mb-4">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs uppercase tracking-wide text-navy-300 mr-1">Status</span>
        <?php foreach (['draft', 'sent', 'paid', 'void'] as $s): ?>
            <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/status" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= e($s) ?>">
                <button type="submit"
                        class="chip border <?= $inv['status'] === $s ? 'border-rust-400 bg-rust-500 text-cream' : 'border-navy-100 text-navy-500 hover:bg-navy-100' ?>">
                    <?= e(invoice_status_label($s)) ?>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<!-- Header edit -->
<div class="card mb-4">
    <h2 class="font-display text-lg mb-3">Invoice details</h2>
    <form method="post" action="/invoices/<?= (int) $inv['id'] ?>" class="space-y-3">
        <?= csrf_field() ?>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label !text-navy-500" for="invoice_number">Number</label>
                <input class="input" id="invoice_number" name="invoice_number" value="<?= e($inv['invoice_number']) ?>" required>
            </div>
            <div>
                <label class="label !text-navy-500" for="issue_date">Issue date</label>
                <input class="input" type="date" id="issue_date" name="issue_date" value="<?= e($inv['issue_date']) ?>" required>
            </div>
        </div>
        <div>
            <label class="label !text-navy-500" for="due_date">Due date</label>
            <input class="input" type="date" id="due_date" name="due_date" value="<?= e($inv['due_date'] ?? '') ?>">
        </div>
        <div>
            <label class="label !text-navy-500" for="notes">Notes (shows on invoice)</label>
            <textarea class="input min-h-[5rem]" id="notes" name="notes"><?= e($inv['notes'] ?? '') ?></textarea>
        </div>
        <button class="btn-primary w-full" data-loading-label="Saving…" type="submit">Save details</button>
    </form>
</div>

<!-- Line items -->
<div class="card mb-4">
    <h2 class="font-display text-lg mb-3">Line items</h2>
    <?php if (empty($inv['lines'])): ?>
        <p class="text-sm text-navy-300 text-center py-4">No lines yet.</p>
    <?php else: ?>
        <ul class="space-y-3 mb-4">
            <?php foreach ($inv['lines'] as $line): ?>
                <li class="border border-navy-100 rounded-xl p-3">
                    <details>
                        <summary class="flex items-center justify-between gap-3 cursor-pointer">
                            <div class="min-w-0">
                                <div class="font-semibold truncate"><?= e($line['description']) ?></div>
                                <div class="text-xs text-navy-300">
                                    <?= e(ucfirst($line['kind'])) ?>
                                    <?php if ($line['line_date']): ?> · <?= e($line['line_date']) ?><?php endif; ?>
                                    <?php if ($line['kind'] === 'time'): ?>
                                        · <?= number_format((float) $line['quantity'], 2) ?>h × <?= e(dollars((int) $line['unit_cents'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="font-display text-lg flex-shrink-0"><?= e(dollars((int) $line['amount_cents'])) ?></div>
                        </summary>
                        <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/lines/<?= (int) $line['id'] ?>" class="space-y-3 mt-3 pt-3 border-t border-navy-100">
                            <?= csrf_field() ?>
                            <input class="input" name="description" value="<?= e($line['description']) ?>" required>
                            <div class="grid grid-cols-3 gap-2">
                                <input class="input" name="line_date" type="date" value="<?= e($line['line_date'] ?? '') ?>" placeholder="Date">
                                <input class="input" name="quantity" inputmode="decimal"
                                       value="<?= e(number_format((float) $line['quantity'], 2, '.', '')) ?>" placeholder="Qty">
                                <input class="input" name="unit" inputmode="decimal"
                                       value="<?= e(number_format(((int) $line['unit_cents']) / 100, 2, '.', '')) ?>" placeholder="Unit $">
                            </div>
                            <div class="flex gap-2">
                                <button class="btn-primary flex-1" data-loading-label="Saving…" type="submit">Save</button>
                                <button class="btn-danger px-3" type="submit"
                                        formaction="/invoices/<?= (int) $inv['id'] ?>/lines/<?= (int) $line['id'] ?>/delete"
                                        formnovalidate
                                        onclick="return confirm('Delete this line?')">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                </button>
                            </div>
                        </form>
                    </details>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <details class="border-t border-navy-100 pt-3">
        <summary class="cursor-pointer text-sm font-semibold text-rust-500">+ Add a line</summary>
        <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/lines" class="space-y-3 mt-3">
            <?= csrf_field() ?>
            <select name="kind" class="input">
                <option value="expense">Expense / one-off</option>
                <option value="time">Time (hours × rate)</option>
            </select>
            <input class="input" name="description" placeholder="Description" required>
            <div class="grid grid-cols-3 gap-2">
                <input class="input" name="line_date" type="date" placeholder="Date">
                <input class="input" name="quantity" inputmode="decimal" value="1" placeholder="Qty">
                <input class="input" name="unit" inputmode="decimal" placeholder="Unit $">
            </div>
            <button class="btn-primary w-full" data-loading-label="Adding…" type="submit">Add line</button>
        </form>
    </details>
</div>

<!-- Totals -->
<div class="card mb-4">
    <dl class="space-y-1.5 text-sm">
        <div class="flex justify-between">
            <dt class="text-navy-300">Hours (<?= e(format_minutes_as_hours((int) $inv['hours_minutes_total'])) ?>)</dt>
            <dd class="font-semibold"><?= e(dollars((int) $inv['hours_amount_cents'])) ?></dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-navy-300">Reimbursable expenses</dt>
            <dd class="font-semibold"><?= e(dollars((int) $inv['expenses_amount_cents'])) ?></dd>
        </div>
        <div class="flex justify-between pt-2 border-t border-navy-100">
            <dt class="font-semibold">Total</dt>
            <dd class="font-display text-xl"><?= e(dollars((int) $inv['total_cents'])) ?></dd>
        </div>
        <?php if ((int) $inv['paid_cents'] > 0): ?>
            <div class="flex justify-between">
                <dt class="text-navy-300">Paid</dt>
                <dd class="font-semibold">−<?= e(dollars((int) $inv['paid_cents'])) ?></dd>
            </div>
            <div class="flex justify-between pt-2 border-t border-navy-100">
                <dt class="font-semibold"><?= $balance <= 0 ? 'Settled' : 'Balance due' ?></dt>
                <dd class="font-display text-xl <?= $balance <= 0 ? 'text-rust-500' : '' ?>">
                    <?= e(dollars(max(0, $balance))) ?>
                </dd>
            </div>
        <?php endif; ?>
    </dl>
</div>

<!-- Payments -->
<div class="card">
    <h2 class="font-display text-lg mb-3">Payments</h2>
    <?php if (empty($inv['payments'])): ?>
        <p class="text-sm text-navy-300 text-center py-2 mb-3">No payments recorded.</p>
    <?php else: ?>
        <ul class="divide-y divide-navy-100 mb-4">
            <?php foreach ($inv['payments'] as $p): ?>
                <li class="py-2 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-semibold"><?= e(dollars((int) $p['amount_cents'])) ?></div>
                        <div class="text-xs text-navy-300 truncate">
                            <?= e($p['payment_date']) ?>
                            <?php if ($p['method']): ?> · <?= e($p['method']) ?><?php endif; ?>
                            <?php if ($p['reference']): ?> · <?= e($p['reference']) ?><?php endif; ?>
                        </div>
                        <?php if ($p['notes']): ?>
                            <div class="text-xs text-navy-400 italic mt-0.5"><?= e($p['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/payments/<?= (int) $p['id'] ?>/delete"
                          onsubmit="return confirm('Remove this payment?')">
                        <?= csrf_field() ?>
                        <button class="btn-danger px-2 py-1" type="submit" aria-label="Delete">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <details<?= empty($inv['payments']) ? ' open' : '' ?> class="border-t border-navy-100 pt-3">
        <summary class="cursor-pointer text-sm font-semibold text-rust-500">+ Record a payment</summary>
        <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/payments" class="space-y-3 mt-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-3">
                <input class="input" name="amount" inputmode="decimal" placeholder="$ amount"
                       value="<?= $balance > 0 ? e(number_format($balance / 100, 2, '.', '')) : '' ?>" required>
                <input class="input" type="date" name="payment_date" value="<?= e(today()) ?>" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input class="input" name="method" placeholder="Method (Venmo, check…)">
                <input class="input" name="reference" placeholder="Reference (check #)">
            </div>
            <input class="input" name="notes" placeholder="Notes (optional)">
            <button class="btn-primary w-full" data-loading-label="Recording…" type="submit">Record payment</button>
        </form>
    </details>
</div>
