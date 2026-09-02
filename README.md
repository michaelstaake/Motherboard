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

### If you deploy behind nginx

The bundled `.htaccess` files are what keep the application source, the database schema, and
the uploaded attachments from being served directly. nginx ignores them, so add the
equivalent rules to your server block:

```nginx
# Never serve application source or the schema.
location ~ ^/(core|models|controllers|views|database|vendors|lang|modules)/ {
    deny all;
}

# Attachments are served by the app, never directly, and never executed.
location ^~ /attachments/ {
    deny all;
}

location = /config.php {
    deny all;
}

autoindex off;
```

## LAMP install

1. Copy `public_html` to your web root.
2. Copy `config.sample.php` to `config.php` and set database, `BASE_URL`, and SMTP values.
3. Confirm `lang/en-us.php` exists (required to install and run).
4. Open the site. The installer runs if the database has no tables.
5. After install, choose language under **Settings → Language**.

## Docker (development)

From `motherboard-app`:

```bash
docker compose up --build
```

- App: http://localhost:8080
- Mailhog UI: http://localhost:8025
- MySQL: localhost:3306 (`motherboard` / `motherboard`)

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
