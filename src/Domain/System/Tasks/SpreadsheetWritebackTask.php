<?php

declare(strict_types=1);

namespace App\Domain\System\Tasks;

use App\Domain\System\ScheduledTask;
use App\Domain\System\ScheduledTaskResult;
use App\Domain\System\SpreadsheetWritebackProcessor;

final class SpreadsheetWritebackTask implements ScheduledTask
{
    public function __construct(
        private SpreadsheetWritebackProcessor $processor,
        private int $intervalMinutes,
        private array $syncSettings = []
    ) {
    }

    public function code(): string
    {
        return 'spreadsheet.writeback';
    }

    public function name(): string
    {
        return 'Enviar alterações para a planilha';
    }

    public function intervalMinutes(): int
    {
        return max(5, $this->intervalMinutes);
    }

    public function run(): ScheduledTaskResult
    {
        $timezone = new \DateTimeZone('America/Sao_Paulo');
        $now = new \DateTimeImmutable('now', $timezone);
        $startHour = min(23, max(0, (int) ($this->syncSettings['sync_start_hour'] ?? 8)));
        $endHour = min(24, max(1, (int) ($this->syncSettings['sync_end_hour'] ?? 18)));
        $currentHour = (int) $now->format('G');
        $isWeekday = (int) $now->format('N') <= 5;
        $insideImportWindow = $isWeekday && $currentHour >= $startHour && $currentHour < $endHour;

        if ($insideImportWindow) {
            return ScheduledTaskResult::skipped(
                'Retorno adiado: a janela de importação da planilha ainda está ativa.',
                [
                    'reason' => 'inside_import_window',
                    'checked_at' => $now->format('Y-m-d H:i:s'),
                    'sync_start_hour' => $startHour,
                    'sync_end_hour' => $endHour,
                ]
            );
        }

        try {
            $result = $this->processor->process();

            return ScheduledTaskResult::success(
                'Retorno da planilha processado: ' . $result['message'],
                [
                    ...$result,
                    'trigger' => 'scheduler',
                    'checked_at' => $now->format('Y-m-d H:i:s'),
                ]
            );
        } catch (\Throwable $exception) {
            return ScheduledTaskResult::failure(
                'Falha ao enviar alterações para a planilha pelo scheduler.',
                [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }
}
