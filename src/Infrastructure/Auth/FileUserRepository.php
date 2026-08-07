<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Role;
use App\Domain\Auth\User;
use App\Domain\Auth\UserRepository;

final class FileUserRepository implements UserRepository
{
    public function __construct(private string $file)
    {
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->all() as $user) {
            if (mb_strtolower($user['email']) === mb_strtolower($email)) {
                return new User(
                    $user['name'],
                    $user['email'],
                    $user['password_hash'],
                    Role::from($user['role'])
                );
            }
        }

        return null;
    }

    private function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $content = file_get_contents($this->file);

        if ($content === false || trim($content) === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }
}
