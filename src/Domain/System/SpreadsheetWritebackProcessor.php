<?php

declare(strict_types=1);

namespace App\Domain\System;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

final class SpreadsheetWritebackProcessor
{
    public function __construct(
        private SpreadsheetWritebackRepository $repository,
        private string $excelFile
    ) {
    }

    public function process(): array
    {
        $pending = $this->repository->pending();

        if ($pending === []) {
            return ['applied' => 0, 'failed' => 0, 'message' => 'Nenhuma alteração pendente.'];
        }

        if (!is_file($this->excelFile)) {
            throw new RuntimeException('Planilha do servidor não encontrada.');
        }

        $spreadsheet = IOFactory::load($this->excelFile);
        $currentFileHash = hash_file('sha256', $this->excelFile);
        $applied = 0;
        $failed = 0;
        $conflicts = 0;
        $appliedIds = [];

        foreach ($pending as $item) {
            try {
                $baseFileHash = $item['base_file_hash'];

                if (!is_string($currentFileHash) || !is_string($baseFileHash) || !hash_equals($baseFileHash, $currentFileHash)) {
                    $this->repository->markConflict(
                        (int) $item['id'],
                        'A planilha mudou desde o cadastro desta alteração. O envio foi bloqueado para evitar restaurar dados removidos.'
                    );
                    $conflicts++;
                    continue;
                }

                if ($item['operation'] !== 'create') {
                    throw new RuntimeException('Operação de retorno não suportada nesta versão.');
                }

                $this->appendExpense($spreadsheet, $item['payload']);
                $appliedIds[] = (int) $item['id'];
                $applied++;
            } catch (\Throwable $exception) {
                $this->repository->markFailed((int) $item['id'], $exception->getMessage());
                $failed++;
            }
        }

        if ($applied > 0) {
            $temporaryFile = $this->excelFile . '.writeback';
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($temporaryFile);

            if (!rename($temporaryFile, $this->excelFile)) {
                throw new RuntimeException('Não foi possível publicar a planilha atualizada.');
            }

            foreach ($appliedIds as $appliedId) {
                $this->repository->markApplied($appliedId);
            }
            $hash = hash_file('sha256', $this->excelFile);
            file_put_contents($this->excelFile . '.server-sync.json', json_encode([
                'source' => 'server_writeback',
                'file_hash' => $hash,
                'updated_at' => (new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')))->format(DATE_ATOM),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        return [
            'applied' => $applied,
            'failed' => $failed,
            'conflicts' => $conflicts,
            'message' => "{$applied} alteração(ões) enviada(s) para a planilha; {$failed} falha(s); {$conflicts} conflito(s) bloqueado(s).",
        ];
    }

    private function appendExpense(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $payload): void
    {
        $date = new \DateTimeImmutable((string) ($payload['date'] ?? ''));

        if (trim((string) ($payload['modality'] ?? '')) === 'RECORRENTE') {
            $endDate = $this->recurrenceEndDate($spreadsheet, $payload, $date);

            for ($month = $date->modify('first day of this month'); $month <= $endDate; $month = $month->modify('+1 month')) {
                $this->appendExpenseRow($spreadsheet, $payload, $month);
            }

            return;
        }

        $this->appendExpenseRow($spreadsheet, $payload, $date);
    }

    private function appendExpenseRow(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $payload, \DateTimeImmutable $date): void
    {
        $sheetName = $this->sheetName($date);
        $worksheet = $spreadsheet->getSheetByName($sheetName);

        if (!$worksheet instanceof Worksheet) {
            throw new RuntimeException("A aba {$sheetName} não foi encontrada na planilha.");
        }

        $columns = $this->detectColumns($worksheet);
        $row = $this->firstAvailableRow($worksheet, $columns);
        $worksheet->setCellValue($columns['description'] . $row, (string) ($payload['description'] ?? ''));
        $worksheet->setCellValue($columns['vendor'] . $row, (string) ($payload['vendor'] ?? ''));
        $worksheet->setCellValue($columns['amount'] . $row, (float) ($payload['amount'] ?? 0));
        $worksheet->setCellValue($columns['category'] . $row, (string) ($payload['category'] ?? ''));

        if (isset($columns['modality'])) {
            $modality = trim((string) ($payload['modality'] ?? ''));
            $worksheet->setCellValue($columns['modality'] . $row, $modality !== '' ? $modality : 'A VISTA');
        }
    }

    private function firstAvailableRow(Worksheet $worksheet, array $columns): int
    {
        $highestRow = max(2, $worksheet->getHighestDataRow());
        $dataColumns = array_values(array_unique(array_filter([
            $columns['description'] ?? null,
            $columns['vendor'] ?? null,
            $columns['amount'] ?? null,
            $columns['category'] ?? null,
            $columns['modality'] ?? null,
        ])));

        for ($row = 2; $row <= $highestRow; $row++) {
            $hasData = false;

            foreach ($dataColumns as $column) {
                if (trim((string) $worksheet->getCell($column . $row)->getValue()) !== '') {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                return $row;
            }
        }

        return $highestRow + 1;
    }

    private function recurrenceEndDate(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $payload, \DateTimeImmutable $startDate): \DateTimeImmutable
    {
        $configuredEnd = trim((string) ($payload['recurrence_end_date'] ?? ''));

        if ($configuredEnd !== '') {
            $endDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $configuredEnd);

            if ($endDate instanceof \DateTimeImmutable && $endDate >= $startDate) {
                return $endDate->modify('first day of this month');
            }

            throw new RuntimeException('A data final da recorrência é inválida.');
        }

        $latestDate = null;
        $monthMap = [
            'JAN' => 1, 'FEV' => 2, 'MAR' => 3, 'ABR' => 4, 'MAI' => 5, 'JUN' => 6,
            'JUL' => 7, 'AGO' => 8, 'SET' => 9, 'OUT' => 10, 'NOV' => 11, 'DEZ' => 12,
        ];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $normalized = $this->normalize($worksheet->getTitle());

            if (preg_match('/^([A-Z]{3})\s*-\s*(\d{2})$/', $normalized, $matches) !== 1 || !isset($monthMap[$matches[1]])) {
                continue;
            }

            $candidate = new \DateTimeImmutable(sprintf('20%s-%02d-01', $matches[2], $monthMap[$matches[1]]));

            if ($latestDate === null || $candidate > $latestDate) {
                $latestDate = $candidate;
            }
        }

        if (!$latestDate instanceof \DateTimeImmutable || $latestDate < $startDate->modify('first day of this month')) {
            throw new RuntimeException('Não há abas mensais disponíveis para a recorrência.');
        }

        return $latestDate;
    }

    private function sheetName(\DateTimeImmutable $date): string
    {
        $months = [1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR', 5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ'];

        return $months[(int) $date->format('n')] . '- ' . $date->format('y');
    }

    private function detectColumns(Worksheet $worksheet): array
    {
        $headers = [];

        for ($column = 1; $column <= Coordinate::columnIndexFromString($worksheet->getHighestDataColumn()); $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $headers[$letter] = $this->normalize((string) $worksheet->getCell($letter . '1')->getValue());
        }

        $find = static function (array $needles) use ($headers): ?string {
            foreach ($headers as $letter => $header) {
                foreach ($needles as $needle) {
                    if (str_contains($header, $needle)) {
                        return $letter;
                    }
                }
            }

            return null;
        };

        $reference = $find(['REF', 'CATEGORIA']);
        $columns = [
            'description' => $find(['DESCRICAO']) ?? 'A',
            'vendor' => $find(['FORNECEDOR', 'REPASSE A']) ?? 'C',
            'amount' => $find(['VALOR']) ?? 'D',
            'category' => $reference ?? 'E',
        ];

        if ($columns['description'] === 'A' && $columns['vendor'] === 'C' && $columns['amount'] === 'D' && $columns['category'] === 'E' && $reference === null) {
            $columns['vendor'] = 'B';
            $columns['amount'] = 'C';
            $columns['category'] = 'D';
        }

        $modality = $find(['MODALIDADE']);

        if ($modality !== null) {
            $columns['modality'] = $modality;
        }

        return $columns;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');

        return strtr($value, ['Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ç' => 'C']);
    }
}
