<?php
require_once 'core/ClientIp.php';

class Controller {
    protected $db;
    protected $logger;
    protected $settingsModel;
    
    public function __construct() {
        $this->db = new Database();
        $this->logger = new Logger();
        
        // Load settings model for global access
        require_once 'models/Settings.php';
        $this->settingsModel = new Settings();
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Check if user is still active
        require_once 'models/User.php';
        $userModel = new User();
        if (!$userModel->isUserActive($_SESSION['user_id'])) {
            // User has been deactivated, destroy session and redirect
            session_destroy();
            header('HTTP/1.1 403 Forbidden');
            header('Location: ' . BASE_URL . '/403?reason=account_deactivated');
            exit;
        }
    }
    
    protected function requireAdmin() {
        $this->requireAuth();
        if ($_SESSION['user_group'] !== 'Admin') {
            header('Location: ' . BASE_URL . '/403');
            exit;
        }
    }
    
    protected function requireTechnician() {
        $this->requireAuth();
        if (!in_array($_SESSION['user_group'], ['Admin', 'Technician'])) {
            header('Location: ' . BASE_URL . '/403');
            exit;
        }
    }
    
    protected function view($viewName, $data = []) {
        $companyName = $this->settingsModel->getSetting('company_name', APP_NAME);
        $data['companyName'] = !empty($companyName) ? $companyName : APP_NAME;
        $data['companyLogoUrl'] = $this->settingsModel->getSetting('company_logo_url', '');
        $data['locale'] = I18n::getInstance()->getLocale();

        $data = Hooks::applyFilters('view.data', $data, $viewName);
        Hooks::doAction('view.render.before', $viewName, $data);

        $viewFile = 'views/' . $viewName . '.php';
        $this->renderViewFile($viewFile, $viewName, $data);
    }

    protected function viewPath($viewFile, $data = [], $viewName = '') {
        $companyName = $this->settingsModel->getSetting('company_name', APP_NAME);
        $data['companyName'] = !empty($companyName) ? $companyName : APP_NAME;
        $data['companyLogoUrl'] = $this->settingsModel->getSetting('company_logo_url', '');
        $data['locale'] = I18n::getInstance()->getLocale();

        $data = Hooks::applyFilters('view.data', $data, $viewName);
        Hooks::doAction('view.render.before', $viewName, $data);
        $this->renderViewFile($viewFile, $viewName, $data);
    }

    private function renderViewFile($viewFile, $viewName, $data) {
        extract($data);
        if (file_exists($viewFile)) {
            require $viewFile;
            Hooks::doAction('view.render.after', $viewName, $data);
        } else {
            throw new Exception("View not found: " . $viewName);
        }
    }
    
    public static function sendRedirect(string $path): void {
        $url = BASE_URL . $path;
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }

        $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=' . $safe . '"></head><body>';
        echo '<p><a href="' . $safe . '">Continue</a></p>';
        echo '<script>location.replace(' . json_encode($url) . ');</script></body></html>';
        exit;
    }

    protected function redirect($path) {
        self::sendRedirect($path);
    }
    
    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function getClientIP() {
        return ClientIp::resolve();
    }
    
    protected function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    protected function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        if ($input === null) {
            return '';
        }
        return trim($input);
    }
    
    protected function validateCSRF(?string $token = null) {
        $token = $token ?? ($_POST['csrf_token'] ?? '');
        if (!isset($_SESSION['csrf_token']) ||
            !is_string($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception('CSRF token validation failed');
        }
    }
    
    protected function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
