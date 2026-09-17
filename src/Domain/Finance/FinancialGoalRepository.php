<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Core\Database;
use PDO;

final class FinancialGoalRepository
{
    public function __construct(private Database $database)
    {
    }

    public function goals(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT * FROM financial_goals ORDER BY is_active DESC, target_date ASC, priority_weight DESC, name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeGoals(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT * FROM financial_goals WHERE is_active = 1 ORDER BY target_date ASC, priority_weight DESC, name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(?int $id, array $data, ?int $userId): int
    {
        if ($id !== null) {
            $statement = $this->database->connection()->prepare(
                'UPDATE financial_goals
                 SET name = :name,
                     goal_type = :goal_type,
                     start_amount = :start_amount,
                     current_amount = :current_amount,
                     target_amount = :target_amount,
                     start_date = :start_date,
                     target_date = :target_date,
                     priority_weight = :priority_weight,
                     notes = :notes,
                     updated_by_user_id = :updated_by_user_id
                 WHERE id = :id'
            );
            $statement->execute([...$data, 'updated_by_user_id' => $userId, 'id' => $id]);

            return $id;
        }

        $statement = $this->database->connection()->prepare(
            'INSERT INTO financial_goals
                (name, goal_type, start_amount, current_amount, target_amount, start_date, target_date, priority_weight, notes, created_by_user_id, updated_by_user_id)
             VALUES
                (:name, :goal_type, :start_amount, :current_amount, :target_amount, :start_date, :target_date, :priority_weight, :notes, :created_by_user_id, :updated_by_user_id)'
        );
        $statement->execute([
            ...$data,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE financial_goals SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'is_active' => $active ? 1 : 0]);
    }

    public function emergencyReserveAmount(): float
    {
        $statement = $this->database->connection()->query(
            "SELECT current_amount
             FROM financial_goals
             WHERE is_active = 1 AND goal_type = 'emergency_reserve'
             ORDER BY target_date ASC, id ASC
             LIMIT 1"
        );
        $amount = $statement->fetchColumn();

        return $amount === false ? 0.0 : (float) $amount;
    }
}
