<?php

declare(strict_types=1);

namespace App\Domain\Contacts;

use App\Core\Database;
use PDO;

final class ContactRepository
{
    public function __construct(private Database $database)
    {
    }

    public function all(?string $type = null, string $search = ''): array
    {
        $this->syncImportedVendors();

        $sql = 'SELECT contacts.*,
                    vendor_stats.entries_count,
                    vendor_stats.total_amount,
                    vendor_stats.last_entry_date
                FROM contacts
                LEFT JOIN (
                    SELECT vendor_id,
                        COUNT(*) AS entries_count,
                        COALESCE(SUM(amount), 0) AS total_amount,
                        MAX(entry_date) AS last_entry_date
                    FROM entries
                    WHERE type = "expense"
                      AND vendor_id IS NOT NULL
                    GROUP BY vendor_id
                ) vendor_stats ON vendor_stats.vendor_id = contacts.vendor_id';
        $params = [];
        $where = [];

        if ($type === 'vendor') {
            $where[] = 'contacts.is_vendor = 1';
        }

        if ($type === 'client') {
            $where[] = 'contacts.is_client = 1';
        }

        if ($search !== '') {
            $where[] = '(contacts.first_name LIKE :search
                OR contacts.last_name LIKE :search
                OR contacts.document LIKE :search
                OR contacts.phone LIKE :search
                OR contacts.email LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY is_active DESC, first_name ASC, last_name ASC';
        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function summary(): array
    {
        $this->syncImportedVendors();

        $row = $this->database->connection()->query(
            'SELECT
                COUNT(*) AS total_contacts,
                COALESCE(SUM(is_vendor), 0) AS total_vendors,
                COALESCE(SUM(is_client), 0) AS total_clients,
                COALESCE(SUM(CASE WHEN vendor_id IS NOT NULL THEN 1 ELSE 0 END), 0) AS imported_vendors
             FROM contacts'
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_contacts' => (int) ($row['total_contacts'] ?? 0),
            'total_vendors' => (int) ($row['total_vendors'] ?? 0),
            'total_clients' => (int) ($row['total_clients'] ?? 0),
            'imported_vendors' => (int) ($row['imported_vendors'] ?? 0),
        ];
    }

    public function save(?int $id, array $data, ?int $userId): int
    {
        if ($id === null) {
            $statement = $this->database->connection()->prepare(
                'INSERT INTO contacts
                    (type, is_vendor, is_client, first_name, last_name, document, phone, email, postal_code, address, address_number, neighborhood, complement, city, state, created_by_user_id)
                 VALUES
                    (:type, :is_vendor, :is_client, :first_name, :last_name, :document, :phone, :email, :postal_code, :address, :address_number, :neighborhood, :complement, :city, :state, :created_by_user_id)'
            );
            $statement->execute([...$data, 'created_by_user_id' => $userId]);

            return (int) $this->database->connection()->lastInsertId();
        }

        $statement = $this->database->connection()->prepare(
            'UPDATE contacts
             SET type = :type,
                 is_vendor = :is_vendor,
                 is_client = :is_client,
                 first_name = :first_name,
                 last_name = :last_name,
                 document = :document,
                 phone = :phone,
                 email = :email,
                 postal_code = :postal_code,
                 address = :address,
                 address_number = :address_number,
                 neighborhood = :neighborhood,
                 complement = :complement,
                 city = :city,
                 state = :state,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute(['id' => $id, ...$data]);

        return $id;
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE contacts
             SET is_active = :is_active,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function syncImportedVendors(): void
    {
        $connection = $this->database->connection();

        $connection->exec(
            'INSERT INTO contacts
                (vendor_id, type, is_vendor, is_client, first_name, city, state)
             SELECT vendors.id, "vendor", 1, 0, vendors.name, "", "PR"
             FROM vendors
             LEFT JOIN contacts linked_contacts ON linked_contacts.vendor_id = vendors.id
             LEFT JOIN contacts named_contacts
                ON named_contacts.vendor_id IS NULL
               AND named_contacts.is_vendor = 1
               AND named_contacts.first_name = vendors.name
             WHERE linked_contacts.id IS NULL
               AND named_contacts.id IS NULL'
        );

        $connection->exec(
            'UPDATE contacts
             INNER JOIN vendors ON vendors.name = contacts.first_name
             LEFT JOIN contacts linked_contacts ON linked_contacts.vendor_id = vendors.id
             SET contacts.vendor_id = vendors.id,
                 contacts.is_vendor = 1,
                 contacts.updated_at = CURRENT_TIMESTAMP
             WHERE contacts.vendor_id IS NULL
               AND contacts.is_vendor = 1
               AND linked_contacts.id IS NULL'
        );
    }
}
