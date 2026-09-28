<?php
require_once $definition['path'] . '/lib.php';
require_once $definition['path'] . '/schema.php';
require_once ROOT_PATH . '/models/Settings.php';
require_once ROOT_PATH . '/models/WorkOrder.php';

motherboard_customer_email_load_models();
const MOTHERBOARD_CUSTOMER_EMAIL_SCHEMA_VERSION = 3;

$customerEmailPath = $definition['path'];
$customerEmailController = $definition['path'] . '/controllers/CustomerEmailController.php';

Hooks::addFilter('schema.needs_migration', function (bool $needs, Database $database): bool {
    if ($needs) {
        return true;
    }
    $settings = new Settings($database);
    return (int) $settings->getSetting('schema_version_customer_email', '0') < MOTHERBOARD_CUSTOMER_EMAIL_SCHEMA_VERSION;
});

Hooks::addAction('schema.migrate', function (Database $database): void {
    $settings = new Settings($database);
    if ((int) $settings->getSetting('schema_version_customer_email', '0') >= MOTHERBOARD_CUSTOMER_EMAIL_SCHEMA_VERSION) {
        return;
    }
    motherboard_customer_email_ensure_schema($database);
    motherboard_customer_email_migrate_settings($settings);
    $settings->setSetting('schema_version_customer_email', (string) MOTHERBOARD_CUSTOMER_EMAIL_SCHEMA_VERSION);
});

Hooks::addAction('router.register', function (Router $router) use ($customerEmailController): void {
    $router->addRoute('/module-manager/customer-email/preview', 'CustomerEmailController', 'preview', $customerEmailController);
    $router->addRoute('/work-orders/view/{id}/customer-email', 'CustomerEmailController', 'toggle', $customerEmailController);
});

Hooks::addAction('work_order.view.after_customer_info', function (array $workOrder, array $context) use ($customerEmailPath): void {
    $workOrderId = (int) ($workOrder['id'] ?? 0);
    if ($workOrderId <= 0) {
        return;
    }
    $hasAddress = filter_var(trim((string) ($workOrder['customer_email'] ?? '')), FILTER_VALIDATE_EMAIL) !== false;
    $disabled = (new CustomerEmailOptOut())->isDisabled($workOrderId);
    $canEdit = !empty($context['canEdit']);
    $csrf_token = $context['csrf_token'] ?? '';
    include $customerEmailPath . '/views/work-order-section.php';
});

// Runs after other create.after handlers so the email reflects anything they persisted.
Hooks::addAction('work_order.create.after', function ($workOrderId): void {
    motherboard_customer_email_notify((int) $workOrderId, 'created');
}, 20);

Hooks::addAction('work_order.status.changed', function (int $workOrderId, string $oldStatus, string $newStatus): void {
    $event = motherboard_customer_email_event_for_status($newStatus);
    if ($event !== null) {
        motherboard_customer_email_notify($workOrderId, $event);
    }
});

// Emails go out automatically, so the Activity log credits them to System rather than to
// whoever saved the change that triggered them. Matching on the action also covers older entries.
Hooks::addFilter('work_order.log.by_system', function (bool $bySystem, array $log): bool {
    return $bySystem || in_array($log['action'] ?? '', ['customer_email_sent', 'customer_email_failed'], true);
});

Hooks::addFilter('module.settings.save.customer-email', function (array $result, array $post, Settings $settings): array {
    foreach (MOTHERBOARD_CUSTOMER_EMAIL_EVENTS as $event => $key) {
        $settings->setSetting($key, isset($post[$key]) ? '1' : '0');

        // Each editable part: its setting, how a submission is cleaned, and the shipped wording.
        $parts = [
            [motherboard_customer_email_subject_key($event), 'motherboard_customer_email_clean_subject', motherboard_customer_email_default_subject($event)],
            [motherboard_customer_email_heading_key($event), 'motherboard_customer_email_clean_heading', motherboard_customer_email_default_heading($event)],
            [motherboard_customer_email_template_key($event), 'motherboard_customer_email_clean_template', motherboard_customer_email_default_template($event)],
            [motherboard_customer_email_footer_key($event), 'motherboard_customer_email_clean_template', motherboard_customer_email_default_footer($event)],
        ];
        foreach ($parts as [$partKey, $clean, $default]) {
            if (!array_key_exists($partKey, $post)) {
                continue;
            }
            // Wording that still matches the shipped text is stored as empty, so an untouched
            // part keeps following the language the shop is reading it in.
            $value = $clean((string) $post[$partKey]);
            if ($value === $clean($default)) {
                $value = '';
            }
            $settings->setSetting($partKey, $value);
        }
    }
    return $result;
});
