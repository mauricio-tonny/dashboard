<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Core\Database;
use PDO;

final class KarinaStatementRepository
{
    public function __construct(private Database $database)
    {
    }

    public function summary(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT
                COALESCE(SUM(CASE WHEN movement_type = "credit" THEN amount ELSE 0 END), 0) AS credit_amount,
                COALESCE(SUM(CASE WHEN movement_type = "debit" THEN amount ELSE 0 END), 0) AS debit_amount,
                COUNT(*) AS entries_count
             FROM karina_statement_entries'
        );
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $credit = (float) ($row['credit_amount'] ?? 0);
        $debit = (float) ($row['debit_amount'] ?? 0);

        return [
            'credit_amount' => $credit,
            'debit_amount' => $debit,
            'balance_amount' => $credit - $debit,
            'entries_count' => (int) ($row['entries_count'] ?? 0),
        ];
    }

    public function rows(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT
                karina_statement_entries.transaction_date,
                karina_statement_entries.movement_type,
                karina_statement_entries.description,
                karina_statement_entries.paid_account,
                karina_statement_entries.amount,
                COALESCE(categories.name, karina_statement_entries.category_name, "Sem categoria") AS category_name
             FROM karina_statement_entries
             LEFT JOIN categories ON categories.id = karina_statement_entries.category_id
             ORDER BY karina_statement_entries.transaction_date DESC, karina_statement_entries.id DESC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function byCategory(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT
                COALESCE(categories.name, karina_statement_entries.category_name, "Sem categoria") AS category_name,
                COALESCE(SUM(CASE WHEN movement_type = "credit" THEN amount ELSE 0 END), 0) AS credit_amount,
                COALESCE(SUM(CASE WHEN movement_type = "debit" THEN amount ELSE 0 END), 0) AS debit_amount,
                COUNT(*) AS entries_count
             FROM karina_statement_entries
             LEFT JOIN categories ON categories.id = karina_statement_entries.category_id
             GROUP BY COALESCE(categories.name, karina_statement_entries.category_name, "Sem categoria")
             ORDER BY debit_amount DESC, credit_amount DESC, category_name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
