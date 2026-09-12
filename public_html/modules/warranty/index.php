<?php
return [
    'slug' => 'warranty',
    'name' => 'Warranty',
    'description' => 'Flag work orders as warranty repairs, reference the prior work order, and mark it on the printout.',
    'version' => '1.0.0',
    'min_motherboard_version' => '26.9.12.4',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'author' => 'Napa AI',
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
