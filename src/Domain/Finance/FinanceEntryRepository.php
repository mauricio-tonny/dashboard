<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Core\Database;
use PDO;

final class FinanceEntryRepository
{
    public function __construct(private Database $database)
    {
    }

    public function expenseCategories(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT DISTINCT categories.id, categories.name
             FROM entries
             INNER JOIN categories ON categories.id = entries.category_id
             WHERE entries.type = "expense"
             ORDER BY categories.name'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function expenseSummary(string $startDate, string $endDate, array $categoryIds): array
    {
        $filters = $this->filters($startDate, $endDate, $categoryIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(SUM(entries.amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" THEN entries.amount ELSE 0 END), 0) AS open_amount,
                COUNT(*) AS entries_count
             FROM entries
             ' . $filters['join'] . '
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['categorySql']
        );
        $statement->execute($filters['params']);
        $summary = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $previousOpen = $this->previousOpenSummary($startDate, $categoryIds);
        $periodTotal = (float) ($summary['total_amount'] ?? 0);
        $periodOpen = (float) ($summary['open_amount'] ?? 0);
        $previousOpenAmount = (float) $previousOpen['total_amount'];

        return [
            'total_amount' => $periodTotal,
            'open_amount' => $periodOpen + $previousOpenAmount,
            'paid_amount' => $periodTotal - $periodOpen,
            'period_total_amount' => $periodTotal,
            'period_open_amount' => $periodOpen,
            'previous_open_amount' => $previousOpenAmount,
            'previous_open_count' => (int) $previousOpen['entries_count'],
            'last_installments_amount' => $this->lastInstallmentsAmount($startDate, $endDate, $categoryIds),
            'entries_count' => (int) ($summary['entries_count'] ?? 0),
        ];
    }

    public function previousOpenSummary(string $startDate, array $categoryIds): array
    {
        $filters = $this->filters($startDate, $startDate, $categoryIds);
        $params = $filters['params'];
        unset($params['end_date']);
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(SUM(entries.amount), 0) AS total_amount,
                COUNT(*) AS entries_count
             FROM entries
             WHERE entries.type = "expense"
               AND entries.status <> "paid"
               AND entries.entry_date < :start_date
               ' . $filters['categorySql']
        );
        $statement->execute($params);
        $summary = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_amount' => (float) ($summary['total_amount'] ?? 0),
            'entries_count' => (int) ($summary['entries_count'] ?? 0),
        ];
    }

    public function payableDashboardSummary(\DateTimeImmutable $reference): array
    {
        $currentMonthStart = $reference->modify('first day of this month')->format('Y-m-d');
        $currentMonthEnd = $reference->modify('last day of this month')->format('Y-m-d');
        $nextMonthStart = $reference->modify('first day of next month')->format('Y-m-d');
        $nextMonthEnd = $reference->modify('last day of next month')->format('Y-m-d');
        $overdue = $this->openAmountUntil($currentMonthEnd);
        $nextPeriod = $this->openAmountBetween($nextMonthStart, $nextMonthEnd);

        return [
            'total_amount' => $overdue['amount'] + $nextPeriod['amount'],
            'current_or_previous_open_amount' => $overdue['amount'],
            'next_period_open_amount' => $nextPeriod['amount'],
            'current_or_previous_open_count' => $overdue['count'],
            'next_period_open_count' => $nextPeriod['count'],
            'next_period_label' => $reference->modify('first day of next month')->format('Y-m'),
        ];
    }

    public function annualExpenseSeries(int $year): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                MONTH(entry_date) AS month_number,
                COALESCE(SUM(amount), 0) AS amount
             FROM entries
             WHERE type = "expense"
               AND YEAR(entry_date) = :year
             GROUP BY MONTH(entry_date)'
        );
        $statement->execute(['year' => $year]);
        $amounts = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $amounts[(int) $row['month_number']] = (float) $row['amount'];
        }

        return array_map(static fn (int $month): array => [
            'month' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'amount' => $amounts[$month] ?? 0.0,
        ], range(1, 12));
    }

    public function annualExpensesByCategory(int $year): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(categories.name, "Sem categoria") AS category_name,
                COALESCE(SUM(entries.amount), 0) AS amount,
                COUNT(*) AS entries_count
             FROM entries
             LEFT JOIN categories ON categories.id = entries.category_id
             WHERE entries.type = "expense"
               AND YEAR(entries.entry_date) = :year
             GROUP BY COALESCE(categories.name, "Sem categoria")
             ORDER BY amount DESC, category_name ASC'
        );
        $statement->execute(['year' => $year]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function openAmountUntil(string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS amount, COUNT(*) AS count
             FROM entries
             WHERE type = "expense"
               AND status <> "paid"
               AND entry_date <= :end_date'
        );
        $statement->execute(['end_date' => $endDate]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'amount' => (float) ($row['amount'] ?? 0),
            'count' => (int) ($row['count'] ?? 0),
        ];
    }

    private function openAmountBetween(string $startDate, string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS amount, COUNT(*) AS count
             FROM entries
             WHERE type = "expense"
               AND status <> "paid"
               AND entry_date BETWEEN :start_date AND :end_date'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'amount' => (float) ($row['amount'] ?? 0),
            'count' => (int) ($row['count'] ?? 0),
        ];
    }

    public function lastInstallmentsAmount(string $startDate, string $endDate, array $categoryIds): float
    {
        $filters = $this->filters($startDate, $endDate, $categoryIds);
        $statement = $this->database->connection()->prepare(
            'SELECT COALESCE(SUM(entries.amount), 0) AS total_amount
             FROM entries
             WHERE entries.type = "expense"
               AND entries.is_last_installment = 1
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['categorySql']
        );
        $statement->execute($filters['params']);

        return (float) $statement->fetchColumn();
    }

    public function expenseRows(string $startDate, string $endDate, array $categoryIds): array
    {
        $filters = $this->filters($startDate, $endDate, $categoryIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                entries.id,
                entries.entry_date,
                entries.competence_month,
                entries.description,
                entries.amount,
                entries.status,
                entries.modality,
                entries.installment_current,
                entries.installment_total,
                entries.is_last_installment,
                categories.name AS category_name,
                vendors.name AS vendor_name
             FROM entries
             LEFT JOIN categories ON categories.id = entries.category_id
             LEFT JOIN vendors ON vendors.id = entries.vendor_id
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['categorySql'] . '
             ORDER BY entries.entry_date ASC, entries.description ASC, entries.id ASC'
        );
        $statement->execute($filters['params']);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function expensesByCategory(string $startDate, string $endDate, array $categoryIds): array
    {
        $filters = $this->filters($startDate, $endDate, $categoryIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(categories.name, "Sem categoria") AS category_name,
                COALESCE(SUM(entries.amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" THEN entries.amount ELSE 0 END), 0) AS open_amount,
                COUNT(*) AS entries_count
             FROM entries
             LEFT JOIN categories ON categories.id = entries.category_id
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['categorySql'] . '
             GROUP BY COALESCE(categories.name, "Sem categoria")
             ORDER BY total_amount DESC, category_name ASC'
        );
        $statement->execute($filters['params']);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function filters(string $startDate, string $endDate, array $categoryIds): array
    {
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        $categorySql = '';

        if ($categoryIds !== []) {
            $placeholders = [];

            foreach (array_values($categoryIds) as $index => $categoryId) {
                $key = 'category_id_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $categoryId;
            }

            $categorySql = ' AND entries.category_id IN (' . implode(', ', $placeholders) . ')';
        }

        return [
            'join' => '',
            'categorySql' => $categorySql,
            'params' => $params,
        ];
    }
}
