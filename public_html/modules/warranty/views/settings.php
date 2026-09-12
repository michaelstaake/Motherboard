<?php
$title = t('module.warranty.name') . ' - ' . ($companyName ?? APP_NAME);
$showOnPrintout = ($settings['warranty_show_on_printout'] ?? '1') !== '0';
$zeroPrices = ($settings['warranty_zero_inventory_prices'] ?? '0') === '1';
ob_start();
?>

<div class="py-8">
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= t('module.warranty.name') ?></h1>
            <p class="mt-1 text-sm text-gray-600"><?= t('module.warranty.description') ?></p>
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
        <form method="POST" action="<?= BASE_URL ?>/module-manager/warranty/settings" class="px-6 py-4 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="flex items-start">
                <input id="warranty_show_on_printout" name="warranty_show_on_printout" type="checkbox" value="1" <?= $showOnPrintout ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <div class="ml-2">
                    <label for="warranty_show_on_printout" class="block text-sm text-gray-700"><?= t('warranty.show_on_printout') ?></label>
                    <p class="mt-1 text-sm text-gray-500"><?= t('warranty.show_on_printout_help') ?></p>
                </div>
            </div>
            <div class="flex items-start">
                <input id="warranty_zero_inventory_prices" name="warranty_zero_inventory_prices" type="checkbox" value="1" <?= $zeroPrices ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <div class="ml-2">
                    <label for="warranty_zero_inventory_prices" class="block text-sm text-gray-700"><?= t('warranty.zero_prices') ?></label>
                    <p class="mt-1 text-sm text-gray-500"><?= t('warranty.zero_prices_help') ?></p>
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
