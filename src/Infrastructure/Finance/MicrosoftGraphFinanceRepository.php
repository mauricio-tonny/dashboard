<?php

declare(strict_types=1);

namespace App\Infrastructure\Finance;

use App\Domain\Finance\FinanceRepository;
use DateTimeImmutable;
use RuntimeException;

final class MicrosoftGraphFinanceRepository implements FinanceRepository
{
    public function __construct(
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly string $driveId,
        private readonly string $itemId
    ) {
    }

    public function createEntry(array $entry, string $createdBy): void
    {
        throw new RuntimeException('Integracao com Microsoft Graph ainda nao implementada.');
    }

    public function monthlySummary(DateTimeImmutable $reference): array
    {
        return [
            'month' => $reference->format('Y-m'),
            'income' => 0.0,
            'expenses' => 0.0,
            'balance' => 0.0,
            'status' => 'repositorio_microsoft_graph_planejado',
        ];
    }

    public function upcomingForecast(): array
    {
        return [
            'next_month_estimated_expenses' => 0.0,
            'next_month_estimated_income' => 0.0,
            'status' => 'repositorio_microsoft_graph_planejado',
        ];
    }
}
