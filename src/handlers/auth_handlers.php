<?php
declare(strict_types=1);

function h_login_show(): void {
    if (current_user()) redirect('/dashboard');
    view('login', ['pageTitle' => 'Sign in']);
}

function h_login_submit(): void {
    csrf_check();
    $u = trim((string) ($_POST['username'] ?? ''));
    $p = (string) ($_POST['password'] ?? '');
    $next = (string) ($_POST['next'] ?? '/dashboard');
    if (!str_starts_with($next, '/')) $next = '/dashboard';

    if (login_attempt($u, $p)) {
        redirect($next);
    }
    redirect('/login?next=' . rawurlencode($next));
}

function h_logout(): void {
    csrf_check();
    logout();
    redirect('/login');
}
