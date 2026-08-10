<?php

declare(strict_types=1);

namespace App\Domain\System;

use App\Core\Database;
use App\Support\Encryption;
use PDO;

final class SpreadsheetSettingsRepository
{
    public function __construct(private Database $database)
    {
    }

    public function settings(): array
    {
        $this->database->connection()->exec('INSERT IGNORE INTO spreadsheet_settings (id) VALUES (1)');
        $statement = $this->database->connection()->query(
            'SELECT *
             FROM spreadsheet_settings
             WHERE id = 1
             LIMIT 1'
        );
        $settings = $statement->fetch(PDO::FETCH_ASSOC);

        if ($settings === false) {
            return $this->emptySettings();
        }

        $url = Encryption::decrypt($settings['encrypted_url'] ?? null);

        return [
            ...$settings,
            'url' => $url,
            'masked_url' => self::maskUrl($url),
            'has_url' => $url !== '',
        ];
    }

    public function save(string $url): void
    {
        $encryptedUrl = Encryption::encrypt($url);
        $urlHash = hash('sha256', $url);
        $statement = $this->database->connection()->prepare(
            'INSERT INTO spreadsheet_settings (id, encrypted_url, url_hash)
             VALUES (1, :encrypted_url, :url_hash)
             ON DUPLICATE KEY UPDATE
                encrypted_url = VALUES(encrypted_url),
                url_hash = VALUES(url_hash),
                updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'encrypted_url' => $encryptedUrl,
            'url_hash' => $urlHash,
        ]);
    }

    public function remove(): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE spreadsheet_settings
             SET encrypted_url = NULL,
                 url_hash = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = 1'
        );
        $statement->execute();
    }

    public static function maskUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '');
        $path = (string) ($parts['path'] ?? '');
        $prefix = (($parts['scheme'] ?? 'https') . '://' . $host);
        $suffix = strlen($path) > 10 ? substr($path, -10) : $path;

        return $prefix . '/...' . $suffix;
    }

    private function emptySettings(): array
    {
        return [
            'id' => 1,
            'encrypted_url' => null,
            'url_hash' => null,
            'url' => '',
            'masked_url' => '',
            'has_url' => false,
        ];
    }
}
