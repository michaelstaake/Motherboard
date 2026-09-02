<?php

function motherboard_s3_posted_settings(array $post, Settings $settings): array {
    $existing = motherboard_s3_settings($settings);
    $secret = trim((string) ($post['s3_secret_key'] ?? ''));
    if ($secret === '') {
        $secret = $existing['secret_key'];
    }

    return [
        'endpoint' => motherboard_s3_normalize_endpoint(trim((string) ($post['s3_endpoint'] ?? ''))),
        'region' => trim((string) ($post['s3_region'] ?? '')),
        'bucket' => trim((string) ($post['s3_bucket'] ?? '')),
        'access_key' => trim((string) ($post['s3_access_key'] ?? '')),
        'secret_key' => $secret,
        'prefix' => motherboard_s3_normalize_prefix(trim((string) ($post['s3_prefix'] ?? ''))),
        'path_style' => !empty($post['s3_path_style']),
    ];
}

function motherboard_s3_settings(?Settings $settings = null): array {
    $settings = $settings ?: new Settings();
    return [
        'endpoint' => (string) $settings->getSetting('s3_endpoint', ''),
        'region' => (string) $settings->getSetting('s3_region', ''),
        'bucket' => (string) $settings->getSetting('s3_bucket', ''),
        'access_key' => (string) $settings->getSetting('s3_access_key', ''),
        'secret_key' => (string) $settings->getSetting('s3_secret_key', ''),
        'prefix' => motherboard_s3_normalize_prefix((string) $settings->getSetting('s3_prefix', '')),
        'path_style' => (bool) $settings->getSetting('s3_path_style', '0'),
    ];
}

function motherboard_s3_save_settings(Settings $settings, array $config): void {
    $settings->setSetting('s3_endpoint', $config['endpoint']);
    $settings->setSetting('s3_region', $config['region']);
    $settings->setSetting('s3_bucket', $config['bucket']);
    $settings->setSetting('s3_access_key', $config['access_key']);
    $settings->setSetting('s3_secret_key', $config['secret_key']);
    $settings->setSetting('s3_prefix', $config['prefix']);
    $settings->setSetting('s3_path_style', !empty($config['path_style']) ? '1' : '0');
}

function motherboard_s3_validate(array $config): string {
    if (!function_exists('curl_init')) {
        return t('module.s3-compatible-storage.missing_curl');
    }
    if ($config['endpoint'] === '' || $config['region'] === '' || $config['bucket'] === '' || $config['access_key'] === '' || $config['secret_key'] === '') {
        return t('module.s3-compatible-storage.incomplete');
    }
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,61}[A-Za-z0-9]$/', $config['bucket'])) {
        return t('module.s3-compatible-storage.invalid_bucket');
    }
    return '';
}

function motherboard_s3_normalize_endpoint(string $endpoint): string {
    $endpoint = trim($endpoint);
    if ($endpoint === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $endpoint)) {
        $endpoint = 'https://' . $endpoint;
    }
    $parts = parse_url($endpoint);
    if ($parts === false || empty($parts['host'])) {
        return '';
    }
    $host = strtolower($parts['host']);

    // Plaintext would put the SigV4 Authorization header and the object body on the wire
    // in the clear, and the payload is signed as UNSIGNED-PAYLOAD so it has no integrity
    // protection either. Permit http only for hosts that cannot leave the local network.
    $scheme = strtolower($parts['scheme'] ?? 'https');
    if ($scheme !== 'http' || !motherboard_s3_is_private_host($host)) {
        $scheme = 'https';
    }

    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    return $scheme . '://' . $host . $port;
}

/**
 * True for hosts that resolve to loopback, link-local, or RFC1918 space.
 *
 * Used for two purposes: allowing plaintext to a MinIO-style endpoint on the same
 * network, and refusing to let an admin point the client at cloud metadata services
 * (169.254.169.254 and friends), which would turn "Test connection" into an SSRF probe.
 */
function motherboard_s3_is_private_host(string $host): bool {
    $host = trim($host, '[]');

    if (filter_var($host, FILTER_VALIDATE_IP) === false) {
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return true;
        }
        $resolved = gethostbyname($host);
        if ($resolved === $host || filter_var($resolved, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        $host = $resolved;
    }

    return filter_var(
        $host,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;
}

/**
 * Link-local space only (169.254.0.0/16, fe80::/10). Deliberately narrower than
 * motherboard_s3_is_private_host(): a MinIO endpoint on the LAN is a legitimate
 * configuration, a cloud metadata endpoint never is.
 */
function motherboard_s3_is_metadata_host(string $host): bool {
    $host = trim($host, '[]');

    if (filter_var($host, FILTER_VALIDATE_IP) === false) {
        $resolved = gethostbyname($host);
        if ($resolved === $host || filter_var($resolved, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        $host = $resolved;
    }

    return str_starts_with($host, '169.254.')
        || str_starts_with(strtolower($host), 'fe80:');
}

function motherboard_s3_normalize_prefix(string $prefix): string {
    $prefix = trim(str_replace('\\', '/', $prefix), '/');
    $prefix = preg_replace('#/+#', '/', $prefix) ?? $prefix;
    return $prefix;
}

function motherboard_s3_object_key(string $relative, ?array $config = null): string {
    $config = $config ?: motherboard_s3_settings();
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    $prefix = $config['prefix'] ?? '';
    if ($prefix === '') {
        return $relative;
    }
    return $relative === '' ? $prefix : $prefix . '/' . $relative;
}

function motherboard_s3_client(?array $config = null): MotherboardS3Client {
    $config = $config ?: motherboard_s3_settings();
    $error = motherboard_s3_validate($config);
    if ($error !== '') {
        throw new Exception($error);
    }
    if ($config['endpoint'] === '') {
        throw new Exception(t('module.s3-compatible-storage.invalid_endpoint'));
    }
    return new MotherboardS3Client(
        $config['endpoint'],
        $config['region'],
        $config['bucket'],
        $config['access_key'],
        $config['secret_key'],
        !empty($config['path_style'])
    );
}

function motherboard_s3_error(string $detail): string {
    return t('module.s3-compatible-storage.error', ['detail' => $detail]);
}

class MotherboardS3Client {
    private string $endpoint;
    private string $region;
    private string $bucket;
    private string $accessKey;
    private string $secretKey;
    private bool $pathStyle;

    public function __construct(
        string $endpoint,
        string $region,
        string $bucket,
        string $accessKey,
        string $secretKey,
        bool $pathStyle
    ) {
        $this->endpoint = rtrim($endpoint, '/');
        $this->region = $region;
        $this->bucket = $bucket;
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->pathStyle = $pathStyle || str_contains($bucket, '.');
    }

    public function putFile(string $key, string $sourcePath, string $contentType): void {
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }
        $this->request('PUT', $key, [
            'infile' => $sourcePath,
            'headers' => [
                'Content-Type' => $contentType !== '' ? $contentType : 'application/octet-stream',
            ],
            'ok' => [200, 201, 204],
        ]);
    }

    public function getObjectToFile(string $key, string $destPath): void {
        $this->request('GET', $key, [
            'outfile' => $destPath,
            'ok' => [200],
        ]);
    }

    public function deleteObject(string $key): void {
        $this->request('DELETE', $key, [
            'ok' => [200, 204, 404],
        ]);
    }

    public function testConnection(string $key = '.motherboard-connection-test'): void {
        $tmp = tempnam(sys_get_temp_dir(), 'mbs3');
        if ($tmp === false) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }
        file_put_contents($tmp, 'ok');
        try {
            $this->putFile($key, $tmp, 'text/plain');
            $this->deleteObject($key);
        } finally {
            @unlink($tmp);
        }
    }

    private function request(string $method, string $key, array $options): array {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $amzDate = $now->format('Ymd\THis\Z');
        $dateStamp = $now->format('Ymd');
        $payloadHash = 'UNSIGNED-PAYLOAD';

        $target = $this->target($key);
        $headers = [
            'Host' => $target['host'],
            'X-Amz-Content-Sha256' => $payloadHash,
            'X-Amz-Date' => $amzDate,
        ];
        foreach ($options['headers'] ?? [] as $name => $value) {
            $headers[$name] = $value;
        }

        // SigV4 requires the signed headers in byte order of their LOWERCASED names.
        // Sorting the original mixed-case keys happens to agree for the fixed header set
        // used here, but would silently break the signature for any added header.
        $signedNames = array_keys($headers);
        usort($signedNames, function ($a, $b) {
            return strcmp(strtolower($a), strtolower($b));
        });
        $canonicalHeaders = '';
        $signedHeaderList = [];
        foreach ($signedNames as $name) {
            $lower = strtolower($name);
            $canonicalHeaders .= $lower . ':' . $this->trimHeader($headers[$name]) . "\n";
            $signedHeaderList[] = $lower;
        }
        $signedHeaders = implode(';', $signedHeaderList);
        $canonicalRequest = implode("\n", [
            $method,
            $target['canonicalUri'],
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = $dateStamp . '/' . $this->region . '/s3/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));
        $headers['Authorization'] = 'AWS4-HMAC-SHA256 Credential=' . $this->accessKey . '/' . $scope
            . ', SignedHeaders=' . $signedHeaders
            . ', Signature=' . $signature;

        $headerLines = ['Expect:'];
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'host') {
                continue;
            }
            $headerLines[] = $name . ': ' . $value;
        }

        $ch = curl_init($target['url']);
        if ($ch === false) {
            throw new Exception(motherboard_s3_error('cURL init failed'));
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $in = null;
        $out = null;
        if (!empty($options['infile'])) {
            $in = fopen($options['infile'], 'rb');
            if ($in === false) {
                curl_close($ch);
                throw new Exception(t('wo.attachment_upload_fail'));
            }
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_INFILE, $in);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($options['infile']));
        }
        if (!empty($options['outfile'])) {
            $out = fopen($options['outfile'], 'wb');
            if ($out === false) {
                if (is_resource($in)) {
                    fclose($in);
                }
                curl_close($ch);
                throw new Exception(t('wo.attachment_upload_fail'));
            }
            curl_setopt($ch, CURLOPT_FILE, $out);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        }

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (is_resource($in)) {
            fclose($in);
        }
        if (is_resource($out)) {
            fclose($out);
        }

        if ($errno) {
            if (!empty($options['outfile'])) {
                @unlink($options['outfile']);
            }
            throw new Exception(motherboard_s3_error($error !== '' ? $error : 'cURL error ' . $errno));
        }

        $ok = $options['ok'] ?? [200, 201, 204];
        if (!in_array($status, $ok, true)) {
            if (!empty($options['outfile'])) {
                @unlink($options['outfile']);
            }
            $detail = $this->errorFromBody(is_string($body) ? $body : '');
            throw new Exception(motherboard_s3_error($detail !== '' ? $detail : 'HTTP ' . $status));
        }

        return [
            'status' => $status,
            'body' => is_string($body) ? $body : '',
        ];
    }

    private function target(string $key): array {
        $parts = parse_url($this->endpoint);
        $scheme = $parts['scheme'] ?? 'https';
        $endpointHost = $parts['host'] ?? '';

        // An admin-supplied endpoint is still an SSRF vector: "Test connection" reports the
        // response body back to the browser. Cloud metadata services live on link-local
        // addresses, so refuse to sign anything aimed there.
        if ($endpointHost !== '' && motherboard_s3_is_metadata_host($endpointHost)) {
            throw new Exception(motherboard_s3_error('Endpoint host is not routable'));
        }
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $pathStyle = $this->pathStyle;
        $host = $pathStyle ? $endpointHost : $this->bucket . '.' . $endpointHost;
        $hostHeader = $host;
        if ($port && !(($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80))) {
            $hostHeader .= ':' . $port;
        }

        $encodedKey = $this->encodePath($key);
        $canonicalUri = $pathStyle
            ? '/' . $this->encodePath($this->bucket) . ($encodedKey !== '' ? '/' . $encodedKey : '')
            : '/' . $encodedKey;
        if ($canonicalUri === '') {
            $canonicalUri = '/';
        }

        $url = $scheme . '://' . $hostHeader . $canonicalUri;
        return [
            'url' => $url,
            'host' => $hostHeader,
            'canonicalUri' => $canonicalUri,
        ];
    }

    private function encodePath(string $path): string {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return '';
        }
        $segments = explode('/', $path);
        foreach ($segments as &$segment) {
            $segment = str_replace('%7E', '~', rawurlencode($segment));
        }
        unset($segment);
        return implode('/', $segments);
    }

    private function signingKey(string $dateStamp): string {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    private function trimHeader(string $value): string {
        return preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
    }

    private function errorFromBody(string $body): string {
        if ($body === '') {
            return '';
        }
        if (preg_match('/<Message>([^<]+)<\/Message>/', $body, $match)) {
            $message = html_entity_decode($match[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            if (preg_match('/<Code>([^<]+)<\/Code>/', $body, $code)) {
                return $code[1] . ': ' . $message;
            }
            return $message;
        }
        $plain = trim(strip_tags($body));
        return $plain !== '' ? substr($plain, 0, 240) : '';
    }
}
