<form method="POST" action="<?= BASE_URL ?>/settings" class="settings-saveable-form px-6 py-4" data-tab="localization">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="section" value="localization">

    <div class="space-y-8">
        <div>
            <h3 class="text-sm font-medium text-gray-900 mb-4"><?= t('settings.format') ?></h3>
            <div>
                <label for="phone_number_format" class="block text-sm font-medium text-gray-700"><?= t('settings.phone_format') ?></label>
                <select id="phone_number_format" name="phone_number_format" class="mt-1 block w-64 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white" onchange="showPhoneFormatExample()">
                    <option value="default" <?= ($settings['phone_number_format'] ?? 'default') === 'default' ? 'selected' : '' ?>><?= t('settings.phone_format_default') ?></option>
                    <option value="usa_format_a" <?= ($settings['phone_number_format'] ?? 'default') === 'usa_format_a' ? 'selected' : '' ?>><?= t('settings.phone_format_a') ?></option>
                    <option value="usa_format_b" <?= ($settings['phone_number_format'] ?? 'default') === 'usa_format_b' ? 'selected' : '' ?>><?= t('settings.phone_format_b') ?></option>
                </select>
                <p class="mt-1 text-sm text-gray-500"><?= t('settings.phone_format_help') ?></p>
                <div id="phone_format_example" class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded-md">
                    <p class="text-sm text-gray-700">
                        <strong><?= t('settings.example') ?>:</strong> <span id="example_text"></span><br>
                        <strong><?= t('settings.pattern') ?>:</strong> <span id="pattern_text"></span>
                    </p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-900 mb-4"><?= t('settings.language_title') ?></h3>
            <div class="space-y-6">
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700"><?= t('settings.language') ?></label>
                    <select id="language" name="language" class="mt-1 block w-64 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        <?php foreach (($languages ?? []) as $code => $name): ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= ($currentLanguage ?? 'en-us') === $code ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?> (<?= htmlspecialchars($code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="print_language" class="block text-sm font-medium text-gray-700"><?= t('settings.print_language') ?></label>
                    <select id="print_language" name="print_language" class="mt-1 block w-64 px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        <?php foreach (($languages ?? []) as $code => $name): ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= ($currentPrintLanguage ?? $currentLanguage ?? 'en-us') === $code ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?> (<?= htmlspecialchars($code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-sm text-gray-500"><?= t('settings.print_language_help') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <?= t('settings.save') ?>
        </button>
    </div>
</form>
