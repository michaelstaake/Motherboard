<?php
$title = t('module.customer-email.name') . ' - ' . ($companyName ?? APP_NAME);
$events = MOTHERBOARD_CUSTOMER_EMAIL_EVENTS;
ob_start();
?>

<div class="py-8">
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex sm:items-baseline sm:gap-3">
            <h1 class="text-2xl font-bold text-gray-900"><?= t('module.customer-email.name') ?></h1>
            <?php if (!empty($module['version'])): ?>
                <p class="mt-1 sm:mt-0 text-sm text-gray-600">v<?= htmlspecialchars($module['version']) ?></p>
            <?php endif; ?>
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
        <form method="POST" action="<?= BASE_URL ?>/module-manager/customer-email/settings" class="px-6 py-4 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div>
                <h2 class="text-sm font-medium text-gray-900"><?= t('customer_email.settings_heading') ?></h2>
                <p class="mt-1 text-sm text-gray-500"><?= t('customer_email.settings_help') ?></p>
            </div>
            <?php foreach ($events as $event => $key): ?>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start">
                        <input id="<?= $key ?>" name="<?= $key ?>" type="checkbox" value="1" <?= ($settings[$key] ?? '0') === '1' ? 'checked' : '' ?> class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <div class="ml-2">
                            <label for="<?= $key ?>" class="block text-sm text-gray-700"><?= t('customer_email.' . $event . '.label') ?></label>
                            <p class="mt-1 text-sm text-gray-500"><?= t('customer_email.' . $event . '.help') ?></p>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-start gap-2">
                        <button type="button" data-template-open="<?= $event ?>" title="<?= htmlspecialchars(t('customer_email.edit_help')) ?>" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <?= t('customer_email.edit') ?>
                        </button>
                        <button type="submit" name="preview_event" value="<?= $event ?>" formaction="<?= BASE_URL ?>/module-manager/customer-email/preview" title="<?= htmlspecialchars(t('customer_email.preview_help')) ?>" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <?= t('customer_email.preview') ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            <p class="text-sm text-gray-500"><?= t('customer_email.preview_help') ?></p>

            <?php foreach ($events as $event => $key): ?>
                <?php
                // The fields sit in this form, so Save here stores the wording with the
                // checkboxes and no extra route is needed.
                $templateKey = motherboard_customer_email_template_key($event);
                $defaultTemplate = motherboard_customer_email_default_template($event);
                $template = trim((string) ($settings[$templateKey] ?? '')) ?: $defaultTemplate;
                $headingKey = motherboard_customer_email_heading_key($event);
                $defaultHeading = motherboard_customer_email_default_heading($event);
                $heading = trim((string) ($settings[$headingKey] ?? '')) ?: $defaultHeading;
                $subjectKey = motherboard_customer_email_subject_key($event);
                $defaultSubject = motherboard_customer_email_default_subject($event);
                $subject = trim((string) ($settings[$subjectKey] ?? '')) ?: $defaultSubject;
                $footerKey = motherboard_customer_email_footer_key($event);
                $defaultFooter = motherboard_customer_email_default_footer($event);
                $footer = trim((string) ($settings[$footerKey] ?? '')) ?: $defaultFooter;
                ?>
                <div id="template-modal-<?= $event ?>" data-template-modal class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 1000;">
                    <div class="relative top-20 mx-auto mb-20 p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                        <h3 class="text-lg font-medium text-gray-900"><?= t('customer_email.edit_title', ['type' => t('customer_email.' . $event . '.name')]) ?></h3>
                        <p class="mt-1 text-sm text-gray-500"><?= t('customer_email.edit_help') ?></p>
                        <div class="mt-4">
                            <label for="<?= $subjectKey ?>" class="block text-sm font-medium text-gray-700"><?= t('customer_email.subject_label') ?></label>
                            <input type="text" id="<?= $subjectKey ?>" name="<?= $subjectKey ?>" maxlength="<?= MOTHERBOARD_CUSTOMER_EMAIL_SUBJECT_MAX ?>" value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>" data-template-default="<?= htmlspecialchars($defaultSubject, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-4 py-2 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        </div>
                        <div class="mt-4">
                            <label for="<?= $headingKey ?>" class="block text-sm font-medium text-gray-700"><?= t('customer_email.heading_label') ?></label>
                            <input type="text" id="<?= $headingKey ?>" name="<?= $headingKey ?>" maxlength="<?= MOTHERBOARD_CUSTOMER_EMAIL_HEADING_MAX ?>" value="<?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?>" data-template-default="<?= htmlspecialchars($defaultHeading, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-4 py-2 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        </div>
                        <div class="mt-4">
                            <label for="<?= $templateKey ?>" class="block text-sm font-medium text-gray-700"><?= t('customer_email.template_label') ?></label>
                            <textarea id="<?= $templateKey ?>" name="<?= $templateKey ?>" rows="8" maxlength="<?= MOTHERBOARD_CUSTOMER_EMAIL_TEMPLATE_MAX ?>" data-template-default="<?= htmlspecialchars($defaultTemplate, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"><?= htmlspecialchars($template, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mt-4">
                            <label for="<?= $footerKey ?>" class="block text-sm font-medium text-gray-700"><?= t('customer_email.footer_label') ?></label>
                            <textarea id="<?= $footerKey ?>" name="<?= $footerKey ?>" rows="4" maxlength="<?= MOTHERBOARD_CUSTOMER_EMAIL_TEMPLATE_MAX ?>" data-template-default="<?= htmlspecialchars($defaultFooter, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"><?= htmlspecialchars($footer, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <p class="mt-1 text-xs text-gray-500"><?= t('customer_email.template_placeholders') ?></p>
                        </div>
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <button type="button" data-template-reset="<?= $event ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                <?= t('customer_email.reset_default') ?>
                            </button>
                            <div class="flex gap-3">
                                <button type="button" data-template-cancel="<?= $event ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    <?= t('common.cancel') ?>
                                </button>
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                                    <?= t('common.save') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                    <?= t('common.save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Only Cancel closes a template modal, as with the inventory modals, so a stray click outside
    // never discards an edit. Cancel puts back what the fields held when the modal was opened,
    // so a closed modal never leaves an edit behind for the next Save.
    const opened = new Map();

    function modalFor(event) {
        return document.getElementById('template-modal-' + event);
    }

    function fieldsIn(modal) {
        return Array.from(modal.querySelectorAll('[data-template-default]'));
    }

    document.querySelectorAll('[data-template-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            const event = button.getAttribute('data-template-open');
            const modal = modalFor(event);
            if (!modal) {
                return;
            }
            const fields = fieldsIn(modal);
            opened.set(event, fields.map(function (field) { return field.value; }));
            modal.classList.remove('hidden');
            if (fields[0]) {
                fields[0].focus();
            }
        });
    });

    document.querySelectorAll('[data-template-cancel]').forEach(function (button) {
        button.addEventListener('click', function () {
            const event = button.getAttribute('data-template-cancel');
            const modal = modalFor(event);
            if (!modal) {
                return;
            }
            const values = opened.get(event);
            if (values) {
                fieldsIn(modal).forEach(function (field, i) {
                    field.value = values[i];
                });
            }
            modal.classList.add('hidden');
        });
    });

    document.querySelectorAll('[data-template-reset]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = modalFor(button.getAttribute('data-template-reset'));
            if (!modal) {
                return;
            }
            const fields = fieldsIn(modal);
            fields.forEach(function (field) {
                field.value = field.getAttribute('data-template-default') || '';
            });
            if (fields[0]) {
                fields[0].focus();
            }
        });
    });

})();
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
