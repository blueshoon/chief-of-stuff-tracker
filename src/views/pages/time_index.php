<?php
/** @var array $clients */
/** @var ?array $timer */
/** @var array $entries */
?>
<div class="flex items-center justify-between mb-5">
    <h1 class="font-display text-2xl">Time</h1>
    <a href="#log" class="btn-primary text-sm py-2">+ Log time</a>
</div>

<?php if (empty($clients)): ?>
    <div class="card text-center py-8">
        <p class="text-navy-300 mb-3">Add a client first to start tracking time.</p>
        <a href="/clients" class="btn-primary inline-flex">Go to Clients</a>
    </div>
<?php else: ?>

    <div class="card mb-6">
        <h2 class="font-display text-lg mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-rust-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            Timer
        </h2>
        <?php if ($timer): ?>
            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-navy-300">Tracking</div>
                    <div class="font-semibold"><?= e($timer['client_name']) ?></div>
                </div>
                <div class="font-display text-3xl tabular-nums text-rust-500"
                     data-timer-elapsed
                     data-started="<?= e($timer['started_at']) ?>">
                    00:00:00
                </div>
            </div>
            <form method="post" action="/time/timer/stop">
                <?= csrf_field() ?>
                <button class="btn-primary w-full" data-loading-label="Stopping…" type="submit">Stop & log</button>
            </form>
        <?php else: ?>
            <form method="post" action="/time/timer/start" class="space-y-3">
                <?= csrf_field() ?>
                <select class="input" name="client_id" required>
                    <option value="">Pick a client…</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?> · <?= e(dollars((int) $c['hourly_rate_cents'])) ?>/hr</option>
                    <?php endforeach; ?>
                </select>
                <input class="input" name="notes" placeholder="Optional note (e.g. 'errands')">
                <button class="btn-primary w-full" data-loading-label="Starting…" type="submit">▶ Start timer</button>
            </form>
        <?php endif; ?>
    </div>

    <div id="log" class="card mb-6">
        <h2 class="font-display text-lg mb-3">Log past time</h2>
        <form method="post" action="/time" class="space-y-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-3">
                <select class="input" name="client_id" required>
                    <option value="">Client…</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="input" type="date" name="entry_date" value="<?= e(today()) ?>" required>
            </div>
            <input class="input" name="hours" inputmode="decimal" placeholder="Hours (e.g. 2.5 or 2:30)" required>
            <input class="input" name="notes" placeholder="Optional note">
            <button class="btn-primary w-full" data-loading-label="Saving…" type="submit">Save entry</button>
        </form>
    </div>

<?php endif; ?>

<?php if (!empty($entries)): ?>
    <h2 class="font-display text-lg mb-3">Recent entries</h2>
    <ul class="space-y-2">
        <?php foreach ($entries as $te): ?>
            <li class="card flex items-center justify-between gap-3 <?= $te['invoice_id'] ? 'opacity-80' : '' ?>">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-2 h-12 rounded-full flex-shrink-0" style="background:<?= e($te['client_color']) ?>"></span>
                    <div class="min-w-0">
                        <div class="font-semibold truncate flex items-center gap-2">
                            <?= e($te['client_name']) ?>
                            <?php if ($te['invoice_id']): ?>
                                <a href="/invoices/<?= (int) $te['invoice_id'] ?>"
                                   class="chip bg-rust-100 text-rust-700 text-[10px] py-0.5 px-1.5"
                                   title="On invoice <?= e($te['invoice_number']) ?>">
                                    <?= e($te['invoice_number']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-navy-300"><?= e($te['entry_date']) ?><?php if ($te['notes']): ?> · <?= e($te['notes']) ?><?php endif; ?></div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <div class="text-right">
                        <div class="font-display text-lg leading-none"><?= e(format_minutes_as_hours((int) $te['minutes'])) ?></div>
                        <div class="text-xs text-navy-300"><?= e(dollars((int) round(((int) $te['minutes']) / 60 * (int) $te['hourly_rate_cents']))) ?></div>
                    </div>
                    <?php if (!$te['invoice_id']): ?>
                        <form method="post" action="/time/<?= (int) $te['id'] ?>/delete" onsubmit="return confirm('Delete this entry?')">
                            <?= csrf_field() ?>
                            <button class="btn-danger px-2 py-1" type="submit" aria-label="Delete">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="px-2 py-1 text-navy-200" title="Invoiced — delete the invoice to edit this entry">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
