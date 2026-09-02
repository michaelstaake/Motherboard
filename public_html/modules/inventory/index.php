<?php
return [
    'slug' => 'inventory',
    'name' => 'Inventory',
    'description' => 'Track product categories, stock, pricing, and assign products to work orders.',
    'version' => '1.3.0',
    'min_motherboard_version' => '26.8.17.5',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'author' => 'Napa AI',
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
