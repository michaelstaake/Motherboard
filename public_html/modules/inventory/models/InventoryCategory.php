<?php
require_once ROOT_PATH . '/core/Model.php';

class InventoryCategory extends Model {
    protected $table = 'inventory_categories';

    /** A category, its subcategory, and that subcategory's own subcategory. */
    public const MAX_DEPTH = 3;

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

    /**
     * Every category in display order, each parent followed by its subcategories. Each
     * row carries its depth (1 for a top-level category), its full path of names, the ids
     * of itself and everything beneath it, the depth of its deepest descendant below it,
     * and a product count that includes its subcategories.
     */
    public function getTree(): array {
        $rows = $this->getAll();
        $children = [];
        foreach ($rows as $row) {
            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : 0;
            $children[$parentId][] = $row;
        }

        $tree = [];
        $walk = function (int $parentId, int $depth, array $path) use (&$walk, &$tree, $children): array {
            $ids = [];
            $count = 0;
            $height = 0;
            foreach ($children[$parentId] ?? [] as $row) {
                $id = (int) $row['id'];
                $row['depth'] = $depth;
                $row['path'] = array_merge($path, [$row['name']]);
                $index = count($tree);
                $tree[] = $row;
                [$subIds, $subCount, $subHeight] = $walk($id, $depth + 1, $row['path']);
                $tree[$index]['subtree_ids'] = array_merge([$id], $subIds);
                $tree[$index]['total_count'] = (int) $row['product_count'] + $subCount;
                $tree[$index]['height'] = $subHeight;
                $ids = array_merge($ids, $tree[$index]['subtree_ids']);
                $count += $tree[$index]['total_count'];
                $height = max($height, $subHeight + 1);
            }
            return [$ids, $count, $height];
        };
        $walk(0, 1, []);

        return $tree;
    }

    /** Category id => "Parent › Child" for labelling products. */
    public function getPaths(?array $tree = null): array {
        $paths = [];
        foreach ($tree ?? $this->getTree() as $row) {
            $paths[(int) $row['id']] = motherboard_inventory_category_path($row['path']);
        }
        return $paths;
    }

    public function createCategory(string $name, ?int $parentId = null): int {
        $name = trim($name);
        if ($name === '') {
            throw new Exception(t('inventory.category_required'));
        }
        $parentId = $this->validParent($parentId, null);
        $this->assertNameUnique($name, $parentId);
        return (int) $this->create([
            'parent_id' => $parentId,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateCategory(int $id, string $name, ?int $parentId = null): bool {
        $name = trim($name);
        if ($name === '') {
            throw new Exception(t('inventory.category_required'));
        }
        $parentId = $this->validParent($parentId, $id);
        $this->assertNameUnique($name, $parentId, $id);
        return $this->update($id, [
            'parent_id' => $parentId,
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Subcategories and products move up to the deleted category's parent, so removing a
     * level never loses anything. Products in a deleted top-level category become
     * uncategorized, as before.
     */
    public function deleteCategory(int $id): bool {
        $category = $this->findById($id);
        if (!$category) {
            return false;
        }
        $parentId = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE inventory_categories SET parent_id = ?, updated_at = ? WHERE parent_id = ?");
            $stmt->execute([$parentId, date('Y-m-d H:i:s'), $id]);
            $stmt = $this->db->prepare("UPDATE inventory_products SET category_id = ?, updated_at = ? WHERE category_id = ?");
            $stmt->execute([$parentId, date('Y-m-d H:i:s'), $id]);
            $deleted = $this->delete($id);
            $this->db->commit();
            return $deleted;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Checks that $parentId can hold the category being saved: it must exist, must not be
     * the category itself or one of its subcategories, and must leave the category's whole
     * branch within MAX_DEPTH levels.
     */
    private function validParent(?int $parentId, ?int $id): ?int {
        if (!$parentId || $parentId <= 0) {
            $parentId = null;
        }
        if ($parentId === null && $id === null) {
            return null;
        }

        $byId = [];
        foreach ($this->getTree() as $row) {
            $byId[(int) $row['id']] = $row;
        }
        if ($parentId !== null && !isset($byId[$parentId])) {
            throw new Exception(t('inventory.category_not_found'));
        }

        $height = 0;
        if ($id !== null && isset($byId[$id])) {
            if ($parentId !== null && in_array($parentId, $byId[$id]['subtree_ids'], true)) {
                throw new Exception(t('inventory.category_parent_invalid'));
            }
            $height = (int) $byId[$id]['height'];
        }

        $parentDepth = $parentId !== null ? (int) $byId[$parentId]['depth'] : 0;
        if ($parentDepth + 1 + $height > self::MAX_DEPTH) {
            throw new Exception(t('inventory.category_too_deep', ['max' => (string) self::MAX_DEPTH]));
        }
        return $parentId;
    }

    private function assertNameUnique(string $name, ?int $parentId, ?int $ignoreId = null): void {
        $where = $parentId === null ? 'name = ? AND parent_id IS NULL' : 'name = ? AND parent_id = ?';
        $params = $parentId === null ? [$name] : [$name, $parentId];
        if ($ignoreId !== null) {
            $where .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        if ($this->findOneWhere($where, $params)) {
            throw new Exception(t('inventory.category_exists'));
        }
    }
}
