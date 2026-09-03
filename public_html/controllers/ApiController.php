<?php
require_once 'core/Controller.php';
require_once 'models/Customer.php';
require_once 'models/User.php';

class ApiController extends Controller {
    private $customerModel;
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->customerModel = new Customer();
        $this->userModel = new User();
    }
    
    public function searchCustomers() {
        header('Content-Type: application/json');
        $this->requireTechnician();
        
        $query = trim((string) ($_GET['q'] ?? ''));
        
        if (strlen($query) < 2) {
            echo json_encode([]);
            return;
        }
        
        $customers = $this->customerModel->searchCustomers($query);
        echo json_encode($customers);
    }
    
    public function updateWorkOrderStatus() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        try {
            $this->requireTechnician();
            $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
            if (!str_starts_with($contentType, 'application/json')) {
                throw new Exception('Content-Type must be application/json');
            }
            $this->validateCSRF($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                throw new Exception('Invalid JSON body');
            }
            $workOrderId = filter_var($input['work_order_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $status = (string) ($input['status'] ?? '');
            $allowedStatuses = ['Open', 'In Progress', 'Awaiting Parts', 'Closed', 'Picked Up'];
            
            if (!$workOrderId || !in_array($status, $allowedStatuses, true)) {
                throw new Exception('Missing required parameters');
            }
            
            require_once 'models/WorkOrder.php';
            $workOrderModel = new WorkOrder();
            
            $updateData = ['status' => $status];
            if ($status === 'Closed') {
                $updateData['closed_at'] = date('Y-m-d H:i:s');
            }
            
            $result = $workOrderModel->updateWorkOrder($workOrderId, $updateData);
            
            if ($result) {
                $this->logger->log('work_order_status_updated', 
                    "Work order #{$workOrderId} status changed to {$status}", $_SESSION['user_id']);
                
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to update work order');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateQuickNavKey() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $this->requireAuth();
            $this->validateCSRF();

            $triggerKey = (string) ($_POST['trigger_key'] ?? '');
            $allowedKeys = ['/', '.', '-'];
            if (!in_array($triggerKey, $allowedKeys, true)) {
                throw new Exception('Invalid trigger key');
            }

            $this->userModel->updateUser($_SESSION['user_id'], ['quick_nav_trigger_key' => $triggerKey]);
            $_SESSION['quick_nav_trigger_key'] = $triggerKey;

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
