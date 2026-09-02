<?php
// Sample configuration for Motherboard
// Copy this to config.php and update with your settings

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'motherboard');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('BASE_URL', rtrim(getenv('BASE_URL') ?: 'https://example.com', '/'));
define('FORCE_HTTPS', filter_var(getenv('FORCE_HTTPS') ?: 'false', FILTER_VALIDATE_BOOLEAN));

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.example.com');
define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: 'noreply@example.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'password');
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@example.com');

// Generate a private random value of at least 32 characters and keep it stable.
// Changing this key makes encrypted settings and device passwords unreadable.
define('APP_ENCRYPTION_KEY', getenv('APP_ENCRYPTION_KEY') ?: 'replace-with-a-private-random-key-of-at-least-32-characters');

define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900);
define('PAGINATION_LIMIT', 10);

define('ROOT_PATH', dirname(__FILE__));
define('VENDOR_PATH', ROOT_PATH . '/vendors');
define('LANG_PATH', ROOT_PATH . '/lang');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('APP_NAME', 'Motherboard');

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Los_Angeles');

require_once ROOT_PATH . '/version.php';

// Errors are shown only when APP_DEBUG is explicitly set. The release channel is a
// distribution marker, not a debug switch: a beta build still must not print stack
// traces, DSNs, or filesystem paths to unauthenticated visitors.
ini_set('log_errors', 1);

if (!empty(getenv('APP_DEBUG'))) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}
