<?php

declare(strict_types=1);

namespace App\Domain\Shopping;

use App\Core\Database;
use PDO;

final class ShoppingRepository
{
    private const SIMPLE_TABLES = [
        'rooms' => 'shopping_rooms',
        'people' => 'shopping_people',
        'vehicle_areas' => 'shopping_vehicle_areas',
    ];

    public function __construct(private Database $database)
    {
    }

    public function nextMonth(): string
    {
        return (new \DateTimeImmutable('first day of next month'))->format('Y-m-01');
    }

    public function marketLists(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT lists.*,
                    COUNT(items.id) AS item_count,
                    COALESCE(SUM(items.is_checked), 0) AS checked_count
             FROM shopping_market_lists lists
             LEFT JOIN shopping_market_items items ON items.list_id = lists.id
             GROUP BY lists.id
             ORDER BY lists.reference_month DESC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOrCreateMarketList(string $referenceMonth, ?int $userId): int
    {
        $month = $this->normalizeMonth($referenceMonth);
        $statement = $this->database->connection()->prepare(
            'INSERT IGNORE INTO shopping_market_lists (reference_month, created_by_user_id)
             VALUES (:reference_month, :created_by_user_id)'
        );
        $statement->execute([
            'reference_month' => $month,
            'created_by_user_id' => $userId,
        ]);

        $find = $this->database->connection()->prepare(
            'SELECT id FROM shopping_market_lists WHERE reference_month = :reference_month LIMIT 1'
        );
        $find->execute(['reference_month' => $month]);

        return (int) $find->fetchColumn();
    }

    public function marketList(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT * FROM shopping_market_lists WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $list = $statement->fetch(PDO::FETCH_ASSOC);

        return $list === false ? null : $list;
    }

    public function marketItems(int $listId): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT *
             FROM shopping_market_items
             WHERE list_id = :list_id
             ORDER BY is_checked ASC, section ASC, name ASC'
        );
        $statement->execute(['list_id' => $listId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addMarketItem(int $listId, string $name, string $section, ?int $userId): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO shopping_market_items (list_id, name, section, created_by_user_id)
             VALUES (:list_id, :name, :section, :created_by_user_id)'
        );
        $statement->execute([
            'list_id' => $listId,
            'name' => $name,
            'section' => $section,
            'created_by_user_id' => $userId,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function updateMarketItem(int $id, string $name, string $section): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_items
             SET name = :name,
                 section = :section,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'section' => $section,
        ]);
    }

    public function toggleMarketItem(int $id, bool $checked): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_items
             SET is_checked = :is_checked,
                 checked_at = ' . ($checked ? 'NOW()' : 'NULL') . ',
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_checked' => $checked ? 1 : 0,
        ]);
    }

    public function deleteMarketItem(int $id): void
    {
        $statement = $this->database->connection()->prepare('DELETE FROM shopping_market_items WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function finishMarketList(int $id, ?float $totalAmount): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_lists
             SET total_amount = :total_amount,
                 finished_at = NOW(),
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'total_amount' => $totalAmount,
        ]);
    }

    public function wishItems(string $type): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT items.*,
                    rooms.name AS room_name,
                    people.name AS person_name,
                    vehicles.name AS vehicle_name,
                    vehicle_areas.name AS vehicle_area_name
             FROM shopping_wish_items items
             LEFT JOIN shopping_rooms rooms ON rooms.id = items.room_id
             LEFT JOIN shopping_people people ON people.id = items.person_id
             LEFT JOIN shopping_vehicles vehicles ON vehicles.id = items.vehicle_id
             LEFT JOIN shopping_vehicle_areas vehicle_areas ON vehicle_areas.id = items.vehicle_area_id
             WHERE items.type = :type
             ORDER BY items.is_purchased ASC, items.priority DESC, items.created_at DESC'
        );
        $statement->execute(['type' => $type]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addWishItem(array $data, ?int $userId): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO shopping_wish_items
                (type, name, room_id, person_id, vehicle_id, vehicle_area_id, estimated_amount, priority, created_by_user_id)
             VALUES
                (:type, :name, :room_id, :person_id, :vehicle_id, :vehicle_area_id, :estimated_amount, :priority, :created_by_user_id)'
        );
        $statement->execute([
            'type' => $data['type'],
            'name' => $data['name'],
            'room_id' => $data['room_id'] ?? null,
            'person_id' => $data['person_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'vehicle_area_id' => $data['vehicle_area_id'] ?? null,
            'estimated_amount' => $data['estimated_amount'] ?? null,
            'priority' => $data['priority'] ?? null,
            'created_by_user_id' => $userId,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function updateWishItem(int $id, array $data): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_wish_items
             SET name = :name,
                 room_id = :room_id,
                 person_id = :person_id,
                 vehicle_id = :vehicle_id,
                 vehicle_area_id = :vehicle_area_id,
                 estimated_amount = :estimated_amount,
                 priority = :priority,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'room_id' => $data['room_id'] ?? null,
            'person_id' => $data['person_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'vehicle_area_id' => $data['vehicle_area_id'] ?? null,
            'estimated_amount' => $data['estimated_amount'] ?? null,
            'priority' => $data['priority'] ?? null,
        ]);
    }

    public function toggleWishItem(int $id, bool $purchased): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_wish_items
             SET is_purchased = :is_purchased,
                 purchased_at = ' . ($purchased ? 'NOW()' : 'NULL') . ',
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_purchased' => $purchased ? 1 : 0,
        ]);
    }

    public function deleteWishItem(int $id): void
    {
        $statement = $this->database->connection()->prepare('DELETE FROM shopping_wish_items WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function simpleOptions(string $kind, bool $activeOnly = false): array
    {
        $table = self::SIMPLE_TABLES[$kind];
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $statement = $this->database->connection()->query(
            "SELECT * FROM {$table} {$where} ORDER BY name ASC"
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveSimpleOption(string $kind, ?int $id, string $name): int
    {
        $table = self::SIMPLE_TABLES[$kind];

        if ($id === null) {
            $statement = $this->database->connection()->prepare(
                "INSERT INTO {$table} (name, is_active) VALUES (:name, 1)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = 1"
            );
            $statement->execute(['name' => $name]);

            return (int) $this->database->connection()->lastInsertId();
        }

        $statement = $this->database->connection()->prepare(
            "UPDATE {$table}
             SET name = :name,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
        ]);

        return $id;
    }

    public function setSimpleOptionActive(string $kind, int $id, bool $active): void
    {
        $table = self::SIMPLE_TABLES[$kind];
        $statement = $this->database->connection()->prepare(
            "UPDATE {$table}
             SET is_active = :is_active,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function vehicles(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $statement = $this->database->connection()->query(
            "SELECT * FROM shopping_vehicles {$where} ORDER BY name ASC"
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveVehicle(?int $id, array $data): int
    {
        if ($id === null) {
            $statement = $this->database->connection()->prepare(
                'INSERT INTO shopping_vehicles
                    (name, model, brand, model_year, manufacture_year, renavam, plate, is_active)
                 VALUES
                    (:name, :model, :brand, :model_year, :manufacture_year, :renavam, :plate, 1)'
            );
            $statement->execute($data);

            return (int) $this->database->connection()->lastInsertId();
        }

        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_vehicles
             SET name = :name,
                 model = :model,
                 brand = :brand,
                 model_year = :model_year,
                 manufacture_year = :manufacture_year,
                 renavam = :renavam,
                 plate = :plate,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute(['id' => $id, ...$data]);

        return $id;
    }

    public function setVehicleActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_vehicles
             SET is_active = :is_active,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    private function normalizeMonth(string $month): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $month)
            ?: \DateTimeImmutable::createFromFormat('Y-m', $month);

        if (!$date instanceof \DateTimeImmutable) {
            return $this->nextMonth();
        }

        return $date->modify('first day of this month')->format('Y-m-01');
    }
}
