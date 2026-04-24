# Chief of Stuff Tracker

Time + expense tracking for Mari's personal-assistant business, **Chief of Stuff**.

PHP 8.1 + SQLite + vanilla JS + Tailwind. Single-user, mobile-first, hosted on a DigitalOcean droplet.

## Quick start

```bash
npm install
php scripts/migrate.php
php scripts/create_user.php       # create Mari's login
npm run build                     # build Tailwind CSS

# Then either:
php -S 127.0.0.1:8765 -t public public/index.php
# ...or use Laragon — point the vhost document root at `public/`.
```

For architecture, conventions, and gotchas, see [`CLAUDE.md`](CLAUDE.md).
