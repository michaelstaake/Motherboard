# Motherboard modules

Each subdirectory with an `index.php` that returns a definition array is a module.

Required keys: `min_motherboard_version`, `min_php_version`.

Optional keys: `name`, `description`, `version`, `default_enabled`, `settings` (bool), `icon`, `conflicts`, `boot`.

Enabled modules are stored in the `enabled_modules` setting. Admins manage them at `/module-manager`.
If `settings` is true, an enabled module can expose `/module-manager/{slug}/settings`.
