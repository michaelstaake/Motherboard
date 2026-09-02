<?php
require_once ROOT_PATH . '/core/Model.php';

class InventoryProduct extends Model {
    protected $table = 'inventory_products';

    public function findById($id) {
        return parent::findById($id);
    }

    public function getAll(?string $search = null, ?int $categoryId = null, $limit = null, int $offset = 0): array {
        $sql = "
            SELECT p.*, c.name AS category_name
            FROM inventory_products p
            LEFT JOIN inventory_categories c ON c.id = p.category_id
            WHERE p.item_number <> ?
        ";
        $params = [motherboard_inventory_custom_item_number()];

        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.item_number LIKE ? OR p.description LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY p.name ASC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int) $limit;
            $params[] = (int) $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCount(?string $search = null, ?int $categoryId = null): int {
        $sql = "SELECT COUNT(*) AS count FROM inventory_products p WHERE p.item_number <> ?";
        $params = [motherboard_inventory_custom_item_number()];

        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.item_number LIKE ? OR p.description LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['count'];
    }

    public function getAvailableForWorkOrder(int $workOrderId): array {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name
            FROM inventory_products p
            LEFT JOIN inventory_categories c ON c.id = p.category_id
            WHERE p.item_number <> ?
            AND p.id NOT IN (
                SELECT product_id FROM work_order_products
                WHERE work_order_id = ? AND product_id IS NOT NULL
            )
            AND (p.stock = -1 OR p.stock > 0)
            ORDER BY p.name ASC
        ");
        $stmt->execute([motherboard_inventory_custom_item_number(), $workOrderId]);
        return $stmt->fetchAll();
    }

    public function findCustomProduct(): ?array {
        $row = $this->findOneWhere('item_number = ?', [motherboard_inventory_custom_item_number()]);
        return $row ?: null;
    }

    public function lockCustomProduct(): array {
        $stmt = $this->db->prepare("SELECT * FROM inventory_products WHERE item_number = ? FOR UPDATE");
        $stmt->execute([motherboard_inventory_custom_item_number()]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new Exception(t('inventory.product_not_found'));
        }
        return $row;
    }

    public function isCustomProduct(array $product): bool {
        return motherboard_inventory_is_custom_item($product['item_number'] ?? null);
    }

    public function createProduct(array $data): int {
        $payload = $this->normalize($data);
        $this->assertItemNumberUnique($payload['item_number']);
        $payload['sold_count'] = 0;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return (int) $this->create($payload);
    }

    public function updateProduct(int $id, array $data): bool {
        $existing = $this->findById($id);
        if (!$existing) {
            throw new Exception(t('inventory.product_not_found'));
        }
        if ($this->isCustomProduct($existing)) {
            throw new Exception(t('inventory.custom_product_protected'));
        }
        $payload = $this->normalize($data);
        $this->assertItemNumberUnique($payload['item_number'], $id);
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $payload);
    }

    public function deleteProduct(int $id): bool {
        $product = $this->findById($id);
        if (!$product) {
            throw new Exception(t('inventory.product_not_found'));
        }
        if ($this->isCustomProduct($product)) {
            throw new Exception(t('inventory.custom_product_protected'));
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM work_order_products WHERE product_id = ?");
        $stmt->execute([$id]);
        if ((int) $stmt->fetch()['count'] > 0) {
            throw new Exception(t('inventory.product_in_use'));
        }
        return $this->delete($id);
    }

    public function lockById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM inventory_products WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function adjustStockAndSold(int $id, int $stockDelta, int $soldDelta): void {
        $product = $this->lockById($id);
        if (!$product) {
            throw new Exception(t('inventory.product_not_found'));
        }

        $stock = (int) $product['stock'];
        if ($stock !== -1) {
            $newStock = $stock + $stockDelta;
            if ($newStock < 0) {
                throw new Exception(t('inventory.insufficient_stock', [
                    'name' => $product['name'],
                    'available' => (string) $stock,
                ]));
            }
            $stock = $newStock;
        }

        $sold = max(0, (int) $product['sold_count'] + $soldDelta);

        $stmt = $this->db->prepare("UPDATE inventory_products SET stock = ?, sold_count = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$stock, $sold, date('Y-m-d H:i:s'), $id]);
    }

    private function normalize(array $data): array {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new Exception(t('inventory.product_required'));
        }

        $rawItemNumber = trim((string) ($data['item_number'] ?? ''));
        if ($rawItemNumber === '') {
            throw new Exception(t('inventory.item_number_required'));
        }
        $itemNumber = motherboard_inventory_slugify_item_number($rawItemNumber);
        if ($itemNumber === '' || strlen($itemNumber) > 100) {
            throw new Exception(t('inventory.item_number_invalid'));
        }
        if (motherboard_inventory_is_custom_item($itemNumber)) {
            throw new Exception(t('inventory.item_number_reserved'));
        }

        $categoryId = $data['category_id'] ?? null;
        if ($categoryId === '' || $categoryId === null) {
            $categoryId = null;
        } else {
            $categoryId = (int) $categoryId;
            if ($categoryId <= 0) {
                $categoryId = null;
            }
        }

        $price = $data['price'] ?? 0;
        if (!is_numeric($price) || (float) $price < 0) {
            throw new Exception(t('inventory.invalid_price'));
        }

        $stock = $data['stock'] ?? 0;
        if (filter_var($stock, FILTER_VALIDATE_INT) === false) {
            throw new Exception(t('inventory.invalid_stock'));
        }
        $stock = (int) $stock;
        if ($stock < -1) {
            throw new Exception(t('inventory.invalid_stock'));
        }

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'item_number' => $itemNumber,
            'description' => trim((string) ($data['description'] ?? '')),
            'price' => number_format((float) $price, 2, '.', ''),
            'stock' => $stock,
            'taxable' => !empty($data['taxable']) ? 1 : 0,
        ];
    }

    private function assertItemNumberUnique(string $itemNumber, ?int $ignoreId = null): void {
        if ($ignoreId) {
            $existing = $this->findOneWhere('item_number = ? AND id != ?', [$itemNumber, $ignoreId]);
        } else {
            $existing = $this->findOneWhere('item_number = ?', [$itemNumber]);
        }
        if ($existing) {
            throw new Exception(t('inventory.item_number_exists'));
        }
    }
}
