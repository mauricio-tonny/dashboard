<?php

declare(strict_types=1);

namespace App\Domain\System;

use App\Core\Database;
use PDO;

final class DiscordNotificationRepository
{
    public function __construct(private Database $database)
    {
    }

    public function settings(): array
    {
        $this->database->connection()->exec('INSERT IGNORE INTO discord_notification_settings (id) VALUES (1)');
        $statement = $this->database->connection()->query(
            'SELECT *
             FROM discord_notification_settings
             WHERE id = 1
             LIMIT 1'
        );
        $settings = $statement->fetch(PDO::FETCH_ASSOC);

        return $settings === false ? [
            'is_enabled' => 0,
            'webhook_url' => '',
            'notify_market_list_created' => 0,
        ] : $settings;
    }

    public function save(bool $enabled, ?string $webhookUrl, bool $notifyMarketListCreated): void
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO discord_notification_settings (id, is_enabled, webhook_url, notify_market_list_created)
             VALUES (1, :is_enabled, :webhook_url, :notify_market_list_created)
             ON DUPLICATE KEY UPDATE
                is_enabled = VALUES(is_enabled),
                webhook_url = VALUES(webhook_url),
                notify_market_list_created = VALUES(notify_market_list_created),
                updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'is_enabled' => $enabled ? 1 : 0,
            'webhook_url' => $webhookUrl,
            'notify_market_list_created' => $notifyMarketListCreated ? 1 : 0,
        ]);
    }
}
