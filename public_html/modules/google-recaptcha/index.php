<?php
return [
    'slug' => 'google-recaptcha',
    'name' => 'Google reCAPTCHA',
    'description' => 'Protect login and password reset forms with Google reCAPTCHA v2.',
    'version' => '1.2.0',
    'min_motherboard_version' => '26.100',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'settings_keys' => ['recaptcha_secret_key'],
    'author' => 'Michael Staake',
    'conflicts' => ['cloudflare-turnstile'],
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
