<form method="POST" action="<?= BASE_URL ?>/settings" class="settings-saveable-form px-6 py-4" data-tab="security">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="section" value="security">

    <div class="space-y-6">
        <div>
            <label for="session_timeout" class="block text-sm font-medium text-gray-700"><?= t('settings.session_timeout') ?></label>
            <input type="number" id="session_timeout" name="session_timeout" value="<?= htmlspecialchars($settings['session_timeout'] ?? '60') ?>" min="5" max="1440" class="mt-1 block w-32 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
            <p class="mt-1 text-sm text-gray-500"><?= t('settings.session_help') ?></p>
        </div>

        <div>
            <label for="max_login_attempts" class="block text-sm font-medium text-gray-700"><?= t('settings.max_login') ?></label>
            <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?= htmlspecialchars($settings['max_login_attempts'] ?? '5') ?>" min="3" max="10" class="mt-1 block w-32 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
            <p class="mt-1 text-sm text-gray-500"><?= t('settings.max_login_help') ?></p>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <?= t('settings.save') ?>
        </button>
    </div>
</form>
