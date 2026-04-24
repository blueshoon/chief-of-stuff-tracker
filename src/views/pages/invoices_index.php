<?php
/** @var string $selectedYm */
/** @var array $uninvoiced */
/** @var array $grouped */
/** @var array $months */

$monthLabel = (new DateTimeImmutable($selectedYm . '-01'))->format('F Y');
$uninvoicedTotal = array_sum(array_column($uninvoiced, 'total_cents'));
?>
<div class="flex items-center justify-between mb-5">
    <h1 class="font-display text-2xl">Invoices</h1>
    <a href="/settings" class="btn-ghost text-sm py-2 px-3">Settings</a>
</div>

<div class="card mb-6">
    <div class="flex items-baseline justify-between mb-3">
        <h2 class="font-display text-lg">Create invoices for</h2>
        <form method="get" action="/invoices" class="contents" data-no-loading="1">
            <select name="month" onchange="this.form.submit()"
                    class="bg-cream border border-navy-100 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-rust-400">
                <?php foreach ($months as $ym):
                    $label = (new DateTimeImmutable($ym . '-01'))->format('M Y');
                ?>
                    <option value="<?= e($ym) ?>" <?= $ym === $selectedYm ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($uninvoiced)): ?>
        <p class="text-sm text-navy-300 text-center py-4">
            Nothing un-invoiced for <?= e($monthLabel) ?>.
        </p>
    <?php else: ?>
        <form method="post" action="/invoices/create-for-month">
            <?= csrf_field() ?>
            <input type="hidden" name="month" value="<?= e($selectedYm) ?>">
            <ul class="divide-y divide-navy-100 mb-4">
                <?php foreach ($uninvoiced as $u): ?>
                    <li class="py-3 flex items-center gap-3">
                        <input type="checkbox" name="client_ids[]" value="<?= (int) $u['client_id'] ?>"
                               id="cli<?= (int) $u['client_id'] ?>"
                               class="w-5 h-5 accent-rust-500 cursor-pointer" checked>
                        <label for="cli<?= (int) $u['client_id'] ?>" class="flex-1 flex items-center justify-between cursor-pointer min-w-0">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?= e($u['client_color']) ?>"></span>
                                <span class="font-semibold truncate"><?= e($u['client_name']) ?></span>
                            </span>
                            <span class="text-right ml-2">
                                <span class="font-display"><?= e(dollars((int) $u['total_cents'])) ?></span>
                                <span class="block text-[10px] text-navy-300">
                                    <?= e(format_minutes_as_hours((int) $u['minutes'])) ?>
                                    <?php if ($u['billable_cents'] > 0): ?> + <?= e(dollars((int) $u['billable_cents'])) ?> bil<?php endif; ?>
                                </span>
                            </span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm">
                    <span class="text-navy-300">Total selectable:</span>
                    <span class="font-display text-lg"><?= e(dollars((int) $uninvoicedTotal)) ?></span>
                </div>
                <button class="btn-primary" data-loading-label="Creating…" type="submit">Create invoices</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if (empty($grouped)): ?>
    <div class="card text-center py-8">
        <p class="text-navy-300">No invoices yet. Use the section above to create your first batch.</p>
    </div>
<?php else: ?>
    <?php foreach ($grouped as $ym => $list):
        $label = (new DateTimeImmutable($ym . '-01'))->format('F Y');
    ?>
        <h2 class="font-display text-lg mb-3 mt-6 first:mt-0"><?= e($label) ?></h2>
        <ul class="space-y-2">
            <?php foreach ($list as $inv):
                $balance = (int) $inv['total_cents'] - (int) $inv['paid_cents'];
            ?>
                <li>
                    <a href="/invoices/<?= (int) $inv['id'] ?>" class="card flex items-center justify-between hover:shadow-glow transition-shadow gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-2 h-12 rounded-full flex-shrink-0" style="background:<?= e($inv['client_color']) ?>"></span>
                            <div class="min-w-0">
                                <div class="font-semibold truncate flex items-center gap-2">
                                    <?= e($inv['invoice_number']) ?>
                                    <span class="chip <?= e(invoice_status_chip_class($inv['status'])) ?>">
                                        <?= e(invoice_status_label($inv['status'])) ?>
                                    </span>
                                </div>
                                <div class="text-xs text-navy-300 truncate"><?= e($inv['client_name']) ?> · issued <?= e($inv['issue_date']) ?></div>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="font-display text-lg"><?= e(dollars((int) $inv['total_cents'])) ?></div>
                            <?php if ($balance > 0 && $inv['status'] !== 'void'): ?>
                                <div class="text-[10px] text-navy-300"><?= e(dollars($balance)) ?> due</div>
                            <?php elseif ((int) $inv['paid_cents'] > 0): ?>
                                <div class="text-[10px] text-navy-300"><?= e(dollars((int) $inv['paid_cents'])) ?> paid</div>
                            <?php endif; ?>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
<?php endif; ?>
