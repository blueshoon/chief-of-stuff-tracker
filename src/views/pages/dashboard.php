<?php
/** @var int $earnedMonth */
/** @var int $earnedFromTime */
/** @var int $billableMonth */
/** @var int $businessMonth */
/** @var int $netMonth */
/** @var int $hoursMonth */
/** @var array $byClient */
/** @var array $spark */
/** @var int $thisWeek */
/** @var int $lastWeek */
/** @var float $weekDelta */
/** @var string $mStart */
/** @var string $mEnd */
/** @var string $selectedYm */
/** @var string $currentYm */
/** @var bool $isCurrentMonth */
/** @var array $months */
/** @var string $prevYm */
/** @var string $nextYm */
/** @var bool $hasNextMonth */
/** @var array $uninvoiced */
/** @var int $uninvoicedTotal */

$monthName = (new DateTimeImmutable($mStart))->format('F Y');
$clientTotals  = array_column($byClient, 'total_cents');
$clientEarned  = array_column($byClient, 'earned_cents');
$clientBillable = array_column($byClient, 'billable_cents');
$totalEarnedFromTime = max(1, array_sum($clientEarned));
$totalBillableSum    = max(1, array_sum($clientBillable));
$maxClientEarned     = $clientEarned   ? max(1, max($clientEarned))   : 1;
$maxClientBillable   = $clientBillable ? max(1, max($clientBillable)) : 1;
$sparkCents = array_column($spark, 'cents');
$sparkMax = $sparkCents ? max(1, max($sparkCents)) : 1;

$clientsWithEarnings  = array_values(array_filter($byClient, fn($c) => $c['earned_cents']   > 0));
$clientsWithBillable  = array_values(array_filter($byClient, fn($c) => $c['billable_cents'] > 0));
?>

<div class="mb-1 text-cream/60 text-xs uppercase tracking-wider">Earnings</div>
<div class="flex items-center justify-between gap-3 mb-4">
    <h1 class="font-display text-3xl"><?= e($monthName) ?></h1>
    <div class="flex items-center gap-1">
        <a href="/dashboard?month=<?= e($prevYm) ?>"
           class="btn-ghost px-2 py-2 text-cream/70 hover:text-cream"
           aria-label="Previous month">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <form method="get" action="/dashboard" class="contents" data-no-loading="1">
            <select name="month" onchange="this.form.submit()"
                    class="bg-navy-600/70 text-cream border border-navy-400/40 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-rust-400">
                <?php foreach ($months as $ym):
                    $label = (new DateTimeImmutable($ym . '-01'))->format('M Y');
                    if ($ym === $currentYm) $label .= ' (current)';
                ?>
                    <option value="<?= e($ym) ?>" <?= $ym === $selectedYm ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="/dashboard?month=<?= e($nextYm) ?>"
           class="btn-ghost px-2 py-2 <?= $hasNextMonth ? 'text-cream/70 hover:text-cream' : 'text-cream/20 pointer-events-none' ?>"
           aria-label="Next month"
           <?= $hasNextMonth ? '' : 'tabindex="-1" aria-disabled="true"' ?>>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
        </a>
    </div>
</div>

<div class="card mb-3 text-center py-7">
    <div class="text-xs uppercase tracking-wider text-navy-300">
        <?= $isCurrentMonth ? 'Earned this month' : 'Earned in ' . e((new DateTimeImmutable($mStart))->format('M Y')) ?>
    </div>
    <div class="font-display text-5xl text-rust-500 my-1"
         data-count-up="<?= $earnedMonth / 100 ?>"
         data-count-prefix="$"
         data-count-decimals="2"
         data-count-duration="1.1">$<?= number_format($earnedMonth / 100, 2) ?></div>
    <div class="text-xs text-navy-300">
        <?= e(format_minutes_as_hours($hoursMonth)) ?> ·
        <?= e(dollars($earnedFromTime)) ?> hourly +
        <?= e(dollars($billableMonth)) ?> billable
    </div>
</div>

<div class="grid grid-cols-2 gap-3 mb-6">
    <div class="card text-center">
        <div class="text-xs uppercase tracking-wider text-navy-300">Hours</div>
        <div class="font-display text-2xl"
             data-count-up="<?= $hoursMonth / 60 ?>"
             data-count-decimals="1"
             data-count-suffix="h"><?= number_format($hoursMonth / 60, 1) ?>h</div>
    </div>
    <div class="card text-center">
        <div class="text-xs uppercase tracking-wider text-navy-300">Net</div>
        <div class="font-display text-2xl <?= $netMonth < 0 ? 'text-rust-700' : '' ?>"
             data-count-up="<?= $netMonth / 100 ?>"
             data-count-prefix="$"
             data-count-decimals="2">$<?= number_format($netMonth / 100, 2) ?></div>
        <div class="text-[10px] text-navy-300 mt-1">earned − <?= e(dollars($businessMonth)) ?> business</div>
    </div>
</div>

<div class="card mb-6">
    <div class="flex items-baseline justify-between mb-2">
        <h2 class="font-display text-lg">Last 14 days</h2>
        <?php if (abs($weekDelta) > 0.001): ?>
            <span class="chip <?= $weekDelta >= 0 ? 'bg-rust-100 text-rust-600' : 'bg-navy-100 text-navy-500' ?>">
                <?= $weekDelta >= 0 ? '▲' : '▼' ?> <?= number_format(abs($weekDelta) * 100, 0) ?>% wk-over-wk
            </span>
        <?php endif; ?>
    </div>
    <svg viewBox="0 0 280 80" class="w-full h-20" preserveAspectRatio="none">
        <line x1="140" y1="0" x2="140" y2="80" stroke="#9aa1c2" stroke-dasharray="2 3" stroke-width="0.5"/>
        <?php
        $w = 280; $h = 80; $bw = $w / count($spark);
        foreach ($spark as $i => $d):
            $bh = $sparkMax > 0 ? max(2, (int) round(($d['cents'] / $sparkMax) * ($h - 8))) : 2;
            $x = $i * $bw + 1;
            $y = $h - $bh;
            $isThisWeek = $i >= 7;
            $color = $isThisWeek ? '#864322' : '#cf8160';
        ?>
            <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $bw - 2 ?>" height="<?= $bh ?>" rx="2" fill="<?= $color ?>" opacity="<?= $isThisWeek ? 1 : 0.55 ?>">
                <title><?= e($d['date']) ?>: <?= e(dollars($d['cents'])) ?></title>
            </rect>
        <?php endforeach; ?>
    </svg>
    <div class="flex justify-between text-[10px] text-navy-300 mt-1">
        <span>Prior 7 days: <?= e(dollars($lastWeek)) ?></span>
        <span>Last 7 days: <?= e(dollars($thisWeek)) ?></span>
    </div>
</div>

<?php if (!empty($uninvoiced)): ?>
    <div class="card mb-6 border-l-4 border-rust-500">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="min-w-0">
                <h2 class="font-display text-lg">Ready to invoice</h2>
                <p class="text-xs text-navy-300 mt-0.5">
                    <?= count($uninvoiced) ?> client<?= count($uninvoiced) === 1 ? '' : 's' ?> ·
                    <?= e(dollars($uninvoicedTotal)) ?> un-invoiced for <?= e($monthName) ?>
                </p>
            </div>
            <a href="/invoices?month=<?= e($selectedYm) ?>"
               class="btn-primary text-sm py-2 flex-shrink-0">Create invoices →</a>
        </div>
        <ul class="divide-y divide-navy-100">
            <?php foreach ($uninvoiced as $u): ?>
                <li class="py-2 flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full" style="background:<?= e($u['client_color']) ?>"></span>
                        <span class="truncate"><?= e($u['client_name']) ?></span>
                    </span>
                    <span class="text-right">
                        <span class="font-display"><?= e(dollars((int) $u['total_cents'])) ?></span>
                        <span class="block text-[10px] text-navy-300">
                            <?= e(format_minutes_as_hours((int) $u['minutes'])) ?>
                            <?php if ($u['billable_cents'] > 0): ?> + <?= e(dollars((int) $u['billable_cents'])) ?> bil<?php endif; ?>
                        </span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($byClient)): ?>
    <div class="card mb-4">
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-lg">Earnings by client</h2>
            <span class="text-xs text-navy-300"><?= e(dollars($earnedFromTime)) ?> total</span>
        </div>
        <?php if (empty($clientsWithEarnings)): ?>
            <p class="text-sm text-navy-300 text-center py-4">No hours logged this month.</p>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($clientsWithEarnings as $c):
                    $widthPct = ($c['earned_cents'] / $maxClientEarned) * 100;
                    $sharePct = ($c['earned_cents'] / $totalEarnedFromTime) * 100;
                ?>
                    <li>
                        <div class="flex items-baseline justify-between mb-1.5">
                            <a href="/clients/<?= (int) $c['id'] ?>" class="font-semibold flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" style="background:<?= e($c['color']) ?>"></span>
                                <?= e($c['name']) ?>
                            </a>
                            <div class="text-right">
                                <div class="font-display"><?= e(dollars((int) $c['earned_cents'])) ?></div>
                                <div class="text-[10px] text-navy-300">
                                    <?= e(format_minutes_as_hours((int) $c['minutes'])) ?>
                                    · <?= number_format($sharePct, 0) ?>%
                                </div>
                            </div>
                        </div>
                        <div class="h-2 rounded-full bg-navy-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                 style="width: <?= number_format($widthPct, 1) ?>%; background:<?= e($c['color']) ?>"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-lg">Reimbursable expenses by client</h2>
            <span class="text-xs text-navy-300"><?= e(dollars($billableMonth)) ?> total</span>
        </div>
        <?php if (empty($clientsWithBillable)): ?>
            <p class="text-sm text-navy-300 text-center py-4">No reimbursable expenses this month.</p>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($clientsWithBillable as $c):
                    $widthPct = ($c['billable_cents'] / $maxClientBillable) * 100;
                    $sharePct = ($c['billable_cents'] / $totalBillableSum) * 100;
                ?>
                    <li>
                        <div class="flex items-baseline justify-between mb-1.5">
                            <a href="/clients/<?= (int) $c['id'] ?>" class="font-semibold flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" style="background:<?= e($c['color']) ?>"></span>
                                <?= e($c['name']) ?>
                            </a>
                            <div class="text-right">
                                <div class="font-display"><?= e(dollars((int) $c['billable_cents'])) ?></div>
                                <div class="text-[10px] text-navy-300"><?= number_format($sharePct, 0) ?>%</div>
                            </div>
                        </div>
                        <div class="h-2 rounded-full bg-navy-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                 style="width: <?= number_format($widthPct, 1) ?>%; background:<?= e($c['color']) ?>"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>
