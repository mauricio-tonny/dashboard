<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Core\Database;
use App\Domain\Auth\User;
use PDO;

final class AuditLogger
{
    public function __construct(private Database $database)
    {
    }

    public function log(string $action, string $entityType, ?int $entityId = null, ?User $user = null, array $metadata = []): void
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata)
             VALUES (:user_id, :action, :entity_type, :entity_id, :metadata)'
        );

        $statement->execute([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function pruneOlderThan(int $days): int
    {
        $statement = $this->database->connection()->prepare(
            'DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)'
        );
        $statement->bindValue('days', $days, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount();
    }

    public function latest(int $limit = 100): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT audit_logs.*, users.name AS user_name, users.email AS user_email
             FROM audit_logs
             LEFT JOIN users ON users.id = audit_logs.user_id
             ORDER BY audit_logs.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
