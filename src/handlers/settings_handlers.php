<?php
declare(strict_types=1);

function h_settings_show(): void {
    require_login();
    $settings = business_settings();
    view('settings', [
        'pageTitle' => 'Settings',
        'settings'  => $settings,
    ]);
}

function h_settings_update(): void {
    require_login();
    csrf_check();

    $name  = trim((string) ($_POST['business_name'] ?? ''));
    if ($name === '') {
        flash('error', 'Business name is required.');
        redirect('/settings');
    }

    $contact = trim((string) ($_POST['contact_name'] ?? ''));
    $email   = trim((string) ($_POST['email'] ?? ''));
    $phone   = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $pay     = trim((string) ($_POST['payment_instructions'] ?? ''));

    db_q(
        "UPDATE business_settings
         SET business_name = ?, contact_name = ?, email = ?, phone = ?,
             address = ?, payment_instructions = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = 1",
        [$name,
         $contact !== '' ? $contact : null,
         $email   !== '' ? $email   : null,
         $phone   !== '' ? $phone   : null,
         $address !== '' ? $address : null,
         $pay     !== '' ? $pay     : null]
    );
    flash('success', 'Settings saved.');
    redirect('/settings');
}
