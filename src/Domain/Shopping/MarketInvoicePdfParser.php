<?php

declare(strict_types=1);

namespace App\Domain\Shopping;

use RuntimeException;

final class MarketInvoicePdfParser
{
    public function parse(string $file): array
    {
        $text = $this->extractText($file);
        $items = $this->items($text);

        if ($items === []) {
            throw new RuntimeException('nenhum produto encontrado no PDF.');
        }

        $expectedItems = $this->match('/Qtd\.\s*total de itens:\s*(\d+)/iu', $text);

        if ($expectedItems !== null && count($items) !== (int) $expectedItems) {
            throw new RuntimeException(sprintf('Foram reconhecidos %d dos %d produtos do PDF. A importação foi interrompida para evitar valores incompletos.', count($items), (int) $expectedItems));
        }

        return [
            'access_key' => $this->accessKey($text),
            'issued_at' => $this->match('/Emiss\S*:\s*(\d{2}\/\d{2}\/\d{4}(?:\h+\d{2}:\d{2}(?::\d{2})?)?)/iu', $text),
            'document_number' => $this->match('/N[uú]mero:\s*(\d+)/iu', $text),
            'issuer' => $this->issuer($text),
            'total_amount' => $this->money($this->match('/Valor a pagar R\$:\s*([0-9.,]+)/u', $text))
                ?? $this->money($this->match('/Valor total R\$:\s*([0-9.,]+)/u', $text)),
            'items' => $items,
        ];
    }

    private function extractText(string $file): string
    {
        if (!is_file($file)) {
            throw new RuntimeException('PDF não encontrado.');
        }

        $output = tempnam(sys_get_temp_dir(), 'market_pdf_');

        if ($output === false) {
            throw new RuntimeException('Não foi possível criar arquivo temporário para leitura do PDF.');
        }

        $command = 'pdftotext -layout -enc UTF-8 ' . escapeshellarg($file) . ' ' . escapeshellarg($output) . ' 2>&1';
        exec($command, $messages, $code);

        if ($code !== 0 || !is_file($output)) {
            @unlink($output);
            throw new RuntimeException('Não foi possível ler o PDF com pdftotext.');
        }

        $text = file_get_contents($output);
        @unlink($output);

        if ($text === false || trim($text) === '') {
            throw new RuntimeException('PDF sem texto extraível.');
        }

        return str_replace("\r\n", "\n", $text);
    }

    private function items(string $text): array
    {
        // Browser print headers and footers can interrupt a product across pages.
        $text = str_replace("\f", "\n", $text);
        $text = preg_replace('/^\h*https?:\/\/[^\r\n]*$/mu', '', $text) ?? $text;
        $text = preg_replace('/^\h*\d{2}\/\d{2}\/\d{4},?\h+\d{2}:\d{2}(?::\d{2})?\h+[^\r\n]*DOCUMENTO AUXILIAR[^\r\n]*$/miu', '', $text) ?? $text;
        $items = [];
        // PDF layout extraction may place the total before or after quantity/unit price.
        $pattern = '/(?P<name>.+?)\s+\(C[oó]digo:\s*(?P<code>\d+)\s*\)\s*Vl\. Total\s+(?:(?P<amount_before>[0-9.,]+)\s+)?Qtde\.:\s*(?P<quantity>[0-9.,]+)\s*UN:\s*(?P<unit>[A-Z]+)\s*Vl\. Unit\.:\s*(?P<unit_amount>[0-9.,]+)(?(amount_before)|\s+(?P<amount_after>[0-9.,]+))/u';

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $quantity = $this->decimal($match['quantity']);
            $unitAmount = $this->money($match['unit_amount']);
            $amount = $this->money($match['amount_before'] !== '' ? $match['amount_before'] : $match['amount_after']);

            $items[] = [
                'code' => $match['code'],
                'ean' => null,
                'name' => trim($match['name']),
                'quantity' => $quantity ?? 1.0,
                'unit_amount' => $unitAmount,
                'amount' => $amount,
                'subtotal_amount' => $amount ?? ($unitAmount === null ? null : round(($quantity ?? 1.0) * $unitAmount, 2)),
            ];
        }

        return $items;
    }

    private function issuer(string $text): ?string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));

        foreach ($lines as $index => $line) {
            if (str_contains($line, 'DOCUMENTO AUXILIAR') && isset($lines[$index + 1]) && !str_contains($lines[$index + 1], 'Vl. Total')) {
                return $lines[$index + 1];
            }
        }

        return null;
    }

    private function match(string $pattern, string $text): ?string
    {
        if (!preg_match($pattern, $text, $match)) {
            return null;
        }

        return trim((string) ($match[1] ?? '')) ?: null;
    }

    private function accessKey(string $text): ?string
    {
        $key = $this->match('/Chave de acesso:\s*([\d\s]{44,70})/u', $text);

        if ($key === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $key) ?? '';

        return strlen($digits) === 44 ? $digits : null;
    }

    private function money(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(['.', ','], ['', '.'], trim($value));

        return $normalized === '' || !is_numeric($normalized) ? null : (float) $normalized;
    }

    private function decimal(string $value): ?float
    {
        $normalized = str_replace(',', '.', trim($value));

        return $normalized === '' || !is_numeric($normalized) ? null : (float) $normalized;
    }
}
