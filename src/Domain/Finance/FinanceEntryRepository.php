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

    public function expenseVendors(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT DISTINCT vendors.id, vendors.name
             FROM entries
             INNER JOIN vendors ON vendors.id = entries.vendor_id
             WHERE entries.type = "expense"
             ORDER BY vendors.name'
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
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS estimated_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" AND COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS open_estimated_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" AND COALESCE(entries.is_estimated, 0) = 0 THEN entries.amount ELSE 0 END), 0) AS open_confirmed_amount,
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
        $periodEstimated = (float) ($summary['estimated_amount'] ?? 0);
        $periodOpenEstimated = (float) ($summary['open_estimated_amount'] ?? 0);
        $periodOpenConfirmed = (float) ($summary['open_confirmed_amount'] ?? 0);
        $previousOpenAmount = (float) $previousOpen['total_amount'];
        $previousOpenEstimatedAmount = (float) $previousOpen['estimated_amount'];
        $previousOpenConfirmedAmount = (float) $previousOpen['confirmed_amount'];

        return [
            'total_amount' => $periodTotal,
            'open_amount' => $periodOpen + $previousOpenAmount,
            'estimated_amount' => $periodEstimated,
            'confirmed_amount' => $periodTotal - $periodEstimated,
            'open_estimated_amount' => $periodOpenEstimated + $previousOpenEstimatedAmount,
            'open_confirmed_amount' => $periodOpenConfirmed + $previousOpenConfirmedAmount,
            'paid_amount' => $periodTotal - $periodOpen,
            'period_total_amount' => $periodTotal,
            'period_open_amount' => $periodOpen,
            'period_estimated_amount' => $periodEstimated,
            'period_open_estimated_amount' => $periodOpenEstimated,
            'period_open_confirmed_amount' => $periodOpenConfirmed,
            'previous_open_amount' => $previousOpenAmount,
            'previous_open_estimated_amount' => $previousOpenEstimatedAmount,
            'previous_open_confirmed_amount' => $previousOpenConfirmedAmount,
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
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS estimated_amount,
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 0 THEN entries.amount ELSE 0 END), 0) AS confirmed_amount,
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
            'estimated_amount' => (float) ($summary['estimated_amount'] ?? 0),
            'confirmed_amount' => (float) ($summary['confirmed_amount'] ?? 0),
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
                entries.is_estimated,
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

    public function incomeSummary(string $startDate, string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(SUM(amount), 0) AS total_amount,
                COUNT(*) AS entries_count,
                COALESCE(SUM(CASE WHEN description = "SALARIO MAURICIO" THEN amount ELSE 0 END), 0) AS mauricio_amount,
                COALESCE(SUM(CASE WHEN description = "SALARIO KARINA" THEN amount ELSE 0 END), 0) AS karina_amount
             FROM entries
             WHERE type = "income"
               AND entry_date BETWEEN :start_date AND :end_date'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $total = (float) ($row['total_amount'] ?? 0);
        $mauricio = (float) ($row['mauricio_amount'] ?? 0);
        $karina = (float) ($row['karina_amount'] ?? 0);

        return [
            'total_amount' => $total,
            'entries_count' => (int) ($row['entries_count'] ?? 0),
            'mauricio_amount' => $mauricio,
            'karina_amount' => $karina,
            'other_amount' => max(0.0, $total - $mauricio - $karina),
        ];
    }

    public function incomeRows(string $startDate, string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                id,
                entry_date,
                competence_month,
                description,
                amount,
                status
             FROM entries
             WHERE type = "income"
               AND entry_date BETWEEN :start_date AND :end_date
             ORDER BY
                entry_date ASC,
                CASE description
                    WHEN "SALARIO MAURICIO" THEN 1
                    WHEN "OUTROS MAURICIO" THEN 2
                    WHEN "SALARIO KARINA" THEN 3
                    WHEN "OUTROS KARINA" THEN 4
                    ELSE 5
                END ASC,
                description ASC,
                id ASC'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function incomeByDescription(string $startDate, string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                description,
                COALESCE(SUM(amount), 0) AS total_amount,
                COUNT(*) AS entries_count
             FROM entries
             WHERE type = "income"
               AND entry_date BETWEEN :start_date AND :end_date
             GROUP BY description
             ORDER BY
                CASE description
                    WHEN "SALARIO MAURICIO" THEN 1
                    WHEN "OUTROS MAURICIO" THEN 2
                    WHEN "SALARIO KARINA" THEN 3
                    WHEN "OUTROS KARINA" THEN 4
                    ELSE 5
                END ASC,
                total_amount DESC,
                description ASC'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

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
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS estimated_amount,
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

    public function expenseReportRows(string $startDate, string $endDate, array $categoryIds, array $vendorIds): array
    {
        $filters = $this->reportFilters($startDate, $endDate, $categoryIds, $vendorIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                entries.id,
                entries.entry_date,
                entries.competence_month,
                entries.description,
                entries.amount,
                entries.status,
                entries.installment_current,
                entries.installment_total,
                entries.is_last_installment,
                entries.is_estimated,
                COALESCE(categories.name, "Sem categoria") AS category_name,
                COALESCE(vendors.name, "Sem fornecedor") AS vendor_name
             FROM entries
             LEFT JOIN categories ON categories.id = entries.category_id
             LEFT JOIN vendors ON vendors.id = entries.vendor_id
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['sql'] . '
             ORDER BY entries.entry_date ASC, category_name ASC, vendor_name ASC, entries.description ASC'
        );
        $statement->execute($filters['params']);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function expenseReportSummary(string $startDate, string $endDate, array $categoryIds, array $vendorIds): array
    {
        $filters = $this->reportFilters($startDate, $endDate, $categoryIds, $vendorIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(SUM(entries.amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN entries.status = "paid" THEN entries.amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" THEN entries.amount ELSE 0 END), 0) AS open_amount,
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS estimated_amount,
                COUNT(*) AS entries_count
             FROM entries
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['sql']
        );
        $statement->execute($filters['params']);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_amount' => (float) ($row['total_amount'] ?? 0),
            'paid_amount' => (float) ($row['paid_amount'] ?? 0),
            'open_amount' => (float) ($row['open_amount'] ?? 0),
            'estimated_amount' => (float) ($row['estimated_amount'] ?? 0),
            'confirmed_amount' => (float) ($row['total_amount'] ?? 0) - (float) ($row['estimated_amount'] ?? 0),
            'entries_count' => (int) ($row['entries_count'] ?? 0),
        ];
    }

    public function expensesByVendor(string $startDate, string $endDate, array $categoryIds, array $vendorIds): array
    {
        $filters = $this->reportFilters($startDate, $endDate, $categoryIds, $vendorIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(vendors.name, "Sem fornecedor") AS vendor_name,
                COALESCE(SUM(entries.amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN entries.status = "paid" THEN entries.amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" THEN entries.amount ELSE 0 END), 0) AS open_amount,
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS estimated_amount,
                COUNT(*) AS entries_count
             FROM entries
             LEFT JOIN vendors ON vendors.id = entries.vendor_id
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['sql'] . '
             GROUP BY COALESCE(vendors.name, "Sem fornecedor")
             ORDER BY total_amount DESC, vendor_name ASC'
        );
        $statement->execute($filters['params']);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function expensesByCategoryReport(string $startDate, string $endDate, array $categoryIds, array $vendorIds): array
    {
        $filters = $this->reportFilters($startDate, $endDate, $categoryIds, $vendorIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                COALESCE(categories.name, "Sem categoria") AS category_name,
                COALESCE(SUM(entries.amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN entries.status = "paid" THEN entries.amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" THEN entries.amount ELSE 0 END), 0) AS open_amount,
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS estimated_amount,
                COUNT(*) AS entries_count
             FROM entries
             LEFT JOIN categories ON categories.id = entries.category_id
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['sql'] . '
             GROUP BY COALESCE(categories.name, "Sem categoria")
             ORDER BY total_amount DESC, category_name ASC'
        );
        $statement->execute($filters['params']);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function expensesByDescriptionReport(string $startDate, string $endDate, array $categoryIds, array $vendorIds): array
    {
        $filters = $this->reportFilters($startDate, $endDate, $categoryIds, $vendorIds);
        $statement = $this->database->connection()->prepare(
            'SELECT
                entries.description,
                entries.modality,
                COALESCE(categories.name, "Sem categoria") AS category_name,
                COALESCE(SUM(entries.amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN entries.status = "paid" THEN entries.amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN entries.status <> "paid" THEN entries.amount ELSE 0 END), 0) AS open_amount,
                COALESCE(SUM(CASE WHEN COALESCE(entries.is_estimated, 0) = 1 THEN entries.amount ELSE 0 END), 0) AS estimated_amount,
                COUNT(*) AS entries_count,
                MAX(entries.installment_total) AS installment_total,
                MAX(entries.installment_current) AS installment_current
             FROM entries
             LEFT JOIN categories ON categories.id = entries.category_id
             WHERE entries.type = "expense"
               AND entries.entry_date BETWEEN :start_date AND :end_date
               ' . $filters['sql'] . '
             GROUP BY entries.description, entries.modality, COALESCE(categories.name, "Sem categoria")
             ORDER BY total_amount DESC, entries.description ASC'
        );
        $statement->execute($filters['params']);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compares expense modalities without relying on the exact spreadsheet label.
     * Installment metadata has precedence over free-text modality labels.
     */
    public function expensesByModality(string $startDate, string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT amount, status, modality, installment_current, installment_total, is_last_installment
             FROM entries
             WHERE type = "expense"
               AND entry_date BETWEEN :start_date AND :end_date'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $buckets = [
            'avista' => ['label' => 'À vista', 'total_amount' => 0.0, 'paid_amount' => 0.0, 'open_amount' => 0.0, 'entries_count' => 0],
            'parcelado' => ['label' => 'Parcelado', 'total_amount' => 0.0, 'paid_amount' => 0.0, 'open_amount' => 0.0, 'entries_count' => 0],
            'ultima_parcela' => ['label' => 'Última parcela', 'total_amount' => 0.0, 'paid_amount' => 0.0, 'open_amount' => 0.0, 'entries_count' => 0],
            'recorrente' => ['label' => 'Recorrente', 'total_amount' => 0.0, 'paid_amount' => 0.0, 'open_amount' => 0.0, 'entries_count' => 0],
            'sem_modalidade' => ['label' => 'Não identificada', 'total_amount' => 0.0, 'paid_amount' => 0.0, 'open_amount' => 0.0, 'entries_count' => 0],
        ];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = $this->modalityBucket($row);
            $amount = (float) ($row['amount'] ?? 0);
            $buckets[$key]['total_amount'] += $amount;
            $buckets[$key]['entries_count']++;

            if ((string) ($row['status'] ?? '') === 'paid') {
                $buckets[$key]['paid_amount'] += $amount;
            } else {
                $buckets[$key]['open_amount'] += $amount;
            }
        }

        return array_values(array_filter($buckets, static fn (array $row): bool => $row['entries_count'] > 0));
    }

    private function modalityBucket(array $row): string
    {
        $modality = mb_strtoupper(trim((string) ($row['modality'] ?? '')), 'UTF-8');
        $modality = strtr($modality, ['Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ç' => 'C']);
        $current = (int) ($row['installment_current'] ?? 0);
        $total = (int) ($row['installment_total'] ?? 0);

        if ((int) ($row['is_last_installment'] ?? 0) === 1 || str_contains($modality, 'ULTIMA PARC')) {
            return 'ultima_parcela';
        }

        if (str_contains($modality, 'RECORRENTE')) {
            return 'recorrente';
        }

        if ($total > 1 || $current > 0 || str_contains($modality, 'PARCEL')) {
            return 'parcelado';
        }

        if (str_contains($modality, 'VISTA')) {
            return 'avista';
        }

        return 'sem_modalidade';
    }


    public function paidVsReceivedByMonth(string $startDate, string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                DATE_FORMAT(entry_date, "%Y-%m") AS month_key,
                COALESCE(SUM(CASE WHEN type = "income" THEN amount ELSE 0 END), 0) AS income_amount,
                COALESCE(SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END), 0) AS expense_amount,
                COALESCE(SUM(CASE WHEN type = "expense" AND COALESCE(is_estimated, 0) = 1 THEN amount ELSE 0 END), 0) AS estimated_expense_amount,
                COALESCE(SUM(CASE WHEN type = "expense" AND COALESCE(is_estimated, 0) = 0 THEN amount ELSE 0 END), 0) AS confirmed_expense_amount,
                COALESCE(SUM(CASE WHEN type = "expense" AND status = "paid" THEN amount ELSE 0 END), 0) AS paid_expense_amount
             FROM entries
             WHERE entry_date BETWEEN :start_date AND :end_date
             GROUP BY DATE_FORMAT(entry_date, "%Y-%m")
             ORDER BY month_key ASC'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $income = (float) $row['income_amount'];
            $expense = (float) $row['expense_amount'];
            $rows[] = [
                'month_key' => $row['month_key'],
                'income_amount' => $income,
                'expense_amount' => $expense,
                'estimated_expense_amount' => (float) $row['estimated_expense_amount'],
                'confirmed_expense_amount' => (float) $row['confirmed_expense_amount'],
                'paid_expense_amount' => (float) $row['paid_expense_amount'],
                'balance_amount' => $income - $expense,
                'confirmed_balance_amount' => $income - (float) $row['confirmed_expense_amount'],
            ];
        }

        return $rows;
    }

    public function cashflowRows(string $startDate, string $endDate): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                entries.entry_date,
                entries.type,
                entries.description,
                entries.amount,
                entries.status,
                entries.is_estimated,
                COALESCE(categories.name, "Sem categoria") AS category_name,
                COALESCE(vendors.name, "Sem fornecedor") AS vendor_name
             FROM entries
             LEFT JOIN categories ON categories.id = entries.category_id
             LEFT JOIN vendors ON vendors.id = entries.vendor_id
             WHERE entries.entry_date BETWEEN :start_date AND :end_date
             ORDER BY entries.entry_date ASC, entries.type DESC, entries.description ASC, entries.id ASC'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

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

    private function reportFilters(string $startDate, string $endDate, array $categoryIds, array $vendorIds): array
    {
        $filters = $this->filters($startDate, $endDate, $categoryIds);
        $params = $filters['params'];
        $sql = $filters['categorySql'];

        if ($vendorIds !== []) {
            $placeholders = [];

            foreach (array_values($vendorIds) as $index => $vendorId) {
                $key = 'vendor_id_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $vendorId;
            }

            $sql .= ' AND entries.vendor_id IN (' . implode(', ', $placeholders) . ')';
        }

        return [
            'sql' => $sql,
            'params' => $params,
        ];
    }
}
