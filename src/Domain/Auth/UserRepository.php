<?php

declare(strict_types=1);

namespace App\Domain\Auth;

interface UserRepository
{
    public function findByEmail(string $email): ?User;
}

