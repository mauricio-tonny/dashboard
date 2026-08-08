<?php

declare(strict_types=1);

namespace App\Domain\Shopping;

use App\Core\Database;
use PDO;

final class ShoppingRepository
{
    private const SIMPLE_TABLES = [
        'market_sections' => 'shopping_market_sections',
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

    public function marketSummaryForMonth(string $referenceMonth): array
    {
        $month = $this->normalizeMonth($referenceMonth);
        $statement = $this->database->connection()->prepare(
            'SELECT lists.*,
                    COUNT(items.id) AS item_count,
                    COALESCE(SUM(items.is_checked), 0) AS checked_count
             FROM shopping_market_lists lists
             LEFT JOIN shopping_market_items items ON items.list_id = lists.id
             WHERE lists.reference_month = :reference_month
             GROUP BY lists.id
             LIMIT 1'
        );
        $statement->execute(['reference_month' => $month]);
        $summary = $statement->fetch(PDO::FETCH_ASSOC);

        return $summary === false ? [
            'reference_month' => $month,
            'item_count' => 0,
            'checked_count' => 0,
            'total_amount' => null,
        ] : $summary;
    }

    public function pendingHomeItems(int $limit = 10): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT items.*, rooms.name AS room_name
             FROM shopping_wish_items items
             LEFT JOIN shopping_rooms rooms ON rooms.id = items.room_id
             WHERE items.type = "home"
               AND items.is_purchased = 0
             ORDER BY items.priority DESC, items.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function homePrioritySummary(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT COUNT(*) AS high_priority_count,
                    COALESCE(SUM(estimated_amount), 0) AS estimated_total
             FROM shopping_wish_items
             WHERE type = "home"
               AND is_purchased = 0
               AND priority >= 8'
        );
        $summary = $statement->fetch(PDO::FETCH_ASSOC);

        return $summary === false ? ['high_priority_count' => 0, 'estimated_total' => 0.0] : $summary;
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
            'SELECT items.*,
                    COALESCE(sections.name, items.section) AS section_name
             FROM shopping_market_items items
             LEFT JOIN shopping_market_sections sections ON sections.id = items.section_id
             WHERE items.list_id = :list_id
             ORDER BY items.is_checked ASC, section_name ASC, items.name ASC'
        );
        $statement->execute(['list_id' => $listId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marketItemsSubtotal(int $listId): float
    {
        $statement = $this->database->connection()->prepare(
            'SELECT COALESCE(SUM(subtotal_amount), 0)
             FROM shopping_market_items
             WHERE list_id = :list_id'
        );
        $statement->execute(['list_id' => $listId]);

        return (float) $statement->fetchColumn();
    }

    public function marketInvoices(int $listId): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT *
             FROM shopping_market_invoices
             WHERE list_id = :list_id
             ORDER BY created_at DESC'
        );
        $statement->execute(['list_id' => $listId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addMarketInvoice(int $listId, string $originalName, string $storedName, string $mimeType, int $fileSize, ?int $userId): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO shopping_market_invoices
                (list_id, source_type, original_name, stored_name, mime_type, file_size, uploaded_by_user_id)
             VALUES
                (:list_id, "file", :original_name, :stored_name, :mime_type, :file_size, :uploaded_by_user_id)'
        );
        $statement->execute([
            'list_id' => $listId,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by_user_id' => $userId,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function addMarketInvoiceAccessKey(int $listId, array $data, ?int $userId): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO shopping_market_invoices
                (list_id, source_type, original_name, stored_name, mime_type, file_size, access_key, uf_code, issued_year_month,
                 issuer_document, document_model, document_series, document_number, issue_type, numeric_code, check_digit,
                 public_url, status, uploaded_by_user_id)
             VALUES
                (:list_id, "access_key", :original_name, "", "application/x-nfe-access-key", 0, :access_key, :uf_code, :issued_year_month,
                 :issuer_document, :document_model, :document_series, :document_number, :issue_type, :numeric_code, :check_digit,
                 :public_url, "pending_public_consultation", :uploaded_by_user_id)'
        );
        $statement->execute([
            'list_id' => $listId,
            'original_name' => 'Chave ' . $data['access_key'],
            'access_key' => $data['access_key'],
            'uf_code' => $data['uf_code'],
            'issued_year_month' => $data['issued_year_month'],
            'issuer_document' => $data['issuer_document'],
            'document_model' => $data['document_model'],
            'document_series' => $data['document_series'],
            'document_number' => $data['document_number'],
            'issue_type' => $data['issue_type'],
            'numeric_code' => $data['numeric_code'],
            'check_digit' => $data['check_digit'],
            'public_url' => $data['public_url'],
            'uploaded_by_user_id' => $userId,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function updateMarketInvoiceMetadata(int $id, array $data): void
    {
        $accessKey = isset($data['access_key'])
            ? preg_replace('/\D+/', '', (string) $data['access_key'])
            : null;
        $purchaseDate = $this->normalizeDateTime($data['issued_at'] ?? null);

        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_invoices
             SET access_key = COALESCE(NULLIF(:access_key, ""), access_key),
                 purchase_date = COALESCE(:purchase_date, purchase_date),
                 status = :status
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'access_key' => $accessKey,
            'purchase_date' => $purchaseDate,
            'status' => ($accessKey !== null && $accessKey !== '') || $purchaseDate !== null ? 'imported_with_metadata' : 'imported',
        ]);
    }

    public function clearMarketInvoiceFile(int $id): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_invoices
             SET stored_name = "",
                 file_size = 0,
                 status = "imported_metadata_only"
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function addMarketItem(array $data, ?int $userId): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO shopping_market_items
                (list_id, section_id, name, section, quantity, unit_amount, amount, subtotal_amount, created_by_user_id)
             VALUES
                (:list_id, :section_id, :name, :section, :quantity, :unit_amount, :amount, :subtotal_amount, :created_by_user_id)'
        );
        $statement->execute([
            'list_id' => $data['list_id'],
            'section_id' => $data['section_id'],
            'name' => $data['name'],
            'section' => $data['section'],
            'quantity' => $data['quantity'],
            'unit_amount' => $data['unit_amount'],
            'amount' => $data['amount'],
            'subtotal_amount' => $data['subtotal_amount'],
            'created_by_user_id' => $userId,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function updateMarketItem(int $id, array $data): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_items
             SET name = :name,
                 section_id = :section_id,
                 section = :section,
                 quantity = :quantity,
                 unit_amount = :unit_amount,
                 amount = :amount,
                 subtotal_amount = :subtotal_amount,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'section_id' => $data['section_id'],
            'section' => $data['section'],
            'quantity' => $data['quantity'],
            'unit_amount' => $data['unit_amount'],
            'amount' => $data['amount'],
            'subtotal_amount' => $data['subtotal_amount'],
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
        $discountAmount = $this->discountAmount($id, $totalAmount);
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_lists
             SET total_amount = :total_amount,
                 discount_amount = :discount_amount,
                 finished_at = NOW(),
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
        ]);
    }

    public function updateMarketListTotal(int $id, ?float $totalAmount): void
    {
        $discountAmount = $this->discountAmount($id, $totalAmount);
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_lists
             SET total_amount = :total_amount,
                 discount_amount = :discount_amount,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
        ]);
    }

    public function reopenMarketList(int $id): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE shopping_market_lists
             SET finished_at = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function deleteMarketList(int $id): array
    {
        $connection = $this->database->connection();
        $list = $this->marketList($id);

        if ($list === null) {
            return ['items' => 0, 'invoices' => 0, 'files' => []];
        }

        $filesStatement = $connection->prepare(
            'SELECT stored_name
             FROM shopping_market_invoices
             WHERE list_id = :list_id
               AND source_type = "file"
               AND stored_name <> ""'
        );
        $filesStatement->execute(['list_id' => $id]);
        $files = $filesStatement->fetchAll(PDO::FETCH_COLUMN);

        $itemCountStatement = $connection->prepare('SELECT COUNT(*) FROM shopping_market_items WHERE list_id = :list_id');
        $itemCountStatement->execute(['list_id' => $id]);
        $itemCount = (int) $itemCountStatement->fetchColumn();

        $invoiceCountStatement = $connection->prepare('SELECT COUNT(*) FROM shopping_market_invoices WHERE list_id = :list_id');
        $invoiceCountStatement->execute(['list_id' => $id]);
        $invoiceCount = (int) $invoiceCountStatement->fetchColumn();

        $connection->beginTransaction();

        try {
            $deleteInvoices = $connection->prepare('DELETE FROM shopping_market_invoices WHERE list_id = :list_id');
            $deleteInvoices->execute(['list_id' => $id]);

            $deleteItems = $connection->prepare('DELETE FROM shopping_market_items WHERE list_id = :list_id');
            $deleteItems->execute(['list_id' => $id]);

            $deleteList = $connection->prepare('DELETE FROM shopping_market_lists WHERE id = :id');
            $deleteList->execute(['id' => $id]);

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        return ['items' => $itemCount, 'invoices' => $invoiceCount, 'files' => $files];
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

    public function simpleOption(string $kind, int $id, bool $activeOnly = false): ?array
    {
        $table = self::SIMPLE_TABLES[$kind];
        $whereActive = $activeOnly ? 'AND is_active = 1' : '';
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM {$table} WHERE id = :id {$whereActive} LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $option = $statement->fetch(PDO::FETCH_ASSOC);

        return $option === false ? null : $option;
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

    private function discountAmount(int $listId, ?float $totalAmount): ?float
    {
        if ($totalAmount === null) {
            return null;
        }

        return max(0.0, round($this->marketItemsSubtotal($listId) - $totalAmount, 2));
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'd/m/Y H:i:s', 'd/m/Y H:i'];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);

            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
