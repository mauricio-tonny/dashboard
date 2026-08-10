<?php

declare(strict_types=1);

namespace App\Domain\Shopping;

use InvalidArgumentException;

final class AccessKeyParser
{
    public function parse(string $value): array
    {
        $key = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($key) !== 44) {
            throw new InvalidArgumentException('A chave de acesso deve conter 44 digitos.');
        }

        if (!$this->hasValidCheckDigit($key)) {
            throw new InvalidArgumentException('Dígito verificador da chave de acesso inválido.');
        }

        $data = [
            'access_key' => $key,
            'uf_code' => substr($key, 0, 2),
            'issued_year_month' => substr($key, 2, 4),
            'issuer_document' => substr($key, 6, 14),
            'document_model' => substr($key, 20, 2),
            'document_series' => substr($key, 22, 3),
            'document_number' => substr($key, 25, 9),
            'issue_type' => substr($key, 34, 1),
            'numeric_code' => substr($key, 35, 8),
            'check_digit' => substr($key, 43, 1),
        ];
        $data['public_url'] = $this->publicUrl($data);

        return $data;
    }

    private function hasValidCheckDigit(string $key): bool
    {
        $base = substr($key, 0, 43);
        $weight = 2;
        $sum = 0;

        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $sum += (int) $base[$i] * $weight;
            $weight = $weight === 9 ? 2 : $weight + 1;
        }

        $digit = 11 - ($sum % 11);
        $digit = $digit >= 10 ? 0 : $digit;

        return (string) $digit === substr($key, 43, 1);
    }

    private function publicUrl(array $data): ?string
    {
        if ($data['uf_code'] === '41' && $data['document_model'] === '65') {
            return 'https://sped.fazenda.pr.gov.br/NFCe/webservices/sped/nfce/completa';
        }

        return null;
    }
}
