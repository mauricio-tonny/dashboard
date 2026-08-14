<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Database;
use PDO;

final class UserPermissionRepository
{
    public function __construct(private Database $database)
    {
    }

    public function users(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT users.id, users.name, users.email, roles.name AS role
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.is_active = 1
             ORDER BY users.name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function permissions(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT name, label, description
             FROM permissions
             ORDER BY label ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function overridesByUser(int $userId): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT permissions.name, user_permission_overrides.effect
             FROM user_permission_overrides
             INNER JOIN permissions ON permissions.id = user_permission_overrides.permission_id
             WHERE user_permission_overrides.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        $overrides = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $overrides[(string) $row['name']] = (string) $row['effect'];
        }

        return $overrides;
    }

    public function saveOverrides(int $userId, array $effects): void
    {
        $pdo = $this->database->connection();
        $pdo->beginTransaction();

        try {
            $delete = $pdo->prepare('DELETE FROM user_permission_overrides WHERE user_id = :user_id');
            $delete->execute(['user_id' => $userId]);

            $insert = $pdo->prepare(
                'INSERT INTO user_permission_overrides (user_id, permission_id, effect)
                 SELECT :user_id, permissions.id, :effect
                 FROM permissions
                 WHERE permissions.name = :permission'
            );

            foreach ($effects as $permission => $effect) {
                if (!in_array($effect, ['allow', 'deny'], true)) {
                    continue;
                }

                $insert->execute([
                    'user_id' => $userId,
                    'effect' => $effect,
                    'permission' => $permission,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
