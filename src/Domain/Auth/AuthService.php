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
        $sessionToken = bin2hex(random_bytes(32));
        $this->session->put('user_email', $user->email);
        $this->session->put('session_token', $sessionToken);
        $this->session->touchActivity();
        $this->users->recordLogin($user->email, hash('sha256', $sessionToken));

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
        $this->session->forget('session_token');
        $this->session->forget('last_activity_at');
        $this->session->regenerate();
    }

    public function verifyPassword(User $user, string $password): bool
    {
        return password_verify($password, $user->passwordHash);
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
            'ip' => request_ip($_SERVER),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        $this->logout();
        $this->session->flash('error', 'Sua sessão expirou por inatividade. Faça login novamente.');
    }

    public function enforceSingleSession(AuditLogger $auditLogger): void
    {
        $user = $this->user();

        if ($user === null) {
            return;
        }

        $sessionToken = $this->session->get('session_token');

        if (!is_string($sessionToken) || $sessionToken === '') {
            $this->logout();
            return;
        }

        $currentTokenHash = $this->users->currentSessionTokenHash($user->email);

        if ($currentTokenHash === null) {
            return;
        }

        if (hash_equals($currentTokenHash, hash('sha256', $sessionToken))) {
            return;
        }

        $auditLogger->log('session_replaced', 'auth', null, $user, [
            'reason' => 'new_login_detected',
            'ip' => request_ip($_SERVER),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        $this->logout();
        $this->session->flash('error', 'Sua sessão foi encerrada porque este usuário entrou em outro dispositivo ou navegador.');
    }
}
