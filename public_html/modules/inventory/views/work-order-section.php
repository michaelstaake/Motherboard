<?php
$assigned = $assigned ?? [];
$available = $available ?? [];
$canEdit = $canEdit ?? false;
$csrf_token = $csrf_token ?? '';
$workOrderId = (int) ($workOrder['id'] ?? 0);
$totals = motherboard_inventory_work_order_totals($assigned);
?>

<div class="grid grid-cols-1 gap-6">
    <div class="mt-6 bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900"><?= t('inventory.wo_section') ?></h2>
            <?php if ($canEdit): ?>
                <button type="button" onclick="openAddInventoryProductModal()" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <?= t('inventory.wo_add') ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="px-6 py-4">
            <?php if (empty($assigned)): ?>
                <p class="text-sm text-gray-500"><?= t('inventory.wo_none') ?></p>
            <?php else: ?>
                <div class="overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.product_name') ?></th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.quantity') ?></th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.price') ?></th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.line_total') ?></th>
                                <?php if ($canEdit): ?>
                                    <th class="px-2 py-2"></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($assigned as $line): ?>
                                <tr>
                                    <td class="px-2 py-3 relative group <?= !empty($line['description']) ? 'cursor-help' : '' ?>">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($line['product_name']) ?></div>
                                        <?php if (!empty($line['item_number'])): ?>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($line['item_number']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($line['description'])): ?>
                                            <div role="tooltip" class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-opacity absolute z-30 left-2 top-full mt-1 w-max max-w-xs rounded-md bg-gray-900 px-3 py-2 text-left text-xs font-normal text-white shadow-lg pointer-events-none"><?= nl2br(htmlspecialchars($line['description'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-3 text-sm text-gray-900">
                                        <?php if ($canEdit): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/work-orders/view/<?= $workOrderId ?>/products/<?= (int) $line['id'] ?>" class="flex items-center space-x-2">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <input type="number" name="quantity" min="1" step="1" value="<?= (int) $line['quantity'] ?>" class="w-20 px-2 py-1 border-2 border-gray-300 rounded-md text-sm">
                                                <button type="submit" class="text-xs font-medium text-primary-600 hover:text-primary-500"><?= t('common.save') ?></button>
                                            </form>
                                        <?php else: ?>
                                            <?= (int) $line['quantity'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-3 text-sm text-gray-900">
                                        <?= htmlspecialchars(motherboard_inventory_format_price($line['unit_price'])) ?>
                                        <?php if (!empty($line['taxable'])): ?>
                                            <span class="ml-1 text-xs font-medium text-gray-500" title="<?= htmlspecialchars(t('inventory.taxable')) ?>"><?= t('inventory.taxable_mark') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-3 text-sm text-gray-900"><?= htmlspecialchars(motherboard_inventory_format_price($line['line_total'])) ?></td>
                                    <?php if ($canEdit): ?>
                                        <td class="px-2 py-3 text-right">
                                            <form method="POST" action="<?= BASE_URL ?>/work-orders/view/<?= $workOrderId ?>/products/<?= (int) $line['id'] ?>/delete" onsubmit="return confirm(<?= json_encode(t('inventory.wo_confirm_remove')) ?>)">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-500"><?= t('common.delete') ?></button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-sm text-gray-700 space-y-1 text-right">
                    <div><?= t('inventory.taxable_total') ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['taxable'])) ?></div>
                    <div><?= t('inventory.nontaxable_total') ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['nontaxable'])) ?></div>
                    <div><?= t('inventory.tax_amount', ['rate' => motherboard_inventory_format_tax_rate($totals['tax_rate'])]) ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['tax'])) ?></div>
                    <div class="font-medium text-gray-900"><?= t('inventory.grand_total') ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['grand_total'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div id="addInventoryProductModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closeAddInventoryProductModal()">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900"><?= t('inventory.wo_add') ?></h3>
                <button type="button" onclick="closeAddInventoryProductModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/work-orders/view/<?= $workOrderId ?>/products">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="space-y-4">
                    <div>
                        <label for="inventory_product_id" class="block text-sm font-medium text-gray-700"><?= t('inventory.product') ?></label>
                        <select id="inventory_product_id" name="product_id" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white" onchange="toggleInventoryCustomFields()">
                            <option value=""><?= t('inventory.select_product') ?></option>
                            <option value="<?= htmlspecialchars(motherboard_inventory_custom_item_number()) ?>"><?= t('inventory.custom_product') ?></option>
                            <?php foreach ($available as $product): ?>
                                <option value="<?= (int) $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                    (<?= htmlspecialchars($product['item_number']) ?>)
                                    — <?= htmlspecialchars(motherboard_inventory_format_price($product['price'])) ?>
                                    — <?= t('inventory.stock') ?>: <?= htmlspecialchars(motherboard_inventory_format_stock($product['stock'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="inventoryCustomFields" class="space-y-4 hidden">
                        <div>
                            <label for="inventory_custom_name" class="block text-sm font-medium text-gray-700"><?= t('inventory.product_name') ?></label>
                            <input type="text" id="inventory_custom_name" name="name" maxlength="255" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700"><?= t('inventory.item_number') ?></label>
                            <input type="text" value="<?= htmlspecialchars(motherboard_inventory_custom_item_number()) ?>" disabled class="mt-1 block w-full px-4 py-3 border-2 border-gray-200 rounded-md shadow-sm sm:text-sm bg-gray-50 text-gray-500">
                        </div>
                        <div>
                            <label for="inventory_custom_description" class="block text-sm font-medium text-gray-700"><?= t('inventory.description') ?></label>
                            <textarea id="inventory_custom_description" name="description" rows="3" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"></textarea>
                        </div>
                        <div>
                            <label for="inventory_custom_price" class="block text-sm font-medium text-gray-700"><?= t('inventory.price') ?></label>
                            <input type="number" id="inventory_custom_price" name="price" min="0" step="0.01" value="0.00" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        </div>
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="inventory_custom_taxable" name="taxable" value="1" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700"><?= t('inventory.taxable') ?></span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="inventory_quantity" class="block text-sm font-medium text-gray-700"><?= t('inventory.quantity') ?></label>
                        <input type="number" id="inventory_quantity" name="quantity" min="1" step="1" value="1" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('inventory.wo_add') ?>
                    </button>
                    <button type="button" onclick="closeAddInventoryProductModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        <?= t('common.cancel') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function toggleInventoryCustomFields() {
    const select = document.getElementById('inventory_product_id');
    const customFields = document.getElementById('inventoryCustomFields');
    const nameField = document.getElementById('inventory_custom_name');
    const priceField = document.getElementById('inventory_custom_price');
    const isCustom = select && select.value === <?= json_encode(motherboard_inventory_custom_item_number()) ?>;
    if (customFields) {
        customFields.classList.toggle('hidden', !isCustom);
    }
    if (nameField) {
        nameField.required = !!isCustom;
    }
    if (priceField) {
        priceField.required = !!isCustom;
    }
}
function openAddInventoryProductModal() {
    document.getElementById('addInventoryProductModal').classList.remove('hidden');
    toggleInventoryCustomFields();
}
function closeAddInventoryProductModal() {
    document.getElementById('addInventoryProductModal').classList.add('hidden');
}
document.getElementById('addInventoryProductModal').addEventListener('click', function (e) {
    if (e.target === this) closeAddInventoryProductModal();
});
toggleInventoryCustomFields();
</script>
<?php endif; ?>
