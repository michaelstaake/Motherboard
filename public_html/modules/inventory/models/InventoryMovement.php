<?php
require_once ROOT_PATH . '/core/Model.php';

/**
 * Every change to a product's stock or sold count after it was first entered: sales and
 * returns through work orders, and stock edits made by staff on the inventory page.
 */
class InventoryMovement extends Model {
    protected $table = 'inventory_movements';

    public const TYPE_SALE = 'sale';
    public const TYPE_RETURN = 'return';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public function record(array $product, string $type, ?int $quantity, int $stockBefore, int $stockAfter, ?int $workOrderId = null): void {
        if (motherboard_inventory_is_custom_item($product['item_number'] ?? null)) {
            return;
        }

        $workOrderNumber = null;
        if ($workOrderId) {
            $stmt = $this->db->prepare("SELECT work_order_number FROM work_orders WHERE id = ?");
            $stmt->execute([$workOrderId]);
            $workOrderNumber = $stmt->fetchColumn() ?: null;
        }

        $this->create([
            'product_id' => (int) $product['id'],
            'product_name' => $product['name'],
            'item_number' => $product['item_number'],
            'movement_type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'work_order_id' => $workOrderId,
            'work_order_number' => $workOrderNumber,
            'user_id' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAllForExport(): array {
        $stmt = $this->db->query("
            SELECT m.*, COALESCE(p.name, m.product_name) AS current_name, COALESCE(p.item_number, m.item_number) AS current_item_number, c.name AS category_name,
                   COALESCE(NULLIF(u.name, ''), u.username) AS user_name
            FROM inventory_movements m
            LEFT JOIN inventory_products p ON p.id = m.product_id
            LEFT JOIN inventory_categories c ON c.id = p.category_id
            LEFT JOIN users u ON u.id = m.user_id
            ORDER BY m.created_at ASC, m.id ASC
        ");
        return $stmt->fetchAll();
    }
}
