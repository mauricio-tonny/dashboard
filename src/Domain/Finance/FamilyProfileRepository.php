<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Core\Database;
use PDO;

final class FamilyProfileRepository
{
    public function __construct(private Database $database)
    {
    }

    public function settings(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT * FROM family_profile_settings WHERE id = 1 LIMIT 1'
        );
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? [
            'region' => 'brasil',
            'residence_area' => 'urban',
            'notes' => null,
        ] : $row;
    }

    public function saveSettings(string $region, string $residenceArea, ?string $notes, ?int $userId): void
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO family_profile_settings (id, region, residence_area, notes, updated_by_user_id)
             VALUES (1, :region, :residence_area, :notes, :updated_by_user_id)
             ON DUPLICATE KEY UPDATE
                region = VALUES(region),
                residence_area = VALUES(residence_area),
                notes = VALUES(notes),
                updated_by_user_id = VALUES(updated_by_user_id)'
        );
        $statement->execute([
            'region' => $region,
            'residence_area' => $residenceArea,
            'notes' => $notes,
            'updated_by_user_id' => $userId,
        ]);
    }

    public function members(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT * FROM family_members ORDER BY is_active DESC, birth_date ASC, name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pets(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT * FROM family_pets ORDER BY is_active DESC, name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveMember(?int $id, array $data, ?int $userId): int
    {
        if ($id !== null) {
            $statement = $this->database->connection()->prepare(
                'UPDATE family_members
                 SET name = :name,
                     sex = :sex,
                     birth_date = :birth_date,
                     role = :role,
                     income_weight = :income_weight
                 WHERE id = :id'
            );
            $statement->execute([...$data, 'id' => $id]);

            return $id;
        }

        $statement = $this->database->connection()->prepare(
            'INSERT INTO family_members (name, sex, birth_date, role, income_weight, created_by_user_id)
             VALUES (:name, :sex, :birth_date, :role, :income_weight, :created_by_user_id)'
        );
        $statement->execute([...$data, 'created_by_user_id' => $userId]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function savePet(?int $id, array $data, ?int $userId): int
    {
        if ($id !== null) {
            $statement = $this->database->connection()->prepare(
                'UPDATE family_pets
                 SET name = :name,
                     type = :type,
                     birth_date = :birth_date
                 WHERE id = :id'
            );
            $statement->execute([...$data, 'id' => $id]);

            return $id;
        }

        $statement = $this->database->connection()->prepare(
            'INSERT INTO family_pets (name, type, birth_date, created_by_user_id)
             VALUES (:name, :type, :birth_date, :created_by_user_id)'
        );
        $statement->execute([...$data, 'created_by_user_id' => $userId]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function setMemberActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE family_members SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'is_active' => $active ? 1 : 0]);
    }

    public function setPetActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE family_pets SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'is_active' => $active ? 1 : 0]);
    }

    public function summary(): array
    {
        $members = array_values(array_filter($this->members(), static fn (array $row): bool => (int) $row['is_active'] === 1));
        $pets = array_values(array_filter($this->pets(), static fn (array $row): bool => (int) $row['is_active'] === 1));
        $adults = 0;
        $children = 0;
        $incomeWeight = 0.0;

        foreach ($members as $member) {
            $age = $this->age($member['birth_date'] ?? null);

            if ($age !== null && $age < 18) {
                $children++;
            } else {
                $adults++;
            }

            $incomeWeight += (float) ($member['income_weight'] ?? 0);
        }

        return [
            'settings' => $this->settings(),
            'members' => $members,
            'pets' => $pets,
            'member_count' => count($members),
            'adult_count' => $adults,
            'child_count' => $children,
            'pet_count' => count($pets),
            'income_weight' => $incomeWeight,
        ];
    }

    private function age(mixed $birthDate): ?int
    {
        if (!is_string($birthDate) || $birthDate === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $birthDate);

        return $date instanceof \DateTimeImmutable ? (int) $date->diff(new \DateTimeImmutable('today'))->format('%y') : null;
    }
}
