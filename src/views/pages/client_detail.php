<?php
/** @var array $client */
/** @var array $palette */
/** @var int $monthMinutes */
/** @var int $monthEarnedCents */
/** @var int $monthBillable */
/** @var int $allTimeMinutes */
/** @var int $allTimeEarnedCents */
/** @var array $recentTime */
/** @var array $recentBillable */
?>
<a href="/clients" class="inline-flex items-center gap-1 text-cream/70 hover:text-cream text-sm mb-4">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
    All clients
</a>

<div class="flex items-center gap-3 mb-5">
    <span class="w-4 h-12 rounded-full" style="background:<?= e($client['color']) ?>"></span>
    <div>
        <h1 class="font-display text-2xl"><?= e($client['name']) ?></h1>
        <p class="text-cream/70 text-sm"><?= e(dollars((int) $client['hourly_rate_cents'])) ?>/hr<?= $client['archived'] ? ' · archived' : '' ?></p>
    </div>
</div>

<div class="grid grid-cols-2 gap-3 mb-6">
    <div class="card">
        <div class="text-xs uppercase tracking-wide text-navy-300">This month</div>
        <div class="font-display text-2xl"
             data-count-up="<?= $monthEarnedCents / 100 ?>"
             data-count-prefix="$"
             data-count-decimals="2">$<?= number_format($monthEarnedCents / 100, 2) ?></div>
        <div class="text-xs text-navy-300 mt-1">
            <?= e(format_minutes_as_hours($monthMinutes)) ?>
            <?php if ($monthBillable > 0): ?> · <?= e(dollars($monthBillable)) ?> billable<?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="text-xs uppercase tracking-wide text-navy-300">All time</div>
        <div class="font-display text-2xl"
             data-count-up="<?= $allTimeEarnedCents / 100 ?>"
             data-count-prefix="$"
             data-count-decimals="2">$<?= number_format($allTimeEarnedCents / 100, 2) ?></div>
        <div class="text-xs text-navy-300 mt-1"><?= e(format_minutes_as_hours($allTimeMinutes)) ?></div>
    </div>
</div>

<div class="card mb-6">
    <h2 class="font-display text-lg mb-3">Edit</h2>
    <form method="post" action="/clients/<?= (int) $client['id'] ?>" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="label !text-navy-500" for="name">Name</label>
            <input class="input" id="name" name="name" value="<?= e($client['name']) ?>" required>
        </div>
        <div>
            <label class="label !text-navy-500" for="hourly_rate">Hourly rate</label>
            <input class="input" id="hourly_rate" name="hourly_rate" value="<?= number_format(((int) $client['hourly_rate_cents']) / 100, 2, '.', '') ?>" required>
        </div>
        <div data-color-picker>
            <label class="label !text-navy-500">Color</label>
            <div class="flex items-center gap-2 flex-wrap">
                <span data-color-preview class="w-8 h-8 rounded-full border-2 border-navy-100" style="background:<?= e($client['color']) ?>"></span>
                <?php foreach ($palette as $hex): ?>
                    <button type="button" data-swatch="<?= e($hex) ?>"
                            class="w-8 h-8 rounded-full ring-offset-2 ring-offset-cream transition-all hover:scale-110"
                            style="background:<?= e($hex) ?>"></button>
                <?php endforeach; ?>
                <input type="hidden" name="color" value="<?= e($client['color']) ?>">
            </div>
        </div>
        <button class="btn-primary w-full" data-loading-label="Saving…" type="submit">Save changes</button>
    </form>

    <form method="post" action="/clients/<?= (int) $client['id'] ?>/archive" class="mt-3 pt-3 border-t border-navy-100">
        <?= csrf_field() ?>
        <button class="btn-danger w-full" type="submit">
            <?= $client['archived'] ? 'Restore client' : 'Archive client' ?>
        </button>
    </form>
</div>

<?php if (!empty($recentTime)): ?>
    <div class="card mb-6">
        <h2 class="font-display text-lg mb-3">Recent time</h2>
        <ul class="divide-y divide-navy-100">
            <?php foreach ($recentTime as $t): ?>
                <li class="py-2 flex justify-between text-sm">
                    <div>
                        <div class="font-semibold"><?= e(format_minutes_as_hours((int) $t['minutes'])) ?></div>
                        <?php if ($t['notes']): ?><div class="text-navy-300 text-xs"><?= e($t['notes']) ?></div><?php endif; ?>
                    </div>
                    <div class="text-navy-300"><?= e($t['entry_date']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($recentBillable)): ?>
    <div class="card">
        <h2 class="font-display text-lg mb-3">Recent billable expenses</h2>
        <ul class="divide-y divide-navy-100">
            <?php foreach ($recentBillable as $x): ?>
                <li class="py-2 flex justify-between text-sm">
                    <div>
                        <div class="font-semibold"><?= e(dollars((int) $x['amount_cents'])) ?></div>
                        <div class="text-navy-300 text-xs"><?= e($x['description']) ?></div>
                    </div>
                    <div class="text-navy-300"><?= e($x['expense_date']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
