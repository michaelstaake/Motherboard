<?php
require_once ROOT_PATH . '/core/Model.php';

class InventoryWorkOrderProduct extends Model {
    protected $table = 'work_order_products';

    public function getByWorkOrder(int $workOrderId): array {
        $stmt = $this->db->prepare("
            SELECT wop.*, p.stock AS current_stock, p.sold_count, p.description AS product_description, c.name AS category_name
            FROM work_order_products wop
            LEFT JOIN inventory_products p ON p.id = wop.product_id
            LEFT JOIN inventory_categories c ON c.id = p.category_id
            WHERE wop.work_order_id = ?
            ORDER BY wop.id ASC
        ");
        $stmt->execute([$workOrderId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $lineDescription = trim((string) ($row['description'] ?? ''));
            $productDescription = trim((string) ($row['product_description'] ?? ''));
            $row['description'] = $lineDescription !== '' ? $lineDescription : $productDescription;
            $row['is_custom'] = motherboard_inventory_is_custom_item($row['item_number'] ?? null);
            $row['line_total'] = round((float) $row['unit_price'] * (int) $row['quantity'], 2);
        }
        unset($row);
        return $rows;
    }

    public function findLine(int $id): ?array {
        $row = $this->findById($id);
        return $row ?: null;
    }

    public function addProduct(int $workOrderId, int $productId, int $quantity, InventoryProduct $productModel): int {
        if ($quantity < 1) {
            throw new Exception(t('inventory.invalid_quantity'));
        }

        $this->db->beginTransaction();
        try {
            $product = $productModel->lockById($productId);
            if (!$product) {
                throw new Exception(t('inventory.product_not_found'));
            }
            if ($productModel->isCustomProduct($product)) {
                throw new Exception(t('inventory.select_product'));
            }

            $existing = $this->findOneWhere('work_order_id = ? AND product_id = ?', [$workOrderId, $productId]);
            if ($existing) {
                $this->setQuantityInternal((int) $existing['id'], (int) $existing['quantity'] + $quantity, $productModel);
                $this->db->commit();
                return (int) $existing['id'];
            }

            $productModel->adjustStockAndSold($productId, $this->stockDeltaForTake($product, $quantity), $quantity);

            $lineId = (int) $this->create([
                'work_order_id' => $workOrderId,
                'product_id' => $productId,
                'product_name' => $product['name'],
                'item_number' => $product['item_number'],
                'quantity' => $quantity,
                'unit_price' => $product['price'],
                'taxable' => !empty($product['taxable']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->db->commit();
            return $lineId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function addCustomProduct(int $workOrderId, array $data, InventoryProduct $productModel): int {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new Exception(t('inventory.product_required'));
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity < 1) {
            throw new Exception(t('inventory.invalid_quantity'));
        }

        $price = $data['price'] ?? 0;
        if (!is_numeric($price) || (float) $price < 0) {
            throw new Exception(t('inventory.invalid_price'));
        }

        $this->db->beginTransaction();
        try {
            $custom = $productModel->lockCustomProduct();
            $productModel->adjustStockAndSold((int) $custom['id'], $this->stockDeltaForTake($custom, $quantity), $quantity);

            $lineId = (int) $this->create([
                'work_order_id' => $workOrderId,
                'product_id' => (int) $custom['id'],
                'product_name' => $name,
                'item_number' => motherboard_inventory_custom_item_number(),
                'description' => trim((string) ($data['description'] ?? '')),
                'quantity' => $quantity,
                'unit_price' => number_format((float) $price, 2, '.', ''),
                'taxable' => !empty($data['taxable']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->db->commit();
            return $lineId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateQuantity(int $lineId, int $quantity, InventoryProduct $productModel): void {
        if ($quantity < 1) {
            throw new Exception(t('inventory.invalid_quantity'));
        }
        $this->db->beginTransaction();
        try {
            $this->setQuantityInternal($lineId, $quantity, $productModel);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function removeProduct(int $lineId, InventoryProduct $productModel): array {
        $this->db->beginTransaction();
        try {
            $line = $this->lockLine($lineId);
            if (!$line) {
                throw new Exception(t('inventory.line_not_found'));
            }
            if (!empty($line['product_id'])) {
                $product = $productModel->lockById((int) $line['product_id']);
                if ($product) {
                    $qty = (int) $line['quantity'];
                    $productModel->adjustStockAndSold((int) $line['product_id'], $this->stockDeltaForReturn($product, $qty), -$qty);
                }
            }
            $this->delete($lineId);
            $this->db->commit();
            return $line;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function restoreStockForWorkOrder(int $workOrderId, InventoryProduct $productModel): void {
        $lines = $this->getByWorkOrder($workOrderId);
        if (!$lines) {
            return;
        }
        $this->db->beginTransaction();
        try {
            foreach ($lines as $line) {
                if (empty($line['product_id'])) {
                    continue;
                }
                $product = $productModel->lockById((int) $line['product_id']);
                if (!$product) {
                    continue;
                }
                $qty = (int) $line['quantity'];
                $productModel->adjustStockAndSold((int) $line['product_id'], $this->stockDeltaForReturn($product, $qty), -$qty);
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function setQuantityInternal(int $lineId, int $quantity, InventoryProduct $productModel): void {
        $line = $this->lockLine($lineId);
        if (!$line) {
            throw new Exception(t('inventory.line_not_found'));
        }

        $current = (int) $line['quantity'];
        $delta = $quantity - $current;
        if ($delta === 0) {
            return;
        }

        if (!empty($line['product_id'])) {
            $product = $productModel->lockById((int) $line['product_id']);
            if ($product) {
                if ($delta > 0) {
                    $productModel->adjustStockAndSold((int) $line['product_id'], $this->stockDeltaForTake($product, $delta), $delta);
                } else {
                    $restore = abs($delta);
                    $productModel->adjustStockAndSold((int) $line['product_id'], $this->stockDeltaForReturn($product, $restore), -$restore);
                }
            }
        }

        $this->update($lineId, [
            'quantity' => $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function lockLine(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM work_order_products WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function stockDeltaForTake(array $product, int $quantity): int {
        return (int) $product['stock'] === -1 ? 0 : -$quantity;
    }

    private function stockDeltaForReturn(array $product, int $quantity): int {
        return (int) $product['stock'] === -1 ? 0 : $quantity;
    }
}
