<?php
require_once ROOT_PATH . '/core/Model.php';

class InventoryCategory extends Model {
    protected $table = 'inventory_categories';

    public function findById($id) {
        return parent::findById($id);
    }

    public function getAll(): array {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(p.id) AS product_count
            FROM inventory_categories c
            LEFT JOIN inventory_products p ON p.category_id = c.id AND p.item_number <> ?
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        $stmt->execute([motherboard_inventory_custom_item_number()]);
        return $stmt->fetchAll();
    }

    public function createCategory(string $name): int {
        $name = trim($name);
        if ($name === '') {
            throw new Exception(t('inventory.category_required'));
        }
        if ($this->findOneWhere('name = ?', [$name])) {
            throw new Exception(t('inventory.category_exists'));
        }
        return (int) $this->create([
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateCategory(int $id, string $name): bool {
        $name = trim($name);
        if ($name === '') {
            throw new Exception(t('inventory.category_required'));
        }
        $existing = $this->findOneWhere('name = ? AND id != ?', [$name, $id]);
        if ($existing) {
            throw new Exception(t('inventory.category_exists'));
        }
        return $this->update($id, [
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteCategory(int $id): bool {
        return $this->delete($id);
    }
}
