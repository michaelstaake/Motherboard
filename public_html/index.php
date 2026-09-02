<?php
require_once 'config.php';

ini_set('session.cache_limiter', '');
ini_set('session.use_strict_mode', '1');
$sessionSecure = FORCE_HTTPS
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $sessionSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (defined('FORCE_HTTPS') && FORCE_HTTPS) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    if (!$https) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

require_once 'version.php';
require_once 'core/I18n.php';
require_once 'core/Hooks.php';
require_once 'core/ModuleLoader.php';
require_once 'core/Router.php';
require_once 'core/Database.php';
require_once 'core/Schema.php';
require_once 'core/Controller.php';
require_once 'core/Model.php';
require_once 'core/Logger.php';
require_once 'models/Settings.php';

if (!is_file(LANG_PATH . '/en-us.php')) {
    http_response_code(500);
    echo 'Motherboard cannot start because lang/en-us.php is missing.';
    exit;
}

$i18n = I18n::getInstance();
$i18n->load('en-us', true);

$database = new Database();
$router = new Router();

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = parse_url(BASE_URL, PHP_URL_PATH);
if ($basePath) {
    $currentPath = str_replace($basePath, '', $currentPath);
}
$currentPath = '/' . ltrim($currentPath, '/');

$installed = $database->isInstalled();

if (!$installed && $currentPath !== '/install') {
    header('Location: ' . BASE_URL . '/install');
    exit;
}

if ($installed) {
    Schema::ensure($database);
    $settingsModel = new Settings();
    if (isset($_SESSION['user_id'])) {
        $timeoutMinutes = (int) $settingsModel->getSetting('session_timeout', max(5, (int) (SESSION_TIMEOUT / 60)));
        $timeoutSeconds = max(5, min(1440, $timeoutMinutes)) * 60;
        $lastActivity = (int) ($_SESSION['last_activity'] ?? time());

        if (time() - $lastActivity > $timeoutSeconds) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            if ($currentPath !== '/login') {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        } else {
            $_SESSION['last_activity'] = time();
        }
    }
    $locale = $settingsModel->getSetting('language', 'en-us');
    if ($locale && $locale !== 'en-us') {
        $i18n->load($locale);
    }
}

$moduleLoader = new ModuleLoader($version);
Hooks::doAction('app.boot', $router, $database);
$moduleLoader->loadAll($installed ? $settingsModel : null);
Hooks::doAction('app.ready', $router, $database, $moduleLoader);

require_once ROOT_PATH . '/models/WorkOrderAttachment.php';
if (WorkOrderAttachment::requestExceededPostLimit()) {
    $overflowAttachments = new WorkOrderAttachment();
    $overflowMessage = t('wo.attachment_too_large', [
        'size' => $overflowAttachments->formatSize($overflowAttachments->maxSizeBytes()),
    ]);
    $overflowPath = $currentPath;
    if (preg_match('#^(/work-orders/view/\d+)/attachments$#', $currentPath, $overflowMatch)) {
        $overflowPath = $overflowMatch[1];
    } elseif ($currentPath === '/work-orders/create') {
        $overflowPath = '/work-orders/create?step=4';
    }
    $overflowPath .= (str_contains($overflowPath, '?') ? '&' : '?') . 'error=' . urlencode($overflowMessage);
    Controller::sendRedirect($overflowPath);
}

$router->addRoute('/', 'HomeController', 'index');
$router->addRoute('/login', 'AuthController', 'login');
$router->addRoute('/logout', 'AuthController', 'logout');
$router->addRoute('/forgot-password', 'AuthController', 'forgotPassword');
$router->addRoute('/reset-password', 'AuthController', 'resetPassword');
$router->addRoute('/work-orders', 'WorkOrderController', 'index');
$router->addRoute('/work-orders/create', 'WorkOrderController', 'create');
$router->addRoute('/work-orders/create/attachments/{token}', 'WorkOrderController', 'downloadPendingAttachment');
$router->addRoute('/work-orders/view/{id}', 'WorkOrderController', 'details');
$router->addRoute('/work-orders/print/{id}', 'WorkOrderController', 'print');
$router->addRoute('/work-orders/submitted/{id}', 'WorkOrderController', 'submitted');
$router->addRoute('/work-orders/delete/{id}', 'WorkOrderController', 'delete');
$router->addRoute('/work-orders/view/{id}/attachments', 'WorkOrderController', 'uploadAttachment');
$router->addRoute('/work-orders/attachments/{id}/download', 'WorkOrderController', 'downloadAttachment');
$router->addRoute('/work-orders/attachments/{id}/update', 'WorkOrderController', 'updateAttachment');
$router->addRoute('/work-orders/attachments/{id}/delete', 'WorkOrderController', 'deleteAttachment');
$router->addRoute('/customers', 'CustomerController', 'index');
$router->addRoute('/customers/auto-merge', 'CustomerController', 'autoMerge');
$router->addRoute('/customers/view/{id}', 'CustomerController', 'details');
$router->addRoute('/customers/merge', 'CustomerController', 'merge');
$router->addRoute('/customers/delete/{id}', 'CustomerController', 'delete');
$router->addRoute('/users', 'UserController', 'index');
$router->addRoute('/users/view/{id}', 'UserController', 'details');
$router->addRoute('/settings', 'SettingsController', 'index');
$router->addRoute('/module-manager', 'ModulesController', 'index');
$router->addRoute('/module-manager/{slug}/settings', 'ModulesController', 'settings');
$router->addRoute('/logs', 'LogsController', 'index');
$router->addRoute('/install', 'InstallController', 'index');
$router->addRoute('/403', 'ErrorController', 'error403');
$router->addRoute('/404', 'ErrorController', 'error404');
$router->addRoute('/api/search-customers', 'ApiController', 'searchCustomers');
$router->addRoute('/api/work-order-status', 'ApiController', 'updateWorkOrderStatus');

Hooks::doAction('router.register', $router);

$router->dispatch();
