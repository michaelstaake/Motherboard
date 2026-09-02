<?php

class ClientIp {
    private const CLOUDFLARE_RANGES = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    public static function resolve(): string {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $forwarded = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';

        if (
            $forwarded !== ''
            && filter_var($forwarded, FILTER_VALIDATE_IP)
            && self::isCloudflareAddress($remote)
        ) {
            return $forwarded;
        }

        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
    }

    private static function isCloudflareAddress(string $ip): bool {
        foreach (self::CLOUDFLARE_RANGES as $range) {
            if (self::isInCidr($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private static function isInCidr(string $ip, string $cidr): bool {
        [$network, $prefix] = explode('/', $cidr, 2);
        $ipBytes = @inet_pton($ip);
        $networkBytes = @inet_pton($network);
        if ($ipBytes === false || $networkBytes === false || strlen($ipBytes) !== strlen($networkBytes)) {
            return false;
        }

        $remaining = (int) $prefix;
        for ($i = 0, $length = strlen($ipBytes); $i < $length; $i++) {
            if ($remaining <= 0) {
                return true;
            }
            $bits = min(8, $remaining);
            $mask = (0xff << (8 - $bits)) & 0xff;
            if ((ord($ipBytes[$i]) & $mask) !== (ord($networkBytes[$i]) & $mask)) {
                return false;
            }
            $remaining -= $bits;
        }

        return true;
    }
}
