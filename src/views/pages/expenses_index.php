<?php
/** @var array $clients */
/** @var array $billable */
/** @var array $business */
/** @var int $billableMonth */
/** @var int $businessMonth */
?>
<h1 class="font-display text-2xl mb-5">Expenses</h1>

<div class="flex gap-2 p-1 bg-navy-600/50 rounded-xl mb-5" data-tabs>
    <button class="tab-active" data-tab="billable">
        Billable
        <span class="block text-xs opacity-80 font-normal mt-0.5">MTD <?= e(dollars($billableMonth)) ?></span>
    </button>
    <button class="tab-inactive" data-tab="business">
        Business
        <span class="block text-xs opacity-80 font-normal mt-0.5">MTD <?= e(dollars($businessMonth)) ?></span>
    </button>
</div>

<!-- Billable panel -->
<section data-tab-panel="billable">
    <?php if (empty($clients)): ?>
        <div class="card text-center py-8">
            <p class="text-navy-300 mb-3">Add a client first to track billable expenses.</p>
            <a href="/clients" class="btn-primary inline-flex">Go to Clients</a>
        </div>
    <?php else: ?>
        <div class="card mb-5">
            <h2 class="font-display text-lg mb-3">Add billable expense</h2>
            <form method="post" action="/expenses/billable" class="space-y-3">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-3">
                    <select class="input" name="client_id" required>
                        <option value="">Client…</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="input" name="amount" inputmode="decimal" placeholder="$ amount" required>
                </div>
                <input class="input" name="description" placeholder="What was it?" required maxlength="200">
                <input class="input" type="date" name="expense_date" value="<?= e(today()) ?>" required>
                <button class="btn-primary w-full" data-loading-label="Adding…" type="submit">Add expense</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($billable)): ?>
        <div class="card text-center py-6">
            <p class="text-navy-300 text-sm">No billable expenses yet.</p>
        </div>
    <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($billable as $b): ?>
                <li class="card flex items-center justify-between gap-3 <?= $b['invoice_id'] ? 'opacity-80' : '' ?>">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-12 rounded-full flex-shrink-0" style="background:<?= e($b['client_color']) ?>"></span>
                        <div class="min-w-0">
                            <div class="font-semibold truncate flex items-center gap-2">
                                <?= e($b['description']) ?>
                                <?php if ($b['invoice_id']): ?>
                                    <a href="/invoices/<?= (int) $b['invoice_id'] ?>"
                                       class="chip bg-rust-100 text-rust-700 text-[10px] py-0.5 px-1.5">
                                        <?= e($b['invoice_number']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-navy-300"><?= e($b['client_name']) ?> · <?= e($b['expense_date']) ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="font-display text-lg"><?= e(dollars((int) $b['amount_cents'])) ?></div>
                        <?php if (!$b['invoice_id']): ?>
                            <form method="post" action="/expenses/billable/<?= (int) $b['id'] ?>/delete" onsubmit="return confirm('Delete this expense?')">
                                <?= csrf_field() ?>
                                <button class="btn-danger px-2 py-1" type="submit" aria-label="Delete">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="px-2 py-1 text-navy-200" title="Invoiced — delete the invoice first">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<!-- Business panel -->
<section data-tab-panel="business" hidden>
    <div class="card mb-5">
        <h2 class="font-display text-lg mb-3">Add business expense</h2>
        <form method="post" action="/expenses/business" class="space-y-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-3">
                <input class="input" name="amount" inputmode="decimal" placeholder="$ amount" required>
                <input class="input" type="date" name="expense_date" value="<?= e(today()) ?>" required>
            </div>
            <input class="input" name="description" placeholder="What was it?" required maxlength="200">
            <input class="input" name="category" placeholder="Category (optional, e.g. 'gas')" list="business-category-list">
            <datalist id="business-category-list">
                <?php
                $cats = db_all('SELECT DISTINCT category FROM business_expenses WHERE category IS NOT NULL ORDER BY category');
                foreach ($cats as $row): ?>
                    <option value="<?= e($row['category']) ?>">
                <?php endforeach; ?>
            </datalist>
            <button class="btn-primary w-full" data-loading-label="Adding…" type="submit">Add expense</button>
        </form>
    </div>

    <?php if (empty($business)): ?>
        <div class="card text-center py-6">
            <p class="text-navy-300 text-sm">No business expenses yet.</p>
        </div>
    <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($business as $b): ?>
                <li class="card flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold truncate"><?= e($b['description']) ?></div>
                        <div class="text-xs text-navy-300">
                            <?= e($b['expense_date']) ?>
                            <?php if (!empty($b['category'])): ?> · <span class="chip bg-navy-100 text-navy-500"><?= e($b['category']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="font-display text-lg"><?= e(dollars((int) $b['amount_cents'])) ?></div>
                        <form method="post" action="/expenses/business/<?= (int) $b['id'] ?>/delete" onsubmit="return confirm('Delete this expense?')">
                            <?= csrf_field() ?>
                            <button class="btn-danger px-2 py-1" type="submit" aria-label="Delete">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
