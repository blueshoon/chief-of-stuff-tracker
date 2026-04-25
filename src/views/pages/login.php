<?php
$next = $_GET['next'] ?? '/dashboard';
?>
<div class="min-h-[80vh] flex flex-col justify-center">
    <div class="text-center mb-8">
        <img src="/assets/images/chief-of-stuff-horizontal-white-text.svg"
             alt="Chief of Stuff"
             class="h-32 w-auto mx-auto mb-3">
        <p class="text-cream/70 text-sm">Welcome back, Mari.</p>
    </div>

    <form method="post" action="/login" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <div>
            <label class="label" for="username">Username</label>
            <input class="input" type="text" id="username" name="username" autocomplete="username" autofocus required>
        </div>

        <div>
            <label class="label" for="password">Password</label>
            <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn-primary w-full text-lg" data-loading-label="Signing in…">
            Sign in
        </button>
    </form>
</div>
