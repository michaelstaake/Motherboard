<?php
require_once $definition['path'] . '/lib.php';
require_once $definition['path'] . '/schema.php';
require_once ROOT_PATH . '/models/Settings.php';

motherboard_inventory_load_models();

$inventoryPath = $definition['path'];
$inventoryController = $definition['path'] . '/controllers/InventoryController.php';

Hooks::addAction('app.ready', function ($router, Database $database) {
    motherboard_inventory_ensure_schema($database);
});

Hooks::addAction('router.register', function (Router $router) use ($inventoryController): void {
    $router->addRoute('/inventory', 'InventoryController', 'index', $inventoryController);
    $router->addRoute('/inventory/categories', 'InventoryController', 'createCategory', $inventoryController);
    $router->addRoute('/inventory/categories/{id}', 'InventoryController', 'updateCategory', $inventoryController);
    $router->addRoute('/inventory/categories/{id}/delete', 'InventoryController', 'deleteCategory', $inventoryController);
    $router->addRoute('/inventory/products', 'InventoryController', 'createProduct', $inventoryController);
    $router->addRoute('/inventory/products/{id}', 'InventoryController', 'updateProduct', $inventoryController);
    $router->addRoute('/inventory/products/{id}/delete', 'InventoryController', 'deleteProduct', $inventoryController);
    $router->addRoute('/work-orders/view/{id}/products', 'InventoryController', 'addWorkOrderProduct', $inventoryController);
    $router->addRoute('/work-orders/view/{id}/products/{lineId}', 'InventoryController', 'updateWorkOrderProduct', $inventoryController);
    $router->addRoute('/work-orders/view/{id}/products/{lineId}/delete', 'InventoryController', 'removeWorkOrderProduct', $inventoryController);
});

Hooks::addAction('layout.nav.after_customers', function (): void {
    echo '<a href="' . htmlspecialchars(BASE_URL . '/inventory') . '" class="block py-2 text-gray-600 hover:text-gray-900 md:inline-flex md:py-2 md:px-4">'
        . htmlspecialchars(t('nav.inventory'))
        . '</a>';
});

Hooks::addAction('work_order.view.before_attachments', function (array $workOrder, array $context) use ($inventoryPath): void {
    if (empty($workOrder['id'])) {
        return;
    }
    $lineModel = new InventoryWorkOrderProduct();
    $productModel = new InventoryProduct();
    $assigned = $lineModel->getByWorkOrder((int) $workOrder['id']);
    $available = $productModel->getAvailableForWorkOrder((int) $workOrder['id']);
    $canEdit = !empty($context['canEdit']);
    $csrf_token = $context['csrf_token'] ?? '';
    include $inventoryPath . '/views/work-order-section.php';
});

Hooks::addAction('work_order.print.before_attachments', function (array $workOrder) use ($inventoryPath): void {
    if (empty($workOrder['id']) || !motherboard_inventory_show_on_printout()) {
        return;
    }
    $lineModel = new InventoryWorkOrderProduct();
    $assigned = $lineModel->getByWorkOrder((int) $workOrder['id']);
    if (!$assigned) {
        return;
    }
    include $inventoryPath . '/views/work-order-print.php';
});

Hooks::addAction('work_order.delete.before', function (int $id) {
    $lineModel = new InventoryWorkOrderProduct();
    $productModel = new InventoryProduct();
    $lineModel->restoreStockForWorkOrder($id, $productModel);
});

Hooks::addFilter('module.settings.save.inventory', function (array $result, array $post, Settings $settings): array {
    $rate = motherboard_inventory_normalize_tax_rate($post['inventory_tax_rate'] ?? '');
    if ($rate === null) {
        $result['error'] = t('inventory.invalid_tax_rate');
        return $result;
    }
    $settings->setSetting('inventory_tax_rate', motherboard_inventory_format_tax_rate($rate));
    $settings->setSetting('inventory_show_on_printout', isset($post['inventory_show_on_printout']) ? '1' : '0');
    return $result;
});
