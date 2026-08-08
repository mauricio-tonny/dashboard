<?php

declare(strict_types=1);

namespace App\Domain\Shopping;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

final class MarketInvoiceXmlParser
{
    public function parse(string $file): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->load($file, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new RuntimeException('XML invalido ou ilegivel.');
        }

        $xpath = new DOMXPath($document);
        $items = [];

        foreach ($xpath->query('//*[local-name()="det"]') ?: [] as $detail) {
            if (!$detail instanceof DOMElement) {
                continue;
            }

            $name = $this->text($xpath, $detail, './/*[local-name()="prod"]/*[local-name()="xProd"]');

            if ($name === '') {
                continue;
            }

            $quantity = $this->number($this->text($xpath, $detail, './/*[local-name()="prod"]/*[local-name()="qCom"]')) ?? 1.0;
            $unitAmount = $this->number($this->text($xpath, $detail, './/*[local-name()="prod"]/*[local-name()="vUnCom"]'));
            $amount = $this->number($this->text($xpath, $detail, './/*[local-name()="prod"]/*[local-name()="vProd"]'));

            $items[] = [
                'code' => $this->text($xpath, $detail, './/*[local-name()="prod"]/*[local-name()="cProd"]') ?: null,
                'ean' => $this->text($xpath, $detail, './/*[local-name()="prod"]/*[local-name()="cEAN"]') ?: null,
                'name' => $name,
                'quantity' => $quantity,
                'unit_amount' => $unitAmount,
                'amount' => $amount,
                'subtotal_amount' => $amount ?? ($unitAmount === null ? null : round($quantity * $unitAmount, 2)),
            ];
        }

        return [
            'access_key' => $this->accessKey($xpath),
            'issued_at' => $this->text($xpath, $document->documentElement, '//*[local-name()="ide"]/*[local-name()="dhEmi"]')
                ?: $this->text($xpath, $document->documentElement, '//*[local-name()="ide"]/*[local-name()="dEmi"]')
                ?: null,
            'issuer' => $this->text($xpath, $document->documentElement, '//*[local-name()="emit"]/*[local-name()="xNome"]') ?: null,
            'total_amount' => $this->number($this->text($xpath, $document->documentElement, '//*[local-name()="ICMSTot"]/*[local-name()="vNF"]')),
            'items' => $items,
        ];
    }

    private function text(DOMXPath $xpath, ?DOMElement $context, string $query): string
    {
        if ($context === null) {
            return '';
        }

        $nodes = $xpath->query($query, $context);
        $value = $nodes === false || $nodes->length === 0 ? '' : (string) $nodes->item(0)?->textContent;

        return trim($value);
    }

    private function number(string $value): ?float
    {
        $normalized = str_replace(',', '.', trim($value));

        return $normalized === '' || !is_numeric($normalized) ? null : (float) $normalized;
    }

    private function accessKey(DOMXPath $xpath): ?string
    {
        $infNFe = $xpath->query('//*[local-name()="infNFe"]')->item(0);

        if (!$infNFe instanceof DOMElement) {
            return null;
        }

        $id = $infNFe->getAttribute('Id');

        return str_starts_with($id, 'NFe') ? substr($id, 3) : ($id ?: null);
    }
}
