<?php
require_once ROOT_PATH . '/models/User.php';
require_once ROOT_PATH . '/models/Settings.php';
require_once ROOT_PATH . '/core/EmailSender.php';

define('MOTHERBOARD_2FA_COOKIE', 'mb_trusted_device');
define('MOTHERBOARD_2FA_TRUST_DAYS', 30);

/**
 * Signed marker proving this browser previously completed a second factor for this user.
 *
 * Replaces the previous "have we seen this IP in 30 days" test, which trusted a property
 * of the network rather than the browser: anyone sharing the victim's NAT egress address
 * (an office, a VPN, carrier-grade NAT) skipped 2FA entirely with just the password.
 */
function motherboard_2fa_device_token(int $userId, int $expires): string {
    $payload = $userId . '|' . $expires;
    return $payload . '|' . hash_hmac('sha256', $payload, APP_ENCRYPTION_KEY);
}

function motherboard_2fa_device_is_trusted(int $userId): bool {
    $raw = $_COOKIE[MOTHERBOARD_2FA_COOKIE] ?? '';
    if (!is_string($raw) || substr_count($raw, '|') !== 2) {
        return false;
    }

    [$cookieUser, $expires, $signature] = explode('|', $raw);
    if ((int) $cookieUser !== $userId || (int) $expires < time()) {
        return false;
    }

    $expected = motherboard_2fa_device_token((int) $cookieUser, (int) $expires);
    return hash_equals($expected, $raw);
}

function motherboard_2fa_trust_device(int $userId): void {
    if (headers_sent()) {
        return;
    }

    $expires = time() + (MOTHERBOARD_2FA_TRUST_DAYS * 86400);
    $params = session_get_cookie_params();
    setcookie(MOTHERBOARD_2FA_COOKIE, motherboard_2fa_device_token($userId, $expires), [
        'expires' => $expires,
        'path' => '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool) ($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

Hooks::addFilter('auth.login.after_credentials', function (array $gate, array $user, string $ip): array {
    $settings = new Settings();
    $userModel = new User();
    $always = (bool) $settings->getSetting('require_2fa', false);

    if (!$always && motherboard_2fa_device_is_trusted((int) $user['id'])) {
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
        motherboard_2fa_trust_device((int) $result['user_id']);
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
