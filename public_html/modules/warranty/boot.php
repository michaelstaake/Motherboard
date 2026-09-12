<?php
require_once $definition['path'] . '/lib.php';
require_once $definition['path'] . '/schema.php';
require_once ROOT_PATH . '/models/Settings.php';
require_once ROOT_PATH . '/models/WorkOrder.php';

motherboard_warranty_load_models();

$warrantyPath = $definition['path'];
$warrantyController = $definition['path'] . '/controllers/WarrantyController.php';

Hooks::addAction('app.ready', function ($router, Database $database) {
    motherboard_warranty_ensure_schema($database);
});

Hooks::addAction('router.register', function (Router $router) use ($warrantyController): void {
    $router->addRoute('/work-orders/view/{id}/warranty', 'WarrantyController', 'save', $warrantyController);
});

// Create wizard: the checkbox and reference picker live under the step 3 description.
Hooks::addAction('work_order.create.step3.after_description', function (array $workOrderData, array $context) use ($warrantyPath): void {
    $pending = motherboard_warranty_pending();
    $customerId = (int) ($workOrderData['customer_id'] ?? 0);
    $model = new WorkOrderWarranty();
    $previousWorkOrders = $model->referenceOptions($customerId);
    $isWarranty = $pending['is_warranty'];
    $referenceId = $pending['reference_work_order_id'];
    include $warrantyPath . '/views/create-step3.php';
});

Hooks::addAction('work_order.create.step', function (int $step, array $post): void {
    if ($step === 1) {
        // A fresh wizard run must not inherit the previous one's answer.
        unset($_SESSION['warranty_create']);
        return;
    }
    if ($step !== 3) {
        return;
    }
    $isWarranty = !empty($post['warranty_is_warranty']);
    $reference = isset($post['warranty_reference_work_order_id']) && $post['warranty_reference_work_order_id'] !== ''
        ? (int) $post['warranty_reference_work_order_id']
        : null;
    $_SESSION['warranty_create'] = [
        'is_warranty' => $isWarranty,
        'reference_work_order_id' => $isWarranty ? $reference : null,
    ];
});

Hooks::addAction('work_order.create.after', function ($workOrderId, array $workOrderData): void {
    $pending = motherboard_warranty_pending();
    unset($_SESSION['warranty_create']);
    if (!$pending['is_warranty']) {
        return;
    }

    $workOrderId = (int) $workOrderId;
    $model = new WorkOrderWarranty();
    $reference = $model->validateReference(
        (int) ($workOrderData['customer_id'] ?? 0),
        $pending['reference_work_order_id'],
        $workOrderId
    );
    $model->setWarranty($workOrderId, $reference);

    (new WorkOrder())->logWorkOrderAction(
        $workOrderId,
        'warranty_set',
        $reference
            ? t('warranty.log_set_reference', ['number' => (string) $reference])
            : t('warranty.log_set')
    );
});

// The warranty line renders under the problem description instead of being written into it,
// so the printout toggle can drop it and editing the description never duplicates it.
Hooks::addAction('work_order.description.after', function (array $workOrder, string $context): void {
    if (empty($workOrder['id'])) {
        return;
    }
    if ($context === 'print' && !motherboard_warranty_show_on_printout()) {
        return;
    }

    $warranty = (new WorkOrderWarranty())->getForWorkOrder((int) $workOrder['id']);
    if (!$warranty) {
        return;
    }

    $line = motherboard_warranty_line($warranty['reference_work_order_id']);
    $class = $context === 'print'
        ? 'mt-2 text-xs font-bold text-gray-900'
        : 'mt-2 text-sm font-bold text-gray-900';
    echo '<p class="' . $class . '">' . htmlspecialchars($line) . '</p>';
});

Hooks::addAction('work_order.view.after_customer_info', function (array $workOrder, array $context) use ($warrantyPath): void {
    if (empty($workOrder['id'])) {
        return;
    }
    $model = new WorkOrderWarranty();
    $warranty = $model->getForWorkOrder((int) $workOrder['id']);
    $previousWorkOrders = $model->referenceOptions((int) ($workOrder['customer_id'] ?? 0), (int) $workOrder['id']);
    $canEdit = !empty($context['canEdit']);
    $csrf_token = $context['csrf_token'] ?? '';
    include $warrantyPath . '/views/work-order-section.php';
});

// Warranty parts are not billed, so the work order and its printout can show 0.00.
Hooks::addFilter('inventory.work_order.lines', function (array $lines, int $workOrderId): array {
    if (!$lines || !motherboard_warranty_zero_prices()) {
        return $lines;
    }
    if (!(new WorkOrderWarranty())->isWarranty($workOrderId)) {
        return $lines;
    }
    foreach ($lines as &$line) {
        $line['unit_price'] = '0.00';
        $line['line_total'] = 0.0;
    }
    unset($line);
    return $lines;
});

Hooks::addFilter('module.settings.save.warranty', function (array $result, array $post, Settings $settings): array {
    $settings->setSetting('warranty_show_on_printout', isset($post['warranty_show_on_printout']) ? '1' : '0');
    $settings->setSetting('warranty_zero_inventory_prices', isset($post['warranty_zero_inventory_prices']) ? '1' : '0');
    return $result;
});
