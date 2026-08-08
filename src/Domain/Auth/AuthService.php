<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Session;
use App\Domain\Audit\AuditLogger;

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
        $this->session->touchActivity();

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
        $this->session->forget('last_activity_at');
        $this->session->regenerate();
    }

    public function enforceIdleTimeout(int $seconds, AuditLogger $auditLogger): void
    {
        $user = $this->user();

        if ($user === null) {
            return;
        }

        if (!$this->session->isIdleForMoreThan($seconds)) {
            $this->session->touchActivity();
            return;
        }

        $auditLogger->log('session_timeout', 'auth', null, $user, [
            'idle_limit_seconds' => $seconds,
        ]);

        $this->logout();
    }
}
