<?php
require_once ROOT_PATH . '/core/ClientIp.php';

if (!function_exists('motherboard_verify_captcha')) {
function motherboard_verify_captcha(string $url, string $secret, string $response, string $ip): bool {
    if ($response === '' || $secret === '') {
        return false;
    }
    $data = [
        'secret' => $secret,
        'response' => $response,
        'remoteip' => $ip,
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            // Without this a stalled provider holds the login request open for
            // default_socket_timeout (60s). Verification fails closed on timeout.
            'timeout' => 5,
        ],
    ];
    $result = @file_get_contents($url, false, stream_context_create($options));
    if ($result === false) {
        return false;
    }
    $json = json_decode($result, true);
    return isset($json['success']) && $json['success'] === true;
}

function motherboard_client_ip(): string {
    return ClientIp::resolve();
}
}
