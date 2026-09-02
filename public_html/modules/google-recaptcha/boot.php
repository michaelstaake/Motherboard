<?php
require_once $definition['path'] . '/lib.php';
require_once ROOT_PATH . '/models/Settings.php';

Hooks::addFilter('auth.captcha.html', function (string $html): string {
    $site = (new Settings())->getSetting('recaptcha_site_key', '');
    if ($site === '') {
        return $html;
    }
    return $html . '<div class="flex justify-center"><div class="g-recaptcha" data-sitekey="' . htmlspecialchars($site) . '"></div></div>';
});

Hooks::addFilter('auth.captcha.scripts', function (string $scripts): string {
    $site = (new Settings())->getSetting('recaptcha_site_key', '');
    if ($site === '') {
        return $scripts;
    }
    return $scripts . '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
});

Hooks::addFilter('auth.captcha.verify', function (string $error, string $form, array $post): string {
    if ($error !== '') {
        return $error;
    }
    $settings = new Settings();
    $site = $settings->getSetting('recaptcha_site_key', '');
    $secret = $settings->getSetting('recaptcha_secret_key', '');
    if ($site === '' || $secret === '') {
        return t('auth.captcha_misconfigured');
    }
    if (!motherboard_verify_captcha(
        'https://www.google.com/recaptcha/api/siteverify',
        $secret,
        $post['g-recaptcha-response'] ?? '',
        motherboard_client_ip()
    )) {
        return t('auth.captcha_failed');
    }
    return '';
});

Hooks::addFilter('module.settings.save.google-recaptcha', function (array $result, array $post, Settings $settings): array {
    $settings->setSetting('recaptcha_site_key', trim($post['recaptcha_site_key'] ?? ''));
    $secret = trim($post['recaptcha_secret_key'] ?? '');
    if ($secret !== '') {
        $settings->setSetting('recaptcha_secret_key', $secret);
    }
    $settings->setSetting('captcha_provider', 'recaptcha');
    return $result;
});
