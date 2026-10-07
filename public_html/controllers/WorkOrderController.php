<?php
require_once 'core/Controller.php';
require_once 'models/WorkOrder.php';
require_once 'models/WorkOrderAttachment.php';
require_once 'models/Customer.php';
require_once 'models/User.php';
require_once 'models/Settings.php';

class WorkOrderController extends Controller {
    private const ALLOWED_STATUSES = ['Open', 'In Progress', 'Awaiting Parts', 'Closed', 'Picked Up'];
    private const ALLOWED_PRIORITIES = ['Standard', 'Priority'];
    private const LIST_SORT_COOKIE = 'motherboard_work_order_sort';
    private $workOrderModel;
    private $attachmentModel;
    private $customerModel;
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->workOrderModel = new WorkOrder($this->db);
        $this->attachmentModel = new WorkOrderAttachment($this->db);
        $this->customerModel = new Customer($this->db);
        $this->userModel = new User($this->db);
    }
    
    public function index() {
        $this->requireAuth();
        
        $status = $_GET['status'] ?? 'All';
        $search = $_GET['search'] ?? '';
        $assignedTo = $_GET['assigned_to'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = PAGINATION_LIMIT;
        $sort = $this->listSort(
            isset($_GET['sort']) ? (string) $_GET['sort'] : null,
            isset($_GET['dir']) ? (string) $_GET['dir'] : null
        );
        
        $totalCount = $this->workOrderModel->countWorkOrders($status, null, $search, $assignedTo);
        $totalPages = ceil($totalCount / $limit);

        // Validate page number
        if ($page > $totalPages && $totalCount > 0) {
            $this->redirect('/404');
        }

        $offset = ($page - 1) * $limit;
        $workOrders = $this->workOrderModel->getWorkOrders($status, null, $search, $limit, $offset, $assignedTo, $sort);
        
        $this->view('work-orders/index', [
            'workOrders' => $workOrders,
            'status' => $status,
            'search' => $search,
            'assignedTo' => $assignedTo,
            'sort' => $sort,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount
        ]);
    }
    
    /**
     * Returns [column, 'asc'|'desc'] for the work order list. A sort picked from a column
     * header is remembered in a cookie so it sticks when coming back to the list.
     */
    private function listSort(?string $column, ?string $direction): array {
        if ($column !== null && in_array($column, WorkOrder::LIST_SORT_COLUMNS, true)) {
            $sort = [$column, $direction === 'desc' ? 'desc' : 'asc'];
            $params = session_get_cookie_params();
            setcookie(self::LIST_SORT_COOKIE, implode(':', $sort), [
                'expires' => time() + 365 * 24 * 60 * 60,
                'path' => '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            return $sort;
        }
        [$saved, $savedDirection] = array_pad(explode(':', (string) ($_COOKIE[self::LIST_SORT_COOKIE] ?? ''), 2), 2, '');
        if (in_array($saved, WorkOrder::LIST_SORT_COLUMNS, true)) {
            return [$saved, $savedDirection === 'desc' ? 'desc' : 'asc'];
        }
        return ['opened', 'desc'];
    }
    
    public function create() {
        $this->requireTechnician();
        
        $step = intval($_GET['step'] ?? 1);
        $error = '';

        // Started from a customer's page: begin a fresh work order for them and skip the customer step.
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['customer_id'])) {
            $customer = $this->customerModel->findById((int) $_GET['customer_id']);
            if (!$customer) {
                $this->redirect('/404');
            }

            foreach ($_SESSION['work_order_data']['pending_attachments'] ?? [] as $pendingAttachment) {
                $this->attachmentModel->removePendingUpload($pendingAttachment);
            }
            unset($_SESSION['new_customer_data']);
            $_SESSION['work_order_data'] = ['customer_id' => (int) $customer['id']];
            $this->redirect('/work-orders/create?step=2');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (WorkOrderAttachment::requestExceededPostLimit()) {
                    throw new Exception(t('wo.attachment_too_large', [
                        'size' => $this->attachmentModel->formatSize($this->attachmentModel->maxSizeBytes()),
                    ]));
                }
                $this->validateCSRF();

                // Modules that add fields to a create step stash their own values in the
                // session here; the work order row does not exist yet.
                Hooks::doAction('work_order.create.step', $step, $_POST);

                if ($step === 1) {
                    // Customer step
                    $customerId = $_POST['customer_id'] ?? null;
                    
                    if (!$customerId) {
                        $customerData = [
                            'name' => $this->sanitizeInput($_POST['customer_name']),
                            'company' => $this->sanitizeInput($_POST['customer_company']),
                            'email' => $this->sanitizeInput($_POST['customer_email']),
                            'phone' => $this->sanitizeInput($_POST['customer_phone'])
                        ];
                        
                        if (empty($customerData['name']) || empty($customerData['phone'])) {
                            throw new Exception(t('wo.customer_required'));
                        }
                        if ($customerData['email'] !== '' && !filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
                            throw new Exception(t('customers.invalid_email'));
                        }

                        $_SESSION['new_customer_data'] = $customerData;
                        unset($_SESSION['work_order_data']['customer_id']);
                    } else {
                        $customer = $this->customerModel->findById((int) $customerId);
                        if (!$customer) {
                            throw new Exception(t('customers.not_found'));
                        }
                        $_SESSION['work_order_data']['customer_id'] = (int) $customerId;
                        unset($_SESSION['new_customer_data']);
                    }
                    $this->redirect('/work-orders/create?step=2');
                    
                } elseif ($step === 2) {
                    // Device step
                    $_SESSION['work_order_data']['computer'] = $this->sanitizeInput($_POST['computer']);
                    $_SESSION['work_order_data']['model'] = $this->sanitizeInput($_POST['model']);
                    $_SESSION['work_order_data']['serial_number'] = $this->sanitizeInput($_POST['serial_number']);
                    $_SESSION['work_order_data']['imei'] = $this->sanitizeInput($_POST['imei'] ?? '');
                    $_SESSION['work_order_data']['remarks'] = $this->sanitizeInput($_POST['remarks'] ?? '');
                    $_SESSION['work_order_data']['accessories'] = json_encode($_POST['accessories'] ?? []);
                    $_SESSION['work_order_data']['username'] = $this->sanitizeInput($_POST['username']);
                    $_SESSION['work_order_data']['password'] = $this->sanitizeInput($_POST['password']);
                    
                    if (empty($_SESSION['work_order_data']['computer'])) {
                        throw new Exception(t('wo.computer_required'));
                    }
                    
                    $this->redirect('/work-orders/create?step=3');
                    
                } elseif ($step === 3) {
                    // Description step
                    $_SESSION['work_order_data']['description'] = $this->sanitizeInput($_POST['description']);
                    
                    if (empty($_SESSION['work_order_data']['description'])) {
                        throw new Exception(t('wo.desc_required'));
                    }
                    
                    $this->redirect('/work-orders/create?step=4');
                    
                } elseif ($step === 4) {
                    $action = $_POST['attachment_action'] ?? 'next';

                    if ($action === 'remove') {
                        $this->removePendingAttachment($_POST['attachment_token'] ?? '');
                        $this->redirect('/work-orders/create?step=4');
                    } elseif ($action === 'update') {
                        $this->updatePendingAttachment(
                            $_POST['attachment_token'] ?? '',
                            $this->sanitizeInput($_POST['attachment_description'] ?? '')
                        );
                        $this->redirect('/work-orders/create?step=4');
                    } elseif ($action === 'add') {
                        $this->addPendingAttachment(
                            $_FILES['attachment'] ?? [],
                            $this->sanitizeInput($_POST['attachment_description'] ?? '')
                        );
                        $this->redirect('/work-orders/create?step=4');
                    } else {
                        $this->redirect('/work-orders/create?step=5');
                    }

                } elseif ($step === 5) {
                    $workOrderData = $_SESSION['work_order_data'] ?? [];
                    $pendingAttachments = $workOrderData['pending_attachments'] ?? [];
                    unset($workOrderData['pending_attachments']);
                    $workOrderData['assigned_to'] = $_POST['assigned_to'] ?: null;
                    $workOrderData['priority'] = $_POST['priority'] ?? 'Standard';
                    if (!in_array($workOrderData['priority'], self::ALLOWED_PRIORITIES, true)) {
                        throw new Exception('Invalid priority');
                    }
                    $workOrderData['created_by'] = $_SESSION['user_id'];
                    $newCustomerData = $_SESSION['new_customer_data'] ?? null;
                    $createdCustomerId = null;
                    $finalizedAttachments = [];
                    try {
                        $this->db->beginTransaction();
                        if (is_array($newCustomerData)) {
                            $createdCustomerId = $this->customerModel->createCustomer($newCustomerData);
                            $workOrderData['customer_id'] = $createdCustomerId;
                        }
                        if (empty($workOrderData['customer_id'])) {
                            throw new Exception(t('wo.customer_required'));
                        }

                        $workOrderData = Hooks::applyFilters('work_order.create.data', $workOrderData);
                        Hooks::doAction('work_order.create.before', $workOrderData);
                        $workOrderId = $this->workOrderModel->createWorkOrder($workOrderData);
                        Hooks::doAction('work_order.create.transaction', $workOrderId, $workOrderData);
                        $finalizedAttachments = $this->attachmentModel->finalizePending(
                            $workOrderId,
                            $pendingAttachments,
                            $_SESSION['user_id']
                        );
                        $this->db->commit();
                    } catch (Throwable $e) {
                        if ($this->db->inTransaction()) {
                            $this->db->rollback();
                        }
                        $this->attachmentModel->cleanupFinalized($finalizedAttachments);
                        foreach ($pendingAttachments as $pendingAttachment) {
                            $this->attachmentModel->removePendingUpload($pendingAttachment);
                        }
                        $_SESSION['work_order_data']['pending_attachments'] = [];
                        $step = 4;
                        throw new Exception(t('wo.create_rolled_back') . ' ' . $e->getMessage(), 0, $e);
                    }

                    unset($_SESSION['work_order_data']);
                    unset($_SESSION['new_customer_data']);

                    try {
                        if ($createdCustomerId !== null) {
                            Hooks::doAction('customer.create.after', $createdCustomerId, $newCustomerData);
                        }
                        Hooks::doAction('work_order.create.after', $workOrderId, $workOrderData);
                    } catch (Throwable $e) {
                        error_log('Post-creation module hook failed for work order #' . $workOrderId . ': ' . $e->getMessage());
                    }

                    $this->logger->log('work_order_created', "Work order #{$workOrderId} created", $_SESSION['user_id']);
                    $this->redirect("/work-orders/submitted/{$workOrderId}");
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        // Get data for the view
        $customers = [];
        $technicians = $this->userModel->getTechnicians();
        $customer = null;
        
        if ($step >= 2 && isset($_SESSION['work_order_data']['customer_id'])) {
            $customer = $this->customerModel->findById($_SESSION['work_order_data']['customer_id']);
        } elseif ($step >= 2 && isset($_SESSION['new_customer_data'])) {
            $customer = $_SESSION['new_customer_data'];
        }

        $previousDevices = [];
        if ($step === 2 && isset($_SESSION['work_order_data']['customer_id'])) {
            $previousDevices = $this->workOrderModel->getRecentDevicesByCustomer((int) $_SESSION['work_order_data']['customer_id']);
        }

        $this->view('work-orders/create', [
            'step' => $step,
            'error' => $error,
            'customers' => $customers,
            'technicians' => $technicians,
            'customer' => $customer,
            'previousDevices' => $previousDevices,
            'workOrderData' => $_SESSION['work_order_data'] ?? [],
            'pendingAttachments' => $_SESSION['work_order_data']['pending_attachments'] ?? [],
            'attachmentModel' => $this->attachmentModel,
            'attachmentSettings' => $this->attachmentModel->getSettings(),
            'attachmentMaxBytes' => $this->attachmentModel->maxSizeBytes(),
            'attachmentAllowedLabel' => $this->attachmentModel->allowedExtensionsLabel(),
            'attachmentStorageWritable' => $this->attachmentModel->canAcceptUploads(),
            'csrf_token' => $this->generateCSRF()
        ]);
    }
    
    public function details($id) {
        $this->requireAuth();
        
        $workOrder = $this->workOrderModel->getWorkOrderById($id);
        if (!$workOrder) {
            $this->redirect('/404');
        }
        
        // Check permissions
        if ($_SESSION['user_group'] === 'Limited') {
            // Limited users can only view
        }
        
        $error = '';
        $message = '';
        $editDevice = false;
        $autosaveEnabled = $this->settingsModel->getSetting('work_order_autosave', '1') === '1';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['update_section'] ?? '') === 'autosave') {
            $this->autosave($workOrder, $autosaveEnabled);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_group'] !== 'Limited') {
            try {
                $this->validateCSRF();

                if (($_POST['update_section'] ?? '') === 'device') {
                    $editDevice = true;
                    $computer = $this->sanitizeInput($_POST['computer'] ?? '');

                    if ($computer === '') {
                        throw new Exception(t('wo.computer_required'));
                    }

                    $accessories = array_values(array_filter(array_map('trim', $_POST['accessories'] ?? [])));

                    $updateData = [
                        'computer' => $computer,
                        'model' => $this->sanitizeInput($_POST['model'] ?? ''),
                        'serial_number' => $this->sanitizeInput($_POST['serial_number'] ?? ''),
                        'imei' => $this->sanitizeInput($_POST['imei'] ?? ''),
                        'remarks' => $this->sanitizeInput($_POST['remarks'] ?? ''),
                        'accessories' => json_encode($accessories),
                        'username' => $this->sanitizeInput($_POST['username'] ?? ''),
                        'password' => $this->sanitizeInput($_POST['password'] ?? ''),
                    ];

                    $updateData = Hooks::applyFilters('work_order.update.data', $updateData, $id);
                    Hooks::doAction('work_order.update.before', $id, $updateData);
                    $this->workOrderModel->updateWorkOrder($id, $updateData);
                    Hooks::doAction('work_order.update.after', $id, $updateData);
                    $this->logger->log('work_order_updated', "Work order #{$workOrder['work_order_number']} device details updated", $_SESSION['user_id']);

                    $message = t('wo.device_updated');
                    $workOrder = $this->workOrderModel->getWorkOrderById($id);
                    $editDevice = false;
                } else {
                    $status = (string) ($_POST['status'] ?? '');
                    $priority = (string) ($_POST['priority'] ?? '');
                    if (!in_array($status, self::ALLOWED_STATUSES, true) ||
                        !in_array($priority, self::ALLOWED_PRIORITIES, true)) {
                        throw new Exception('Invalid work order status or priority');
                    }
                    $updateData = [
                        'description' => $this->sanitizeInput($_POST['description']),
                        'resolution' => $this->sanitizeInput($_POST['resolution']),
                        'notes' => $this->sanitizeInput($_POST['notes']),
                        'status' => $status,
                        'priority' => $priority,
                        'assigned_to' => $_POST['assigned_to'] ?: null
                    ];
                    
                    if ($updateData['status'] === 'Closed' && !$workOrder['closed_at']) {
                        $updateData['closed_at'] = date('Y-m-d H:i:s');
                    }
                    
                    $updateData = Hooks::applyFilters('work_order.update.data', $updateData, $id);
                    Hooks::doAction('work_order.update.before', $id, $updateData);
                    $this->workOrderModel->updateWorkOrder($id, $updateData);
                    Hooks::doAction('work_order.update.after', $id, $updateData);
                    $this->logger->log('work_order_updated', "Work order #{$workOrder['work_order_number']} updated", $_SESSION['user_id']);
                    
                    $message = t('wo.updated');
                    $workOrder = $this->workOrderModel->getWorkOrderById($id);
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $technicians = $this->userModel->getTechnicians();
        $workOrderLogs = $this->workOrderModel->getWorkOrderLogs($id);
        foreach ($workOrderLogs as &$log) {
            $log['by_system'] = (bool) Hooks::applyFilters('work_order.log.by_system', false, $log);
        }
        unset($log);
        
        $this->view('work-orders/view', [
            'workOrder' => $workOrder,
            'technicians' => $technicians,
            'workOrderLogs' => $workOrderLogs,
            'attachments' => $this->attachmentModel->getByWorkOrder($id),
            'attachmentModel' => $this->attachmentModel,
            'attachmentMaxBytes' => $this->attachmentModel->maxSizeBytes(),
            'attachmentAllowedLabel' => $this->attachmentModel->allowedExtensionsLabel(),
            'attachmentStorageWritable' => $this->attachmentModel->canAcceptUploads((int) $id),
            'error' => $error,
            'message' => $message,
            'csrf_token' => $this->generateCSRF(),
            'canEdit' => $_SESSION['user_group'] !== 'Limited',
            'editDevice' => $editDevice,
            'autosaveEnabled' => $autosaveEnabled,
            'printOptions' => $this->printOptionDefaults()
        ]);
    }

    /**
     * Saves only status, priority, and assigned to for the view page's auto-save countdown and
     * answers with JSON. Description, resolution, and notes are left alone, so an auto-save can
     * never overwrite text someone else saved since this page was loaded.
     */
    private function autosave(array $workOrder, bool $enabled): void {
        $id = (int) $workOrder['id'];

        try {
            if ($_SESSION['user_group'] === 'Limited' || !$enabled) {
                http_response_code(403);
                $this->json(['success' => false, 'error' => t('wo.autosave_failed')]);
            }

            $this->validateCSRF();

            $status = (string) ($_POST['status'] ?? '');
            $priority = (string) ($_POST['priority'] ?? '');
            if (!in_array($status, self::ALLOWED_STATUSES, true) ||
                !in_array($priority, self::ALLOWED_PRIORITIES, true)) {
                throw new Exception('Invalid work order status or priority');
            }

            $updateData = [
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => ($_POST['assigned_to'] ?? '') ?: null,
            ];

            if ($updateData['status'] === 'Closed' && !$workOrder['closed_at']) {
                $updateData['closed_at'] = date('Y-m-d H:i:s');
            }

            $updateData = Hooks::applyFilters('work_order.update.data', $updateData, $id);
            Hooks::doAction('work_order.update.before', $id, $updateData);
            if (!$this->workOrderModel->updateWorkOrder($id, $updateData)) {
                throw new Exception(t('wo.autosave_failed'));
            }
            Hooks::doAction('work_order.update.after', $id, $updateData);
            $this->logger->log('work_order_updated', "Work order #{$workOrder['work_order_number']} auto-saved", $_SESSION['user_id']);

            $saved = $this->workOrderModel->getWorkOrderById($id);
            $this->json([
                'success' => true,
                'message' => t('wo.updated'),
                'status' => $saved['status'],
                'status_label' => tlabel('status', $saved['status']),
                'priority' => $saved['priority'],
                'assigned_to' => $saved['assigned_to'] !== null ? (string) $saved['assigned_to'] : '',
                'technician_display_name' => (string) ($saved['technician_display_name'] ?? ''),
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            $this->json(['success' => false, 'error' => t('wo.autosave_failed') . ' ' . $e->getMessage()]);
        }
    }
    
    public function submitted($id) {
        $this->requireTechnician();
        
        $workOrder = $this->workOrderModel->getWorkOrderById($id);
        if (!$workOrder) {
            $this->redirect('/404');
        }
        
        $this->view('work-orders/submitted', [
            'workOrder' => $workOrder
        ]);
    }
    
    private function printOptionDefaults() {
        $companyInfo = $this->settingsModel->getCompanyInfo();

        return [
            'has_disclaimer' => trim((string) ($companyInfo['work_order_disclaimer'] ?? '')) !== '',
            'customer_signature' => !empty($companyInfo['print_customer_signature']),
            'technician_signature' => !empty($companyInfo['print_technician_signature']),
        ];
    }

    public function print($id) {
        $this->requireAuth();
        
        $workOrder = $this->workOrderModel->getWorkOrderById($id);
        if (!$workOrder) {
            $this->redirect('/404');
        }

        applyPrintLanguage($this->settingsModel);
        
        $companyInfo = $this->settingsModel->getCompanyInfo();

        // Per-printout overrides chosen in the print options modal on the work order view.
        if (isset($_GET['disclaimer']) && $_GET['disclaimer'] !== '1') {
            $companyInfo['work_order_disclaimer'] = '';
        }
        if (isset($_GET['customer_signature'])) {
            $companyInfo['print_customer_signature'] = $_GET['customer_signature'] === '1' ? '1' : '0';
        }
        if (isset($_GET['technician_signature'])) {
            $companyInfo['print_technician_signature'] = $_GET['technician_signature'] === '1' ? '1' : '0';
        }

        $this->view('work-orders/print', [
            'workOrder' => $workOrder,
            'companyInfo' => $companyInfo,
            'attachments' => $this->attachmentModel->getByWorkOrder($id)
        ]);
    }

    public function uploadAttachment($id) {
        $this->requireTechnician();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/work-orders/view/' . $id);
        }

        try {
            if (WorkOrderAttachment::requestExceededPostLimit()) {
                throw new Exception(t('wo.attachment_too_large', [
                    'size' => $this->attachmentModel->formatSize($this->attachmentModel->maxSizeBytes()),
                ]));
            }
            $this->validateCSRF();

            $workOrder = $this->workOrderModel->getWorkOrderById($id);
            if (!$workOrder) {
                $this->redirect('/404');
            }

            $description = $this->sanitizeInput($_POST['attachment_description'] ?? '');
            $this->attachmentModel->createFromUpload((int) $id, $_FILES['attachment'] ?? [], $description, $_SESSION['user_id']);
            $this->workOrderModel->logWorkOrderAction($id, 'updated', 'Attachment uploaded');
            $this->logger->log('attachment_uploaded', "Attachment uploaded to work order #{$id}", $_SESSION['user_id']);

            $this->redirectWithFlash('/work-orders/view/' . $id, t('wo.attachment_added'));
        } catch (Exception $e) {
            $this->redirectWithFlash('/work-orders/view/' . $id, $e->getMessage(), 'error');
        }
    }

    public function updateAttachment($id) {
        $this->requireTechnician();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/work-orders');
        }

        $attachment = $this->attachmentModel->getAttachmentById($id);
        if (!$attachment) {
            $this->redirect('/404');
        }

        try {
            $this->validateCSRF();
            $description = $this->sanitizeInput($_POST['attachment_description'] ?? '');
            $oldDescription = $attachment['description'] ?? '';
            $this->attachmentModel->updateDescription($id, $description);

            if ($oldDescription !== $description) {
                $filename = $attachment['original_filename'] ?? t('wo.attachments');
                $details = 'Attachment description updated for ' . $filename;
                $details .= '|||OLD:' . base64_encode($oldDescription) . '|||NEW:' . base64_encode($description);
                $this->workOrderModel->logWorkOrderAction($attachment['work_order_id'], 'updated', $details);
            }

            $this->redirectWithFlash('/work-orders/view/' . $attachment['work_order_id'], t('wo.attachment_updated'));
        } catch (Exception $e) {
            $this->redirectWithFlash('/work-orders/view/' . $attachment['work_order_id'], $e->getMessage(), 'error');
        }
    }

    public function deleteAttachment($id) {
        $this->requireTechnician();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/work-orders');
        }

        $attachment = $this->attachmentModel->getAttachmentById($id);
        if (!$attachment) {
            $this->redirect('/404');
        }

        $workOrderId = $attachment['work_order_id'];

        try {
            $this->validateCSRF();
            $this->attachmentModel->deleteAttachment($id);
            $this->workOrderModel->logWorkOrderAction($workOrderId, 'updated', 'Attachment removed: ' . $attachment['original_filename']);
            $this->logger->log('attachment_deleted', "Attachment removed from work order #{$workOrderId}", $_SESSION['user_id']);
            $this->redirectWithFlash('/work-orders/view/' . $workOrderId, t('wo.attachment_removed'));
        } catch (Exception $e) {
            $this->redirectWithFlash('/work-orders/view/' . $workOrderId, $e->getMessage(), 'error');
        }
    }

    public function downloadPendingAttachment($token) {
        $this->requireTechnician();

        $pending = $this->getPendingAttachment($token);
        if (!$pending) {
            $this->redirect('/404');
        }

        $path = $this->attachmentModel->absolutePath($pending);
        if (!is_file($path)) {
            $this->redirect('/404');
        }

        $this->outputAttachmentFile($pending, $path);
    }

    public function downloadAttachment($id) {
        $this->requireAuth();

        $attachment = $this->attachmentModel->getAttachmentById($id);
        if (!$attachment) {
            $this->redirect('/404');
        }

        $path = $this->attachmentModel->localReadablePath($attachment);
        if ($path === null) {
            $this->redirect('/404');
        }

        $cleanup = $this->attachmentModel->destinationOf($attachment) !== 'local';
        $this->outputAttachmentFile($attachment, $path, $cleanup);
    }
    
    public function delete($id) {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/work-orders/view/' . $id);
        }
        
        try {
            $this->validateCSRF();
            
            $workOrder = $this->workOrderModel->getWorkOrderById($id);
            if (!$workOrder) {
                $this->redirect('/404');
            }
            
            // Delete the work order
            Hooks::doAction('work_order.delete.before', $id, $workOrder);
            $this->workOrderModel->deleteWorkOrder($id);
            Hooks::doAction('work_order.delete.after', $id, $workOrder);
            
            // Log the deletion
            $this->logger->log('work_order_deleted', "Work Order #{$id} deleted", $_SESSION['user_id']);
            
            // Redirect to work orders list with success message
            $this->redirectWithFlash('/work-orders', t('wo.deleted'));
            
        } catch (Exception $e) {
            error_log("Error deleting work order: " . $e->getMessage());
            $this->redirectWithFlash('/work-orders/view/' . $id, t('wo.delete_fail'), 'error');
        }
    }

    private function addPendingAttachment(array $file, string $description): void {
        $pending = $this->attachmentModel->storePendingUpload($file, $description);
        if (!isset($_SESSION['work_order_data']['pending_attachments']) || !is_array($_SESSION['work_order_data']['pending_attachments'])) {
            $_SESSION['work_order_data']['pending_attachments'] = [];
        }
        $_SESSION['work_order_data']['pending_attachments'][] = $pending;
    }

    private function updatePendingAttachment(string $token, string $description): void {
        $pendingList = $_SESSION['work_order_data']['pending_attachments'] ?? [];
        foreach ($pendingList as $index => $item) {
            if (($item['token'] ?? '') === $token) {
                $_SESSION['work_order_data']['pending_attachments'][$index]['description'] = $description;
                return;
            }
        }
    }

    private function getPendingAttachment(string $token): ?array {
        foreach ($_SESSION['work_order_data']['pending_attachments'] ?? [] as $item) {
            if (($item['token'] ?? '') === $token) {
                return $item;
            }
        }
        return null;
    }

    private function outputAttachmentFile(array $attachment, string $path, bool $cleanup = false): void {
        $filename = $attachment['original_filename'] ?: 'attachment';
        $safeName = str_replace(['"', '\\', "\r", "\n"], '', $filename);
        $mime = $attachment['mime_type'] ?: 'application/octet-stream';
        $inline = $this->attachmentModel->isDisplayableImage($attachment);
        $disposition = $inline ? 'inline' : 'attachment';

        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($path);
        if ($cleanup) {
            @unlink($path);
        }
        exit;
    }

    private function removePendingAttachment(string $token): void {
        $pendingList = $_SESSION['work_order_data']['pending_attachments'] ?? [];
        $_SESSION['work_order_data']['pending_attachments'] = [];

        foreach ($pendingList as $item) {
            if (($item['token'] ?? '') === $token) {
                $this->attachmentModel->removePendingUpload($item);
                continue;
            }
            $_SESSION['work_order_data']['pending_attachments'][] = $item;
        }
    }
}
