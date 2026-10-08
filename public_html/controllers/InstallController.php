<?php
require_once 'core/Controller.php';

class InstallController extends Controller {
    private const INSTALL_LOCK = 'motherboard_install';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        $installationState = $this->db->installationState();
        if ($installationState === 'installed') {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }
        if ($installationState !== 'empty') {
            http_response_code($installationState === 'unavailable' ? 503 : 409);
            $this->view('install/incomplete', [
                'databaseUnavailable' => $installationState === 'unavailable',
            ]);
            return;
        }
        
        // Check system requirements
        $requirements = $this->checkSystemRequirements();
        if (!$requirements['meets_requirements']) {
            $this->view('install/requirements', [
                'requirements' => $requirements
            ]);
            return;
        }
        
        // Check if database connection works
        if (!$this->db->canConnect()) {
            $this->view('install/error', [
                'error' => t('install.db_fail')
            ]);
            return;
        }
        
        $error = '';
        $success = false;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lockAcquired = false;
            $installationStarted = false;
            $installationComplete = false;
            try {
                $this->validateCSRF();
                $lockAcquired = $this->db->acquireLock(self::INSTALL_LOCK, 0);
                if (!$lockAcquired) {
                    throw new Exception(t('install.already_running'));
                }
                if ($this->db->installationState() !== 'empty') {
                    throw new Exception(t('install.database_not_empty'));
                }
                
                $username = $this->sanitizeInput($_POST['username']);
                $email = $this->sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];
                
                // Validation
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception(t('install.all_required'));
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(t('install.invalid_email'));
                }
                
                if (strlen($password) < 8) {
                    throw new Exception(t('auth.password_short'));
                }
                
                if ($password !== $confirmPassword) {
                    throw new Exception(t('auth.password_mismatch'));
                }
                
                $installationStarted = true;
                $this->createTables();
                $this->createAdminAndSettings($username, $email, $password);
                $installationComplete = true;
                
                $success = true;
                $this->logger->log('system_installed', 'System installed successfully', null, $this->getClientIP());
                Hooks::doAction('install.complete');
                
            } catch (Throwable $e) {
                if ($installationStarted && !$installationComplete) {
                    $this->removeInterruptedInstallation();
                }
                $error = $e->getMessage();
            } finally {
                if ($lockAcquired) {
                    $this->db->releaseLock(self::INSTALL_LOCK);
                }
            }
        }
        
        $this->view('install/index', [
            'error' => $error,
            'success' => $success,
            'csrf_token' => $this->generateCSRF()
        ]);
    }
    
    // Override the parent view method to avoid loading settings during installation
    protected function view($viewName, $data = []) {
        // During installation, use default company name and no logo
        $data['companyName'] = APP_NAME;
        $data['companyLogoUrl'] = '';
        $data['locale'] = I18n::getInstance()->getLocale();
        
        extract($data, EXTR_SKIP);
        $viewFile = 'views/' . $viewName . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            http_response_code(404);
            require_once 'views/errors/404.php';
        }
    }
    
    private function createTables() {
        $sql = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(100) NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                user_group ENUM('Admin', 'Technician', 'Limited') NOT NULL DEFAULT 'Limited',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                reset_token VARCHAR(255) NULL,
                reset_expires DATETIME NULL,
                created_at DATETIME NOT NULL,
                last_login DATETIME NULL,
                quick_nav_trigger_key VARCHAR(1) NOT NULL DEFAULT '/',
                last_seen_version VARCHAR(32) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                company VARCHAR(100) NULL,
                email VARCHAR(100) NULL,
                phone VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS work_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                work_order_number VARCHAR(20) UNIQUE NOT NULL,
                customer_id INT NOT NULL,
                computer VARCHAR(100) NOT NULL,
                model VARCHAR(100) NULL,
                serial_number VARCHAR(100) NULL,
                imei VARCHAR(50) NULL,
                remarks TEXT NULL,
                accessories TEXT NULL,
                username VARCHAR(50) NULL,
                password TEXT NULL,
                description TEXT NOT NULL,
                resolution TEXT NULL,
                notes TEXT NULL,
                status ENUM('Open', 'In Progress', 'Awaiting Parts', 'Closed', 'Picked Up') NOT NULL DEFAULT 'Open',
                priority ENUM('Standard', 'Priority') NOT NULL DEFAULT 'Standard',
                assigned_to INT NULL,
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL,
                closed_at DATETIME NULL,
                FOREIGN KEY (customer_id) REFERENCES customers(id),
                FOREIGN KEY (assigned_to) REFERENCES users(id),
                FOREIGN KEY (created_by) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS work_order_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                work_order_id INT NOT NULL,
                user_id INT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS user_logins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                login_time DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                username VARCHAR(50) NULL,
                success BOOLEAN NOT NULL DEFAULT FALSE,
                attempted_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS two_factor_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                code VARCHAR(255) NOT NULL,
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                last_attempt_at DATETIME NULL,
                expires_at DATETIME NOT NULL,
                UNIQUE KEY unique_user (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS work_order_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                work_order_id INT NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                stored_path VARCHAR(255) NOT NULL,
                description TEXT NULL,
                mime_type VARCHAR(127) NULL,
                file_size INT NOT NULL DEFAULT 0,
                uploaded_by INT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS work_order_counters (
                counter_year SMALLINT UNSIGNED PRIMARY KEY,
                next_number INT UNSIGNED NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        foreach ($sql as $query) {
            $this->db->query($query);
        }
    }
    
    private function createAdminAndSettings(string $username, string $email, string $password): void {
        $pdo = $this->db->connect();
        $defaultSettings = [
            'company_name' => APP_NAME,
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_logo' => '',
            'work_order_disclaimer' => 'Set up your Disclaimer on the Settings page',
            'print_customer_signature' => '1',
            'print_technician_signature' => '1',
            'print_hide_notes' => '0',
            'print_hide_attachments' => '0',
            'print_condensed' => '0',
            'captcha_provider' => 'off',
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'language' => 'en-us',
            'print_language' => 'en-us',
            'phone_number_format' => 'default',
            'currency' => '$',
            'require_2fa' => '0',
            'attachment_destination' => 'local',
            'attachment_max_size_mb' => '10',
            'attachment_allowed_extensions' => 'png,jpg,pdf,md,txt',
            'update_notice_enabled' => '1',
            'schema_version' => (string) Schema::VERSION,
        ];

        $pdo->beginTransaction();
        try {
            $insertUser = $pdo->prepare(
                "INSERT INTO users (username, email, password, user_group, is_active, created_at)
                 VALUES (?, ?, ?, 'Admin', 1, ?)"
            );
            $insertUser->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                date('Y-m-d H:i:s'),
            ]);

            $insertSetting = $pdo->prepare(
                'INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, ?)'
            );
            $now = date('Y-m-d H:i:s');
            foreach ($defaultSettings as $key => $value) {
                $insertSetting->execute([$key, $value, $now, $now]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function removeInterruptedInstallation(): void {
        $pdo = $this->db->connect();
        $tables = [
            'work_order_attachments',
            'work_order_counters',
            'work_order_logs',
            'work_orders',
            'activity_logs',
            'user_logins',
            'login_attempts',
            'two_factor_codes',
            'customers',
            'users',
            'settings',
        ];

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($tables as $table) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
    
    private function checkSystemRequirements() {
        $requirements = [
            'php_version' => [
                'required' => '8.4.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.4.0', '>='),
                'name' => t('install.check.php_version')
            ],
            'web_server' => [
                'required' => t('install.web_required'),
                'current' => $this->getWebServer(),
                'status' => $this->isWebServerCompatible(),
                'name' => t('install.check.web_server')
            ],
            'pdo_mysql' => [
                'required' => t('install.pdo_required'),
                'current' => extension_loaded('pdo_mysql') ? t('install.available') : t('install.not_available'),
                'status' => extension_loaded('pdo_mysql'),
                'name' => t('install.check.pdo_mysql')
            ],
            'openssl' => [
                'required' => t('install.openssl_required'),
                'current' => extension_loaded('openssl') ? t('install.available') : t('install.not_available'),
                'status' => extension_loaded('openssl'),
                'name' => t('install.check.openssl')
            ],
            'mbstring' => [
                'required' => t('install.mbstring_required'),
                'current' => extension_loaded('mbstring') ? t('install.available') : t('install.not_available'),
                'status' => extension_loaded('mbstring'),
                'name' => t('install.check.mbstring')
            ],
            'mod_rewrite' => [
                'required' => t('install.rewrite_required'),
                'current' => $this->hasUrlRewriting() ? t('install.available') : t('install.not_available'),
                'status' => $this->hasUrlRewriting(),
                'name' => t('install.check.url_rewriting')
            ]
        ];
        
        $allPassed = true;
        foreach ($requirements as $req) {
            if (!$req['status']) {
                $allPassed = false;
                break;
            }
        }
        
        return [
            'meets_requirements' => $allPassed,
            'checks' => $requirements
        ];
    }
    
    private function getWebServer() {
        $server = $_SERVER['SERVER_SOFTWARE'] ?? t('common.unknown');
        
        if (stripos($server, 'apache') !== false) {
            return 'Apache ' . $this->extractVersion($server, 'apache');
        } elseif (stripos($server, 'litespeed') !== false) {
            return 'LiteSpeed ' . $this->extractVersion($server, 'litespeed');
        } elseif (stripos($server, 'nginx') !== false) {
            return 'Nginx ' . $this->extractVersion($server, 'nginx');
        } elseif (stripos($server, 'microsoft-iis') !== false) {
            return 'IIS ' . $this->extractVersion($server, 'microsoft-iis');
        }
        
        return $server;
    }
    
    private function isWebServerCompatible() {
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';
        
        // Check for Apache or LiteSpeed (both support .htaccess)
        if (stripos($server, 'apache') !== false || 
            stripos($server, 'litespeed') !== false) {
            return true;
        }
        
        // Also accept if mod_rewrite functionality is available
        return $this->hasUrlRewriting();
    }
    
    private function hasUrlRewriting() {
        // Check if mod_rewrite is loaded (Apache)
        if (function_exists('apache_get_modules')) {
            return in_array('mod_rewrite', apache_get_modules());
        }
        
        // Check if URL rewriting is working by testing a simple rewrite
        // This is a basic check - in production, .htaccess rules would be tested
        if (isset($_SERVER['REQUEST_URI']) && 
            strpos($_SERVER['REQUEST_URI'], 'index.php') === false) {
            return true;
        }
        
        // For LiteSpeed and other compatible servers
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';
        if (stripos($server, 'litespeed') !== false) {
            return true;
        }
        
        return false;
    }
    
    private function extractVersion($serverString, $serverName) {
        $pattern = '/' . preg_quote($serverName, '/') . '\/([0-9.]+)/i';
        if (preg_match($pattern, $serverString, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
