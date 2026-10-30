<?php

declare(strict_types=1);

namespace App\Domain\System;

use App\Core\Database;
use PDO;

final class SpreadsheetWritebackRepository
{
    public function __construct(private Database $database, private ?string $excelFile = null)
    {
    }

    public function queueExpense(array $payload, ?int $userId): int
    {
        $baseFileHash = $this->currentFileHash();
        $statement = $this->database->connection()->prepare(
            'INSERT INTO spreadsheet_writebacks (operation, status, payload, base_file_hash, created_by_user_id)
             VALUES ("create", "pending", :payload, :base_file_hash, :created_by_user_id)'
        );
        $statement->execute([
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'base_file_hash' => $baseFileHash,
            'created_by_user_id' => $userId,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function pending(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT id, operation, payload, base_file_hash
             FROM spreadsheet_writebacks
             WHERE status = "pending"
             ORDER BY id ASC'
        );

        return array_map(static function (array $row): array {
            $payload = json_decode((string) $row['payload'], true);

            return [
                'id' => (int) $row['id'],
                'operation' => (string) $row['operation'],
                'payload' => is_array($payload) ? $payload : [],
                'base_file_hash' => $row['base_file_hash'] !== null ? (string) $row['base_file_hash'] : null,
            ];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function markApplied(int $id): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE spreadsheet_writebacks
             SET status = "applied", applied_at = NOW(), error_message = NULL
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function markFailed(int $id, string $message): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE spreadsheet_writebacks
             SET status = "failed", error_message = :error_message
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'error_message' => mb_substr($message, 0, 500),
        ]);
    }

    public function markConflict(int $id, string $message): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE spreadsheet_writebacks
             SET status = "conflict", error_message = :error_message
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'error_message' => mb_substr($message, 0, 500),
        ]);
    }

    private function currentFileHash(): ?string
    {
        if ($this->excelFile === null || !is_file($this->excelFile)) {
            return null;
        }

        $hash = hash_file('sha256', $this->excelFile);

        return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) === 1 ? $hash : null;
    }
}
