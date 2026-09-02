<?php
$title = t('module.email-2fa.name') . ' - ' . ($companyName ?? APP_NAME);
ob_start();
?>

<div class="py-8">
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= t('module.email-2fa.name') ?></h1>
            <p class="mt-1 text-sm text-gray-600"><?= t('module.email-2fa.description') ?></p>
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
        <form method="POST" action="<?= BASE_URL ?>/module-manager/email-2fa/settings" class="px-6 py-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="flex items-center">
                <input id="require_2fa" name="require_2fa" type="checkbox" <?= !empty($settings['require_2fa']) ? 'checked' : '' ?> class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <label for="require_2fa" class="ml-2 block text-sm text-gray-900"><?= t('settings.force_2fa') ?></label>
            </div>
            <p class="mt-1 text-sm text-gray-500"><?= t('settings.force_2fa_help') ?></p>
            <div class="mt-6 flex justify-end">
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
