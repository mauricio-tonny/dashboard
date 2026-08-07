<?php

declare(strict_types=1);

namespace App\Domain\Auth;

final class User
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly Role $role
    ) {
    }

    public function canEdit(): bool
    {
        return $this->hasRole(Role::ADMIN, Role::EDITOR);
    }

    public function hasRole(Role ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->role === $role) {
                return true;
            }
        }

        return false;
    }
}

