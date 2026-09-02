<?php
require_once ROOT_PATH . '/models/User.php';
require_once ROOT_PATH . '/models/Settings.php';
require_once ROOT_PATH . '/core/EmailSender.php';

Hooks::addFilter('auth.login.after_credentials', function (array $gate, array $user, string $ip): array {
    $settings = new Settings();
    $userModel = new User();
    $always = (bool) $settings->getSetting('require_2fa', false);
    $hasRecentLogin = $userModel->hasRecentLogin($user['id'], $ip);

    if (!$always && $hasRecentLogin) {
        return $gate;
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $userModel->store2FACode($user['id'], $code);

    $sent = false;
    try {
        $companyName = $settings->getSetting('company_name', APP_NAME);
        $companyName = !empty($companyName) ? $companyName : APP_NAME;
        $emailSender = new EmailSender($companyName);
        $sent = $emailSender->send2FACode($user['email'], $code, $user['username'] ?? 'User');
    } catch (Exception $e) {
        error_log('Failed to send 2FA email to ' . $user['email'] . ': ' . $e->getMessage());
    }

    $_SESSION['pending_2fa_user'] = $user['id'];
    $gate['proceed'] = false;
    $gate['requires_2fa'] = true;
    if ($sent) {
        $gate['message'] = $always ? t('auth.2fa_required') : t('auth.2fa_new_location');
    } else {
        $gate['message'] = t('auth.2fa_fallback');
    }
    return $gate;
});

Hooks::addFilter('auth.2fa.verify', function (array $result): array {
    $userModel = new User();
    $result['handled'] = true;
    if (!empty($result['user_id']) && $userModel->verify2FACode($result['user_id'], $result['code'] ?? '')) {
        $result['success'] = true;
        $result['error'] = '';
    } else {
        $result['success'] = false;
        $result['error'] = t('auth.invalid_code');
    }
    return $result;
});

Hooks::addFilter('module.settings.save.email-2fa', function (array $result, array $post, Settings $settings): array {
    $settings->setSetting('require_2fa', isset($post['require_2fa']) ? '1' : '0');
    return $result;
});
