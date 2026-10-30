<?php

declare(strict_types=1);

use App\Domain\Shopping\MarketInvoicePdfParser;

require_once dirname(__DIR__) . '/vendor/autoload.php';
$options = getopt('', ['parser-file:']);
if (isset($options['parser-file'])) {
    require_once (string) $options['parser-file'];
}
$parser = new MarketInvoicePdfParser();
$readItems = new ReflectionMethod($parser, 'items');
$pageBreak = "\nhttps://notaparana.pr.gov.br/nfprweb/NotaFiscalHtml?idDocFiscal=123 1/2\n\f30/10/2026, 10:44 Completa | Imprimir DOCUMENTO AUXILIAR DA NOTA FISCAL DE CONSUMIDOR ELETRÔNICA\n";
$cases = [
    'total antes da quantidade, com quebra de página' => "Produto (Código: 123) Vl. Total\n13,98{$pageBreak}Qtde.:1 UN: UN Vl. Unit.: 13,98",
    'total depois do preço unitário, com quebra de página' => "Produto (Código: 123) Vl. Total\nQtde.:1 UN: UN Vl. Unit.: 13,98{$pageBreak}13,98",
];
foreach ($cases as $label => $text) {
    $items = $readItems->invoke($parser, $text);
    if (count($items) !== 1 || $items[0]['name'] !== 'Produto' || $items[0]['quantity'] !== 1.0 || $items[0]['amount'] !== 13.98) {
        throw new RuntimeException('Falha de regressão: ' . $label);
    }
    echo "OK: {$label}\n";
}
$files = glob(dirname(__DIR__) . '/storage/shopping-invoices/*.pdf') ?: [];
$failures = 0;

foreach ($files as $file) {
    try {
        $invoice = $parser->parse($file);
        $subtotal = array_sum(array_column($invoice['items'], 'amount'));
        printf("OK: %s | nota %s | %d produtos | subtotal %.2f | total %.2f\n", basename($file), $invoice['document_number'] ?? '-', count($invoice['items']), $subtotal, $invoice['total_amount'] ?? 0);
    } catch (Throwable $exception) {
        $failures++;
        printf("FALHA: %s | %s\n", basename($file), $exception->getMessage());
    }
}

printf("PDFs conferidos: %d; falhas: %d\n", count($files), $failures);
exit($failures > 0 ? 1 : 0);
