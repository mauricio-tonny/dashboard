<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use DateTimeImmutable;

interface FinanceRepository
{
    public function createEntry(array $entry, string $createdBy): void;

    public function monthlySummary(DateTimeImmutable $reference): array;

    public function upcomingForecast(): array;

    public function annualExpenseSeries(int $year): array;
}
