<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Encryption
{
    private const CIPHER = 'aes-256-gcm';

    public static function encrypt(string $plainText): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt(
            $plainText,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new RuntimeException('Não foi possível criptografar o valor informado.');
        }

        return base64_encode(json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'value' => base64_encode($encrypted),
        ], JSON_THROW_ON_ERROR));
    }

    public static function decrypt(?string $payload): string
    {
        if ($payload === null || trim($payload) === '') {
            return '';
        }

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw new RuntimeException('Valor criptografado inválido.');
        }

        $data = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        $iv = base64_decode((string) ($data['iv'] ?? ''), true);
        $tag = base64_decode((string) ($data['tag'] ?? ''), true);
        $encrypted = base64_decode((string) ($data['value'] ?? ''), true);

        if ($iv === false || $tag === false || $encrypted === false) {
            throw new RuntimeException('Valor criptografado inválido.');
        }

        $plainText = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plainText === false) {
            throw new RuntimeException('Não foi possível descriptografar o valor salvo.');
        }

        return $plainText;
    }

    public static function isConfigured(): bool
    {
        try {
            self::key();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    private static function key(): string
    {
        $appKey = trim((string) ($_ENV['APP_KEY'] ?? ''));

        if ($appKey === '') {
            throw new RuntimeException('APP_KEY precisa estar configurada no .env para salvar segredos.');
        }

        if (str_starts_with($appKey, 'base64:')) {
            $key = base64_decode(substr($appKey, 7), true);
        } else {
            $key = $appKey;
        }

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('APP_KEY deve conter 32 bytes. Gere com: php -r "echo \'base64:\'.base64_encode(random_bytes(32)).PHP_EOL;"');
        }

        return $key;
    }
}
