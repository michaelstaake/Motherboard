<?php
$title = t('module.google-recaptcha.name') . ' - ' . ($companyName ?? APP_NAME);
$secretSet = !empty($settings['recaptcha_secret_key']);
ob_start();
?>

<div class="py-8">
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= t('module.google-recaptcha.name') ?></h1>
            <p class="mt-1 text-sm text-gray-600"><?= t('module.google-recaptcha.description') ?></p>
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
        <form method="POST" action="<?= BASE_URL ?>/module-manager/google-recaptcha/settings" class="px-6 py-4 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="recaptcha_site_key" class="block text-sm font-medium text-gray-700"><?= t('settings.recaptcha_site') ?></label>
                    <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?= htmlspecialchars($settings['recaptcha_site_key'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('settings.recaptcha_site_help') ?></p>
                </div>
                <div>
                    <label for="recaptcha_secret_key" class="block text-sm font-medium text-gray-700"><?= t('settings.recaptcha_secret') ?></label>
                    <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="" placeholder="<?= $secretSet ? '••••••••' : '' ?>" autocomplete="new-password" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-sm text-gray-500"><?= t('settings.recaptcha_secret_help') ?></p>
                </div>
            </div>
            <div class="p-3 bg-green-50 border border-green-200 rounded-md">
                <p class="text-sm text-green-800">
                    <strong><?= t('settings.setup') ?></strong><br>
                    1. <?= t('module.google-recaptcha.setup_go') ?> <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-green-600 hover:text-green-500 underline"><?= t('module.google-recaptcha.console') ?></a><br>
                    2. <?= t('module.google-recaptcha.setup_2') ?><br>
                    3. <?= t('module.google-recaptcha.setup_3') ?><br>
                    4. <?= t('module.google-recaptcha.setup_4') ?>
                </p>
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
