<?php

declare(strict_types=1);

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

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    echo "Banco preparado com sucesso.\n";
    echo "Tabelas encontradas: " . implode(', ', $tables) . "\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Erro ao preparar banco: {$exception->getMessage()}\n");
    exit(1);
}
