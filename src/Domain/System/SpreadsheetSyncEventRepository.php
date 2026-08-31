<?php

declare(strict_types=1);

namespace App\Domain\System;

use App\Core\Database;
use DateTimeImmutable;
use PDO;

final class SpreadsheetSyncEventRepository
{
    public function __construct(private Database $database)
    {
    }

    public function record(
        string $status,
        DateTimeImmutable $occurredAt,
        ?string $fileHash,
        ?int $fileSize,
        ?string $machineName,
        ?string $message,
        array $metadata = []
    ): int {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO spreadsheet_sync_events
                (source, status, occurred_at, file_hash, file_size, machine_name, message, metadata)
             VALUES
                ("windows", :status, :occurred_at, :file_hash, :file_size, :machine_name, :message, :metadata)'
        );
        $encodedMetadata = $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE);
        $statement->execute([
            'status' => mb_substr($status, 0, 30),
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'file_hash' => $fileHash === null || $fileHash === '' ? null : mb_substr($fileHash, 0, 64),
            'file_size' => $fileSize,
            'machine_name' => $machineName === null || $machineName === '' ? null : mb_substr($machineName, 0, 120),
            'message' => $message === null || $message === '' ? null : mb_substr($message, 0, 500),
            'metadata' => $encodedMetadata === false ? null : $encodedMetadata,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function latest(int $limit = 10): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT *
             FROM spreadsheet_sync_events
             ORDER BY occurred_at DESC, id DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
