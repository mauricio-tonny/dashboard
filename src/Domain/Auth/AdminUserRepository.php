<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Database;
use PDO;

final class AdminUserRepository
{
    public function __construct(private Database $database)
    {
    }

    public function all(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT users.id, users.name, users.email, users.is_active, users.last_login_at,
                    users.created_at, users.updated_at, roles.name AS role
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             ORDER BY users.name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $name, string $email, Role $role, string $password): int
    {
        $pdo = $this->database->connection();
        $statement = $pdo->prepare(
            'INSERT INTO users (role_id, name, email, password_hash, is_active)
             VALUES (:role_id, :name, :email, :password_hash, 1)'
        );

        $statement->execute([
            'role_id' => $this->roleId($role),
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $email, Role $role, ?string $password = null): void
    {
        $params = [
            'id' => $id,
            'role_id' => $this->roleId($role),
            'name' => $name,
            'email' => $email,
        ];

        $passwordSql = '';

        if ($password !== null && $password !== '') {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $statement = $this->database->connection()->prepare(
            "UPDATE users
             SET role_id = :role_id,
                 name = :name,
                 email = :email
                 {$passwordSql},
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );

        $statement->execute($params);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE users
             SET is_active = :is_active,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT users.id, users.name, users.email, users.is_active, roles.name AS role
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    private function roleId(Role $role): int
    {
        $statement = $this->database->connection()->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $role->value]);

        return (int) $statement->fetchColumn();
    }
}
