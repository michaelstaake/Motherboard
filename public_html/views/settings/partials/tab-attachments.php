<?php
    $attachmentExtensions = array_values(array_filter(array_map('trim', explode(',', strtolower($settings['attachment_allowed_extensions'] ?? 'png')))));
    if (empty($attachmentExtensions)) {
        $attachmentExtensions = ['png'];
    }
    $attachmentDestinations = $attachmentDestinations ?? ['local' => t('settings.attachment_destination_local')];
?>
<form method="POST" action="<?= BASE_URL ?>/settings" class="settings-saveable-form px-6 py-4" id="attachmentSettingsForm" data-tab="attachments">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="section" value="attachments">
    <input type="hidden" name="attachment_allowed_extensions" id="attachment_allowed_extensions" value="<?= htmlspecialchars(implode(',', $attachmentExtensions)) ?>">

    <div class="space-y-6">
        <div>
            <label for="attachment_destination" class="block text-sm font-medium text-gray-700"><?= t('settings.attachment_destination') ?></label>
            <select id="attachment_destination" name="attachment_destination" class="mt-1 block w-full sm:w-96 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                <?php foreach ($attachmentDestinations as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= ($settings['attachment_destination'] ?? 'local') === $value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="mt-1 text-sm text-gray-500"><?= t('settings.attachment_destination_help') ?></p>
            <?php Hooks::doAction('settings.attachments.destination_help', $settings['attachment_destination'] ?? 'local'); ?>
        </div>

        <div>
            <label for="attachment_max_size_mb" class="block text-sm font-medium text-gray-700"><?= t('settings.attachment_max_size') ?></label>
            <input type="number" id="attachment_max_size_mb" name="attachment_max_size_mb" value="<?= htmlspecialchars($settings['attachment_max_size_mb'] ?? '10') ?>" min="1" max="1024" class="mt-1 block w-32 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
            <p class="mt-1 text-sm text-gray-500"><?= t('settings.attachment_max_size_help') ?></p>
            <?php
                $configuredMaxBytes = ((int) ($settings['attachment_max_size_mb'] ?? 10)) * 1048576;
                $phpUploadLimitBytes = $phpUploadLimitBytes ?? 0;
                if ($phpUploadLimitBytes > 0 && $configuredMaxBytes > $phpUploadLimitBytes):
            ?>
                <p class="mt-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                    <?= t('settings.attachment_php_limit', ['size' => htmlspecialchars($phpUploadLimitLabel ?? '')]) ?>
                </p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700"><?= t('settings.attachment_extensions') ?></label>
            <p class="mt-1 text-sm text-gray-500 mb-3"><?= t('settings.attachment_extensions_help') ?></p>
            <div id="attachment_extension_list" class="flex flex-wrap gap-2 mb-3"></div>
            <div class="flex items-center gap-2">
                <input type="text" id="attachment_extension_input" placeholder="<?= htmlspecialchars(t('settings.attachment_extension_ph')) ?>" class="block w-40 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                <button type="button" id="attachment_extension_add" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <?= t('settings.attachment_add_extension') ?>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <?= t('settings.save') ?>
        </button>
    </div>
</form>

<!-- Wildcard extension warning modal -->
<div id="wildcardWarningModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900"><?= t('settings.attachment_wildcard_title') ?></h3>
                    <p class="mt-2 text-sm text-gray-500"><?= t('settings.attachment_wildcard_body') ?></p>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button type="button" id="wildcardWarningConfirm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                    <?= t('settings.attachment_wildcard_confirm') ?>
                </button>
                <button type="button" id="wildcardWarningCancel" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                    <?= t('common.cancel') ?>
                </button>
            </div>
        </div>
    </div>
</div>
