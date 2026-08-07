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
            'SELECT users.name, users.email, users.password_hash, roles.name AS role
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
            $user['name'],
            $user['email'],
            $user['password_hash'],
            Role::from($user['role'])
        );
    }
}
