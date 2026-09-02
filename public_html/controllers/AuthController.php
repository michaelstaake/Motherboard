<?php
require_once 'core/Controller.php';
require_once 'models/User.php';
require_once 'models/Settings.php';

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }
    
    public function login() {
        // If already logged in, redirect to home
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        
        $error = '';
        $message = '';
        $requires2FA = false;
        $pendingUserId = $_SESSION['pending_2fa_user'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                $ip = $this->getClientIP();

                if (isset($_POST['two_factor_code'])) {
                    $verify = Hooks::applyFilters('auth.2fa.verify', [
                        'handled' => false,
                        'success' => false,
                        'error' => t('auth.invalid_code'),
                        'user_id' => $_SESSION['pending_2fa_user'] ?? null,
                        'code' => $this->sanitizeInput($_POST['two_factor_code']),
                        'ip' => $ip,
                    ]);

                    if (!empty($verify['handled']) && !empty($verify['success']) && !empty($verify['user_id'])) {
                        $this->completeLogin($verify['user_id'], $ip);
                        $this->redirect('/');
                    }

                    $error = $verify['error'] ?? t('auth.invalid_code');
                    $requires2FA = true;
                    $pendingUserId = $_SESSION['pending_2fa_user'] ?? null;
                } else {
                    $username = $this->sanitizeInput($_POST['username']);
                    $password = $_POST['password'];

                    $captchaError = Hooks::applyFilters('auth.captcha.verify', '', 'login', $_POST);
                    if ($captchaError) {
                        throw new Exception($captchaError);
                    }

                    $maxLoginAttempts = (int) $this->settingsModel->getSetting('max_login_attempts', MAX_LOGIN_ATTEMPTS);
                    $maxLoginAttempts = max(3, min(10, $maxLoginAttempts));
                    if ($this->userModel->getLoginAttempts($ip) >= $maxLoginAttempts) {
                        throw new Exception(t('auth.too_many'));
                    }
                    $user = $this->userModel->findByUsername($username);
                    
                    if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
                        $gate = Hooks::applyFilters('auth.login.after_credentials', [
                            'proceed' => true,
                            'requires_2fa' => false,
                            'message' => '',
                            'error' => '',
                        ], $user, $ip);

                        if (empty($gate['proceed'])) {
                            $requires2FA = !empty($gate['requires_2fa']);
                            $message = $gate['message'] ?? '';
                            $error = $gate['error'] ?? '';
                            $pendingUserId = $_SESSION['pending_2fa_user'] ?? $user['id'];
                        } else {
                            $this->completeLogin($user['id'], $ip);
                            $this->redirect('/');
                        }
                    } else {
                        $this->userModel->recordLoginAttempt($ip, $username, false);
                        $error = t('auth.invalid_credentials');
                    }
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $this->view('auth/login', [
            'error' => $error,
            'message' => $message ?? '',
            'requires2FA' => $requires2FA,
            'pendingUserId' => $pendingUserId,
            'captcha_html' => Hooks::applyFilters('auth.captcha.html', '', 'login'),
            'captcha_scripts' => Hooks::applyFilters('auth.captcha.scripts', '', 'login'),
            'csrf_token' => $this->generateCSRF()
        ]);
    }
    
    private function completeLogin($userId, $ip) {
        $user = $this->userModel->findById($userId);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_group'] = $user['user_group'];
        $_SESSION['last_activity'] = time();
        
        // Record login
        $this->userModel->recordLogin($userId, $ip);
        $this->userModel->recordLoginAttempt($ip, $user['username'], true);
        
        // Update last login
        $this->userModel->updateLastLogin($userId);
        
        // Log the login
        $this->logger->log('user_login', 'User logged in successfully', $userId, $ip);
        
        // Clean up old logs (older than 60 days)
        $this->logger->cleanupOldLogs(60);
        
        // Clean up pending 2FA
        Hooks::doAction('auth.login.after', $userId, $user);
        unset($_SESSION['pending_2fa_user']);
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logger->log('user_logout', 'User logged out', $_SESSION['user_id']);
            Hooks::doAction('auth.logout', $_SESSION['user_id']);
        }
        
        session_destroy();
        $this->redirect('/login');
    }
    
    public function forgotPassword() {
        $message = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                
                $captchaError = Hooks::applyFilters('auth.captcha.verify', '', 'forgot-password', $_POST);
                if ($captchaError) {
                    throw new Exception($captchaError);
                }

                $email = $this->sanitizeInput($_POST['email']);
                $user = $this->userModel->findByEmail($email);
                
                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $this->userModel->updateUser($user['id'], [
                        'reset_token' => $token,
                        'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
                    ]);
                    
                    $this->sendPasswordResetEmail($user['email'], $token);
                    $this->logger->log('password_reset_requested', 'Password reset requested', $user['id']);
                }
                
                // Always show success message for security
                $message = t('auth.reset_sent');
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $this->view('auth/forgot-password', [
            'message' => $message,
            'error' => $error,
            'captcha_html' => Hooks::applyFilters('auth.captcha.html', '', 'forgot-password'),
            'captcha_scripts' => Hooks::applyFilters('auth.captcha.scripts', '', 'forgot-password'),
            'csrf_token' => $this->generateCSRF()
        ]);
    }
    
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        $error = '';
        $message = '';
        
        if (!$token) {
            $this->redirect('/login');
        }
        
        $user = $this->userModel->findByResetToken($token);
        
        if (!$user) {
            $error = t('auth.invalid_token');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
            try {
                $this->validateCSRF();
                
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if (strlen($password) < 8) {
                    throw new Exception(t('auth.password_short'));
                }
                
                if ($password !== $confirmPassword) {
                    throw new Exception(t('auth.password_mismatch'));
                }
                
                $this->userModel->updatePassword($user['id'], $password);
                $this->userModel->updateUser($user['id'], [
                    'reset_token' => null,
                    'reset_expires' => null
                ]);
                
                $this->logger->log('password_reset_completed', 'Password reset completed', $user['id']);
                $message = t('auth.password_reset_ok');
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $this->view('auth/reset-password', [
            'token' => $token,
            'error' => $error,
            'message' => $message,
            'valid_token' => !empty($user),
            'csrf_token' => $this->generateCSRF()
        ]);
    }
    
    private function sendPasswordResetEmail($email, $token) {
        try {
            require_once ROOT_PATH . '/core/EmailSender.php';
            $companyName = $this->settingsModel->getSetting('company_name', APP_NAME);
            $companyName = !empty($companyName) ? $companyName : APP_NAME;
            $emailSender = new EmailSender($companyName);
            
            // Get user info for personalized email
            $user = $this->userModel->findByEmail($email);
            $username = $user ? $user['username'] : 'User';
            
            return $emailSender->sendPasswordReset($email, $token, $username);
        } catch (Exception $e) {
            error_log("Failed to send password reset email to $email: " . $e->getMessage());
            return false;
        }
    }
}
