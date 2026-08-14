<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Core\Database;
use App\Domain\Auth\Role;
use App\Domain\Auth\User;
use App\Domain\Auth\UserRepository;
use PDO;

final class DatabaseUserRepository implements UserRepository
{
    public function __construct(private Database $database)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->database->connection()->prepare(
            'SELECT users.id, users.name, users.email, users.password_hash, roles.name AS role
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.email = :email AND users.is_active = 1
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return new User(
            (int) $user['id'],
            $user['name'],
            $user['email'],
            $user['password_hash'],
            Role::from($user['role']),
            $this->permissionOverrides((int) $user['id'])
        );
    }

    public function recordLogin(string $email): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE users
             SET last_login_at = NOW(),
                 updated_at = CURRENT_TIMESTAMP
             WHERE email = :email'
        );
        $statement->execute(['email' => $email]);
    }

    private function permissionOverrides(int $userId): array
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
}
