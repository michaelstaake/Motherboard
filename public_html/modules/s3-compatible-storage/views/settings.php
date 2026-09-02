<?php
$title = t('module.s3-compatible-storage.name') . ' - ' . ($companyName ?? APP_NAME);
ob_start();
$secretSet = !empty($settings['s3_secret_key']);
?>

<div class="py-8">
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= t('module.s3-compatible-storage.name') ?></h1>
            <p class="mt-1 text-sm text-gray-600"><?= t('module.s3-compatible-storage.description') ?></p>
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
        <form method="POST" action="<?= BASE_URL ?>/module-manager/s3-compatible-storage/settings" class="px-6 py-4 space-y-6" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="s3_endpoint" class="block text-sm font-medium text-gray-700"><?= t('module.s3-compatible-storage.endpoint') ?></label>
                    <input type="text" id="s3_endpoint" name="s3_endpoint" value="<?= htmlspecialchars($settings['s3_endpoint'] ?? '') ?>" placeholder="<?= htmlspecialchars(t('module.s3-compatible-storage.endpoint_ph')) ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('module.s3-compatible-storage.endpoint_help') ?></p>
                </div>
                <div>
                    <label for="s3_region" class="block text-sm font-medium text-gray-700"><?= t('module.s3-compatible-storage.region') ?></label>
                    <input type="text" id="s3_region" name="s3_region" value="<?= htmlspecialchars($settings['s3_region'] ?? '') ?>" placeholder="<?= htmlspecialchars(t('module.s3-compatible-storage.region_ph')) ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('module.s3-compatible-storage.region_help') ?></p>
                </div>
                <div>
                    <label for="s3_bucket" class="block text-sm font-medium text-gray-700"><?= t('module.s3-compatible-storage.bucket') ?></label>
                    <input type="text" id="s3_bucket" name="s3_bucket" value="<?= htmlspecialchars($settings['s3_bucket'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('module.s3-compatible-storage.bucket_help') ?></p>
                </div>
                <div>
                    <label for="s3_access_key" class="block text-sm font-medium text-gray-700"><?= t('module.s3-compatible-storage.access_key') ?></label>
                    <input type="text" id="s3_access_key" name="s3_access_key" value="<?= htmlspecialchars($settings['s3_access_key'] ?? '') ?>" autocomplete="off" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('module.s3-compatible-storage.access_key_help') ?></p>
                </div>
                <div>
                    <label for="s3_secret_key" class="block text-sm font-medium text-gray-700"><?= t('module.s3-compatible-storage.secret_key') ?></label>
                    <input type="password" id="s3_secret_key" name="s3_secret_key" value="" placeholder="<?= $secretSet ? '••••••••' : '' ?>" autocomplete="new-password" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('module.s3-compatible-storage.secret_key_help') ?></p>
                </div>
                <div class="sm:col-span-2">
                    <label for="s3_prefix" class="block text-sm font-medium text-gray-700"><?= t('module.s3-compatible-storage.prefix') ?></label>
                    <input type="text" id="s3_prefix" name="s3_prefix" value="<?= htmlspecialchars($settings['s3_prefix'] ?? '') ?>" placeholder="<?= htmlspecialchars(t('module.s3-compatible-storage.prefix_ph')) ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('module.s3-compatible-storage.prefix_help') ?></p>
                </div>
                <div class="sm:col-span-2">
                    <div class="flex items-start">
                        <input id="s3_path_style" name="s3_path_style" type="checkbox" value="1" <?= !empty($settings['s3_path_style']) ? 'checked' : '' ?> class="h-4 w-4 mt-1 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <div class="ml-2">
                            <label for="s3_path_style" class="block text-sm text-gray-900"><?= t('module.s3-compatible-storage.path_style') ?></label>
                            <p class="text-sm text-gray-500"><?= t('module.s3-compatible-storage.path_style_help') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-blue-50 border border-blue-200 rounded-md">
                <p class="text-sm text-blue-800 mb-2"><?= t('module.s3-compatible-storage.choose_destination') ?></p>
                <p class="text-sm text-blue-800">
                    <strong><?= t('settings.setup') ?></strong><br>
                    <?= t('module.s3-compatible-storage.setup_amazon') ?><br>
                    <?= t('module.s3-compatible-storage.setup_b2') ?><br>
                    <?= t('module.s3-compatible-storage.setup_wasabi') ?><br>
                    <?= t('module.s3-compatible-storage.setup_r2') ?><br>
                    <?= t('module.s3-compatible-storage.setup_minio') ?>
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" name="s3_action" value="test" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <?= t('module.s3-compatible-storage.test') ?>
                </button>
                <button type="submit" name="s3_action" value="save" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
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
