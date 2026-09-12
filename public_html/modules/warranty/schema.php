<?php

function motherboard_warranty_ensure_schema(Database $database): void {
    $pdo = $database->connect();

    if (motherboard_warranty_table_exists($pdo, 'work_order_warranty')) {
        return;
    }

    $pdo->exec("CREATE TABLE work_order_warranty (
        work_order_id INT NOT NULL PRIMARY KEY,
        reference_work_order_id INT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        KEY idx_work_order_warranty_reference (reference_work_order_id),
        FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (reference_work_order_id) REFERENCES work_orders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function motherboard_warranty_table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
    return $stmt && $stmt->rowCount() > 0;
}
