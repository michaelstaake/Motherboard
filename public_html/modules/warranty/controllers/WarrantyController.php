<?php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/models/WorkOrder.php';
require_once dirname(__DIR__) . '/lib.php';
motherboard_warranty_load_models();

class WarrantyController extends Controller {
    private WorkOrderWarranty $warrantyModel;
    private WorkOrder $workOrderModel;

    public function __construct() {
        parent::__construct();
        $this->warrantyModel = new WorkOrderWarranty();
        $this->workOrderModel = new WorkOrder();
    }

    public function save($id) {
        $this->requireWorkOrderEditor();
        $this->requirePost();
        $workOrder = $this->requireWorkOrder($id);
        $workOrderId = (int) $workOrder['id'];

        try {
            $this->validateCSRF();

            $existing = $this->warrantyModel->getForWorkOrder($workOrderId);
            $isWarranty = !empty($_POST['warranty_is_warranty']);

            if (!$isWarranty) {
                if (!$existing) {
                    $this->redirectWorkOrder($workOrderId, 'message', t('warranty.unchanged'));
                }
                $this->warrantyModel->clearWarranty($workOrderId);
                $this->recordChange($workOrderId, 'warranty_removed', t('warranty.log_removed'));
                $this->redirectWorkOrder($workOrderId, 'message', t('warranty.removed'));
            }

            $requested = isset($_POST['warranty_reference_work_order_id']) && $_POST['warranty_reference_work_order_id'] !== ''
                ? (int) $_POST['warranty_reference_work_order_id']
                : null;
            $reference = $this->warrantyModel->validateReference(
                (int) ($workOrder['customer_id'] ?? 0),
                $requested,
                $workOrderId
            );

            if ($existing && $existing['reference_work_order_id'] === $reference) {
                $this->redirectWorkOrder($workOrderId, 'message', t('warranty.unchanged'));
            }

            $this->warrantyModel->setWarranty($workOrderId, $reference);

            if (!$existing) {
                $this->recordChange(
                    $workOrderId,
                    'warranty_set',
                    $reference
                        ? t('warranty.log_set_reference', ['number' => (string) $reference])
                        : t('warranty.log_set')
                );
                $this->redirectWorkOrder($workOrderId, 'message', t('warranty.saved'));
            }

            $this->recordChange(
                $workOrderId,
                'warranty_updated',
                $reference
                    ? t('warranty.log_reference_set', ['number' => (string) $reference])
                    : t('warranty.log_reference_cleared')
            );
            $this->redirectWorkOrder($workOrderId, 'message', t('warranty.saved'));
        } catch (Exception $e) {
            $this->redirectWorkOrder($workOrderId, 'error', $e->getMessage());
        }
    }

    private function recordChange(int $workOrderId, string $action, string $details): void {
        $this->workOrderModel->logWorkOrderAction($workOrderId, $action, $details);
        $this->logger->log($action, $details . " (work order #{$workOrderId})", $_SESSION['user_id']);
    }

    private function requirePost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/404');
        }
    }

    private function requireWorkOrderEditor(): void {
        $this->requireAuth();
        if ($_SESSION['user_group'] === 'Limited') {
            $this->redirect('/403');
        }
    }

    private function requireWorkOrder($id): array {
        $workOrder = $this->workOrderModel->getWorkOrderById($id);
        if (!$workOrder) {
            $this->redirect('/404');
        }
        return $workOrder;
    }

    private function redirectWorkOrder(int $id, string $type, string $text): void {
        $this->redirectWithFlash('/work-orders/view/' . $id, $text, $type);
    }
}
