<?php

declare(strict_types=1);

use App\Core\Database;
use App\Domain\Shopping\MarketInvoicePdfParser;
use App\Domain\Shopping\MarketInvoiceXmlParser;
use App\Domain\Shopping\ShoppingRepository;
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

$database = new Database();
$repository = new ShoppingRepository($database);
$pdo = $database->connection();
$directory = dirname(__DIR__) . '/storage/shopping-invoices';
$statement = $pdo->query(
    'SELECT id, list_id, original_name, stored_name
     FROM shopping_market_invoices
     WHERE source_type = "file"
       AND stored_name <> ""
       AND (purchase_date IS NULL OR access_key IS NULL OR access_key = "")'
);
$invoices = $statement->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($invoices as $invoice) {
    $storedName = (string) $invoice['stored_name'];
    $file = $directory . '/' . $storedName;
    $extension = mb_strtolower(pathinfo($storedName, PATHINFO_EXTENSION));

    if (!is_file($file) || !in_array($extension, ['xml', 'pdf'], true)) {
        $skipped++;
        continue;
    }

    try {
        $parsed = $extension === 'xml'
            ? (new MarketInvoiceXmlParser())->parse($file)
            : (new MarketInvoicePdfParser())->parse($file);
        $repository->updateMarketInvoiceMetadata((int) $invoice['id'], [
            'access_key' => $parsed['access_key'] ?? null,
            'issued_at' => $parsed['issued_at'] ?? null,
        ]);
        $repository->updateMarketListPurchaseDate((int) $invoice['list_id'], $parsed['issued_at'] ?? null);
        $updated++;
    } catch (Throwable $exception) {
        $failed++;
        fwrite(STDERR, "Falha ao ler nota {$invoice['id']} ({$invoice['original_name']}): {$exception->getMessage()}\n");
    }
}

echo "Metadados de notas atualizados: {$updated}\n";
echo "Notas ignoradas: {$skipped}\n";
echo "Falhas: {$failed}\n";
