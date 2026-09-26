<?php

function motherboard_inventory_ensure_schema(Database $database): void {
    $pdo = $database->connect();

    if (!motherboard_inventory_table_exists($pdo, 'inventory_categories')) {
        $pdo->exec("CREATE TABLE inventory_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NULL,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            KEY idx_inventory_categories_parent (parent_id),
            FOREIGN KEY (parent_id) REFERENCES inventory_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        motherboard_inventory_ensure_subcategories($pdo);
    }

    if (!motherboard_inventory_table_exists($pdo, 'inventory_products')) {
        $pdo->exec("CREATE TABLE inventory_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NULL,
            name VARCHAR(255) NOT NULL,
            item_number VARCHAR(100) NOT NULL,
            description TEXT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            stock INT NOT NULL DEFAULT 0,
            sold_count INT NOT NULL DEFAULT 0,
            taxable TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY unique_inventory_item_number (item_number),
            KEY idx_inventory_products_category (category_id),
            KEY idx_inventory_products_name (name),
            FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        motherboard_inventory_ensure_item_number_required($pdo);
    }

    if (!motherboard_inventory_table_exists($pdo, 'work_order_products')) {
        $pdo->exec("CREATE TABLE work_order_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            work_order_id INT NOT NULL,
            product_id INT NULL,
            product_name VARCHAR(255) NOT NULL,
            item_number VARCHAR(100) NULL,
            description TEXT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            taxable TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            KEY idx_work_order_products_wo (work_order_id),
            KEY idx_work_order_products_product (product_id),
            FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES inventory_products(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        motherboard_inventory_ensure_work_order_product_lines($pdo);
    }

    if (!motherboard_inventory_table_exists($pdo, 'inventory_movements')) {
        $pdo->exec("CREATE TABLE inventory_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NULL,
            product_name VARCHAR(255) NOT NULL,
            item_number VARCHAR(100) NOT NULL,
            movement_type VARCHAR(20) NOT NULL,
            quantity INT NULL,
            stock_before INT NULL,
            stock_after INT NULL,
            work_order_id INT NULL,
            work_order_number VARCHAR(20) NULL,
            user_id INT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_inventory_movements_product (product_id),
            KEY idx_inventory_movements_created (created_at),
            FOREIGN KEY (product_id) REFERENCES inventory_products(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        motherboard_inventory_backfill_movements($pdo);
    }

    motherboard_inventory_ensure_custom_product($pdo);
}

/**
 * Movements were not recorded before the table existed, so seed it with the sales that
 * work orders still show. Stock edits made by staff before then left no trace to recover.
 */
function motherboard_inventory_backfill_movements(PDO $pdo): void {
    if (!motherboard_inventory_table_exists($pdo, 'work_order_products')) {
        return;
    }
    $stmt = $pdo->prepare("
        INSERT INTO inventory_movements
            (product_id, product_name, item_number, movement_type, quantity, stock_before, stock_after,
             work_order_id, work_order_number, user_id, created_at)
        SELECT wop.product_id, p.name, p.item_number, 'sale', -wop.quantity, NULL, NULL,
               wop.work_order_id, wo.work_order_number, NULL, wop.created_at
        FROM work_order_products wop
        JOIN inventory_products p ON p.id = wop.product_id
        LEFT JOIN work_orders wo ON wo.id = wop.work_order_id
        WHERE p.item_number <> ?
        ORDER BY wop.created_at ASC, wop.id ASC
    ");
    $stmt->execute([motherboard_inventory_custom_item_number()]);
}

function motherboard_inventory_table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
    return $stmt && $stmt->rowCount() > 0;
}

function motherboard_inventory_index_exists(PDO $pdo, string $table, string $index): bool {
    $stmt = $pdo->query('SHOW INDEX FROM `' . str_replace('`', '', $table) . '`');
    foreach ($stmt->fetchAll() as $row) {
        if (($row['Key_name'] ?? '') === $index) {
            return true;
        }
    }
    return false;
}

function motherboard_inventory_ensure_item_number_required(PDO $pdo): void {
    $column = $pdo->query("SHOW COLUMNS FROM inventory_products LIKE 'item_number'")->fetch();
    if (!$column) {
        return;
    }

    $nullable = strtoupper((string) ($column['Null'] ?? '')) === 'YES';
    $hasUnique = motherboard_inventory_index_exists($pdo, 'inventory_products', 'unique_inventory_item_number');
    $rows = $pdo->query('SELECT id, item_number FROM inventory_products ORDER BY id ASC')->fetchAll();

    $seen = [];
    $needsSlugFix = false;
    foreach ($rows as $row) {
        $current = (string) ($row['item_number'] ?? '');
        $slug = motherboard_inventory_slugify_item_number($current);
        if ($current === '' || $current !== $slug || isset($seen[$slug])) {
            $needsSlugFix = true;
            break;
        }
        $seen[$slug] = true;
    }

    if (!$needsSlugFix) {
        if ($nullable) {
            $pdo->exec('ALTER TABLE inventory_products MODIFY item_number VARCHAR(100) NOT NULL');
        }
        if (!$hasUnique) {
            $pdo->exec('ALTER TABLE inventory_products ADD UNIQUE KEY unique_inventory_item_number (item_number)');
        }
        return;
    }

    if ($hasUnique) {
        $pdo->exec('ALTER TABLE inventory_products DROP INDEX unique_inventory_item_number');
    }

    $used = [];
    $updateProduct = $pdo->prepare('UPDATE inventory_products SET item_number = ? WHERE id = ?');
    $hasLines = motherboard_inventory_table_exists($pdo, 'work_order_products');
    $updateLines = $hasLines
        ? $pdo->prepare('UPDATE work_order_products SET item_number = ? WHERE product_id = ?')
        : null;

    foreach ($rows as $row) {
        $slug = motherboard_inventory_slugify_item_number((string) ($row['item_number'] ?? ''));
        if ($slug === '') {
            $slug = 'ITEM-' . (int) $row['id'];
        }
        $base = $slug;
        $suffix = 2;
        while (isset($used[$slug])) {
            $candidate = $base . '-' . $suffix;
            if (strlen($candidate) > 100) {
                $candidate = substr($base, 0, max(1, 100 - strlen((string) $suffix) - 1)) . '-' . $suffix;
            }
            $slug = $candidate;
            $suffix++;
        }
        $used[$slug] = true;

        if ($slug !== (string) ($row['item_number'] ?? '')) {
            $updateProduct->execute([$slug, $row['id']]);
            if ($updateLines) {
                $updateLines->execute([$slug, $row['id']]);
            }
        }
    }

    // Lines whose product was deleted still carry the old item number.
    if ($hasLines) {
        $pdo->exec('UPDATE work_order_products SET item_number = UPPER(item_number) WHERE product_id IS NULL AND item_number IS NOT NULL');
    }

    if ($nullable) {
        $pdo->exec('ALTER TABLE inventory_products MODIFY item_number VARCHAR(100) NOT NULL');
    }

    $pdo->exec('ALTER TABLE inventory_products ADD UNIQUE KEY unique_inventory_item_number (item_number)');
}

function motherboard_inventory_table_columns(PDO $pdo, string $table): array {
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
    return array_column($stmt->fetchAll(), 'Field');
}

/**
 * Subcategories: each category may sit under a parent. Names only need to be unique among
 * siblings now ("Accessories" can live under both Laptops and Phones), which the model
 * checks, so the table-wide unique name index goes.
 */
function motherboard_inventory_ensure_subcategories(PDO $pdo): void {
    $columns = motherboard_inventory_table_columns($pdo, 'inventory_categories');
    if (!in_array('parent_id', $columns, true)) {
        $pdo->exec('ALTER TABLE inventory_categories ADD COLUMN parent_id INT NULL AFTER id');
        $pdo->exec('ALTER TABLE inventory_categories ADD KEY idx_inventory_categories_parent (parent_id)');
        $pdo->exec('ALTER TABLE inventory_categories ADD FOREIGN KEY (parent_id) REFERENCES inventory_categories(id) ON DELETE SET NULL');
    }
    if (motherboard_inventory_index_exists($pdo, 'inventory_categories', 'unique_inventory_category_name')) {
        $pdo->exec('ALTER TABLE inventory_categories DROP INDEX unique_inventory_category_name');
    }
}

function motherboard_inventory_ensure_work_order_product_lines(PDO $pdo): void {
    $columns = motherboard_inventory_table_columns($pdo, 'work_order_products');
    if (!in_array('description', $columns, true)) {
        $after = in_array('item_number', $columns, true) ? ' AFTER item_number' : '';
        $pdo->exec("ALTER TABLE work_order_products ADD COLUMN description TEXT NULL{$after}");
    }

    if (motherboard_inventory_index_exists($pdo, 'work_order_products', 'unique_work_order_product')) {
        $pdo->exec('ALTER TABLE work_order_products DROP INDEX unique_work_order_product');
    }
}

function motherboard_inventory_ensure_custom_product(PDO $pdo): void {
    $itemNumber = motherboard_inventory_custom_item_number();
    $stmt = $pdo->prepare('SELECT id, stock FROM inventory_products WHERE item_number = ? LIMIT 1');
    $stmt->execute([$itemNumber]);
    $existing = $stmt->fetch();
    $now = date('Y-m-d H:i:s');

    if ($existing) {
        if ((int) $existing['stock'] !== -1) {
            $update = $pdo->prepare('UPDATE inventory_products SET stock = -1, updated_at = ? WHERE id = ?');
            $update->execute([$now, (int) $existing['id']]);
        }
        return;
    }

    $insert = $pdo->prepare("
        INSERT INTO inventory_products
            (category_id, name, item_number, description, price, stock, sold_count, taxable, created_at, updated_at)
        VALUES
            (NULL, ?, ?, '', 0.00, -1, 0, 1, ?, ?)
    ");
    $insert->execute(['Custom', $itemNumber, $now, $now]);
}
