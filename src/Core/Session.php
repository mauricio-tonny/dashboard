<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public function __construct(string $name)
    {
        session_name($name);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public function touchActivity(): void
    {
        $_SESSION['last_activity_at'] = time();
    }

    public function isIdleForMoreThan(int $seconds): bool
    {
        $lastActivityAt = $_SESSION['last_activity_at'] ?? null;

        if (!is_int($lastActivityAt)) {
            return false;
        }

        return time() - $lastActivityAt > $seconds;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
