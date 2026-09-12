<?php

require_once ROOT_PATH . '/models/Settings.php';

function motherboard_warranty_path(): string {
    return MODULES_PATH . '/warranty';
}

function motherboard_warranty_load_models(): void {
    require_once ROOT_PATH . '/core/Model.php';
    require_once motherboard_warranty_path() . '/models/WorkOrderWarranty.php';
}

function motherboard_warranty_show_on_printout(?Settings $settings = null): bool {
    $settings = $settings ?: new Settings();
    return $settings->getSetting('warranty_show_on_printout', '1') !== '0';
}

function motherboard_warranty_zero_prices(?Settings $settings = null): bool {
    $settings = $settings ?: new Settings();
    return $settings->getSetting('warranty_zero_inventory_prices', '0') === '1';
}

/**
 * The line appended under the problem description. The reference is the work order id,
 * which is the number shown everywhere else in the interface.
 */
function motherboard_warranty_line(?int $referenceWorkOrderId): string {
    if ($referenceWorkOrderId) {
        return t('warranty.marker_reference', ['number' => (string) $referenceWorkOrderId]);
    }
    return t('warranty.marker');
}

/**
 * Session bucket for the create wizard. Warranty is chosen on step 3 but cannot be stored
 * until step 5 inserts the row, and it must stay out of $_SESSION['work_order_data'] so it
 * never reaches the work_orders insert.
 */
function motherboard_warranty_pending(): array {
    $pending = $_SESSION['warranty_create'] ?? [];
    return [
        'is_warranty' => !empty($pending['is_warranty']),
        'reference_work_order_id' => isset($pending['reference_work_order_id']) && $pending['reference_work_order_id'] !== null
            ? (int) $pending['reference_work_order_id']
            : null,
    ];
}

/**
 * Label for one entry in the reference picker: the number users see, when it was opened,
 * and the device, so a shop can tell two visits apart.
 */
function motherboard_warranty_option_label(array $workOrder): string {
    $parts = ['#' . (int) ($workOrder['id'] ?? 0)];

    $date = ldate($workOrder['created_at'] ?? '', 'M j, Y');
    if ($date !== '') {
        $parts[] = $date;
    }

    $device = trim(($workOrder['computer'] ?? '') . ' ' . ($workOrder['model'] ?? ''));
    if ($device !== '') {
        $parts[] = $device;
    }

    return implode(' - ', $parts);
}
