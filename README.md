# Motherboard

Motherboard is a work order and customer management app for computer repair shops. It is a fork of LibreWO with a module system and localization.

## Features

- Work order tracking, from intake through assignment, status changes, and completion
- Customer management
- User accounts with role-based permissions (Admin / standard / Limited)
- Activity logs for auditing changes across the app
- Optional modules (e.g. inventory) that can be dropped in and enabled per install
- Multi-language support via self-contained language files
- Keyboard-driven navigation with a command palette (`Alt + /`)

## Requirements

- PHP 8.4+
- Apache (or compatible) with `mod_rewrite`
- MySQL 8 / MariaDB
- PDO MySQL and OpenSSL

If you deploy behind nginx instead of Apache, see [documentation/using-nginx.md](documentation/using-nginx.md).

## LAMP install

1. Copy `public_html` to your web root.
2. Copy `config.sample.php` to `config.php` and set database, `BASE_URL`, and SMTP values.
3. Confirm `lang/en-us.php` exists (required to install and run).
4. Open the site. The installer runs if the database has no tables.
5. After install, choose language under **Settings → Language**.

## Docker (development)

Clone the repo (or pull the latest changes if you already have it):

```bash
git clone https://github.com/michaelstaake/Motherboard.git motherboard-app
cd motherboard-app
```

```bash
git pull
```

Then build and start the stack:

```bash
docker compose up --build
```

- App: http://localhost:8080
- Mailhog UI: http://localhost:8025
- phpMyAdmin: http://localhost:8081 (logs in as `motherboard` / `motherboard`)
- MySQL: localhost:3306 (`motherboard` / `motherboard`)

Every port binds to `127.0.0.1`, so the dev stack is reachable only from this
machine. It also ships a shared encryption key and leaves debug output on — do
not use it to serve a real shop. See below.

## Docker (production)

For running Motherboard on a machine in your shop, reachable from the LAN.

```bash
git clone https://github.com/michaelstaake/Motherboard.git motherboard-app
cd motherboard-app
cp .env.example .env
chmod 600 .env
```

Generate an encryption key and paste it into `.env` as `APP_ENCRYPTION_KEY`:

```bash
docker run --rm php:8.4-cli php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Keep this key forever and back it up alongside your database — it encrypts
customer device passwords, and losing or changing it makes them unreadable.

Set `BASE_URL` in `.env` to this machine's LAN address (for example
`http://192.168.1.50:8080`), fill in `DB_PASS`, `DB_ROOT_PASS` and your SMTP
details, then start the stack:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

- App: the `BASE_URL` you set
- phpMyAdmin: port 8081, behind its own login form. Set `PMA_BIND=127.0.0.1`
  in `.env` to restrict it to that machine, or start with
  `--scale phpmyadmin=0` to leave it off
- MySQL: not published — use phpMyAdmin or `docker compose exec db`

**Open the app and complete the installer right away.** Until the first Admin
account exists, anyone who can reach the app can create it.

Do not rename or copy `docker-compose.prod.yml` over `docker-compose.yml` —
that blocks `git pull`. Always pass `-f docker-compose.prod.yml`.

To update: back up, then `git pull`. Code changes apply within seconds. Re-run
`docker compose -f docker-compose.prod.yml up -d --build` if `Dockerfile` or
anything in `docker/` changed.

See [documentation/docker-production.md](documentation/docker-production.md) for
backups, firewall rules, HTTPS and reverse proxies, and troubleshooting.

## Modules

Drop each module in `public_html/modules/<slug>/` with an `index.php`. See `documentation/module-development.md`.

## Languages

Self-contained PHP arrays in `public_html/lang/`:

- `en-us.php` (required)
- `es-mx.php`
- `cs-cz.php`
- `de-de.php`
- `fr-fr.php`
- `pup.php` (fictional)

Add more files using the same keys as `en-us.php`.
