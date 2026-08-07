<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Session;

final class AuthService
{
    public function __construct(
        private UserRepository $users,
        private Session $session
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user->passwordHash)) {
            return false;
        }

        $this->session->regenerate();
        $this->session->put('user_email', $user->email);

        return true;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        $email = $this->session->get('user_email');

        if (!is_string($email) || $email === '') {
            return null;
        }

        return $this->users->findByEmail($email);
    }

    public function logout(): void
    {
        $this->session->forget('user_email');
        $this->session->regenerate();
    }
}

