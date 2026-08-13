<?php

declare(strict_types=1);

namespace App\Domain\Auth;

final class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly Role $role
    ) {
    }

    public function canEdit(): bool
    {
        return $this->can(Permission::CREATE_EXPENSE) || $this->can(Permission::CREATE_INCOME);
    }

    public function can(Permission $permission): bool
    {
        return RolePermissionMap::grants($this->role, $permission);
    }

    public function masksFinancialValues(): bool
    {
        return $this->role === Role::VALIDATOR;
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
