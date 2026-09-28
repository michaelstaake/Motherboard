<?php
return [
    'slug' => 'customer-email',
    'name' => 'Customer Email',
    'description' => 'Email customers when their work order is created, first worked on, completed, and picked up.',
    'version' => '1.6.0',
    'min_motherboard_version' => '26.9.17.1',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'author' => 'Michael Staake',
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
