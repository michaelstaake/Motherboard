<?php
$title = t('module.inventory.name') . ' - ' . ($companyName ?? APP_NAME);
$taxRate = motherboard_inventory_format_tax_rate($settings['inventory_tax_rate'] ?? '0');
$showOnPrintout = ($settings['inventory_show_on_printout'] ?? '1') !== '0';
ob_start();
?>

<div class="py-8">
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= t('module.inventory.name') ?></h1>
            <p class="mt-1 text-sm text-gray-600"><?= t('module.inventory.description') ?></p>
        </div>
        <a href="<?= BASE_URL ?>/settings?tab=modules" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <?= t('modules.back') ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <p class="text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
            <p class="text-sm text-green-600"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow rounded-lg">
        <form method="POST" action="<?= BASE_URL ?>/module-manager/inventory/settings" class="px-6 py-4 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div>
                <label for="inventory_tax_rate" class="block text-sm font-medium text-gray-700"><?= t('inventory.tax_rate') ?></label>
                <div class="mt-1 relative rounded-md shadow-sm max-w-xs">
                    <input type="number" id="inventory_tax_rate" name="inventory_tax_rate" min="0" max="100" step="0.0001" value="<?= htmlspecialchars($taxRate) ?>" class="block w-full px-4 py-3 pr-10 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">%</span>
                    </div>
                </div>
                <p class="mt-1 text-sm text-gray-500"><?= t('inventory.tax_rate_help') ?></p>
            </div>
            <div class="flex items-start">
                <input id="inventory_show_on_printout" name="inventory_show_on_printout" type="checkbox" value="1" <?= $showOnPrintout ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <div class="ml-2">
                    <label for="inventory_show_on_printout" class="block text-sm text-gray-700"><?= t('inventory.show_on_printout') ?></label>
                    <p class="mt-1 text-sm text-gray-500"><?= t('inventory.show_on_printout_help') ?></p>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                    <?= t('common.save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
