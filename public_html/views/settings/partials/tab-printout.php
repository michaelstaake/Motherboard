<form method="POST" action="<?= BASE_URL ?>/settings" class="settings-saveable-form px-6 py-4" data-tab="printout">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="section" value="printout">

    <div class="space-y-6">
        <div>
            <label for="work_order_disclaimer" class="block text-sm font-medium text-gray-700"><?= t('settings.disclaimer') ?></label>
            <textarea id="work_order_disclaimer" name="work_order_disclaimer" rows="4" placeholder="<?= htmlspecialchars(t('settings.disclaimer_ph')) ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"><?= htmlspecialchars($settings['work_order_disclaimer'] ?? '') ?></textarea>
            <p class="mt-1 text-sm text-gray-500"><?= t('settings.disclaimer_help') ?></p>
        </div>

        <div class="space-y-3">
            <div class="flex items-start">
                <input id="print_customer_signature" name="print_customer_signature" type="checkbox" value="1" <?= ($settings['print_customer_signature'] ?? '1') === '1' ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <label for="print_customer_signature" class="ml-2 block text-sm text-gray-700"><?= t('settings.print_customer_signature') ?></label>
            </div>
            <div class="flex items-start">
                <input id="print_technician_signature" name="print_technician_signature" type="checkbox" value="1" <?= ($settings['print_technician_signature'] ?? '1') === '1' ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <label for="print_technician_signature" class="ml-2 block text-sm text-gray-700"><?= t('settings.print_technician_signature') ?></label>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <?= t('settings.save') ?>
        </button>
    </div>
</form>
