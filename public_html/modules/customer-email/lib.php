<?php

require_once ROOT_PATH . '/models/Settings.php';
require_once ROOT_PATH . '/core/EmailSender.php';

const MOTHERBOARD_CUSTOMER_EMAIL_EVENTS = [
    'created' => 'customer_email_on_created',
    'initial_update' => 'customer_email_on_initial_update',
    'completed' => 'customer_email_on_completed',
    'picked_up' => 'customer_email_on_picked_up',
];

/** Statuses that count as the first update. */
const MOTHERBOARD_CUSTOMER_EMAIL_UPDATE_STATUSES = ['In Progress', 'Awaiting Parts'];

/** Longest message a shop can save for one event. */
const MOTHERBOARD_CUSTOMER_EMAIL_TEMPLATE_MAX = 4000;

/** Longest heading a shop can save for one event. */
const MOTHERBOARD_CUSTOMER_EMAIL_HEADING_MAX = 200;

/** Longest subject line a shop can save for one event. */
const MOTHERBOARD_CUSTOMER_EMAIL_SUBJECT_MAX = 200;

function motherboard_customer_email_path(): string {
    return MODULES_PATH . '/customer-email';
}

function motherboard_customer_email_load_models(): void {
    require_once ROOT_PATH . '/core/Model.php';
    require_once motherboard_customer_email_path() . '/models/CustomerEmailEvent.php';
    require_once motherboard_customer_email_path() . '/models/CustomerEmailOptOut.php';
}

function motherboard_customer_email_is_event(string $event): bool {
    return array_key_exists($event, MOTHERBOARD_CUSTOMER_EMAIL_EVENTS);
}

function motherboard_customer_email_enabled(string $event, ?Settings $settings = null): bool {
    if (!motherboard_customer_email_is_event($event)) {
        return false;
    }
    $settings = $settings ?: new Settings();
    return $settings->getSetting(MOTHERBOARD_CUSTOMER_EMAIL_EVENTS[$event], '0') === '1';
}

/** Setting that holds the shop's own message for one event; empty means the shipped one. */
function motherboard_customer_email_template_key(string $event): string {
    return 'customer_email_template_' . $event;
}

/** The message shipped with the active language, greeting included, placeholders still in place. */
function motherboard_customer_email_default_template(string $event): string {
    return t('customer_email.greeting') . "\n\n" . t('customer_email.' . $event . '.body');
}

/** The message an event sends: the shop's own wording, or the shipped one. */
function motherboard_customer_email_template(string $event, ?Settings $settings = null): string {
    if (!motherboard_customer_email_is_event($event)) {
        return '';
    }
    $settings = $settings ?: new Settings();
    $custom = trim((string) $settings->getSetting(motherboard_customer_email_template_key($event), ''));
    return $custom !== '' ? $custom : motherboard_customer_email_default_template($event);
}

/** Setting that holds the shop's own heading for one event; empty means the shipped one. */
function motherboard_customer_email_heading_key(string $event): string {
    return 'customer_email_heading_' . $event;
}

/** The heading shipped with the active language, placeholders still in place. */
function motherboard_customer_email_default_heading(string $event): string {
    return t('customer_email.' . $event . '.heading');
}

/** The heading an event shows: the shop's own wording, or the shipped one. */
function motherboard_customer_email_heading(string $event, ?Settings $settings = null): string {
    if (!motherboard_customer_email_is_event($event)) {
        return '';
    }
    $settings = $settings ?: new Settings();
    $custom = trim((string) $settings->getSetting(motherboard_customer_email_heading_key($event), ''));
    return $custom !== '' ? $custom : motherboard_customer_email_default_heading($event);
}

/** Puts a submitted heading on one line, in the shape that gets stored and compared. */
function motherboard_customer_email_clean_heading(string $heading): string {
    $heading = trim((string) preg_replace('/\s+/u', ' ', $heading));
    return mb_substr($heading, 0, MOTHERBOARD_CUSTOMER_EMAIL_HEADING_MAX);
}

/** Setting that holds the shop's own subject line for one event; empty means the shipped one. */
function motherboard_customer_email_subject_key(string $event): string {
    return 'customer_email_subject_' . $event;
}

/** The subject line shipped with the active language, placeholders still in place. */
function motherboard_customer_email_default_subject(string $event): string {
    return t('customer_email.' . $event . '.subject');
}

/** The subject line an event sends: the shop's own wording, or the shipped one. */
function motherboard_customer_email_subject(string $event, ?Settings $settings = null): string {
    if (!motherboard_customer_email_is_event($event)) {
        return '';
    }
    $settings = $settings ?: new Settings();
    $custom = trim((string) $settings->getSetting(motherboard_customer_email_subject_key($event), ''));
    return $custom !== '' ? $custom : motherboard_customer_email_default_subject($event);
}

/** Puts a submitted subject line on one line, in the shape that gets stored and compared. */
function motherboard_customer_email_clean_subject(string $subject): string {
    $subject = trim((string) preg_replace('/\s+/u', ' ', $subject));
    return mb_substr($subject, 0, MOTHERBOARD_CUSTOMER_EMAIL_SUBJECT_MAX);
}

/** Setting that holds the shop's own footer for one event; empty means the shipped one. */
function motherboard_customer_email_footer_key(string $event): string {
    return 'customer_email_footer_' . $event;
}

/**
 * The footer shipped with the active language, placeholders still in place: the line about
 * questions, then the sign-off with the company name under it.
 */
function motherboard_customer_email_default_footer(string $event): string {
    return t('customer_email.questions') . "\n\n" . t('customer_email.sign_off') . "\n{company}";
}

/** The footer an event sends: the shop's own wording, or the shipped one. */
function motherboard_customer_email_footer(string $event, ?Settings $settings = null): string {
    if (!motherboard_customer_email_is_event($event)) {
        return '';
    }
    $settings = $settings ?: new Settings();
    $custom = trim((string) $settings->getSetting(motherboard_customer_email_footer_key($event), ''));
    return $custom !== '' ? $custom : motherboard_customer_email_default_footer($event);
}

/** Swaps each {placeholder} in the text for its value. */
function motherboard_customer_email_fill(string $text, array $vars): string {
    foreach ($vars as $key => $value) {
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    }
    return $text;
}

/** Puts a submitted message into the one shape that gets stored and compared. */
function motherboard_customer_email_clean_template(string $template): string {
    $template = str_replace(["\r\n", "\r"], "\n", $template);
    $template = (string) preg_replace('/\n{3,}/', "\n\n", $template);
    $template = (string) preg_replace('/[ \t]+\n/', "\n", $template);
    return mb_substr(trim($template), 0, MOTHERBOARD_CUSTOMER_EMAIL_TEMPLATE_MAX);
}

/**
 * Fills in a message's placeholders and splits it into paragraphs on blank lines. Single
 * newlines stay inside their paragraph for the view to break.
 */
function motherboard_customer_email_paragraphs(string $template, array $vars): array {
    $template = motherboard_customer_email_fill($template, $vars);

    $paragraphs = [];
    $clean = motherboard_customer_email_clean_template($template);
    foreach (preg_split('/\n\s*\n/', $clean) ?: [] as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph !== '') {
            $paragraphs[] = $paragraph;
        }
    }
    return $paragraphs;
}

/**
 * Which email a status change should send, if any. The initial update is only a candidate
 * here; the caller still checks that this is the first time.
 */
function motherboard_customer_email_event_for_status(string $newStatus): ?string {
    if (in_array($newStatus, MOTHERBOARD_CUSTOMER_EMAIL_UPDATE_STATUSES, true)) {
        return 'initial_update';
    }
    if ($newStatus === 'Closed') {
        return 'completed';
    }
    if ($newStatus === 'Picked Up') {
        return 'picked_up';
    }
    return null;
}

/**
 * Builds the subject, HTML body and plain-text body for one event.
 *
 * $data keys: number (string), name (string), status (English enum, optional),
 * device (string, optional).
 */
function motherboard_customer_email_render(string $event, array $data, ?Settings $settings = null): array {
    $settings = $settings ?: new Settings();
    $company = $settings->getCompanyInfo();
    $companyName = trim((string) ($company['company_name'] ?? '')) ?: APP_NAME;

    $vars = [
        'company' => $companyName,
        'number' => (string) ($data['number'] ?? ''),
        'name' => (string) ($data['name'] ?? ''),
        'app_name' => APP_NAME,
    ];

    $status = (string) ($data['status'] ?? '');
    $device = trim((string) ($data['device'] ?? ''));

    $details = [
        [t('customer_email.detail_work_order'), '#' . $vars['number']],
    ];
    if ($device !== '') {
        $details[] = [t('customer_email.detail_device'), $device];
    }
    if ($status !== '') {
        $details[] = [t('customer_email.detail_status'), tlabel('status', $status)];
    }

    $contact = array_values(array_filter([
        trim((string) ($company['company_phone'] ?? '')),
        trim((string) ($company['company_email'] ?? '')),
        trim((string) ($company['company_website'] ?? '')),
    ]));

    $email = [
        'company' => $companyName,
        'address' => trim((string) ($company['company_address'] ?? '')),
        'contact' => $contact,
        'heading' => motherboard_customer_email_fill(motherboard_customer_email_heading($event, $settings), $vars),
        'body' => motherboard_customer_email_paragraphs(motherboard_customer_email_template($event, $settings), $vars),
        'details' => $details,
        'footer' => motherboard_customer_email_paragraphs(motherboard_customer_email_footer($event, $settings), $vars),
        'notice' => t('customer_email.footer', $vars),
        'lang' => substr(I18n::getInstance()->getLocale(), 0, 2),
    ];

    ob_start();
    include motherboard_customer_email_path() . '/views/email.php';
    $html = (string) ob_get_clean();

    $text = [$email['heading'], ''];
    foreach ($email['body'] as $paragraph) {
        $text[] = $paragraph;
        $text[] = '';
    }
    foreach ($details as [$label, $value]) {
        $text[] = $label . ': ' . $value;
    }
    foreach ($email['footer'] as $paragraph) {
        $text[] = '';
        $text[] = $paragraph;
    }
    if ($email['address'] !== '') {
        $text[] = $email['address'];
    }
    foreach ($contact as $line) {
        $text[] = $line;
    }
    $text[] = '';
    $text[] = $email['notice'];

    return [
        'subject' => motherboard_customer_email_fill(motherboard_customer_email_subject($event, $settings), $vars),
        'html' => $html,
        'text' => implode("\n", $text),
    ];
}

/**
 * Renders and sends one event's email. Returns true only when the mail server accepted it.
 */
function motherboard_customer_email_send(string $event, string $to, array $data, ?Settings $settings = null): bool {
    if (!motherboard_customer_email_is_event($event) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $settings = $settings ?: new Settings();
    try {
        $message = motherboard_customer_email_render($event, $data, $settings);
        $companyName = trim((string) $settings->getSetting('company_name', APP_NAME)) ?: APP_NAME;
        $replyTo = trim((string) $settings->getSetting('company_email', ''));
        if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $replyTo = '';
        }

        $sender = new EmailSender($companyName);
        return (bool) $sender->send($to, $message['subject'], $message['html'], $message['text'], $replyTo);
    } catch (Throwable $e) {
        error_log('Customer email (' . $event . ') failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Handles an event on a real work order: records it, and emails the customer when the
 * event is switched on and the customer has an address. Never throws, so a mail problem
 * cannot break saving the work order.
 */
function motherboard_customer_email_notify(int $workOrderId, string $event): void {
    try {
        if ($workOrderId <= 0 || !motherboard_customer_email_is_event($event)) {
            return;
        }

        $events = new CustomerEmailEvent();

        if ($event === 'initial_update'
            && $events->countStatusChangesTo($workOrderId, MOTHERBOARD_CUSTOMER_EMAIL_UPDATE_STATUSES) > 1) {
            // The change that brought us here is already logged; any earlier one means this
            // is not the first update, including moves made before the module was enabled.
            $events->claim($workOrderId, $event);
            return;
        }

        if (!$events->claim($workOrderId, $event)) {
            return;
        }

        $settings = new Settings();
        if (!motherboard_customer_email_enabled($event, $settings)) {
            return;
        }

        if ((new CustomerEmailOptOut())->isDisabled($workOrderId)) {
            return;
        }

        $workOrderModel = new WorkOrder();
        $workOrder = $workOrderModel->getWorkOrderById($workOrderId);
        $to = trim((string) ($workOrder['customer_email'] ?? ''));
        if (!$workOrder || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $name = trim((string) ($workOrder['customer_name'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($workOrder['customer_company'] ?? ''));
        }

        $sent = motherboard_customer_email_send($event, $to, [
            'number' => (string) $workOrderId,
            'name' => $name,
            'status' => (string) ($workOrder['status'] ?? ''),
            'device' => trim(($workOrder['computer'] ?? '') . ' ' . ($workOrder['model'] ?? '')),
        ], $settings);

        if ($sent) {
            $events->markSent($workOrderId, $event);
            $workOrderModel->logWorkOrderAction(
                $workOrderId,
                'customer_email_sent',
                t('customer_email.log_sent', ['type' => t('customer_email.' . $event . '.name'), 'email' => $to]),
                true
            );
        } else {
            $workOrderModel->logWorkOrderAction(
                $workOrderId,
                'customer_email_failed',
                t('customer_email.log_failed', ['type' => t('customer_email.' . $event . '.name'), 'email' => $to]),
                true
            );
        }
    } catch (Throwable $e) {
        error_log('Customer email (' . $event . ') for work order #' . $workOrderId . ' failed: ' . $e->getMessage());
    }
}
