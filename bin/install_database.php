<?php

declare(strict_types=1);

use App\Domain\Auth\Role;
use App\Domain\Auth\RolePermissionMap;
use App\Support\Env;

require_once dirname(__DIR__) . '/src/Support/helpers.php';

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

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
    fwrite(STDERR, "Não foi possível ler database/schema.sql.\n");
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

    ensureColumn($pdo, 'users', 'active_session_token_hash', 'ALTER TABLE users ADD COLUMN active_session_token_hash CHAR(64) NULL AFTER last_login_at');
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(255) NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        attempted_at DATETIME NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_login_attempts_email_time (email, attempted_at),
        INDEX idx_login_attempts_ip_time (ip_address, attempted_at),
        INDEX idx_login_attempts_success_time (success, attempted_at)
    )');
    ensureIndex($pdo, 'audit_logs', 'idx_audit_logs_created_at', 'CREATE INDEX idx_audit_logs_created_at ON audit_logs (created_at)');
    ensureIndex($pdo, 'audit_logs', 'idx_audit_logs_action', 'CREATE INDEX idx_audit_logs_action ON audit_logs (action)');
    ensureColumn($pdo, 'contacts', 'vendor_id', 'ALTER TABLE contacts ADD COLUMN vendor_id BIGINT UNSIGNED NULL AFTER id');
    ensureIndex($pdo, 'contacts', 'uq_contacts_vendor', 'CREATE UNIQUE INDEX uq_contacts_vendor ON contacts (vendor_id)');
    ensureColumn($pdo, 'contacts', 'is_vendor', 'ALTER TABLE contacts ADD COLUMN is_vendor TINYINT(1) NOT NULL DEFAULT 0 AFTER type');
    ensureColumn($pdo, 'contacts', 'is_client', 'ALTER TABLE contacts ADD COLUMN is_client TINYINT(1) NOT NULL DEFAULT 0 AFTER is_vendor');
    ensureColumn($pdo, 'contacts', 'postal_code', 'ALTER TABLE contacts ADD COLUMN postal_code VARCHAR(9) NULL AFTER email');
    ensureColumn($pdo, 'contacts', 'address_number', 'ALTER TABLE contacts ADD COLUMN address_number VARCHAR(30) NULL AFTER address');
    ensureColumn($pdo, 'contacts', 'neighborhood', 'ALTER TABLE contacts ADD COLUMN neighborhood VARCHAR(120) NULL AFTER address_number');
    ensureColumn($pdo, 'contacts', 'complement', 'ALTER TABLE contacts ADD COLUMN complement VARCHAR(120) NULL AFTER neighborhood');
    ensureColumn($pdo, 'shopping_market_invoices', 'source_type', "ALTER TABLE shopping_market_invoices ADD COLUMN source_type ENUM('file', 'access_key') NOT NULL DEFAULT 'file' AFTER list_id");
    ensureColumn($pdo, 'shopping_market_invoices', 'access_key', 'ALTER TABLE shopping_market_invoices ADD COLUMN access_key CHAR(44) NULL AFTER file_size');
    ensureColumn($pdo, 'shopping_market_invoices', 'total_amount', 'ALTER TABLE shopping_market_invoices ADD COLUMN total_amount DECIMAL(14,2) NULL AFTER file_size');
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
    ensureColumn($pdo, 'shopping_wish_items', 'purchased_amount', 'ALTER TABLE shopping_wish_items ADD COLUMN purchased_amount DECIMAL(14,2) NULL AFTER purchased_at');
    ensureColumn($pdo, 'entries', 'modality', 'ALTER TABLE entries ADD COLUMN modality VARCHAR(40) NULL AFTER status');
    ensureColumn($pdo, 'entries', 'legacy_reference', 'ALTER TABLE entries ADD COLUMN legacy_reference VARCHAR(120) NULL AFTER modality');
    ensureColumn($pdo, 'entries', 'installment_current', 'ALTER TABLE entries ADD COLUMN installment_current SMALLINT UNSIGNED NULL AFTER legacy_reference');
    ensureColumn($pdo, 'entries', 'installment_total', 'ALTER TABLE entries ADD COLUMN installment_total SMALLINT UNSIGNED NULL AFTER installment_current');
    ensureColumn($pdo, 'entries', 'is_last_installment', 'ALTER TABLE entries ADD COLUMN is_last_installment TINYINT(1) NOT NULL DEFAULT 0 AFTER installment_total');
    ensureColumn($pdo, 'entries', 'is_estimated', 'ALTER TABLE entries ADD COLUMN is_estimated TINYINT(1) NOT NULL DEFAULT 0 AFTER is_last_installment');
    $pdo->exec('CREATE TABLE IF NOT EXISTS discord_notification_settings (
        id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        is_enabled TINYINT(1) NOT NULL DEFAULT 0,
        webhook_url VARCHAR(500) NULL,
        notify_market_list_created TINYINT(1) NOT NULL DEFAULT 0,
        notify_spreadsheet_import_changed TINYINT(1) NOT NULL DEFAULT 0,
        notify_spreadsheet_import_unchanged TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');
    ensureColumn($pdo, 'discord_notification_settings', 'notify_spreadsheet_import_changed', 'ALTER TABLE discord_notification_settings ADD COLUMN notify_spreadsheet_import_changed TINYINT(1) NOT NULL DEFAULT 0 AFTER notify_market_list_created');
    ensureColumn($pdo, 'discord_notification_settings', 'notify_spreadsheet_import_unchanged', 'ALTER TABLE discord_notification_settings ADD COLUMN notify_spreadsheet_import_unchanged TINYINT(1) NOT NULL DEFAULT 0 AFTER notify_spreadsheet_import_changed');
    $pdo->exec('INSERT IGNORE INTO discord_notification_settings (id) VALUES (1)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS spreadsheet_settings (
        id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        encrypted_url TEXT NULL,
        url_hash CHAR(64) NULL,
        sync_is_enabled TINYINT(1) NOT NULL DEFAULT 0,
        sync_interval_minutes INT UNSIGNED NOT NULL DEFAULT 30,
        sync_start_hour TINYINT UNSIGNED NOT NULL DEFAULT 8,
        sync_end_hour TINYINT UNSIGNED NOT NULL DEFAULT 18,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');
    ensureColumn($pdo, 'spreadsheet_settings', 'sync_is_enabled', 'ALTER TABLE spreadsheet_settings ADD COLUMN sync_is_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER url_hash');
    ensureColumn($pdo, 'spreadsheet_settings', 'sync_interval_minutes', 'ALTER TABLE spreadsheet_settings ADD COLUMN sync_interval_minutes INT UNSIGNED NOT NULL DEFAULT 30 AFTER sync_is_enabled');
    ensureColumn($pdo, 'spreadsheet_settings', 'sync_start_hour', 'ALTER TABLE spreadsheet_settings ADD COLUMN sync_start_hour TINYINT UNSIGNED NOT NULL DEFAULT 8 AFTER sync_interval_minutes');
    ensureColumn($pdo, 'spreadsheet_settings', 'sync_end_hour', 'ALTER TABLE spreadsheet_settings ADD COLUMN sync_end_hour TINYINT UNSIGNED NOT NULL DEFAULT 18 AFTER sync_start_hour');
    $pdo->exec('INSERT IGNORE INTO spreadsheet_settings (id) VALUES (1)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS security_settings (
        id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        recaptcha_is_enabled TINYINT(1) NOT NULL DEFAULT 0,
        recaptcha_site_key VARCHAR(190) NULL,
        encrypted_recaptcha_secret_key TEXT NULL,
        recaptcha_min_score DECIMAL(3,2) NOT NULL DEFAULT 0.50,
        analytics_is_enabled TINYINT(1) NOT NULL DEFAULT 0,
        analytics_measurement_id VARCHAR(30) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');
    ensureColumn($pdo, 'security_settings', 'recaptcha_is_enabled', 'ALTER TABLE security_settings ADD COLUMN recaptcha_is_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER id');
    ensureColumn($pdo, 'security_settings', 'recaptcha_site_key', 'ALTER TABLE security_settings ADD COLUMN recaptcha_site_key VARCHAR(190) NULL AFTER recaptcha_is_enabled');
    ensureColumn($pdo, 'security_settings', 'encrypted_recaptcha_secret_key', 'ALTER TABLE security_settings ADD COLUMN encrypted_recaptcha_secret_key TEXT NULL AFTER recaptcha_site_key');
    ensureColumn($pdo, 'security_settings', 'recaptcha_min_score', 'ALTER TABLE security_settings ADD COLUMN recaptcha_min_score DECIMAL(3,2) NOT NULL DEFAULT 0.50 AFTER encrypted_recaptcha_secret_key');
    ensureColumn($pdo, 'security_settings', 'analytics_is_enabled', 'ALTER TABLE security_settings ADD COLUMN analytics_is_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER recaptcha_min_score');
    ensureColumn($pdo, 'security_settings', 'analytics_measurement_id', 'ALTER TABLE security_settings ADD COLUMN analytics_measurement_id VARCHAR(30) NULL AFTER analytics_is_enabled');
    $pdo->exec('INSERT IGNORE INTO security_settings (id) VALUES (1)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS scheduled_tasks (
        code VARCHAR(120) PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        interval_minutes INT UNSIGNED NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_run_at DATETIME NULL,
        next_run_at DATETIME NOT NULL,
        locked_until DATETIME NULL,
        lock_token CHAR(32) NULL,
        consecutive_failures INT UNSIGNED NOT NULL DEFAULT 0,
        last_status VARCHAR(30) NULL,
        last_message VARCHAR(500) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_scheduled_tasks_due (is_active, next_run_at),
        INDEX idx_scheduled_tasks_lock (locked_until)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS scheduled_task_runs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        task_code VARCHAR(120) NOT NULL,
        status VARCHAR(30) NOT NULL,
        started_at DATETIME NOT NULL,
        finished_at DATETIME NULL,
        duration_ms INT UNSIGNED NULL,
        message VARCHAR(500) NULL,
        metadata JSON NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_scheduled_task_runs_task (task_code, started_at),
        INDEX idx_scheduled_task_runs_status (status),
        CONSTRAINT fk_scheduled_task_runs_task FOREIGN KEY (task_code) REFERENCES scheduled_tasks(code)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS karina_statement_entries (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id BIGINT UNSIGNED NULL,
        transaction_date DATE NOT NULL,
        movement_type VARCHAR(20) NOT NULL,
        description VARCHAR(255) NOT NULL,
        paid_account VARCHAR(80) NULL,
        amount DECIMAL(14,2) NOT NULL,
        category_name VARCHAR(120) NULL,
        source_system VARCHAR(30) NOT NULL DEFAULT "spreadsheet",
        source_key VARCHAR(190) NOT NULL,
        source_hash CHAR(64) NOT NULL,
        raw_payload JSON NULL,
        imported_at DATETIME NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT uq_karina_statement_source_key UNIQUE (source_system, source_key),
        CONSTRAINT fk_karina_statement_category FOREIGN KEY (category_id) REFERENCES categories(id),
        INDEX idx_karina_statement_date (transaction_date),
        INDEX idx_karina_statement_type (movement_type)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS spreadsheet_sync_events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(30) NOT NULL DEFAULT "windows",
        status VARCHAR(30) NOT NULL,
        occurred_at DATETIME NOT NULL,
        file_hash CHAR(64) NULL,
        file_size BIGINT UNSIGNED NULL,
        machine_name VARCHAR(120) NULL,
        message VARCHAR(500) NULL,
        metadata JSON NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_spreadsheet_sync_events_source_time (source, occurred_at),
        INDEX idx_spreadsheet_sync_events_status (status)
    )');
    $pdo->exec("UPDATE contacts SET is_vendor = 1 WHERE type = 'vendor' AND is_vendor = 0 AND is_client = 0");
    $pdo->exec("UPDATE contacts SET is_client = 1 WHERE type = 'client' AND is_vendor = 0 AND is_client = 0");
    $pdo->exec("CREATE TABLE IF NOT EXISTS category_budget_targets (
        category_id BIGINT UNSIGNED PRIMARY KEY,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        control_type ENUM('maximum', 'minimum', 'track') NOT NULL DEFAULT 'maximum',
        target_percent DECIMAL(5,2) NULL,
        desired_amount DECIMAL(14,2) NULL,
        reference_key VARCHAR(80) NULL,
        reference_label VARCHAR(160) NULL,
        reference_min_percent DECIMAL(5,2) NULL,
        reference_max_percent DECIMAL(5,2) NULL,
        notes VARCHAR(255) NULL,
        updated_by_user_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_category_budget_targets_category FOREIGN KEY (category_id) REFERENCES categories(id),
        CONSTRAINT fk_category_budget_targets_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
    )");
    ensureColumn($pdo, 'category_budget_targets', 'is_active', 'ALTER TABLE category_budget_targets ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER category_id');
    ensureColumn($pdo, 'category_budget_targets', 'desired_amount', 'ALTER TABLE category_budget_targets ADD COLUMN desired_amount DECIMAL(14,2) NULL AFTER target_percent');
    $pdo->exec("CREATE TABLE IF NOT EXISTS category_budget_settings (
        id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        base_income_amount DECIMAL(14,2) NULL,
        updated_by_user_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_category_budget_settings_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
    )");
    $pdo->exec('INSERT IGNORE INTO category_budget_settings (id) VALUES (1)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS family_profile_settings (
        id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        region ENUM('brasil', 'norte', 'nordeste', 'sudeste', 'sul', 'centro_oeste') NOT NULL DEFAULT 'brasil',
        residence_area ENUM('urban', 'rural') NOT NULL DEFAULT 'urban',
        notes VARCHAR(255) NULL,
        updated_by_user_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_family_profile_settings_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
    )");
    $pdo->exec('INSERT IGNORE INTO family_profile_settings (id) VALUES (1)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS family_members (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        sex ENUM('female', 'male', 'other') NOT NULL DEFAULT 'other',
        birth_date DATE NULL,
        role VARCHAR(80) NULL,
        income_weight DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by_user_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_family_members_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS family_pets (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        type VARCHAR(80) NOT NULL,
        birth_date DATE NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by_user_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_family_pets_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS financial_goals (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        goal_type ENUM('emergency_reserve', 'home_down_payment', 'investment', 'debt_payoff', 'other') NOT NULL DEFAULT 'other',
        start_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        current_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        target_amount DECIMAL(14,2) NOT NULL,
        start_date DATE NOT NULL,
        target_date DATE NOT NULL,
        priority_weight TINYINT UNSIGNED NOT NULL DEFAULT 5,
        notes VARCHAR(255) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by_user_id BIGINT UNSIGNED NULL,
        updated_by_user_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_financial_goals_active_target (is_active, target_date),
        INDEX idx_financial_goals_type (goal_type),
        CONSTRAINT fk_financial_goals_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id),
        CONSTRAINT fk_financial_goals_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
    )");

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
    echo "Permissões cadastradas: {$permissionCount}\n";
    echo "Vinculos perfil/permissão: {$rolePermissionCount}\n";
    echo "Logs removidos por retenção ({$auditRetentionDays} dias): {$pruneStatement->rowCount()}\n";
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
