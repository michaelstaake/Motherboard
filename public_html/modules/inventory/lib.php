<?php

require_once ROOT_PATH . '/models/Settings.php';

function motherboard_inventory_path(): string {
    return MODULES_PATH . '/inventory';
}

function motherboard_inventory_format_price($price): string {
    return number_format((float) $price, 2, '.', ',');
}

function motherboard_inventory_format_tax_rate($rate): string {
    $formatted = rtrim(rtrim(number_format((float) $rate, 4, '.', ''), '0'), '.');
    return $formatted === '' ? '0' : $formatted;
}

function motherboard_inventory_normalize_tax_rate($value): ?float {
    if ($value === null || $value === '') {
        return 0.0;
    }
    if (!is_numeric($value)) {
        return null;
    }
    $rate = (float) $value;
    if ($rate < 0 || $rate > 100) {
        return null;
    }
    return round($rate, 4);
}

function motherboard_inventory_tax_rate(?Settings $settings = null): float {
    $settings = $settings ?: new Settings();
    $normalized = motherboard_inventory_normalize_tax_rate($settings->getSetting('inventory_tax_rate', '0'));
    return $normalized ?? 0.0;
}

function motherboard_inventory_show_on_printout(?Settings $settings = null): bool {
    $settings = $settings ?: new Settings();
    return $settings->getSetting('inventory_show_on_printout', '1') !== '0';
}

function motherboard_inventory_work_order_totals(array $assigned, ?float $taxRate = null): array {
    $taxRate = $taxRate ?? motherboard_inventory_tax_rate();
    $taxableTotal = 0.0;
    $nontaxableTotal = 0.0;
    foreach ($assigned as $line) {
        if (!empty($line['taxable'])) {
            $taxableTotal += (float) $line['line_total'];
        } else {
            $nontaxableTotal += (float) $line['line_total'];
        }
    }
    $taxAmount = round($taxableTotal * ($taxRate / 100), 2);
    return [
        'taxable' => $taxableTotal,
        'nontaxable' => $nontaxableTotal,
        'tax_rate' => $taxRate,
        'tax' => $taxAmount,
        'grand_total' => $taxableTotal + $nontaxableTotal + $taxAmount,
    ];
}

function motherboard_inventory_format_stock($stock): string {
    if ((int) $stock === -1) {
        return t('inventory.unlimited');
    }
    return (string) (int) $stock;
}

function motherboard_inventory_slugify_item_number(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[\s_]+/', '-', $value) ?? '';
    $value = preg_replace('/[^a-z0-9-]/', '', $value) ?? '';
    $value = preg_replace('/-+/', '-', $value) ?? '';
    return trim($value, '-');
}

function motherboard_inventory_custom_item_number(): string {
    return 'custom';
}

function motherboard_inventory_is_custom_item(?string $itemNumber): bool {
    return motherboard_inventory_slugify_item_number((string) $itemNumber) === motherboard_inventory_custom_item_number();
}

function motherboard_inventory_load_models(): void {
    require_once ROOT_PATH . '/core/Model.php';
    require_once motherboard_inventory_path() . '/models/InventoryCategory.php';
    require_once motherboard_inventory_path() . '/models/InventoryProduct.php';
    require_once motherboard_inventory_path() . '/models/InventoryWorkOrderProduct.php';
}
