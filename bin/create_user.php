<?php

declare(strict_types=1);

use App\Core\Database;
use App\Domain\Auth\Role;
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

$options = getopt('', [
    'name:',
    'email:',
    'role:',
    'password:',
]);

$name = trim((string) ($options['name'] ?? ''));
$email = mb_strtolower(trim((string) ($options['email'] ?? '')));
$role = trim((string) ($options['role'] ?? ''));
$password = (string) ($options['password'] ?? '');

if ($name === '' || $email === '' || $role === '' || $password === '') {
    fwrite(STDERR, "Uso: php bin/create_user.php --name=\"Nome\" --email=email@dominio.com --role=admin|editor|viewer --password=senha\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "E-mail invalido.\n");
    exit(1);
}

try {
    $roleEnum = Role::from($role);
} catch (ValueError) {
    fwrite(STDERR, "Perfil invalido. Use admin, editor ou viewer.\n");
    exit(1);
}

$pdo = (new Database())->connection();

$roleStatement = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
$roleStatement->execute(['name' => $roleEnum->value]);
$roleId = $roleStatement->fetchColumn();

if ($roleId === false) {
    fwrite(STDERR, "Perfil {$roleEnum->value} nao encontrado. Rode php bin/install_database.php primeiro.\n");
    exit(1);
}

$statement = $pdo->prepare(
    'INSERT INTO users (role_id, name, email, password_hash, is_active)
     VALUES (:role_id, :name, :email, :password_hash, 1)
     ON DUPLICATE KEY UPDATE
        role_id = VALUES(role_id),
        name = VALUES(name),
        password_hash = VALUES(password_hash),
        is_active = 1,
        updated_at = CURRENT_TIMESTAMP'
);

$statement->execute([
    'role_id' => $roleId,
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);

echo "Usuario {$email} preparado com perfil {$roleEnum->value}.\n";
