<?php

declare(strict_types=1);

namespace App\Domain\System;

use App\Core\Database;
use App\Support\Encryption;
use PDO;

final class SecuritySettingsRepository
{
    public function __construct(private Database $database)
    {
    }

    public function settings(): array
    {
        $this->database->connection()->exec('INSERT IGNORE INTO security_settings (id) VALUES (1)');
        $statement = $this->database->connection()->query(
            'SELECT *
             FROM security_settings
             WHERE id = 1
             LIMIT 1'
        );
        $settings = $statement->fetch(PDO::FETCH_ASSOC);

        if ($settings === false) {
            return $this->emptySettings();
        }

        $recaptchaSecretKey = Encryption::decrypt($settings['encrypted_recaptcha_secret_key'] ?? null);

        return [
            ...$settings,
            'recaptcha_secret_key' => $recaptchaSecretKey,
            'has_recaptcha_secret_key' => $recaptchaSecretKey !== '',
            'masked_recaptcha_secret_key' => self::maskSecret($recaptchaSecretKey),
            'recaptcha_is_ready' => (int) ($settings['recaptcha_is_enabled'] ?? 0) === 1
                && trim((string) ($settings['recaptcha_site_key'] ?? '')) !== ''
                && $recaptchaSecretKey !== '',
            'analytics_is_ready' => (int) ($settings['analytics_is_enabled'] ?? 0) === 1
                && self::isValidMeasurementId((string) ($settings['analytics_measurement_id'] ?? '')),
        ];
    }

    public function publicSettings(): array
    {
        $settings = $this->settings();

        return [
            'recaptcha_is_enabled' => (bool) ($settings['recaptcha_is_ready'] ?? false),
            'recaptcha_site_key' => (string) ($settings['recaptcha_site_key'] ?? ''),
            'recaptcha_min_score' => (float) ($settings['recaptcha_min_score'] ?? 0.5),
            'analytics_is_enabled' => (bool) ($settings['analytics_is_ready'] ?? false),
            'analytics_measurement_id' => (string) ($settings['analytics_measurement_id'] ?? ''),
        ];
    }

    public function save(array $data): void
    {
        $current = $this->settings();
        $secretKey = trim((string) ($data['recaptcha_secret_key'] ?? ''));
        $encryptedSecretKey = $secretKey !== ''
            ? Encryption::encrypt($secretKey)
            : ($data['keep_recaptcha_secret_key'] ?? false ? ($current['encrypted_recaptcha_secret_key'] ?? null) : null);

        $statement = $this->database->connection()->prepare(
            'INSERT INTO security_settings (
                id,
                recaptcha_is_enabled,
                recaptcha_site_key,
                encrypted_recaptcha_secret_key,
                recaptcha_min_score,
                analytics_is_enabled,
                analytics_measurement_id
             ) VALUES (
                1,
                :recaptcha_is_enabled,
                :recaptcha_site_key,
                :encrypted_recaptcha_secret_key,
                :recaptcha_min_score,
                :analytics_is_enabled,
                :analytics_measurement_id
             ) ON DUPLICATE KEY UPDATE
                recaptcha_is_enabled = VALUES(recaptcha_is_enabled),
                recaptcha_site_key = VALUES(recaptcha_site_key),
                encrypted_recaptcha_secret_key = VALUES(encrypted_recaptcha_secret_key),
                recaptcha_min_score = VALUES(recaptcha_min_score),
                analytics_is_enabled = VALUES(analytics_is_enabled),
                analytics_measurement_id = VALUES(analytics_measurement_id),
                updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'recaptcha_is_enabled' => !empty($data['recaptcha_is_enabled']) ? 1 : 0,
            'recaptcha_site_key' => trim((string) ($data['recaptcha_site_key'] ?? '')),
            'encrypted_recaptcha_secret_key' => $encryptedSecretKey,
            'recaptcha_min_score' => min(1, max(0, (float) ($data['recaptcha_min_score'] ?? 0.5))),
            'analytics_is_enabled' => !empty($data['analytics_is_enabled']) ? 1 : 0,
            'analytics_measurement_id' => strtoupper(trim((string) ($data['analytics_measurement_id'] ?? ''))),
        ]);
    }

    public static function isValidMeasurementId(string $measurementId): bool
    {
        return preg_match('/^G-[A-Z0-9]{4,}$/', strtoupper(trim($measurementId))) === 1;
    }

    public static function maskSecret(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        return str_repeat('*', 8) . substr($secret, -4);
    }

    private function emptySettings(): array
    {
        return [
            'id' => 1,
            'recaptcha_is_enabled' => 0,
            'recaptcha_site_key' => '',
            'encrypted_recaptcha_secret_key' => null,
            'recaptcha_secret_key' => '',
            'has_recaptcha_secret_key' => false,
            'masked_recaptcha_secret_key' => '',
            'recaptcha_min_score' => '0.50',
            'recaptcha_is_ready' => false,
            'analytics_is_enabled' => 0,
            'analytics_measurement_id' => '',
            'analytics_is_ready' => false,
        ];
    }
}
