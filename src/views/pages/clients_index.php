<?php
/** @var array $clients */
/** @var array $palette */
$active   = array_values(array_filter($clients, fn($c) => !$c['archived']));
$archived = array_values(array_filter($clients, fn($c) =>  $c['archived']));
?>
<div class="flex items-center justify-between mb-5">
    <h1 class="font-display text-2xl">Clients</h1>
    <a href="#new" class="btn-primary text-sm py-2">+ New client</a>
</div>

<?php if (empty($active) && empty($archived)): ?>
    <div class="card text-center py-10">
        <div class="font-display text-xl mb-1">No clients yet</div>
        <p class="text-navy-300 text-sm mb-5">Add your first one below to start tracking time.</p>
    </div>
<?php endif; ?>

<div class="space-y-3 mb-8">
    <?php foreach ($active as $c): ?>
        <a href="/clients/<?= (int) $c['id'] ?>" class="card flex items-center justify-between hover:shadow-glow transition-shadow">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-3 h-10 rounded-full flex-shrink-0" style="background: <?= e($c['color']) ?>"></span>
                <div class="min-w-0">
                    <div class="font-semibold truncate"><?= e($c['name']) ?></div>
                    <div class="text-sm text-navy-300"><?= e(dollars((int) $c['hourly_rate_cents'])) ?>/hr</div>
                </div>
            </div>
            <svg class="w-5 h-5 text-navy-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($archived)): ?>
    <details class="mb-8">
        <summary class="text-sm text-cream/70 cursor-pointer mb-3">Archived (<?= count($archived) ?>)</summary>
        <div class="space-y-3">
            <?php foreach ($archived as $c): ?>
                <a href="/clients/<?= (int) $c['id'] ?>" class="card flex items-center justify-between opacity-70 hover:opacity-100">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-10 rounded-full" style="background: <?= e($c['color']) ?>"></span>
                        <div>
                            <div class="font-semibold"><?= e($c['name']) ?></div>
                            <div class="text-sm text-navy-300"><?= e(dollars((int) $c['hourly_rate_cents'])) ?>/hr · archived</div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </details>
<?php endif; ?>

<div id="new" class="card">
    <h2 class="font-display text-xl mb-4">Add a client</h2>
    <form method="post" action="/clients" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="label !text-navy-500" for="name">Name</label>
            <input class="input" id="name" name="name" required maxlength="80" placeholder="e.g. The Smiths">
        </div>
        <div>
            <label class="label !text-navy-500" for="hourly_rate">Hourly rate</label>
            <input class="input" id="hourly_rate" name="hourly_rate" inputmode="decimal" required placeholder="50.00">
        </div>
        <div data-color-picker>
            <label class="label !text-navy-500">Color</label>
            <div class="flex items-center gap-2 flex-wrap">
                <span data-color-preview class="w-8 h-8 rounded-full border-2 border-navy-100" style="background:<?= e($palette[0]) ?>"></span>
                <?php foreach ($palette as $hex): ?>
                    <button type="button"
                            data-swatch="<?= e($hex) ?>"
                            class="w-8 h-8 rounded-full ring-offset-2 ring-offset-cream transition-all hover:scale-110"
                            style="background:<?= e($hex) ?>"
                            aria-label="Pick <?= e($hex) ?>"></button>
                <?php endforeach; ?>
                <input type="hidden" name="color" value="<?= e($palette[0]) ?>">
            </div>
        </div>
        <button class="btn-primary w-full" data-loading-label="Adding…" type="submit">Add client</button>
    </form>
</div>
