<?php
require $definition['path'] . '/lib.php';
require_once ROOT_PATH . '/models/Settings.php';

Hooks::addFilter('attachment.destinations', function (array $destinations): array {
    $destinations['s3'] = t('module.s3-compatible-storage.destination');
    return $destinations;
});

Hooks::addAction('settings.attachments.destination_help', function (): void {
    $url = BASE_URL . '/module-manager/s3-compatible-storage/settings';
    echo '<p class="mt-1 text-sm text-gray-500">'
        . t('module.s3-compatible-storage.destination_help')
        . ' <a href="' . htmlspecialchars($url) . '" class="text-primary-600 hover:text-primary-500 underline">'
        . htmlspecialchars(t('modules.configure'))
        . '</a></p>';
});

Hooks::addFilter('attachment.storage.put', function (array $result, string $destination, string $key, string $sourcePath, array $meta): array {
    if ($destination !== 's3' || !empty($result['handled'])) {
        return $result;
    }
    $result['handled'] = true;
    try {
        motherboard_s3_client()->putFile(
            motherboard_s3_object_key($key),
            $sourcePath,
            $meta['mime_type'] ?? 'application/octet-stream'
        );
        $result['ok'] = true;
    } catch (Exception $e) {
        $result['ok'] = false;
        $result['error'] = $e->getMessage();
    }
    return $result;
});

Hooks::addFilter('attachment.storage.fetch', function (array $result, string $destination, string $key, array $attachment): array {
    if ($destination !== 's3' || !empty($result['handled'])) {
        return $result;
    }
    $result['handled'] = true;
    $tmp = tempnam(sys_get_temp_dir(), 'mbs3');
    if ($tmp === false) {
        $result['error'] = t('wo.attachment_upload_fail');
        return $result;
    }
    try {
        motherboard_s3_client()->getObjectToFile(motherboard_s3_object_key($key), $tmp);
        $result['ok'] = true;
        $result['path'] = $tmp;
    } catch (Exception $e) {
        @unlink($tmp);
        $result['ok'] = false;
        $result['error'] = $e->getMessage();
    }
    return $result;
});

Hooks::addFilter('attachment.storage.delete', function (array $result, string $destination, string $key, array $attachment): array {
    if ($destination !== 's3' || !empty($result['handled'])) {
        return $result;
    }
    $result['handled'] = true;
    try {
        motherboard_s3_client()->deleteObject(motherboard_s3_object_key($key));
        $result['ok'] = true;
    } catch (Exception $e) {
        $result['ok'] = false;
        $result['error'] = $e->getMessage();
    }
    return $result;
});

Hooks::addFilter('module.settings.save.s3-compatible-storage', function (array $result, array $post, Settings $settings): array {
    $config = motherboard_s3_posted_settings($post, $settings);
    $error = motherboard_s3_validate($config);
    if ($error !== '') {
        $result['error'] = $error;
        return $result;
    }

    if (($post['s3_action'] ?? 'save') === 'test') {
        try {
            motherboard_s3_client($config)->testConnection(
                motherboard_s3_object_key('.motherboard-connection-test', $config)
            );
            $result['message'] = t('module.s3-compatible-storage.test_ok');
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    motherboard_s3_save_settings($settings, $config);
    return $result;
});
