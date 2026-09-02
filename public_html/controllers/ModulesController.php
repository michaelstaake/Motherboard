<?php
require_once 'core/Controller.php';

class ModulesController extends Controller {
    public function index() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $msg = isset($_GET['msg']) ? '&msg=' . urlencode($_GET['msg']) : '';
            $this->redirect('/settings?tab=modules' . $msg);
        }

        $error = '';
        $message = '';
        $loader = ModuleLoader::instance();

        try {
            $this->validateCSRF();
            $slug = $this->sanitizeInput($_POST['slug'] ?? '');
            $enable = isset($_POST['enable']) && $_POST['enable'] === '1';
            $module = $loader->getModule($slug);
            if (!$module) {
                throw new Exception(t('modules.not_found'));
            }
            $loader->setEnabled($this->settingsModel, $slug, $enable);
            $this->logger->log('module_toggled', ($enable ? 'Enabled' : 'Disabled') . ' module ' . $slug, $_SESSION['user_id']);
            $message = $enable ? t('modules.enabled_ok', ['name' => $module['name'] ?? $slug]) : t('modules.disabled_ok', ['name' => $module['name'] ?? $slug]);
            $this->redirect('/settings?tab=modules&msg=' . urlencode($message));
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->redirect('/settings?tab=modules&error=' . urlencode($error));
        }
    }

    public function settings($slug) {
        $this->requireAdmin();
        $loader = ModuleLoader::instance();
        $module = $loader->getModule($slug);

        if (!$module || !$loader->isEnabled($slug) || empty($module['settings'])) {
            $this->redirect('/settings?tab=modules');
        }

        $loader->loadModuleLanguage($module);

        $error = '';
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                $result = Hooks::applyFilters('module.settings.save.' . $slug, [
                    'ok' => true,
                    'error' => '',
                    'message' => t('modules.settings_saved'),
                ], $_POST, $this->settingsModel);

                if (!empty($result['error'])) {
                    $error = $result['error'];
                } else {
                    $this->logger->log('module_settings', 'Updated settings for module ' . $slug, $_SESSION['user_id']);
                    $message = $result['message'] ?? t('modules.settings_saved');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $viewFile = $module['path'] . '/views/settings.php';
        $this->viewPath($viewFile, [
            'module' => $module,
            'settings' => $this->settingsModel->getAllSettingsForModule($module['settings_keys'] ?? []),
            'error' => $error,
            'message' => $message,
            'csrf_token' => $this->generateCSRF(),
        ], 'module-settings/' . $slug);
    }
}
