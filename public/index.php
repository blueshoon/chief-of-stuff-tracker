<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/csrf.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/router.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/invoices.php';

require __DIR__ . '/../src/handlers/auth_handlers.php';
require __DIR__ . '/../src/handlers/clients_handlers.php';
require __DIR__ . '/../src/handlers/time_handlers.php';
require __DIR__ . '/../src/handlers/expenses_handlers.php';
require __DIR__ . '/../src/handlers/dashboard_handlers.php';
require __DIR__ . '/../src/handlers/invoices_handlers.php';
require __DIR__ . '/../src/handlers/settings_handlers.php';

route('GET',  '/login',  'h_login_show');
route('POST', '/login',  'h_login_submit');
route('POST', '/logout', 'h_logout');

route('GET',  '/',                          fn() => redirect('/dashboard'));
route('GET',  '/dashboard',                 'h_dashboard');

route('GET',  '/clients',                   'h_clients_index');
route('POST', '/clients',                   'h_clients_create');
route('GET',  '/clients/{id}',              'h_clients_show');
route('POST', '/clients/{id}',              'h_clients_update');
route('POST', '/clients/{id}/archive',      'h_clients_archive');

route('GET',  '/time',                      'h_time_index');
route('POST', '/time',                      'h_time_create');
route('POST', '/time/{id}/delete',          'h_time_delete');
route('POST', '/time/timer/start',          'h_timer_start');
route('POST', '/time/timer/stop',           'h_timer_stop');

route('GET',  '/expenses',                  'h_expenses_index');
route('POST', '/expenses/billable',         'h_expenses_billable_create');
route('POST', '/expenses/business',         'h_expenses_business_create');
route('POST', '/expenses/{type}/{id}/delete', 'h_expenses_delete');

route('GET',  '/invoices',                              'h_invoices_index');
route('POST', '/invoices/create-for-month',             'h_invoices_create_for_month');
route('GET',  '/invoices/{id}',                         'h_invoice_show');
route('POST', '/invoices/{id}',                         'h_invoice_update');
route('POST', '/invoices/{id}/delete',                  'h_invoice_delete');
route('POST', '/invoices/{id}/status',                  'h_invoice_set_status');
route('POST', '/invoices/{id}/lines',                   'h_invoice_line_create');
route('POST', '/invoices/{id}/lines/{lineId}',          'h_invoice_line_update');
route('POST', '/invoices/{id}/lines/{lineId}/delete',   'h_invoice_line_delete');
route('POST', '/invoices/{id}/payments',                'h_invoice_payment_create');
route('POST', '/invoices/{id}/payments/{payId}/delete', 'h_invoice_payment_delete');
route('GET',  '/invoices/{id}/print',                   'h_invoice_print');
route('GET',  '/invoices/{id}/pdf',                     'h_invoice_pdf');

route('GET',  '/settings',                              'h_settings_show');
route('POST', '/settings',                              'h_settings_update');

dispatch();
