<?php

declare(strict_types=1);

use App\Domain\Auth\Role;
use App\Domain\Auth\RolePermissionMap;
use App\Support\Env;

require_once dirname(__DIR__) . '/src/Support/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

Env::load(dirname(__DIR__) . '/.env');

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

if ($database === '' || $username === '') {
    fwrite(STDERR, "DB_DATABASE e DB_USERNAME precisam estar definidos no .env.\n");
    exit(1);
}

$schemaFile = dirname(__DIR__) . '/database/schema.sql';
$schema = file_get_contents($schemaFile);

if ($schema === false) {
    fwrite(STDERR, "Nao foi possivel ler database/schema.sql.\n");
    exit(1);
}

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        $pdo->exec($statement);
    }

    ensureIndex($pdo, 'audit_logs', 'idx_audit_logs_created_at', 'CREATE INDEX idx_audit_logs_created_at ON audit_logs (created_at)');
    ensureIndex($pdo, 'audit_logs', 'idx_audit_logs_action', 'CREATE INDEX idx_audit_logs_action ON audit_logs (action)');
    ensureColumn($pdo, 'contacts', 'is_vendor', 'ALTER TABLE contacts ADD COLUMN is_vendor TINYINT(1) NOT NULL DEFAULT 0 AFTER type');
    ensureColumn($pdo, 'contacts', 'is_client', 'ALTER TABLE contacts ADD COLUMN is_client TINYINT(1) NOT NULL DEFAULT 0 AFTER is_vendor');
    ensureColumn($pdo, 'shopping_market_invoices', 'source_type', "ALTER TABLE shopping_market_invoices ADD COLUMN source_type ENUM('file', 'access_key') NOT NULL DEFAULT 'file' AFTER list_id");
    ensureColumn($pdo, 'shopping_market_invoices', 'access_key', 'ALTER TABLE shopping_market_invoices ADD COLUMN access_key CHAR(44) NULL AFTER file_size');
    ensureColumn($pdo, 'shopping_market_invoices', 'uf_code', 'ALTER TABLE shopping_market_invoices ADD COLUMN uf_code CHAR(2) NULL AFTER access_key');
    ensureColumn($pdo, 'shopping_market_invoices', 'issued_year_month', 'ALTER TABLE shopping_market_invoices ADD COLUMN issued_year_month CHAR(4) NULL AFTER uf_code');
    ensureColumn($pdo, 'shopping_market_invoices', 'issuer_document', 'ALTER TABLE shopping_market_invoices ADD COLUMN issuer_document CHAR(14) NULL AFTER issued_year_month');
    ensureColumn($pdo, 'shopping_market_invoices', 'document_model', 'ALTER TABLE shopping_market_invoices ADD COLUMN document_model CHAR(2) NULL AFTER issuer_document');
    ensureColumn($pdo, 'shopping_market_invoices', 'document_series', 'ALTER TABLE shopping_market_invoices ADD COLUMN document_series CHAR(3) NULL AFTER document_model');
    ensureColumn($pdo, 'shopping_market_invoices', 'document_number', 'ALTER TABLE shopping_market_invoices ADD COLUMN document_number CHAR(9) NULL AFTER document_series');
    ensureColumn($pdo, 'shopping_market_invoices', 'issue_type', 'ALTER TABLE shopping_market_invoices ADD COLUMN issue_type CHAR(1) NULL AFTER document_number');
    ensureColumn($pdo, 'shopping_market_invoices', 'numeric_code', 'ALTER TABLE shopping_market_invoices ADD COLUMN numeric_code CHAR(8) NULL AFTER issue_type');
    ensureColumn($pdo, 'shopping_market_invoices', 'check_digit', 'ALTER TABLE shopping_market_invoices ADD COLUMN check_digit CHAR(1) NULL AFTER numeric_code');
    ensureColumn($pdo, 'shopping_market_invoices', 'purchase_date', 'ALTER TABLE shopping_market_invoices ADD COLUMN purchase_date DATETIME NULL AFTER check_digit');
    ensureColumn($pdo, 'shopping_market_invoices', 'public_url', 'ALTER TABLE shopping_market_invoices ADD COLUMN public_url VARCHAR(500) NULL AFTER check_digit');
    ensureColumn($pdo, 'shopping_market_invoices', 'status', "ALTER TABLE shopping_market_invoices ADD COLUMN status VARCHAR(40) NOT NULL DEFAULT 'stored' AFTER public_url");
    ensureIndex($pdo, 'shopping_market_invoices', 'idx_market_invoices_access_key', 'CREATE INDEX idx_market_invoices_access_key ON shopping_market_invoices (access_key)');
    ensureColumn($pdo, 'shopping_market_lists', 'discount_amount', 'ALTER TABLE shopping_market_lists ADD COLUMN discount_amount DECIMAL(14,2) NULL AFTER total_amount');
    ensureColumn($pdo, 'shopping_market_lists', 'purchase_date', 'ALTER TABLE shopping_market_lists ADD COLUMN purchase_date DATETIME NULL AFTER discount_amount');
    ensureColumn($pdo, 'shopping_market_items', 'section_id', 'ALTER TABLE shopping_market_items ADD COLUMN section_id BIGINT UNSIGNED NULL AFTER list_id');
    ensureColumn($pdo, 'shopping_market_items', 'quantity', 'ALTER TABLE shopping_market_items ADD COLUMN quantity DECIMAL(10,3) NOT NULL DEFAULT 1.000 AFTER section');
    ensureColumn($pdo, 'shopping_market_items', 'unit_amount', 'ALTER TABLE shopping_market_items ADD COLUMN unit_amount DECIMAL(14,2) NULL AFTER quantity');
    ensureColumn($pdo, 'shopping_market_items', 'amount', 'ALTER TABLE shopping_market_items ADD COLUMN amount DECIMAL(14,2) NULL AFTER unit_amount');
    ensureColumn($pdo, 'shopping_market_items', 'subtotal_amount', 'ALTER TABLE shopping_market_items ADD COLUMN subtotal_amount DECIMAL(14,2) NULL AFTER amount');
    ensureIndex($pdo, 'shopping_market_items', 'idx_market_items_section', 'CREATE INDEX idx_market_items_section ON shopping_market_items (section_id)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS discord_notification_settings (
        id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        is_enabled TINYINT(1) NOT NULL DEFAULT 0,
        webhook_url VARCHAR(500) NULL,
        notify_market_list_created TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');
    $pdo->exec('INSERT IGNORE INTO discord_notification_settings (id) VALUES (1)');
    $pdo->exec("UPDATE contacts SET is_vendor = 1 WHERE type = 'vendor' AND is_vendor = 0 AND is_client = 0");
    $pdo->exec("UPDATE contacts SET is_client = 1 WHERE type = 'client' AND is_vendor = 0 AND is_client = 0");

    $permissionStatement = $pdo->prepare(
        'INSERT INTO permissions (name, label, description)
         VALUES (:name, :label, :description)
         ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description)'
    );

    foreach (RolePermissionMap::definitions() as $name => [$label, $description]) {
        $permissionStatement->execute([
            'name' => $name,
            'label' => $label,
            'description' => $description,
        ]);
    }

    $pdo->exec('DELETE FROM role_permissions');

    $rolePermissionStatement = $pdo->prepare(
        'INSERT IGNORE INTO role_permissions (role_id, permission_id)
         SELECT roles.id, permissions.id
         FROM roles
         INNER JOIN permissions ON permissions.name = :permission
         WHERE roles.name = :role'
    );

    foreach (Role::cases() as $role) {
        foreach (RolePermissionMap::permissionsFor($role) as $permission) {
            $rolePermissionStatement->execute([
                'role' => $role->value,
                'permission' => $permission->value,
            ]);
        }
    }

    seedSimpleOptions($pdo, 'shopping_rooms', [
        'SALA',
        'COZINHA',
        'COPA',
        'BANHEIRO SOCIAL',
        'BANHEIRO SUITE',
        'QUARTO INFANTIL',
        'QUARTO CASAL',
        'ESCRITORIO',
        'GARAGEM',
        'CORREDOR',
        'QUINTAL',
    ]);

    seedSimpleOptions($pdo, 'shopping_people', [
        'MAURICIO',
        'KARINA',
        'MAITE',
        'BETHOVEM',
    ]);

    seedSimpleOptions($pdo, 'shopping_vehicle_areas', [
        'ELETRICA',
        'SUSPENSAO',
        'ALIMENTACAO',
        'LATARIA',
        'FREIO',
        'ACESSORIO',
        'ACABAMENTO',
        'INTERNA',
        'CUSTOMIZACAO',
    ]);

    seedSimpleOptions($pdo, 'shopping_market_sections', [
        'LIMPEZA',
        'CARNES',
        'ENLATADOS',
        'BEBIDAS',
        'LEITES E DERIVADOS',
        'HIGIENE E BELEZA',
        'BEBE E INFANTIL',
        'PADARIA',
        'DOCES',
        'CASA',
        'PET',
        'COMIDAS PRONTAS',
        'DESPENSA',
    ]);

    $vehicleStatement = $pdo->prepare(
        'INSERT IGNORE INTO shopping_vehicles (name)
         VALUES (:name)'
    );

    foreach (['FOX VERMELHO', 'VARIANT'] as $vehicle) {
        $vehicleStatement->execute(['name' => $vehicle]);
    }

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $permissionCount = (int) $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn();
    $rolePermissionCount = (int) $pdo->query('SELECT COUNT(*) FROM role_permissions')->fetchColumn();
    $auditRetentionDays = (int) ($_ENV['AUDIT_LOG_RETENTION_DAYS'] ?? 90);
    $pruneStatement = $pdo->prepare('DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)');
    $pruneStatement->bindValue('days', $auditRetentionDays, PDO::PARAM_INT);
    $pruneStatement->execute();

    echo "Banco preparado com sucesso.\n";
    echo "Tabelas encontradas: " . implode(', ', $tables) . "\n";
    echo "Permissoes cadastradas: {$permissionCount}\n";
    echo "Vinculos perfil/permissao: {$rolePermissionCount}\n";
    echo "Logs removidos por retencao ({$auditRetentionDays} dias): {$pruneStatement->rowCount()}\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Erro ao preparar banco: {$exception->getMessage()}\n");
    exit(1);
}

function ensureIndex(PDO $pdo, string $table, string $index, string $sql): void
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.statistics
         WHERE table_schema = DATABASE()
           AND table_name = :table
           AND index_name = :index'
    );
    $statement->execute([
        'table' => $table,
        'index' => $index,
    ]);

    if ((int) $statement->fetchColumn() === 0) {
        $pdo->exec($sql);
    }
}

function ensureColumn(PDO $pdo, string $table, string $column, string $sql): void
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :table
           AND column_name = :column'
    );
    $statement->execute([
        'table' => $table,
        'column' => $column,
    ]);

    if ((int) $statement->fetchColumn() === 0) {
        $pdo->exec($sql);
    }
}

function seedSimpleOptions(PDO $pdo, string $table, array $names): void
{
    $statement = $pdo->prepare(
        "INSERT IGNORE INTO {$table} (name)
         VALUES (:name)"
    );

    foreach ($names as $name) {
        $statement->execute(['name' => $name]);
    }
}
