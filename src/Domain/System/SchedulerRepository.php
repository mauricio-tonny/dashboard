<?php

declare(strict_types=1);

namespace App\Domain\System;

use App\Core\Database;
use DateTimeImmutable;
use PDO;

final class SchedulerRepository
{
    public function __construct(private Database $database)
    {
    }

    public function ensureTask(ScheduledTask $task, DateTimeImmutable $now): void
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO scheduled_tasks (code, name, interval_minutes, next_run_at)
             VALUES (:code, :name, :interval_minutes, :next_run_at)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                interval_minutes = VALUES(interval_minutes),
                updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'code' => $task->code(),
            'name' => $task->name(),
            'interval_minutes' => $task->intervalMinutes(),
            'next_run_at' => $this->format($now),
        ]);
    }

    public function dueTaskCodes(DateTimeImmutable $now): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT code
             FROM scheduled_tasks
             WHERE is_active = 1
               AND next_run_at <= :now
             ORDER BY next_run_at ASC, code ASC'
        );
        $statement->execute(['now' => $this->format($now)]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function acquireLock(string $code, string $token, DateTimeImmutable $now, int $lockMinutes = 15): bool
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE scheduled_tasks
             SET locked_until = :locked_until,
                 lock_token = :lock_token,
                 updated_at = CURRENT_TIMESTAMP
             WHERE code = :code
               AND is_active = 1
               AND next_run_at <= :now
               AND (locked_until IS NULL OR locked_until < :now)'
        );
        $statement->execute([
            'code' => $code,
            'now' => $this->format($now),
            'locked_until' => $this->format($now->modify("+{$lockMinutes} minutes")),
            'lock_token' => $token,
        ]);

        return $statement->rowCount() === 1;
    }

    public function startRun(string $code, DateTimeImmutable $startedAt): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO scheduled_task_runs (task_code, status, started_at)
             VALUES (:task_code, "running", :started_at)'
        );
        $statement->execute([
            'task_code' => $code,
            'started_at' => $this->format($startedAt),
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function finishRun(int $runId, ScheduledTaskResult $result, DateTimeImmutable $startedAt, DateTimeImmutable $finishedAt): void
    {
        $metadata = $result->metadata === [] ? null : json_encode($result->metadata, JSON_UNESCAPED_UNICODE);
        $durationMs = max(0, ((int) $finishedAt->format('Uv')) - ((int) $startedAt->format('Uv')));
        $statement = $this->database->connection()->prepare(
            'UPDATE scheduled_task_runs
             SET status = :status,
                 finished_at = :finished_at,
                 duration_ms = :duration_ms,
                 message = :message,
                 metadata = :metadata
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $runId,
            'status' => $result->success ? 'success' : 'failed',
            'finished_at' => $this->format($finishedAt),
            'duration_ms' => $durationMs,
            'message' => mb_substr($result->message, 0, 500),
            'metadata' => $metadata === false ? null : $metadata,
        ]);
    }

    public function releaseTask(string $code, string $token, ScheduledTask $task, ScheduledTaskResult $result, DateTimeImmutable $finishedAt): void
    {
        $nextRunAt = $finishedAt->modify('+' . max(1, $task->intervalMinutes()) . ' minutes');
        $statement = $this->database->connection()->prepare(
            'UPDATE scheduled_tasks
             SET last_run_at = :last_run_at,
                 next_run_at = :next_run_at,
                 locked_until = NULL,
                 lock_token = NULL,
                 consecutive_failures = CASE WHEN :success = 1 THEN 0 ELSE consecutive_failures + 1 END,
                 last_status = :last_status,
                 last_message = :last_message,
                 updated_at = CURRENT_TIMESTAMP
             WHERE code = :code
               AND lock_token = :lock_token'
        );
        $statement->execute([
            'code' => $code,
            'lock_token' => $token,
            'last_run_at' => $this->format($finishedAt),
            'next_run_at' => $this->format($nextRunAt),
            'success' => $result->success ? 1 : 0,
            'last_status' => $result->success ? 'success' : 'failed',
            'last_message' => mb_substr($result->message, 0, 500),
        ]);
    }

    private function format(DateTimeImmutable $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
