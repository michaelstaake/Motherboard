<?php
return [
    'slug' => 'email-2fa',
    'name' => 'Email Two-Factor Authentication',
    'description' => 'Require an emailed code on every login or when signing in from a new IP address.',
    'version' => '1.0.0',
    'min_motherboard_version' => '26.8.14.1',
    'min_php_version' => '8.1',
    'default_enabled' => true,
    'settings' => true,
    'author' => 'Napa AI',
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
