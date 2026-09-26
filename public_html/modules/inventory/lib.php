<?php

require_once ROOT_PATH . '/models/Settings.php';

function motherboard_inventory_path(): string {
    return MODULES_PATH . '/inventory';
}

function motherboard_inventory_format_price($price): string {
    return number_format((float) $price, 2, '.', ',');
}

function motherboard_inventory_currency(?Settings $settings = null): string {
    // Every amount on a page asks for this, and Settings hits the database on each
    // read, so remember the symbol for the request once it has been looked up.
    static $cached = null;
    if ($settings !== null) {
        return trim((string) $settings->getSetting('currency', '$'));
    }
    if ($cached === null) {
        $cached = motherboard_inventory_currency(new Settings());
    }
    return $cached;
}

function motherboard_inventory_format_money($amount, ?Settings $settings = null): string {
    $currency = motherboard_inventory_currency($settings);
    if ($currency === '') {
        return motherboard_inventory_format_price($amount);
    }
    // Word-like currencies ("USD") need the space that symbols ("$", "€") do not.
    $separator = preg_match('/[\p{L}\p{N}]$/u', $currency) ? ' ' : '';
    return $currency . $separator . motherboard_inventory_format_price($amount);
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

function motherboard_inventory_hide_printout_heading(?Settings $settings = null): bool {
    $settings = $settings ?: new Settings();
    return $settings->getSetting('inventory_hide_printout_heading', '0') === '1';
}

function motherboard_inventory_hide_subtotals(?Settings $settings = null): bool {
    $settings = $settings ?: new Settings();
    return $settings->getSetting('inventory_hide_subtotals', '0') === '1';
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

const MOTHERBOARD_INVENTORY_TAXABLE_COOKIE = 'motherboard_inventory_taxable';

/**
 * Whether the Add Product form should start with Taxable checked: it follows the last
 * product added on this computer, so a run of similar products needs no re-checking.
 */
function motherboard_inventory_last_taxable(): bool {
    return ($_COOKIE[MOTHERBOARD_INVENTORY_TAXABLE_COOKIE] ?? '1') !== '0';
}

function motherboard_inventory_remember_taxable(bool $taxable): void {
    $params = session_get_cookie_params();
    setcookie(MOTHERBOARD_INVENTORY_TAXABLE_COOKIE, $taxable ? '1' : '0', [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool) ($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Spreadsheet apps run cells that start with =, +, -, or @ as formulas, so text that
 * staff typed (names, descriptions) is prefixed with an apostrophe. Plain numbers such as
 * a -3 quantity change are left alone.
 */
function motherboard_inventory_csv_cell($value): string {
    $value = (string) $value;
    if ($value !== '' && !is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
        return "'" . $value;
    }
    return $value;
}

function motherboard_inventory_slugify_item_number(string $value): string {
    $value = strtoupper(trim($value));
    $value = preg_replace('/[\s_]+/', '-', $value) ?? '';
    $value = preg_replace('/[^A-Z0-9-]/', '', $value) ?? '';
    $value = preg_replace('/-+/', '-', $value) ?? '';
    return trim($value, '-');
}

function motherboard_inventory_custom_item_number(): string {
    return 'CUSTOM';
}

function motherboard_inventory_is_custom_item(?string $itemNumber): bool {
    return motherboard_inventory_slugify_item_number((string) $itemNumber) === motherboard_inventory_custom_item_number();
}

function motherboard_inventory_load_models(): void {
    require_once ROOT_PATH . '/core/Model.php';
    require_once motherboard_inventory_path() . '/models/InventoryCategory.php';
    require_once motherboard_inventory_path() . '/models/InventoryProduct.php';
    require_once motherboard_inventory_path() . '/models/InventoryWorkOrderProduct.php';
    require_once motherboard_inventory_path() . '/models/InventoryMovement.php';
}
