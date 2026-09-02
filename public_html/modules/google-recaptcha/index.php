<?php
return [
    'slug' => 'google-recaptcha',
    'name' => 'Google reCAPTCHA',
    'description' => 'Protect login and password reset forms with Google reCAPTCHA v2.',
    'version' => '1.0.0',
    'min_motherboard_version' => '26.8.14.1',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'author' => 'Napa AI',
    'conflicts' => ['cloudflare-turnstile'],
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
