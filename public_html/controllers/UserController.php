<?php
require_once 'core/Controller.php';
require_once 'models/User.php';
require_once 'models/WorkOrder.php';

class UserController extends Controller {
    private $userModel;
    private $workOrderModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->workOrderModel = new WorkOrder();
    }
    
    public function index() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $page = max(1, intval($_GET['page'] ?? 1));
            $query = '?tab=users';
            if ($page > 1) {
                $query .= '&page=' . $page;
            }
            $this->redirect('/settings' . $query);
        }

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = PAGINATION_LIMIT;
        
        $totalCount = $this->userModel->countUsers();
        $totalPages = ceil($totalCount / $limit);

        // Validate page number
        if ($page > $totalPages && $totalCount > 0) {
            $this->redirect('/404');
        }

        $offset = ($page - 1) * $limit;
        $users = $this->userModel->getAllUsers($limit, $offset);
        
        $error = '';
        $message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                
                if (isset($_POST['create_user'])) {
                    $userData = [
                        'username' => $this->sanitizeInput($_POST['username']),
                        'name' => $this->sanitizeInput($_POST['name'] ?? ''),
                        'email' => $this->sanitizeInput($_POST['email']),
                        'password' => $_POST['password'],
                        'user_group' => $_POST['user_group']
                    ];
                    
                    // Validation
                    if (empty($userData['username']) || empty($userData['email']) || empty($userData['password'])) {
                        throw new Exception(t('users.required_fields'));
                    }
                    
                    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception(t('users.invalid_email'));
                    }
                    
                    if (strlen($userData['password']) < 8) {
                        throw new Exception(t('auth.password_short'));
                    }
                    
                    if (!in_array($userData['user_group'], ['Admin', 'Technician', 'Limited'])) {
                        throw new Exception(t('users.invalid_group'));
                    }
                    
                    // Check if username or email already exists
                    if ($this->userModel->findByUsername($userData['username'])) {
                        throw new Exception(t('users.username_exists'));
                    }
                    
                    if ($this->userModel->findByEmail($userData['email'])) {
                        throw new Exception(t('users.email_exists'));
                    }
                    
                    $userId = $this->userModel->createUser($userData);
                    $this->logger->log('user_created', "User {$userData['username']} created", $_SESSION['user_id']);
                    
                    $message = t('users.created');
                    
                    // Refresh users list
                    $users = $this->userModel->getAllUsers($limit, $offset);
                    
                } elseif (isset($_POST['is_active']) && isset($_POST['user_id'])) {
                    // Handle user activation/deactivation
                    $userId = intval($_POST['user_id']);
                    $isActive = intval($_POST['is_active']);
                    
                    $user = $this->userModel->findById($userId);
                    if (!$user) {
                        throw new Exception(t('users.not_found'));
                    }
                    
                    if ($user['id'] === $_SESSION['user_id']) {
                        throw new Exception(t('users.cannot_deactivate_own'));
                    }
                    
                    $this->userModel->updateUser($userId, ['is_active' => $isActive]);
                    $this->logger->log('user_status_changed', "User {$user['username']} " . ($isActive ? 'activated' : 'deactivated'), $_SESSION['user_id']);
                    
                    $message = t('users.status_updated');
                    
                    // Refresh users list
                    $users = $this->userModel->getAllUsers($limit, $offset);
                    
                } elseif (isset($_POST['delete_user'])) {
                    $userId = intval($_POST['user_id']);
                    $user = $this->userModel->findById($userId);
                    
                    if (!$user) {
                        throw new Exception(t('users.not_found'));
                    }
                    
                    if ($user['id'] === $_SESSION['user_id']) {
                        throw new Exception(t('users.cannot_delete_own'));
                    }
                    
                    // Check if user has work orders assigned
                    $assignedWorkOrders = $this->workOrderModel->getWorkOrdersByTechnician($userId);
                    if (!empty($assignedWorkOrders)) {
                        throw new Exception(t('users.cannot_delete_wo'));
                    }
                    
                    $this->userModel->deleteUser($userId);
                    $this->logger->log('user_deleted', "User {$user['username']} deleted", $_SESSION['user_id']);
                    
                    $message = t('users.deleted');
                    
                    // Refresh users list
                    $users = $this->userModel->getAllUsers($limit, $offset);
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $redirect = '/settings?tab=users';
        if ($page > 1) {
            $redirect .= '&page=' . $page;
        }
        if ($error) {
            $this->redirect($redirect . '&error=' . urlencode($error));
        }
        if ($message) {
            $this->redirect($redirect . '&msg=' . urlencode($message));
        }
        $this->redirect($redirect);
    }
    
    public function details($id) {
        $this->requireAuth();
        if ($_SESSION['user_group'] !== 'Admin' && (int) $_SESSION['user_id'] !== (int) $id) {
            $this->redirect('/403');
        }
        
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->redirect('/404');
        }
        
        // Get user's work orders
        $workOrders = $this->workOrderModel->getWorkOrdersByTechnician($id);
        
        $error = '';
        $message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_group'] === 'Admin') {
            try {
                $this->validateCSRF();
                
                // Handle password change separately
                if (isset($_POST['password_change'])) {
                    if (empty($_POST['new_password'])) {
                        throw new Exception(t('users.password_required'));
                    }
                    
                    if (strlen($_POST['new_password']) < 8) {
                        throw new Exception(t('auth.password_short'));
                    }
                    
                    $this->userModel->updatePassword($id, $_POST['new_password']);
                    $this->logger->log('user_password_changed', "Password changed for user {$user['username']}", $_SESSION['user_id']);
                    
                    $message = t('users.password_updated');
                } else {
                    // Handle regular user updates (excluding password)
                    $updateData = [
                        'name' => $this->sanitizeInput($_POST['name'] ?? ''),
                        'email' => $this->sanitizeInput($_POST['email']),
                        'user_group' => $_POST['user_group']
                    ];
                    
                    // Prevent admins from changing their own user group
                    if ($id == $_SESSION['user_id'] && $updateData['user_group'] !== $user['user_group']) {
                        throw new Exception(t('users.cannot_change_own_group'));
                    }
                    
                    if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception(t('users.invalid_email'));
                    }
                    
                    if (!in_array($updateData['user_group'], ['Admin', 'Technician', 'Limited'])) {
                        throw new Exception(t('users.invalid_group'));
                    }
                    
                    // Check if email already exists (excluding current user)
                    $existingUser = $this->userModel->findByEmail($updateData['email']);
                    if ($existingUser && $existingUser['id'] != $id) {
                        throw new Exception(t('users.email_exists'));
                    }
                    
                    $this->userModel->updateUser($id, $updateData);
                    $this->logger->log('user_updated', "User {$user['username']} updated", $_SESSION['user_id']);
                    
                    $message = t('users.updated');
                    $user = array_merge($user, $updateData);
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $this->view('users/view', [
            'user' => $user,
            'workOrders' => $workOrders,
            'error' => $error,
            'message' => $message,
            'csrf_token' => $this->generateCSRF(),
            'canEdit' => $_SESSION['user_group'] === 'Admin'
        ]);
    }
}
