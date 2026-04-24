<?php
/** @var string $__page */
$user      = function_exists('current_user') ? current_user() : null;
$timer     = $user ? active_timer() : null;
$flashes   = flash_all();
$pageTitle = $pageTitle ?? 'Chief of Stuff';
$current   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#000042">
    <title><?= e($pageTitle) ?> · Chief of Stuff</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,700&display=swap">
    <link rel="stylesheet" href="/assets/app.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23000042'/><text x='50' y='66' font-family='Fraunces,Georgia,serif' font-size='58' font-weight='700' text-anchor='middle' fill='%23864322'>C</text></svg>">
</head>
<body class="pb-32">
    <div id="app" class="mx-auto max-w-2xl px-4 pt-6 sm:pt-10">
        <?php if ($user): ?>
            <header class="flex items-center justify-between mb-6">
                <a href="/dashboard" class="flex items-center gap-2.5 group">
                    <span class="inline-grid place-items-center w-9 h-9 rounded-xl bg-rust-500 text-cream font-display font-bold text-lg shadow-card group-hover:scale-105 transition-transform">C</span>
                    <span class="font-display text-xl tracking-tight">Chief of Stuff</span>
                </a>
                <form method="post" action="/logout">
                    <?= csrf_field() ?>
                    <button class="btn-ghost text-sm px-3 py-2" type="submit">Sign out</button>
                </form>
            </header>

            <?php if ($timer): ?>
                <a href="/time" class="block mb-5">
                    <div class="card bg-rust-500 text-cream flex items-center justify-between animate-slide-up">
                        <div class="flex items-center gap-3">
                            <span class="timer-pulse w-3 h-3 bg-cream rounded-full"></span>
                            <div>
                                <div class="text-xs uppercase tracking-wider opacity-80">Tracking</div>
                                <div class="font-semibold"><?= e($timer['client_name']) ?></div>
                            </div>
                        </div>
                        <div class="font-display text-2xl tabular-nums"
                             data-timer-elapsed
                             data-started="<?= e($timer['started_at']) ?>">
                            00:00:00
                        </div>
                    </div>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($flashes)): ?>
            <div class="space-y-2 mb-4" data-flash-region>
                <?php foreach ($flashes as $type => $msg): ?>
                    <div class="card animate-slide-up <?= $type === 'error' ? 'bg-rust-500 text-cream' : '' ?>">
                        <?= e($msg) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main class="animate-slide-up">
            <?php view_partial($__page, get_defined_vars()); ?>
        </main>
    </div>

    <?php if ($user): ?>
        <nav class="fixed bottom-0 inset-x-0 z-30 bg-navy-600/90 backdrop-blur border-t border-navy-400/30">
            <div class="mx-auto max-w-2xl grid grid-cols-5 px-1 pb-[env(safe-area-inset-bottom)]">
                <?php
                $tabs = [
                    ['/time',      'Time',      '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>'],
                    ['/expenses',  'Expenses',  '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M3 12h18M3 17h12"/></svg>'],
                    ['/clients',   'Clients',   '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
                    ['/invoices',  'Invoices',  '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>'],
                    ['/dashboard', 'Dashboard', '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>'],
                ];
                foreach ($tabs as [$href, $label, $icon]):
                    $active = str_starts_with($current, $href);
                ?>
                    <a href="<?= e($href) ?>"
                       class="flex flex-col items-center gap-1 py-3 transition-colors <?= $active ? 'text-rust-300' : 'text-cream/70 hover:text-cream' ?>">
                        <?= $icon ?>
                        <span class="text-[10px] font-semibold tracking-wide"><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>

    <div id="toast-region" class="fixed bottom-24 inset-x-0 flex flex-col items-center gap-2 px-4 pointer-events-none z-40"></div>

    <script src="https://cdn.jsdelivr.net/npm/motion@10.18.0/dist/motion.min.js"></script>
    <script src="/assets/app.js?v=1" defer></script>
</body>
</html>
