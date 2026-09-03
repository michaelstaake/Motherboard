<?php
require_once 'core/Controller.php';
require_once 'models/Customer.php';
require_once 'models/WorkOrder.php';

class CustomerController extends Controller {
    private $customerModel;
    private $workOrderModel;
    
    public function __construct() {
        parent::__construct();
        $this->customerModel = new Customer();
        $this->workOrderModel = new WorkOrder();
    }
    
    public function index() {
        $this->requireAdmin();
        
        $error = '';
        $message = '';
        
        // Handle POST request for creating new customer
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                
                $customerData = [
                    'name' => $this->sanitizeInput($_POST['name']),
                    'company' => $this->sanitizeInput($_POST['company']),
                    'email' => $this->sanitizeInput($_POST['email']),
                    'phone' => $this->sanitizeInput($_POST['phone'])
                ];
                
                // Validate required fields
                if (empty($customerData['name'])) {
                    throw new Exception(t('customers.name_required'));
                }
                
                if (empty($customerData['phone'])) {
                    throw new Exception(t('customers.phone_required'));
                }
                
                // Validate email if provided
                if ($customerData['email'] && !filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(t('customers.invalid_email'));
                }
                
                // Create the customer
                $customerId = $this->customerModel->createCustomer($customerData);
                Hooks::doAction('customer.create.after', $customerId, $customerData);
                
                // Log the creation
                $this->logger->log('customer_created', "Customer '{$customerData['name']}' created", $_SESSION['user_id']);
                
                // Redirect to prevent resubmission
                $this->redirectWithFlash('/customers', t('customers.created'));
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = PAGINATION_LIMIT;
        
        $totalCount = $this->customerModel->getCustomerCount($search);
        $totalPages = ceil($totalCount / $limit);

        // Validate page number
        if ($page > $totalPages && $totalCount > 0) {
            $this->redirect('/404');
        }

        $offset = ($page - 1) * $limit;
        $customers = $this->customerModel->getAllCustomers($limit, $offset, $search);
        
        $this->view('customers/index', [
            'customers' => $customers,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'error' => $error,
            'message' => $message,
            'csrf_token' => $this->generateCSRF()
        ]);
    }
    
    public function details($id) {
        $this->requireAuth();
        
        $customer = $this->customerModel->getCustomerWithWorkOrders($id);
        if (!$customer) {
            $this->redirect('/404');
        }
        
        $error = '';
        $message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['user_group'], ['Admin', 'Technician'])) {
            try {
                $this->validateCSRF();
                
                $updateData = [
                    'name' => $this->sanitizeInput($_POST['name']),
                    'company' => $this->sanitizeInput($_POST['company']),
                    'email' => $this->sanitizeInput($_POST['email']),
                    'phone' => $this->sanitizeInput($_POST['phone'])
                ];
                
                if (empty($updateData['name']) || empty($updateData['phone'])) {
                    throw new Exception(t('customers.name_phone_required'));
                }
                
                if ($updateData['email'] && !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(t('customers.invalid_email'));
                }
                
                $this->customerModel->updateCustomer($id, $updateData);
                Hooks::doAction('customer.update.after', $id, $updateData);
                $this->logger->log('customer_updated', "Customer {$updateData['name']} updated", $_SESSION['user_id']);
                
                $message = t('customers.updated');
                $customer = array_merge($customer, $updateData);
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $this->view('customers/view', [
            'customer' => $customer,
            'workOrders' => $customer['work_orders'] ?? [],
            'error' => $error,
            'message' => $message,
            'csrf_token' => $this->generateCSRF(),
            'canEdit' => in_array($_SESSION['user_group'], ['Admin', 'Technician'])
        ]);
    }
    
    public function merge() {
        $this->requireAdmin();
        
        $error = '';
        $message = '';
        $step = intval($_GET['step'] ?? 1);
        $sourceId = intval($_GET['source'] ?? $_POST['source_customer'] ?? 0);
        $destinationId = intval($_POST['destination_customer'] ?? 0);
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                
                if (isset($_POST['confirm_merge'])) {
                    // Step 3: Perform the merge
                    $sourceId = intval($_POST['source_customer']);
                    $destinationId = intval($_POST['destination_customer']);
                    
                    if ($sourceId === $destinationId) {
                        throw new Exception(t('customers.merge_self'));
                    }
                    
                    $sourceCustomer = $this->customerModel->findById($sourceId);
                    $destinationCustomer = $this->customerModel->findById($destinationId);
                    
                    if (!$sourceCustomer || !$destinationCustomer) {
                        throw new Exception(t('customers.merge_not_found'));
                    }
                    
                    // Perform the merge
                    $this->customerModel->mergeCustomers($destinationId, $sourceId);
                    $this->logger->log('customers_merged', 
                        "Merged customer '{$sourceCustomer['name']}' into '{$destinationCustomer['name']}'", $_SESSION['user_id']);
                    
                    $this->redirectWithFlash('/customers/view/' . $destinationId, t('customers.merged'));
                    return;
                }
                
                // Step navigation
                if (isset($_POST['next_step'])) {
                    $step = intval($_POST['next_step']);
                    if ($step === 2 && !$sourceId) {
                        throw new Exception(t('customers.select_source'));
                    }
                    if ($step === 3 && (!$sourceId || !$destinationId)) {
                        throw new Exception(t('customers.select_both'));
                    }
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        // Get customers for dropdowns
        $customers = $this->customerModel->getAllCustomers();
        
        // Get specific customer details if IDs are set
        $sourceCustomer = $sourceId ? $this->customerModel->getCustomerWithWorkOrders($sourceId) : null;
        $destinationCustomer = $destinationId ? $this->customerModel->getCustomerWithWorkOrders($destinationId) : null;
        
        $this->view('customers/merge', [
            'step' => $step,
            'customers' => $customers,
            'sourceId' => $sourceId,
            'destinationId' => $destinationId,
            'sourceCustomer' => $sourceCustomer,
            'destinationCustomer' => $destinationCustomer,
            'error' => $error,
            'message' => $message,
            'csrf_token' => $this->generateCSRF()
        ]);
    }
    
    public function delete($id) {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/customers/view/' . $id);
        }
        
        try {
            $this->validateCSRF();
            
            $customer = $this->customerModel->getCustomerById($id);
            if (!$customer) {
                $this->redirect('/404');
            }
            
            // Check if customer has any work orders
            $workOrders = $this->workOrderModel->getWorkOrdersByCustomerId($id);
            if (!empty($workOrders)) {
                $this->redirectWithFlash('/customers/view/' . $id, t('customers.cannot_delete_wo'), 'error');
            }
            
            // Delete the customer
            $this->customerModel->deleteCustomer($id);
            Hooks::doAction('customer.delete.after', $id, $customer);
            
            // Log the deletion
            $this->logger->log('customer_deleted', "Customer '{$customer['name']}' deleted", $_SESSION['user_id']);
            
            // Redirect to customers list with success message
            $this->redirectWithFlash('/customers', t('customers.deleted'));
            
        } catch (Exception $e) {
            error_log("Error deleting customer: " . $e->getMessage());
            $this->redirectWithFlash('/customers/view/' . $id, t('customers.delete_fail'), 'error');
        }
    }
    
    public function autoMerge() {
        $this->requireAdmin();
        
        $message = '';
        $error = '';
        $mergedCount = 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                
                $groupsToMerge = $_POST['merge_groups'] ?? [];
                
                foreach ($groupsToMerge as $groupIds) {
                    $ids = explode(',', $groupIds);
                    if (count($ids) < 2) continue;
                    
                    // Sort IDs to keep the oldest one (lowest ID)
                    sort($ids);
                    $keepId = array_shift($ids); // First one is kept
                    
                    foreach ($ids as $mergeId) {
                        $this->customerModel->mergeCustomers($keepId, $mergeId);
                        $mergedCount++;
                    }
                }
                
                if ($mergedCount > 0) {
                    $message = t('customers.auto_merged', ['count' => $mergedCount]);
                    $this->logger->log('auto_merge_executed', "Auto-merged $mergedCount customer records", $_SESSION['user_id']);
                } else {
                    $message = t('customers.auto_none');
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $duplicates = $this->customerModel->findDuplicates();
        
        // Process duplicates to get full customer details for display
        $duplicateGroups = [];
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['ids']);
            $customers = [];
            foreach ($ids as $id) {
                $customers[] = $this->customerModel->getCustomerWithWorkOrders($id);
            }
            $duplicateGroups[] = [
                'name' => $dup['name'],
                'phone' => $dup['phone'],
                'ids' => $dup['ids'],
                'customers' => $customers
            ];
        }
        
        $this->view('customers/auto-merge', [
            'duplicateGroups' => $duplicateGroups,
            'message' => $message,
            'error' => $error,
            'csrf_token' => $this->generateCSRF()
        ]);
    }
}
