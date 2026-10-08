<form method="POST" action="<?= BASE_URL ?>/settings" class="settings-saveable-form px-6 py-4" data-tab="general">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="section" value="general">

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="company_name" class="block text-sm font-medium text-gray-700"><?= t('settings.company_name') ?></label>
            <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
        </div>

        <div>
            <label for="company_logo_url" class="block text-sm font-medium text-gray-700"><?= t('settings.logo_url') ?></label>
            <input type="url" id="company_logo_url" name="company_logo_url" value="<?= htmlspecialchars($settings['company_logo_url'] ?? '') ?>" placeholder="https://example.com/logo.png" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
            <p class="mt-1 text-sm text-gray-500"><?= t('settings.logo_help') ?></p>
        </div>

        <div>
            <label for="company_phone" class="block text-sm font-medium text-gray-700"><?= t('settings.phone') ?></label>
            <input type="tel" id="company_phone" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>" data-no-auto-format class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
        </div>

        <div>
            <label for="company_email" class="block text-sm font-medium text-gray-700"><?= t('settings.email') ?></label>
            <input type="email" id="company_email" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
        </div>

        <div>
            <label for="company_website" class="block text-sm font-medium text-gray-700"><?= t('settings.website') ?></label>
            <input type="text" id="company_website" name="company_website" value="<?= htmlspecialchars($settings['company_website'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
        </div>

        <div class="sm:col-span-2">
            <label for="company_address" class="block text-sm font-medium text-gray-700"><?= t('settings.address') ?></label>
            <textarea id="company_address" name="company_address" rows="3" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-6 space-y-3">
        <div class="flex items-start">
            <input id="nav_always_expanded" name="nav_always_expanded" type="checkbox" value="1" <?= ($settings['nav_always_expanded'] ?? '0') === '1' ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
            <div class="ml-2">
                <label for="nav_always_expanded" class="block text-sm text-gray-700"><?= t('settings.nav_always_expanded') ?></label>
                <p class="mt-1 text-sm text-gray-500"><?= t('settings.nav_always_expanded_help') ?></p>
            </div>
        </div>

        <div class="flex items-start">
            <input id="work_order_autosave" name="work_order_autosave" type="checkbox" value="1" <?= ($settings['work_order_autosave'] ?? '1') === '1' ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
            <div class="ml-2">
                <label for="work_order_autosave" class="block text-sm text-gray-700"><?= t('settings.work_order_autosave') ?></label>
                <p class="mt-1 text-sm text-gray-500"><?= t('settings.work_order_autosave_help') ?></p>
            </div>
        </div>

        <div class="flex items-start">
            <input id="work_order_days_under_date" name="work_order_days_under_date" type="checkbox" value="1" <?= ($settings['work_order_days_under_date'] ?? '0') === '1' ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
            <div class="ml-2">
                <label for="work_order_days_under_date" class="block text-sm text-gray-700"><?= t('settings.work_order_days_under_date') ?></label>
                <p class="mt-1 text-sm text-gray-500"><?= t('settings.work_order_days_under_date_help') ?></p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <?= t('settings.save') ?>
        </button>
    </div>
</form>
