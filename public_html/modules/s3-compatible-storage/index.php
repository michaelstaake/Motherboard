<?php
return [
    'slug' => 's3-compatible-storage',
    'name' => 'S3 Compatible Storage',
    'description' => 'Store work order attachments on Amazon S3, Backblaze B2, Wasabi, or other S3-compatible services.',
    'version' => '1.2.0',
    'min_motherboard_version' => '26.100',
    'min_php_version' => '8.1',
    'default_enabled' => false,
    'settings' => true,
    'settings_keys' => ['s3_access_key', 's3_secret_key'],
    'author' => 'Michael Staake',
    'boot' => function (array $definition): void {
        require $definition['path'] . '/boot.php';
    },
];
