# CLAUDE.md

Guidance for Claude Code when working in `chief-of-stuff-tracker`.

## What this is

Single-user time + expense tracker for **Mari** (Chief of Stuff, a personal-assistant business). One login (just hers). Tracks billable hours per client, client-billable expenses she fronts, and her own business expenses. Dashboard shows MTD earnings, hours, net, and a per-client breakdown.

Runs on Roy's existing DigitalOcean droplet — that's the deploy target. Mobile-first because Mari logs entries from her phone between client visits.

## Stack — the unusual choices

- **No framework.** PHP 8.1+ with hand-rolled router/auth/CSRF in `src/`. Front controller is `public/index.php`; every URL flows through it. Don't add Laravel/Slim/etc. — the whole point is staying frictionless to deploy.
- **Composer is required for vendor only.** `public/index.php` requires `vendor/autoload.php` for `dompdf/dompdf` (PDF export). Our own code in `src/` is still loaded by manual `require` calls in `public/index.php` — the Composer autoloader does NOT include any of our files (no `psr-4` or `files` entries). If you're tempted to add one, don't — manual requires keep load order explicit and prevent function-redeclaration crashes.
- **SQLite via PDO**, file lives at `data/chief.sqlite` (gitignored). `db()` in `src/db.php` is a memoized singleton with `foreign_keys=ON`, `journal_mode=WAL`, `busy_timeout=5000`.
- **Vanilla JS only** in `public/assets/app.js`. No bundler, no React. Animations use **Motion One** loaded from CDN (search `motion@10.18.0` in `src/views/layout.php`).
- **Tailwind via npm** for the build only. `npm run build` (or `./build.sh`) compiles `tailwind/input.css` → `public/assets/app.css`. The output **IS committed** (Forge's old npm chokes on the build, so we ship the artifact). Rebuild + commit `public/assets/app.css` whenever you change markup classes or `tailwind/input.css`.
- **Money is integer cents** everywhere in the DB and PHP. Use `dollars($cents)` to render and `parse_dollars_to_cents($str)` to parse. Time is integer **minutes**; use `format_minutes_as_hours($mins)` and `parse_hours_to_minutes($str)` (handles both `2.5` and `2:30`).

## Local dev

PHP is **not** on bash PATH. Use the absolute path to Laragon's PHP:

```
/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe
```

(Version directory may bump — `ls /c/laragon/bin/php/` to discover.) Composer is at `/c/ProgramData/ComposerSetup/bin/composer` — call it with `PATH="/c/laragon/bin/php/.../:$PATH" composer ...` so it finds PHP.

```bash
# one-time
npm install
composer install
php scripts/migrate.php
php scripts/create_user.php   # interactive: prompts for username + password

# every dev session
npm run watch &                # rebuilds CSS on change
php -S 127.0.0.1:8765 -t public public/index.php
```

Or just visit `http://chief-of-stuff-tracker.test/` — Laragon auto-vhosts the `public/` dir if `Document Root` is left at default; if not, edit the vhost to point at `public/`.

## Routes & the request lifecycle

All routes are registered in `public/index.php` under `dispatch()`. The router (`src/router.php`) supports `{param}` placeholders (`/clients/{id}`). Handlers live in `src/handlers/*_handlers.php` and are named `h_*` (e.g. `h_clients_show`).

Every handler that mutates state must:
1. Call `require_login()` first.
2. Call `csrf_check()` on POST.
3. Use `redirect('/path')` (which `exit`s) after success/error rather than rendering inline.

Use `flash('success'|'error', $msg)` to set a one-shot message; `layout.php` renders any pending flashes at the top of the next page.

## Views

Pages render via `view($pageName, $data)` (in `src/helpers.php`). That includes `src/views/layout.php`, which wraps the page (header, timer chip if running, bottom nav on mobile, toast region) and then `view_partial($pageName, $data)`s the page file from `src/views/pages/`. Page files are plain PHP — `e($v)` for HTML escaping, `csrf_field()` for the CSRF input, `dollars()` / `format_minutes_as_hours()` for display formatting.

Tailwind color tokens that matter: `navy-500` (`#000042`, brand primary, app bg), `rust-500` (`#864322`, brand accent, primary buttons), `cream` (`#fbf7f2`, card bg). Component classes (`card`, `btn-primary`, `input`, `label`, `tab-active`/`tab-inactive`, `chip`, `timer-pulse`) are defined in `tailwind/input.css` — prefer those over re-deriving the same Tailwind chains in markup.

## Invoices, line items, and the "invoiced" lifecycle

Three tables drive billing:
- `invoices` — header (number, period, dates, status, snapshot totals).
- `invoice_line_items` — per-line snapshot (description / qty / unit_cents / amount_cents). Each line may link back to its source via `time_entry_id` or `billable_expense_id`.
- `invoice_payments` — payments recorded against an invoice (amount, date, method, reference, notes).

Both `time_entries` and `billable_expenses` carry an `invoice_id` FK. When `invoice_id IS NOT NULL`, that row appears with a chip in the time/expense lists and **delete is blocked at both the handler and the UI level**. Deleting an invoice un-invoices its sources (sets `invoice_id = NULL` on each linked row before cascading the line items).

The "Create invoices for [Month]" workflow lives at `/invoices?month=YYYY-MM`. It calls `create_invoice_for_client_month()` (in `src/invoices.php`) once per selected client. That function snapshots all un-invoiced time + billable rows in the month into line items — the snapshot is intentional, so editing a time entry later does NOT change a sent invoice. The link via `invoice_id` is purely for tracking.

Status flow: `draft → sent → paid` (or `void`). `maybe_mark_paid()` auto-promotes `sent` (and `draft`, defensively) to `paid` whenever `sum(payments) >= total_cents`. It never demotes — once paid, paid.

Invoice numbers are issued by `next_invoice_number($issueDate)` as `INV-YYYY-NNNN`, sequential per year. Editing the number is allowed (e.g. to match a client's PO format) but uniqueness is enforced — collisions get a flashed error.

`recompute_invoice_totals($id)` is the single source of truth for totals; call it after any line-item change. It also bumps `updated_at`.

## PDF generation

Invoices render via `src/views/invoice_print.php` — a **standalone HTML template** (no app chrome) with **inline CSS only** so it works in both the browser print path (`/invoices/{id}/print`) and the dompdf path (`/invoices/{id}/pdf`). Constraints:
- Use tables for layout (dompdf's flex/grid support is partial).
- Keep CSS in the `<style>` block at the top — no Tailwind classes here, dompdf doesn't process them.
- Brand colors are inline hex values, not CSS variables.
- The `.toolbar` div is hidden via `@media print` AND a `$forPdf` PHP guard so it doesn't appear in the PDF or printed output.

The font default is `DejaVu Sans` because it ships with dompdf and renders consistently. Don't switch to Inter/Fraunces in this template — those would require font registration.

## Business settings

`business_settings` is a single-row table (`CHECK (id = 1)`) edited at `/settings`. The values render on every invoice header. `business_settings()` in `src/handlers/invoices_handlers.php` returns the row (or a sane default if missing).

## Time entries vs the active timer

There are two tables:
- `time_entries` — every committed unit of work (manual entries OR completed timers).
- `active_timer` — `CHECK (id = 1)` so at most one row exists. When a timer stops, its row is converted into a `time_entries` row inside a transaction (see `h_timer_stop` in `src/handlers/time_handlers.php`).

`active_timer.started_at` is stored in **UTC** (`gmdate('Y-m-d H:i:s')`) so the JS in `app.js` can parse it as UTC and compute elapsed time correctly across timezones. `time_entries.entry_date` is the **local** date — that's the day Mari did the work, regardless of UTC rollover.

## Adding a new migration

1. Drop a file at `migrations/NNN_name.sql` (zero-padded version, lexicographic order matters).
2. Run `php scripts/migrate.php` — only unapplied versions are run, tracked in `schema_migrations`.
3. Each migration runs in a single transaction; if it fails, nothing is partially applied.

## Deploy notes (DigitalOcean)

Manual deploy via `git pull` on the droplet:
```bash
git pull
composer install --no-dev --optimize-autoloader
php scripts/migrate.php
# nginx/apache vhost should point at public/, with .htaccess (Apache) or
# a try_files rewrite (nginx) sending all unknown paths to /index.php
```

`vendor/` is gitignored — `composer install` is **required** on every deploy now (dompdf isn't optional anymore). Node is **not** installed on the deploy host; `public/assets/app.css` is built locally and committed.

The DB is one file (`data/chief.sqlite`). **Cron a nightly backup** — `cp data/chief.sqlite /var/backups/chief-$(date +%F).sqlite` is enough.

## Things that would otherwise waste a session

- **Two `csrf_check()` traps.** It calls `cos_start_session()` itself, so don't manually `session_start()` before — it's a no-op but signals confusion. Conversely, `current_user()`/`require_login()` also start the session lazily; you don't need to manage it.
- **Front controller does NOT autoload handlers.** If you add a new handler file under `src/handlers/`, also `require` it in `public/index.php`. Same for new helper files.
- **Don't add `files` or `psr-4` autoload entries to `composer.json`.** That would have Composer's autoloader pull in `src/*.php` on top of our manual `require`s and crash with `Cannot redeclare`. Composer is for vendor classes only.
- **`max(int, ...$emptyArray)` blows up under PHP 8** — guard for empty before spreading. Use `$arr ? max(1, max($arr)) : 1` or `max(array_merge([1], $arr))`.
- **Invoiced source rows are read-only by design.** A `time_entries` or `billable_expenses` row with non-NULL `invoice_id` is rejected by the delete handler — fix that by deleting the invoice first (which un-invoices the rows). Don't loosen this without thinking through accounting consequences.
- **Invoice PDFs use `src/views/invoice_print.php`, NOT the layout chain.** It's a self-contained HTML doc with inline CSS for dompdf. Tailwind classes won't render in the PDF.
- **`public/assets/app.css` is built, not source — but it IS committed.** Editing it directly will be wiped on next build. Edit `tailwind/input.css` (for `@layer` definitions) or the markup classes themselves, then `npm run build` and commit the regenerated file. Forge's deploy can't run npm reliably, so the artifact ships in git.
- **Tailwind `content` paths only scan `public/**/*.php` and `src/**/*.php`.** New view directories outside those globs will produce stripped CSS. Update `tailwind/tailwind.config.js` if you reorganize.
- **Login throttling state lives in `data/login_attempts.json`** (per-IP, 5 failures / 5 min window). It's gitignored. To unlock yourself during development, just delete the file.
- **`active_timer` table has a `CHECK (id = 1)` constraint.** Trying to insert a second timer row will fail at the SQL level — the `h_timer_start` handler guards against this with an `active_timer()` check first, but if you write a new path, respect the invariant.
- **Money columns end in `_cents` for a reason.** Never store dollars as floats. Always parse via `parse_dollars_to_cents()` and render via `dollars()`/`dollars_short()`.
- **Date filtering uses `expense_date`/`entry_date` (TEXT, `YYYY-MM-DD`).** SQLite compares them lexicographically, which works because of the ISO format. Don't try to use `DATE()` functions or store as integers.
