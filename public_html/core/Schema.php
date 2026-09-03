<?php
require_once ROOT_PATH . '/core/Crypto.php';

class Schema {
    public static function ensure(Database $database): void {
        $pdo = $database->connect();
        $columns = self::tableColumns($pdo, 'work_orders');

        if (!in_array('imei', $columns, true)) {
            $after = in_array('serial_number', $columns, true) ? ' AFTER serial_number' : '';
            $pdo->exec("ALTER TABLE work_orders ADD COLUMN imei VARCHAR(50) NULL{$after}");
            $columns[] = 'imei';
        }

        if (!in_array('remarks', $columns, true)) {
            $after = in_array('imei', $columns, true) ? ' AFTER imei' : '';
            $pdo->exec("ALTER TABLE work_orders ADD COLUMN remarks TEXT NULL{$after}");
        }

        self::ensureUserPreferences($pdo);
        self::ensureSecurityStorage($pdo);
        self::ensureAttachmentsTable($pdo);
        self::ensureDefaultSettings($pdo);
    }

    private static function ensureUserPreferences(PDO $pdo): void {
        $columns = self::tableColumns($pdo, 'users');
        if (!in_array('quick_nav_trigger_key', $columns, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN quick_nav_trigger_key VARCHAR(1) NOT NULL DEFAULT '/' AFTER last_login");
        }
    }

    private static function ensureSecurityStorage(PDO $pdo): void {
        $passwordType = self::columnType($pdo, 'work_orders', 'password');
        if ($passwordType !== null && !str_starts_with(strtolower($passwordType), 'text')) {
            $pdo->exec('ALTER TABLE work_orders MODIFY COLUMN password TEXT NULL');
        }

        if (self::tableExists($pdo, 'two_factor_codes')) {
            $columns = self::tableColumns($pdo, 'two_factor_codes');
            $codeType = self::columnType($pdo, 'two_factor_codes', 'code');
            if ($codeType === null || !str_starts_with(strtolower($codeType), 'varchar(255)')) {
                $pdo->exec('ALTER TABLE two_factor_codes MODIFY COLUMN code VARCHAR(255) NOT NULL');
                $pdo->exec('DELETE FROM two_factor_codes');
            }
            if (!in_array('attempts', $columns, true)) {
                $pdo->exec('ALTER TABLE two_factor_codes ADD COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER code');
            }
            if (!in_array('last_attempt_at', $columns, true)) {
                $pdo->exec('ALTER TABLE two_factor_codes ADD COLUMN last_attempt_at DATETIME NULL AFTER attempts');
            }
        }

        $passwords = $pdo->query("SELECT id, password FROM work_orders WHERE password IS NOT NULL AND password <> ''");
        $updatePassword = $pdo->prepare('UPDATE work_orders SET password = ? WHERE id = ?');
        foreach ($passwords->fetchAll() as $row) {
            if (!Crypto::isEncrypted($row['password'])) {
                $updatePassword->execute([Crypto::encrypt($row['password']), $row['id']]);
            }
        }

        $sensitiveKeys = ['turnstile_secret_key', 'recaptcha_secret_key', 's3_access_key', 's3_secret_key'];
        $placeholders = implode(',', array_fill(0, count($sensitiveKeys), '?'));
        $stmt = $pdo->prepare("SELECT id, setting_value FROM settings WHERE setting_key IN ({$placeholders}) AND setting_value <> ''");
        $stmt->execute($sensitiveKeys);
        $updateSetting = $pdo->prepare('UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE id = ?');
        foreach ($stmt->fetchAll() as $row) {
            if (!Crypto::isEncrypted($row['setting_value'])) {
                $updateSetting->execute([Crypto::encrypt($row['setting_value']), $row['id']]);
            }
        }
    }

    private static function ensureAttachmentsTable(PDO $pdo): void {
        if (!self::tableExists($pdo, 'work_order_attachments')) {
            $pdo->exec("CREATE TABLE work_order_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                work_order_id INT NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                stored_path VARCHAR(255) NOT NULL,
                storage_destination VARCHAR(64) NOT NULL DEFAULT 'local',
                description TEXT NULL,
                mime_type VARCHAR(127) NULL,
                file_size INT NOT NULL DEFAULT 0,
                uploaded_by INT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            return;
        }

        $columns = self::tableColumns($pdo, 'work_order_attachments');
        if (!in_array('storage_destination', $columns, true)) {
            $pdo->exec("ALTER TABLE work_order_attachments ADD COLUMN storage_destination VARCHAR(64) NOT NULL DEFAULT 'local' AFTER stored_path");
        }
    }

    private static function ensureDefaultSettings(PDO $pdo): void {
        $defaults = [
            'attachment_destination' => 'local',
            'attachment_max_size_mb' => '10',
            'attachment_allowed_extensions' => 'png,jpg,pdf,md,txt',
            'print_customer_signature' => '1',
            'print_technician_signature' => '1',
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())"
        );

        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }

    private static function tableExists(PDO $pdo, string $table): bool {
        $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
        return $stmt && $stmt->rowCount() > 0;
    }

    private static function tableColumns(PDO $pdo, string $table): array {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
        return array_column($stmt->fetchAll(), 'Field');
    }

    private static function columnType(PDO $pdo, string $table, string $column): ?string {
        $stmt = $pdo->prepare(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        $type = $stmt->fetchColumn();
        return is_string($type) ? $type : null;
    }
}
