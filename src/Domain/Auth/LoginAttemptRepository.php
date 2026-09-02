<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Database;
use PDO;

final class LoginAttemptRepository
{
    public function __construct(private Database $database)
    {
    }

    public function isBlocked(string $email, string $ipAddress, int $limit = 5, int $windowMinutes = 10): bool
    {
        return $this->failedAttempts($email, $ipAddress, $windowMinutes) >= $limit;
    }

    public function failedAttempts(string $email, string $ipAddress, int $windowMinutes = 10): int
    {
        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*)
             FROM login_attempts
             WHERE success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL :window_minutes MINUTE)
               AND (email = :email OR ip_address = :ip_address)'
        );
        $statement->bindValue('window_minutes', $windowMinutes, PDO::PARAM_INT);
        $statement->bindValue('email', mb_strtolower(trim($email)));
        $statement->bindValue('ip_address', $ipAddress);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function record(string $email, string $ipAddress, string $userAgent, bool $success): void
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO login_attempts (email, ip_address, user_agent, success, attempted_at)
             VALUES (:email, :ip_address, :user_agent, :success, NOW())'
        );
        $statement->execute([
            'email' => mb_strtolower(trim($email)),
            'ip_address' => $ipAddress,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'success' => $success ? 1 : 0,
        ]);
    }
}
