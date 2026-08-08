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

    public function all(?string $type = null): array
    {
        $sql = 'SELECT * FROM contacts';
        $params = [];

        if ($type === 'vendor') {
            $sql .= ' WHERE is_vendor = 1';
        }

        if ($type === 'client') {
            $sql .= ' WHERE is_client = 1';
        }

        $sql .= ' ORDER BY is_active DESC, first_name ASC, last_name ASC';
        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(?int $id, array $data, ?int $userId): int
    {
        if ($id === null) {
            $statement = $this->database->connection()->prepare(
                'INSERT INTO contacts
                    (type, is_vendor, is_client, first_name, last_name, document, phone, email, address, city, state, created_by_user_id)
                 VALUES
                    (:type, :is_vendor, :is_client, :first_name, :last_name, :document, :phone, :email, :address, :city, :state, :created_by_user_id)'
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
                 address = :address,
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
}
