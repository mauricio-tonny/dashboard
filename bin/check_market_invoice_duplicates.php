<?php

declare(strict_types=1);

use App\Core\Database;
use App\Domain\Shopping\DuplicateMarketInvoice;
use App\Domain\Shopping\ShoppingRepository;
use App\Support\Env;

require_once dirname(__DIR__) . '/src/Support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
Env::load(dirname(__DIR__) . '/.env');
$database = new Database();
$pdo = $database->connection();
$repository = new ShoppingRepository($database);
$invoices = $pdo->query('SELECT list_id, access_key, file_hash FROM shopping_market_invoices WHERE access_key IS NOT NULL AND file_hash IS NOT NULL LIMIT 1')->fetch();
$listIds = $pdo->query('SELECT id FROM shopping_market_lists ORDER BY id DESC LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);

if (!$invoices || count($listIds) < 2) {
    throw new RuntimeException('O teste requer uma nota identificada e duas listas existentes.');
}

$expectDuplicate = static function (string $label, callable $action): void {
    try {
        $action();
    } catch (DuplicateMarketInvoice $exception) {
        echo "OK: {$label}: {$exception->getMessage()}\n";
        return;
    }
    throw new RuntimeException("Duplicidade não bloqueada: {$label}");
};

$pdo->beginTransaction();
try {
    $otherListId = (int) ($listIds[0] == $invoices['list_id'] ? $listIds[1] : $listIds[0]);
    $expectDuplicate('Mesmo arquivo renomeado em outra lista', static fn () => $repository->addMarketInvoice($otherListId, 'renomeado.pdf', 'test.pdf', 'application/pdf', 1, null, null, $invoices['file_hash']));
    $expectDuplicate('Mesma chave em outro formato e lista', static fn () => $repository->addMarketInvoice($otherListId, 'outro.xml', 'test.xml', 'application/xml', 1, null, $invoices['access_key'], hash('sha256', random_bytes(32))));
    $expectDuplicate('Chave digitada já anexada', static fn () => $repository->addMarketInvoiceAccessKey($otherListId, ['access_key' => $invoices['access_key']], null));

    foreach (['access_key', 'file_hash'] as $column) {
        try {
            $sql = 'INSERT INTO shopping_market_invoices (list_id, original_name, stored_name, mime_type, file_size, ' . $column . ') VALUES (:list_id, "test", "test", "application/pdf", 1, :identity)';
            $pdo->prepare($sql)->execute(['list_id' => $otherListId, 'identity' => $invoices[$column]]);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }
            echo "OK: restrição no banco para {$column}\n";
            continue;
        }
        throw new RuntimeException("Restrição ausente para {$column}");
    }

    $repository->addMarketInvoice($otherListId, 'nota-nova.pdf', 'test-new.pdf', 'application/pdf', 1, null, null, hash('sha256', random_bytes(32)));
    echo "OK: arquivo novo permitido\n";
} finally {
    $pdo->rollBack();
}
echo "Verificações concluídas; nenhuma alteração de teste foi mantida.\n";
