<?php

declare(strict_types=1);

namespace App\Infrastructure\Finance;

use App\Domain\Finance\FinanceRepository;
use DateTimeImmutable;

final class ExcelFinanceRepository implements FinanceRepository
{
    public function __construct(private string $excelFile)
    {
    }

    public function createEntry(array $entry, string $createdBy): void
    {
        $auditFile = base_path('storage/entries.log');
        $line = json_encode([
            'created_by' => $createdBy,
            'created_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
            'entry' => $entry,
            'excel_file' => $this->excelFile,
            'status' => 'pending_excel_integration',
        ], JSON_UNESCAPED_UNICODE);

        file_put_contents($auditFile, $line . PHP_EOL, FILE_APPEND);
    }

    public function monthlySummary(DateTimeImmutable $reference): array
    {
        return [
            'month' => $reference->format('Y-m'),
            'income' => 0.0,
            'expenses' => 0.0,
            'balance' => 0.0,
            'status' => 'aguardando_integracao_com_excel',
        ];
    }

    public function upcomingForecast(): array
    {
        return [
            'next_month_estimated_expenses' => 0.0,
            'next_month_estimated_income' => 0.0,
            'status' => 'aguardando_integracao_com_excel',
        ];
    }
}

