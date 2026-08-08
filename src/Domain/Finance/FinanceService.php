<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use DateTimeImmutable;
use InvalidArgumentException;

final class FinanceService
{
    public function __construct(private FinanceRepository $repository)
    {
    }

    public function createEntry(array $payload, string $createdBy): void
    {
        $required = ['date', 'description', 'category', 'type', 'amount'];

        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new InvalidArgumentException("Campo obrigatorio ausente: {$field}");
            }
        }

        $payload['amount'] = (float) $payload['amount'];
        $payload['vendor'] = (string) ($payload['vendor'] ?? '');

        $this->repository->createEntry($payload, $createdBy);
    }

    public function monthlySummary(DateTimeImmutable $reference): array
    {
        return $this->repository->monthlySummary($reference);
    }

    public function upcomingForecast(): array
    {
        return $this->repository->upcomingForecast();
    }

    public function annualExpenseSeries(int $year): array
    {
        return $this->repository->annualExpenseSeries($year);
    }
}
