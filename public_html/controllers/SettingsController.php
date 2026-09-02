<?php
require_once 'core/Controller.php';
require_once 'models/Settings.php';
require_once 'models/User.php';
require_once 'models/WorkOrder.php';

class SettingsController extends Controller {

    private const TABS = ['general', 'printout', 'security', 'localization', 'attachments', 'modules', 'users'];

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->requireAdmin();

        $error = '';
        $message = '';
        $activeTab = $this->resolveActiveTab($_GET['tab'] ?? 'general');

        if (isset($_GET['msg']) && $_GET['msg'] !== '') {
            $message = $_GET['msg'];
        }
        if (isset($_GET['error']) && $_GET['error'] !== '') {
            $error = $_GET['error'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                $section = $_POST['section'] ?? '';
                $activeTab = $this->sectionToTab($section);

                if ($section === 'general') {
                    $companyData = [
                        'company_name' => $this->sanitizeInput($_POST['company_name']),
                        'company_address' => $this->sanitizeInput($_POST['company_address']),
                        'company_phone' => $this->sanitizeInput($_POST['company_phone']),
                        'company_email' => $this->sanitizeInput($_POST['company_email']),
                        'company_website' => $this->sanitizeInput($_POST['company_website']),
                        'company_logo_url' => $this->sanitizeInput($_POST['company_logo_url']),
                    ];
                    $this->settingsModel->updateCompanyInfo($companyData);
                    $this->logger->log('settings_updated', 'Company information updated', $_SESSION['user_id']);
                    $message = t('settings.saved_company');

                } elseif ($section === 'printout') {
                    $printoutData = [
                        'work_order_disclaimer' => $this->sanitizeInput($_POST['work_order_disclaimer']),
                        'print_customer_signature' => isset($_POST['print_customer_signature']) ? '1' : '0',
                        'print_technician_signature' => isset($_POST['print_technician_signature']) ? '1' : '0',
                    ];
                    $this->settingsModel->updateCompanyInfo($printoutData);
                    $this->logger->log('settings_updated', 'Printout settings updated', $_SESSION['user_id']);
                    $message = t('settings.saved_printout');

                } elseif ($section === 'security') {
                    $sessionTimeout = max(5, min(1440, intval($_POST['session_timeout'] ?? 60)));
                    $maxLoginAttempts = max(3, min(10, intval($_POST['max_login_attempts'] ?? 5)));
                    $securityData = [
                        'session_timeout' => $sessionTimeout,
                        'max_login_attempts' => $maxLoginAttempts,
                    ];
                    $this->settingsModel->updateSecuritySettings($securityData);
                    $this->logger->log('settings_updated', 'Security settings updated', $_SESSION['user_id']);
                    $message = t('settings.saved_security');

                } elseif ($section === 'localization') {
                    $formatData = [
                        'phone_number_format' => $_POST['phone_number_format'],
                    ];
                    $this->settingsModel->updateFormatSettings($formatData);

                    $available = I18n::getInstance()->availableLocales();
                    $language = strtolower($this->sanitizeInput($_POST['language'] ?? 'en-us'));
                    $printLanguage = strtolower($this->sanitizeInput($_POST['print_language'] ?? $language));
                    if (!isset($available[$language]) || !isset($available[$printLanguage])) {
                        throw new Exception(t('settings.invalid_language'));
                    }
                    $this->settingsModel->setSetting('language', $language);
                    $this->settingsModel->setSetting('print_language', $printLanguage);
                    I18n::getInstance()->loadLocale($language);
                    $loader = ModuleLoader::instance();
                    if ($loader) {
                        $loader->reloadLanguages();
                    }
                    $this->logger->log('settings_updated', 'Localization settings updated', $_SESSION['user_id']);
                    $message = t('settings.saved_localization');

                } elseif ($section === 'attachments') {
                    $maxSize = intval($_POST['attachment_max_size_mb'] ?? 10);
                    if ($maxSize < 1) {
                        $maxSize = 1;
                    }
                    if ($maxSize > 1024) {
                        $maxSize = 1024;
                    }

                    require_once 'models/WorkOrderAttachment.php';
                    $extensions = WorkOrderAttachment::normalizeExtensions($_POST['attachment_allowed_extensions'] ?? 'png');
                    if ($extensions === '') {
                        $extensions = 'png';
                    }

                    $attachmentModel = new WorkOrderAttachment();
                    $destination = $this->sanitizeInput($_POST['attachment_destination'] ?? 'local');
                    $destinations = array_keys($attachmentModel->availableDestinations());
                    if (!in_array($destination, $destinations, true)) {
                        $destination = 'local';
                    }

                    $this->settingsModel->updateAttachmentSettings([
                        'attachment_destination' => $destination,
                        'attachment_max_size_mb' => (string) $maxSize,
                        'attachment_allowed_extensions' => $extensions,
                    ]);
                    $this->logger->log('settings_updated', 'Attachment settings updated', $_SESSION['user_id']);
                    $message = t('settings.saved_attachments');
                } else {
                    throw new Exception('Invalid settings section');
                }

                $posted = $_POST;
                unset($posted['csrf_token']);
                Hooks::doAction('settings.saved', $section, $posted);

                $this->redirect('/settings?tab=' . urlencode($activeTab) . '&msg=' . urlencode($message));

            } catch (Exception $e) {
                $error = $e->getMessage();
                if (isset($_POST['section'])) {
                    $activeTab = $this->sectionToTab($_POST['section']);
                }
            }
        }

        $allSettings = $this->settingsModel->getAllSettings();
        require_once 'models/WorkOrderAttachment.php';
        $attachmentModel = new WorkOrderAttachment();
        $phpUploadLimitBytes = WorkOrderAttachment::phpUploadLimitBytes();

        $appVersion = t('common.unknown');
        if (file_exists(ROOT_PATH . '/version.php')) {
            require ROOT_PATH . '/version.php';
            $appVersion = trim($version . (!empty($channel) ? ' (' . $channel . ')' : ''));
        }

        $viewData = [
            'settings' => $allSettings,
            'error' => $error,
            'message' => $message,
            'activeTab' => $activeTab,
            'tabs' => self::TABS,
            'languages' => I18n::getInstance()->availableLocales(),
            'currentLanguage' => $allSettings['language'] ?? 'en-us',
            'currentPrintLanguage' => $allSettings['print_language'] ?? ($allSettings['language'] ?? 'en-us'),
            'attachmentDestinations' => $attachmentModel->availableDestinations(),
            'phpUploadLimitBytes' => $phpUploadLimitBytes,
            'phpUploadLimitLabel' => $phpUploadLimitBytes ? $attachmentModel->formatSize($phpUploadLimitBytes) : '',
            'appVersion' => $appVersion,
            'phpVersion' => PHP_VERSION,
            'csrf_token' => $this->generateCSRF(),
        ];

        $viewData = array_merge($viewData, $this->loadUsersTabData(), $this->loadModulesTabData());

        $this->view('settings/index', $viewData);
    }

    private function resolveActiveTab(string $tab): string {
        if ($tab === 'company') {
            return 'general';
        }
        return in_array($tab, self::TABS, true) ? $tab : 'general';
    }

    private function sectionToTab(string $section): string {
        $map = [
            'general' => 'general',
            'company' => 'general',
            'printout' => 'printout',
            'security' => 'security',
            'localization' => 'localization',
            'format' => 'localization',
            'language' => 'localization',
            'attachments' => 'attachments',
        ];
        return $map[$section] ?? 'general';
    }

    private function loadUsersTabData(): array {
        $userModel = new User();
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = PAGINATION_LIMIT;
        $totalCount = $userModel->countUsers();
        $totalPages = max(1, (int) ceil($totalCount / $limit));

        if ($page > $totalPages && $totalCount > 0) {
            $this->redirect('/404');
        }

        $offset = ($page - 1) * $limit;
        $users = $userModel->getAllUsers($limit, $offset);

        return [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
        ];
    }

    private function loadModulesTabData(): array {
        $loader = ModuleLoader::instance();
        $enabledModules = [];
        $disabledModules = [];
        foreach ($loader->getDiscovered() as $slug => $definition) {
            $loader->loadModuleLanguage($definition);
            $module = [
                'slug' => $slug,
                'name' => $definition['name'] ?? $slug,
                'description' => $definition['description'] ?? '',
                'version' => $definition['version'] ?? '',
                'author' => $definition['author'] ?? 'Napa AI',
                'enabled' => $loader->isEnabled($slug),
                'has_settings' => !empty($definition['settings']),
                'sort_name' => $this->moduleDisplayName($slug, $definition),
            ];
            if ($module['enabled']) {
                $enabledModules[] = $module;
            } else {
                $disabledModules[] = $module;
            }
        }

        $sortByName = fn(array $a, array $b) => strcasecmp($a['sort_name'], $b['sort_name']);
        usort($enabledModules, $sortByName);
        usort($disabledModules, $sortByName);

        return [
            'enabledModules' => $enabledModules,
            'disabledModules' => $disabledModules,
            'skipped' => $loader->getSkipped(),
        ];
    }

    private function moduleDisplayName(string $slug, array $definition): string {
        $nameKey = 'module.' . $slug . '.name';
        $name = t($nameKey);
        if ($name === $nameKey) {
            $name = $definition['name'] ?? $slug;
        }

        return $name;
    }
}
