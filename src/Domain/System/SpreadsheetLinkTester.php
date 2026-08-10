<?php

declare(strict_types=1);

namespace App\Domain\System;

final class SpreadsheetLinkTester
{
    public function test(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => true,
                'header' => "User-Agent: DashboardFinanceiro/1.0\r\nRange: bytes=0-4095\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $handle = @fopen($url, 'rb', false, $context);

        if ($handle === false) {
            return [
                'success' => false,
                'message' => 'Não foi possível acessar o link informado.',
            ];
        }

        $sample = (string) fread($handle, 4096);
        fclose($handle);

        $headers = $http_response_header ?? [];
        $statusCode = $this->statusCode($headers);
        $contentType = $this->headerValue($headers, 'content-type');
        $contentDisposition = $this->headerValue($headers, 'content-disposition');
        $looksLikeSpreadsheet = str_contains(strtolower($contentType), 'spreadsheet')
            || str_contains(strtolower($contentDisposition), '.xlsx')
            || str_starts_with($sample, 'PK');

        if ($statusCode < 200 || $statusCode >= 400) {
            return [
                'success' => false,
                'message' => "O link respondeu HTTP {$statusCode}.",
            ];
        }

        if (!$looksLikeSpreadsheet) {
            return [
                'success' => false,
                'message' => 'O link abriu, mas não parece entregar um arquivo .xlsx diretamente.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Link acessível e com aparência de arquivo Excel.',
        ];
    }

    private function statusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', (string) $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function headerValue(array $headers, string $name): string
    {
        foreach ($headers as $header) {
            if (str_starts_with(strtolower((string) $header), strtolower($name) . ':')) {
                return trim(substr((string) $header, strlen($name) + 1));
            }
        }

        return '';
    }
}
