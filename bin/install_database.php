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
