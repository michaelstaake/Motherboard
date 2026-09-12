<?php
require_once ROOT_PATH . '/core/Model.php';

class WorkOrderWarranty extends Model {
    protected $table = 'work_order_warranty';

    /**
     * The description line and the sidebar section both ask for the same row on one page
     * render, and the print view asks again for the inventory prices.
     */
    private static array $cache = [];

    public function getForWorkOrder(int $workOrderId): ?array {
        if ($workOrderId <= 0) {
            return null;
        }
        if (array_key_exists($workOrderId, self::$cache)) {
            return self::$cache[$workOrderId];
        }

        $stmt = $this->db->prepare("
            SELECT w.work_order_id, w.reference_work_order_id, w.created_at, w.updated_at,
                   ref.computer AS reference_computer, ref.model AS reference_model,
                   ref.created_at AS reference_created_at
            FROM work_order_warranty w
            LEFT JOIN work_orders ref ON ref.id = w.reference_work_order_id
            WHERE w.work_order_id = ?
            LIMIT 1
        ");
        $stmt->execute([$workOrderId]);
        $row = $stmt->fetch();

        if ($row) {
            $row['reference_work_order_id'] = $row['reference_work_order_id'] !== null
                ? (int) $row['reference_work_order_id']
                : null;
        }

        self::$cache[$workOrderId] = $row ?: null;
        return self::$cache[$workOrderId];
    }

    public function isWarranty(int $workOrderId): bool {
        return $this->getForWorkOrder($workOrderId) !== null;
    }

    public function setWarranty(int $workOrderId, ?int $referenceWorkOrderId): void {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            INSERT INTO work_order_warranty (work_order_id, reference_work_order_id, created_at, updated_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reference_work_order_id = VALUES(reference_work_order_id), updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$workOrderId, $referenceWorkOrderId, $now, $now]);
        unset(self::$cache[$workOrderId]);
    }

    public function clearWarranty(int $workOrderId): void {
        $stmt = $this->db->prepare('DELETE FROM work_order_warranty WHERE work_order_id = ?');
        $stmt->execute([$workOrderId]);
        unset(self::$cache[$workOrderId]);
    }

    /**
     * The customer's other work orders, newest first, for the reference picker.
     */
    public function referenceOptions(int $customerId, int $excludeWorkOrderId = 0): array {
        if ($customerId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare("
            SELECT id, computer, model, status, created_at
            FROM work_orders
            WHERE customer_id = ? AND id <> ?
            ORDER BY id DESC
        ");
        $stmt->execute([$customerId, $excludeWorkOrderId]);
        return $stmt->fetchAll();
    }

    /**
     * Only a work order belonging to the same customer may be referenced; anything else is
     * dropped rather than rejected, so a stale picker cannot block saving the warranty flag.
     */
    public function validateReference(int $customerId, ?int $referenceWorkOrderId, int $excludeWorkOrderId = 0): ?int {
        if (!$referenceWorkOrderId || $customerId <= 0 || $referenceWorkOrderId === $excludeWorkOrderId) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT id FROM work_orders WHERE id = ? AND customer_id = ? LIMIT 1');
        $stmt->execute([$referenceWorkOrderId, $customerId]);
        return $stmt->fetch() ? $referenceWorkOrderId : null;
    }
}
