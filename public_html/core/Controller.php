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
    
    /**
     * Renders operator-authored rich text. strip_tags() keeps the attributes of any tag it
     * retains, so "<p onmouseover=...>" survives it; escape everything first, then restore
     * only the bare tags we intend to support.
     */
    public static function safeBasicHtml(?string $html, array $allowed = ['p', 'b', 'strong', 'i', 'em', 'br', 'ul', 'ol', 'li']): string {
        $escaped = htmlspecialchars((string) $html, ENT_QUOTES, 'UTF-8');

        foreach ($allowed as $tag) {
            $tag = preg_quote($tag, '/');
            $escaped = preg_replace('/&lt;' . $tag . '&gt;/i', '<' . $tag . '>', $escaped);
            $escaped = preg_replace('/&lt;\/' . $tag . '&gt;/i', '</' . $tag . '>', $escaped);
            $escaped = preg_replace('/&lt;' . $tag . '\s*\/&gt;/i', '<' . $tag . ' />', $escaped);
        }

        return $escaped;
    }

    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Re-read the account on every request: both the active flag and the group are
        // authorization inputs, and a session snapshot would let a demoted or deactivated
        // user keep their old rights until the session expired.
        require_once 'models/User.php';
        $userModel = new User();
        $user = $userModel->findById($_SESSION['user_id']);
        if (!$user || (int) $user['is_active'] !== 1) {
            // User has been deactivated, destroy session and redirect
            self::destroySession();
            header('HTTP/1.1 403 Forbidden');
            header('Location: ' . BASE_URL . '/403?reason=account_deactivated');
            exit;
        }

        // A password change rewrites the stored hash, so sessions minted against the old
        // one stop validating here. Sessions predating this check have no fingerprint and
        // are retired once, at upgrade.
        $fingerprint = hash('sha256', (string) $user['password']);
        if (!isset($_SESSION['auth_fingerprint']) || !hash_equals($fingerprint, (string) $_SESSION['auth_fingerprint'])) {
            self::destroySession();
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $_SESSION['user_group'] = $user['user_group'];
        $_SESSION['quick_nav_trigger_key'] = $user['quick_nav_trigger_key'] ?? '/';
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
        if (!in_array($_SESSION['user_group'], ['Admin', 'Technician'], true)) {
            header('Location: ' . BASE_URL . '/403');
            exit;
        }
    }
    
    /**
     * Flash banners live in the session for exactly one render, so success and error
     * text no longer has to ride along in the query string (and stick around in the
     * address bar, history, and any link the user copies).
     */
    protected function setFlash(string $text, string $type = 'message'): void {
        if ($text === '') {
            return;
        }
        $_SESSION['flash'][$type === 'error' ? 'error' : 'message'] = $text;
    }

    protected function redirectWithFlash(string $path, string $text, string $type = 'message'): void {
        $this->setFlash($text, $type);
        $this->redirect($path);
    }

    /**
     * Reads and clears the pending flash. Called once per render, from view()/viewPath().
     */
    public static function takeFlash(): array {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return [
            'message' => (string) ($flash['message'] ?? ''),
            'error' => (string) ($flash['error'] ?? ''),
        ];
    }

    /**
     * Gives every view a defined $message/$error, preferring what the controller already
     * produced on this request over anything left in the session.
     */
    private function withFlash(array $data): array {
        $flash = self::takeFlash();
        foreach (['message', 'error'] as $key) {
            $existing = (string) ($data[$key] ?? '');
            $data[$key] = $existing !== '' ? $existing : $flash[$key];
        }
        return $data;
    }

    protected function view($viewName, $data = []) {
        $companyName = $this->settingsModel->getSetting('company_name', APP_NAME);
        $data['companyName'] = !empty($companyName) ? $companyName : APP_NAME;
        $data['companyLogoUrl'] = $this->settingsModel->getSetting('company_logo_url', '');
        $data['locale'] = I18n::getInstance()->getLocale();
        $data = $this->withFlash($data);

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
        $data = $this->withFlash($data);

        $data = Hooks::applyFilters('view.data', $data, $viewName);
        Hooks::doAction('view.render.before', $viewName, $data);
        $this->renderViewFile($viewFile, $viewName, $data);
    }

    private function renderViewFile($viewFile, $viewName, $data) {
        extract($data, EXTR_SKIP);
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

    /**
     * Issues a fresh CSRF token. Called when the session crosses a privilege boundary, so
     * that a token captured before authentication is not still valid afterwards.
     */
    protected function rotateCSRF() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Fully tears down the session: contents, cookie, and server-side record. Mirrors the
     * idle-timeout path in index.php.
     */
    public static function destroySession() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
