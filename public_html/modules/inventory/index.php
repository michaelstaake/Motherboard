<?php
return [
    'slug' => 'inventory',
    'name' => 'Inventory',
    'description' => 'Track product categories, stock, pricing, and assign products to work orders.',
    'version' => '1.18.0',
    'min_motherboard_version' => '26.100',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'author' => 'Michael Staake',
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
