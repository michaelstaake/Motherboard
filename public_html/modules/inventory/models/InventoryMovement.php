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

        $this->create([
            'product_id' => (int) $product['id'],
            'product_name' => $product['name'],
            'item_number' => $product['item_number'],
            'movement_type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'work_order_id' => $workOrderId,
            'user_id' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param string|null $since Only movements on or after this 'Y-m-d H:i:s' time; null for all of them.
     */
    public function getAllForExport(?string $since = null): array {
        $where = $since === null ? '' : 'WHERE m.created_at >= ?';
        $stmt = $this->db->prepare("
            SELECT m.*, COALESCE(p.name, m.product_name) AS current_name, COALESCE(p.item_number, m.item_number) AS current_item_number, p.category_id,
                   COALESCE(NULLIF(u.name, ''), u.username) AS user_name
            FROM inventory_movements m
            LEFT JOIN inventory_products p ON p.id = m.product_id
            LEFT JOIN users u ON u.id = m.user_id
            {$where}
            ORDER BY m.created_at ASC, m.id ASC
        ");
        $stmt->execute($since === null ? [] : [$since]);
        return $stmt->fetchAll();
    }
}
