<?php
require_once 'core/Model.php';
require_once 'core/Crypto.php';

class WorkOrder extends Model {
    protected $table = 'work_orders';
    
    public function countWorkOrders($status = null, $priority = null, $search = null, $assignedTo = null) {
        $sql = "SELECT COUNT(*) as count FROM work_orders wo LEFT JOIN customers c ON wo.customer_id = c.id WHERE 1=1";
        $params = [];
        
        if ($status && $status !== 'All') {
            if ($status === 'Priority') {
                $sql .= " AND wo.priority = 'Priority'";
            } else {
                $sql .= " AND wo.status = ?";
                $params[] = $status;
            }
        }
        
        if ($priority && $priority !== 'All') {
            $sql .= " AND wo.priority = ?";
            $params[] = $priority;
        }
        
        if ($assignedTo) {
            $sql .= " AND wo.assigned_to = ? AND wo.status NOT IN ('Closed', 'Picked Up')";
            $params[] = $assignedTo;
        }
        
        if ($search) {
            $sql .= " AND (c.name LIKE ? OR c.company LIKE ? OR wo.computer LIKE ? OR wo.model LIKE ? OR wo.description LIKE ? OR wo.imei LIKE ? OR wo.serial_number LIKE ? OR wo.remarks LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'];
    }
    
    public function getWorkOrders($status = null, $priority = null, $search = null, $limit = 10, $offset = 0, $assignedTo = null) {
        $sql = "
            SELECT wo.*, c.name as customer_name, c.company as customer_company,
                   u.username as technician_username, u.name as technician_name,
                   COALESCE(NULLIF(u.name, ''), u.username) as technician_display_name
            FROM work_orders wo
            LEFT JOIN customers c ON wo.customer_id = c.id
            LEFT JOIN users u ON wo.assigned_to = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($status && $status !== 'All') {
            if ($status === 'Priority') {
                $sql .= " AND wo.priority = 'Priority'";
            } else {
                $sql .= " AND wo.status = ?";
                $params[] = $status;
            }
        }
        
        if ($assignedTo) {
            $sql .= " AND wo.assigned_to = ? AND wo.status NOT IN ('Closed', 'Picked Up')";
            $params[] = $assignedTo;
        }
        
        if ($search) {
            $sql .= " AND (wo.work_order_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR c.company LIKE ? OR c.email LIKE ? OR wo.imei LIKE ? OR wo.serial_number LIKE ? OR wo.computer LIKE ? OR wo.model LIKE ? OR wo.remarks LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $sql .= " ORDER BY wo.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getWorkOrderById($id) {
        $stmt = $this->db->prepare("
            SELECT wo.*, c.name as customer_name, c.email as customer_email, 
                   c.phone as customer_phone, c.company as customer_company,
                   u.username as technician_username, u.name as technician_name,
                   COALESCE(NULLIF(u.name, ''), u.username) as technician_display_name,
                   creator.username as creator_username, creator.name as creator_name,
                   COALESCE(NULLIF(creator.name, ''), creator.username) as creator_display_name
            FROM work_orders wo
            LEFT JOIN customers c ON wo.customer_id = c.id
            LEFT JOIN users u ON wo.assigned_to = u.id
            LEFT JOIN users creator ON wo.created_by = creator.id
            WHERE wo.id = ?
        ");
        $stmt->execute([$id]);
        $workOrder = $stmt->fetch();
        if ($workOrder && array_key_exists('password', $workOrder)) {
            $workOrder['password'] = Crypto::decrypt($workOrder['password']);
        }
        return $workOrder;
    }
    
    public function createWorkOrder($data) {
        if (!empty($data['password'])) {
            $data['password'] = Crypto::encrypt($data['password']);
        }
        $data['work_order_number'] = $this->generateWorkOrderNumber();
        $data['status'] = 'Open';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $workOrderId = $this->create($data);
        
        // Log the creation
        $this->logWorkOrderAction($workOrderId, 'created', 'Work order created');
        
        return $workOrderId;
    }
    
    public function updateWorkOrder($id, $data) {
        $oldData = $this->findById($id);
        if ($oldData && array_key_exists('password', $oldData)) {
            $oldData['password'] = Crypto::decrypt($oldData['password']);
        }

        $storageData = $data;
        if (array_key_exists('password', $storageData) && $storageData['password'] !== '') {
            $storageData['password'] = Crypto::encrypt($storageData['password']);
        }
        $result = $this->update($id, $storageData);
        
        if ($result) {
            foreach ($data as $field => $newValue) {
                $oldValue = $oldData[$field] ?? null;
                if ($this->fieldValueChanged($field, $oldValue, $newValue)) {
                    $this->logWorkOrderChange($id, $field, $oldValue, $newValue);
                }
            }
        }
        
        return $result;
    }

    private function fieldValueChanged($field, $oldValue, $newValue): bool {
        if ($field === 'accessories') {
            return $this->normalizeAccessoriesJson($oldValue) !== $this->normalizeAccessoriesJson($newValue);
        }

        return (string) ($oldValue ?? '') !== (string) ($newValue ?? '');
    }

    private function normalizeAccessoriesJson($value): string {
        $items = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($items)) {
            $items = [];
        }

        $items = array_values(array_filter(array_map('trim', $items)));
        sort($items);

        return json_encode($items);
    }
    
    public function getWorkOrdersByCustomer($customerId) {
        return $this->findWhere('customer_id = ? ORDER BY created_at DESC', [$customerId]);
    }
    
    public function getWorkOrdersByTechnician($technicianId) {
        $stmt = $this->db->prepare("
            SELECT wo.*, c.name as customer_name, wo.computer as device_type, wo.model as device_model
            FROM work_orders wo
            LEFT JOIN customers c ON wo.customer_id = c.id
            WHERE wo.assigned_to = ? AND wo.status NOT IN (?, ?) 
            ORDER BY wo.created_at DESC
        ");
        $stmt->execute([$technicianId, 'Closed', 'Picked Up']);
        return $stmt->fetchAll();
    }
    
    public function getStatusCounts() {
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM work_orders 
            GROUP BY status
        ");
        $results = $stmt->fetchAll();
        
        $counts = [
            'Open' => 0,
            'In Progress' => 0,
            'Awaiting Parts' => 0,
            'Closed' => 0,
            'Picked Up' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['status']] = $row['count'];
        }
        
        return $counts;
    }
    
    public function getPriorityCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM work_orders WHERE priority = 'Priority' AND status NOT IN ('Closed', 'Picked Up')");
        return $stmt->fetch()['count'];
    }
    
    public function getAssignedCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM work_orders 
            WHERE assigned_to = ? AND status NOT IN ('Closed', 'Picked Up')
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'];
    }
    
    public function getTotalCount() {
        return $this->count();
    }
    
    private function generateWorkOrderNumber() {
        $prefix = 'WO';
        $year = date('Y');
        
        // Get the highest number for this year
        $stmt = $this->db->prepare("
            SELECT work_order_number 
            FROM work_orders 
            WHERE work_order_number LIKE ? 
            ORDER BY work_order_number DESC 
            LIMIT 1
        ");
        $stmt->execute(["$prefix$year%"]);
        $lastNumber = $stmt->fetch();
        
        if ($lastNumber) {
            $number = intval(substr($lastNumber['work_order_number'], -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    public function logWorkOrderAction($workOrderId, $action, $details) {
        $stmt = $this->db->prepare("
            INSERT INTO work_order_logs (work_order_id, user_id, action, details, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $workOrderId, 
            $_SESSION['user_id'] ?? null, 
            $action, 
            $details
        ]);
    }
    
    public function logWorkOrderChange($workOrderId, $field, $oldValue, $newValue) {
        $details = '';
        $oldValueForLog = $oldValue;
        $newValueForLog = $newValue;
        
        // Handle special cases
        if ($field === 'assigned_to') {
            // Get usernames for assignment changes
            $oldUser = $oldValue ? $this->getUserById($oldValue) : null;
            $newUser = $newValue ? $this->getUserById($newValue) : null;
            
            $oldValueForLog = $oldUser ? $oldUser['username'] : 'Unassigned';
            $newValueForLog = $newUser ? $newUser['username'] : 'Unassigned';
            $details = "Assigned to {$newValueForLog}" . ($oldUser ? " (was {$oldValueForLog})" : "");
            
        } elseif ($field === 'password') {
            $details = 'Password updated';

        } elseif ($field === 'accessories') {
            $details = 'Accessories updated';

        } elseif (in_array($field, ['description', 'resolution', 'notes', 'remarks'])) {
            // For long text fields, just indicate what changed
            $fieldName = ucfirst($field);
            $details = "{$fieldName} updated";
            
        } else {
            // For other fields, show the change
            $fieldLabels = [
                'computer' => 'Device',
                'serial_number' => 'Serial Number',
                'model' => 'Model',
            ];
            $fieldName = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $details = "Changed {$fieldName} from '{$oldValueForLog}' to '{$newValueForLog}'";
        }
        
        // Store additional data for modal viewing (concatenated into details for now)
        if (in_array($field, ['description', 'resolution', 'notes', 'remarks'])) {
            $details .= "|||OLD:" . base64_encode($oldValue) . "|||NEW:" . base64_encode($newValue);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO work_order_logs (work_order_id, user_id, action, details, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $workOrderId, 
            $_SESSION['user_id'] ?? null, 
            'updated', 
            $details
        ]);
    }
    
    private function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT id, username, name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getWorkOrderLogs($workOrderId) {
        $stmt = $this->db->prepare("
            SELECT wol.*, u.username, u.name as user_name,
                   COALESCE(NULLIF(u.name, ''), u.username) as user_display_name
            FROM work_order_logs wol
            LEFT JOIN users u ON wol.user_id = u.id
            WHERE wol.work_order_id = ?
            ORDER BY wol.created_at DESC
        ");
        $stmt->execute([$workOrderId]);
        return $stmt->fetchAll();
    }
    
    public function getWorkOrdersByCustomerId($customerId) {
        $stmt = $this->db->prepare("SELECT * FROM work_orders WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
    
    public function deleteWorkOrder($id) {
        require_once 'models/WorkOrderAttachment.php';
        $attachmentModel = new WorkOrderAttachment();
        $attachments = $attachmentModel->getByWorkOrder($id);

        try {
            $this->db->beginTransaction();
            
            // Delete related logs first
            $stmt = $this->db->prepare("DELETE FROM work_order_logs WHERE work_order_id = ?");
            $stmt->execute([$id]);
            
            // Delete the work order (attachment rows cascade)
            $stmt = $this->db->prepare("DELETE FROM work_orders WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        $attachmentModel->removeStoredFiles($id, $attachments);
        return $result;
    }
}
