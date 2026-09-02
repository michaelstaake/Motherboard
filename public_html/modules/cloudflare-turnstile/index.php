<?php
return [
    'slug' => 'cloudflare-turnstile',
    'name' => 'Cloudflare Turnstile',
    'description' => 'Protect login and password reset forms with Cloudflare Turnstile.',
    'version' => '1.0.0',
    'min_motherboard_version' => '26.8.14.1',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'settings_keys' => ['turnstile_secret_key'],
    'author' => 'Napa AI',
    'conflicts' => ['google-recaptcha'],
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
