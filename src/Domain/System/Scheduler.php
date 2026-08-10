<?php

declare(strict_types=1);

namespace App\Domain\System;

use DateTimeImmutable;
use Throwable;

final class Scheduler
{
    /**
     * @param array<string, ScheduledTask> $tasks
     */
    public function __construct(
        private SchedulerRepository $repository,
        private array $tasks
    ) {
    }

    public function runDue(DateTimeImmutable $now): array
    {
        foreach ($this->tasks as $task) {
            $this->repository->ensureTask($task, $now);
        }

        $results = [];

        foreach ($this->repository->dueTaskCodes($now) as $code) {
            $task = $this->tasks[$code] ?? null;

            if ($task === null) {
                continue;
            }

            $results[] = $this->runTask($task, $now);
        }

        return $results;
    }

    private function runTask(ScheduledTask $task, DateTimeImmutable $now): array
    {
        $token = bin2hex(random_bytes(16));

        if (!$this->repository->acquireLock($task->code(), $token, $now)) {
            return [
                'code' => $task->code(),
                'status' => 'locked',
                'message' => 'Tarefa já está em execução ou não está mais pendente.',
            ];
        }

        $startedAt = new DateTimeImmutable();
        $runId = $this->repository->startRun($task->code(), $startedAt);

        try {
            $result = $task->run();
        } catch (Throwable $exception) {
            $result = ScheduledTaskResult::failure($exception->getMessage(), [
                'exception' => $exception::class,
            ]);
        }

        $finishedAt = new DateTimeImmutable();
        $this->repository->finishRun($runId, $result, $startedAt, $finishedAt);
        $this->repository->releaseTask($task->code(), $token, $task, $result, $finishedAt);

        return [
            'code' => $task->code(),
            'status' => $result->success ? 'success' : 'failed',
            'message' => $result->message,
        ];
    }
}
