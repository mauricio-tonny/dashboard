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

    public function topEvents(string $action, int $days = 30, int $limit = 10): array
    {
        $since = (new \DateTimeImmutable('now'))
            ->modify(sprintf('-%d days', max(1, $days)))
            ->format('Y-m-d H:i:s');

        $statement = $this->database->connection()->prepare(
            'SELECT
                JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.label")) AS label,
                JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.path")) AS path,
                COUNT(*) AS total,
                COUNT(DISTINCT user_id) AS users_count,
                MAX(created_at) AS last_seen_at
             FROM audit_logs
             WHERE action = :action
               AND created_at >= :since
             GROUP BY label, path
             ORDER BY total DESC, last_seen_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('action', $action);
        $statement->bindValue('since', $since);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
