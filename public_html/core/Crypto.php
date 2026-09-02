<?php

class Crypto {
    private const PREFIX = 'enc:v1:';

    public static function encrypt(?string $value): ?string {
        if ($value === null || $value === '') {
            return $value;
        }
        if (self::isEncrypted($value)) {
            return $value;
        }

        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $value,
            'aes-256-gcm',
            self::key(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::PREFIX,
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Unable to encrypt sensitive data.');
        }

        return self::PREFIX . base64_encode($nonce . $tag . $ciphertext);
    }

    public static function decrypt(?string $value): ?string {
        if ($value === null || $value === '' || !self::isEncrypted($value)) {
            return $value;
        }

        $payload = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($payload === false || strlen($payload) < 29) {
            throw new RuntimeException('Encrypted data is invalid.');
        }

        $nonce = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $ciphertext = substr($payload, 28);
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            self::key(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::PREFIX
        );

        if ($plaintext === false) {
            throw new RuntimeException('Unable to decrypt sensitive data. Check APP_ENCRYPTION_KEY.');
        }

        return $plaintext;
    }

    public static function isEncrypted(?string $value): bool {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    private static function key(): string {
        $configured = defined('APP_ENCRYPTION_KEY') ? trim((string) APP_ENCRYPTION_KEY) : '';
        if (strlen($configured) < 32 || str_starts_with($configured, 'replace-')) {
            throw new RuntimeException('APP_ENCRYPTION_KEY must be a private random value of at least 32 characters.');
        }

        return hash('sha256', $configured, true);
    }
}
