<?php

declare(strict_types=1);

use App\Support\Env;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

require_once dirname(__DIR__) . '/src/Support/helpers.php';

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

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
    'file::',
    'limit::',
    'sheet::',
]);

$file = (string) ($options['file'] ?? ($_ENV['EXCEL_FILE'] ?? ''));
$limit = max(1, (int) ($options['limit'] ?? 3));
$onlySheet = isset($options['sheet']) ? trim((string) $options['sheet']) : '';

if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Planilha não encontrada. Configure EXCEL_FILE ou use --file=/caminho/arquivo.xlsx.\n");
    exit(1);
}

if (!class_exists(IOFactory::class)) {
    fwrite(STDERR, "PhpSpreadsheet não está disponível. Rode composer install.\n");
    exit(1);
}

$spreadsheet = IOFactory::load($file);
$referenceMonth = new DateTimeImmutable('first day of this month 00:00:00');
$summary = [
    'monthly' => 0,
    'ignored' => 0,
    'unknown' => 0,
    'entries' => 0,
];

echo "Arquivo: {$file}\n";
echo 'SHA256: ' . hash_file('sha256', $file) . "\n";
echo 'Abas encontradas: ' . $spreadsheet->getSheetCount() . "\n\n";

foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
    $sheetName = $worksheet->getTitle();

    if ($onlySheet !== '' && normalizeText($sheetName) !== normalizeText($onlySheet)) {
        continue;
    }

    $classification = classifySheet($sheetName);

    if ($classification['kind'] === 'ignored') {
        $summary['ignored']++;
        printIgnoredSheet($worksheet, $classification['reason']);
        continue;
    }

    if ($classification['kind'] === 'base') {
        printBaseSheet($worksheet, $limit);
        continue;
    }

    if ($classification['kind'] !== 'monthly') {
        $summary['unknown']++;
        printUnknownSheet($worksheet);
        continue;
    }

    $summary['monthly']++;
    $entries = inspectMonthlySheet($worksheet, $classification, $referenceMonth, $limit);
    $summary['entries'] += $entries['count'];
}

echo "\nResumo geral\n";
echo "- Abas mensais lidas: {$summary['monthly']}\n";
echo "- Abas ignoradas: {$summary['ignored']}\n";
echo "- Abas desconhecidas: {$summary['unknown']}\n";
echo "- Lançamentos candidatos encontrados: {$summary['entries']}\n";

function classifySheet(string $sheetName): array
{
    $normalized = normalizeText($sheetName);

    if (str_starts_with($normalized, 'RES-')) {
        return ['kind' => 'ignored', 'reason' => 'resultado legado'];
    }

    if ($normalized === 'KARINA') {
        return ['kind' => 'ignored', 'reason' => 'extrato de empréstimo futuro'];
    }

    if ($normalized === 'BASE') {
        return ['kind' => 'base'];
    }

    if (preg_match('/^([A-Z]{3})-\s*(\d{2})$/', $normalized, $matches) !== 1) {
        return ['kind' => 'unknown'];
    }

    $monthMap = [
        'JAN' => 1,
        'FEV' => 2,
        'MAR' => 3,
        'ABR' => 4,
        'MAI' => 5,
        'JUN' => 6,
        'JUL' => 7,
        'AGO' => 8,
        'SET' => 9,
        'OUT' => 10,
        'NOV' => 11,
        'DEZ' => 12,
    ];
    $month = $monthMap[$matches[1]] ?? null;

    if ($month === null) {
        return ['kind' => 'unknown'];
    }

    $year = 2000 + (int) $matches[2];
    $reference = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $layout = match (true) {
        $reference < new DateTimeImmutable('2023-03-01') => 'legacy',
        $reference < new DateTimeImmutable('2023-08-01') => 'standardized',
        default => 'current',
    };

    return [
        'kind' => 'monthly',
        'month' => $month,
        'year' => $year,
        'reference' => $reference,
        'layout' => $layout,
        'columns' => columnsForLayout($layout),
    ];
}

function columnsForLayout(string $layout): array
{
    return match ($layout) {
        'legacy' => [
            'description' => 'A',
            'vendor' => 'B',
            'amount' => 'C',
            'category' => 'D',
        ],
        'standardized' => [
            'description' => 'A',
            'vendor' => 'B',
            'amount' => 'C',
            'category' => 'D',
        ],
        default => [
            'description' => 'A',
            'observation' => 'B',
            'vendor' => 'C',
            'amount' => 'D',
            'category' => 'E',
        ],
    };
}

function inspectMonthlySheet(Worksheet $worksheet, array $classification, DateTimeImmutable $referenceMonth, int $limit): array
{
    $sheetName = $worksheet->getTitle();
    $columns = detectMonthlyColumns($worksheet, $classification['columns']);
    $highestRow = $worksheet->getHighestDataRow();
    $highestColumn = $worksheet->getHighestDataColumn();
    $statusByRule = $classification['reference'] < $referenceMonth ? 'paid' : 'open';
    $salary = findLabelValues($worksheet, ['SALARIO'], 1, 5);
    $paymentMarkers = findLabelValues($worksheet, ['FALTA PAGAR', 'CONTAS'], 1, 15);
    $legacyG10 = cellValue($worksheet, 'G10');
    $headers = [];

    foreach ($columns as $field => $column) {
        $headers[$field] = cellValue($worksheet, "{$column}1");
    }

    $samples = [];
    $count = 0;

    for ($row = 2; $row <= $highestRow; $row++) {
        $description = cellValue($worksheet, $columns['description'] . $row);
        $amount = cellValue($worksheet, $columns['amount'] . $row);

        if (isBlank($description) && isBlank($amount)) {
            continue;
        }

        $count++;

        if (count($samples) < $limit) {
            $sample = [
                'row' => $row,
                'description' => $description,
                'vendor' => cellValue($worksheet, $columns['vendor'] . $row),
                'amount' => $amount,
                'category' => cellValue($worksheet, $columns['category'] . $row),
            ];

            if (isset($columns['observation'])) {
                $sample['observation'] = cellValue($worksheet, $columns['observation'] . $row);
            }

            if (isset($columns['modality'])) {
                $sample['modality'] = cellValue($worksheet, $columns['modality'] . $row);
            }

            $samples[] = $sample;
        }
    }

    echo "Aba mensal: {$sheetName}\n";
    echo "- Referência: " . $classification['reference']->format('Y-m') . "\n";
    echo "- Layout detectado: {$classification['layout']}\n";
    echo "- Dimensão: A1:{$highestColumn}{$highestRow}\n";
    echo "- Status padrão por regra: {$statusByRule}\n";
    echo '- Cabeçalhos mapeados: ' . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";
    echo '- Recebido/salário detectado: ' . json_encode($salary, JSON_UNESCAPED_UNICODE) . "\n";
    echo '- Marcadores de pagamento/total: ' . json_encode($paymentMarkers, JSON_UNESCAPED_UNICODE) . "\n";
    echo "- Valor bruto em G10: " . printable($legacyG10) . "\n";
    echo "- Lançamentos candidatos: {$count}\n";

    foreach ($samples as $sample) {
        echo '  - ' . json_encode($sample, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n";

    return ['count' => $count];
}

function detectMonthlyColumns(Worksheet $worksheet, array $fallback): array
{
    $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());
    $headers = [];

    for ($column = 1; $column <= $highestColumnIndex; $column++) {
        $letter = Coordinate::stringFromColumnIndex($column);
        $header = normalizeText((string) cellValue($worksheet, $letter . '1'));

        if ($header !== '') {
            $headers[$letter] = $header;
        }
    }

    $columns = [
        'description' => findHeaderColumn($headers, ['DESCRICAO']) ?? $fallback['description'],
        'vendor' => findHeaderColumn($headers, ['FORNECEDOR', 'REPASSE A']) ?? $fallback['vendor'],
        'amount' => findHeaderColumn($headers, ['VALOR']) ?? $fallback['amount'],
        'category' => findHeaderColumn($headers, ['CATEGORIA', 'REF']) ?? $fallback['category'],
    ];

    $observation = findHeaderColumn($headers, ['OBS']);
    if ($observation !== null) {
        $columns['observation'] = $observation;
    }

    $modality = findHeaderColumn($headers, ['MODALIDADE']);
    if ($modality !== null) {
        $columns['modality'] = $modality;
    }

    return $columns;
}

function findHeaderColumn(array $headers, array $needles): ?string
{
    foreach ($headers as $column => $header) {
        foreach ($needles as $needle) {
            if (str_contains($header, $needle)) {
                return $column;
            }
        }
    }

    return null;
}

function findLabelValues(Worksheet $worksheet, array $labels, int $startRow, int $endRow): array
{
    $matches = [];
    $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());

    for ($row = $startRow; $row <= $endRow; $row++) {
        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $value = cellValue($worksheet, $letter . $row);
            $normalized = normalizeText((string) $value);

            foreach ($labels as $label) {
                if ($normalized === normalizeText($label)) {
                    $nextColumn = Coordinate::stringFromColumnIndex($column + 1);
                    $matches[] = [
                        'label_cell' => $letter . $row,
                        'label' => $value,
                        'value_cell' => $nextColumn . $row,
                        'value' => cellValue($worksheet, $nextColumn . $row),
                    ];
                }
            }
        }
    }

    return $matches;
}

function printBaseSheet(Worksheet $worksheet, int $limit): void
{
    $highestRow = $worksheet->getHighestDataRow();
    $highestColumn = $worksheet->getHighestDataColumn();
    $highestColumnIndex = min(Coordinate::columnIndexFromString($highestColumn), 10);

    echo "Aba BASE\n";
    echo "- Dimensão: A1:{$highestColumn}{$highestRow}\n";
    echo "- Primeiras linhas A:J\n";

    for ($row = 1; $row <= min($highestRow, $limit + 1); $row++) {
        $values = [];

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $coordinate = Coordinate::stringFromColumnIndex($column) . $row;
            $values[$coordinate] = cellValue($worksheet, $coordinate);
        }

        echo '  - ' . json_encode($values, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n";
}

function printIgnoredSheet(Worksheet $worksheet, string $reason): void
{
    echo "Aba ignorada: {$worksheet->getTitle()} ({$reason})\n\n";
}

function printUnknownSheet(Worksheet $worksheet): void
{
    echo "Aba desconhecida: {$worksheet->getTitle()} (sem padrão de mês/base conhecido)\n\n";
}

function cellValue(Worksheet $worksheet, string $coordinate): mixed
{
    $value = $worksheet->getCell($coordinate)->getCalculatedValue();

    if (is_string($value)) {
        return trim($value);
    }

    return $value;
}

function isBlank(mixed $value): bool
{
    return $value === null || (is_string($value) && trim($value) === '');
}

function printable(mixed $value): string
{
    if ($value === null) {
        return '-';
    }

    if (is_float($value) || is_int($value)) {
        return number_format((float) $value, 2, ',', '.');
    }

    return (string) $value;
}

function normalizeText(string $value): string
{
    $value = trim(mb_strtoupper($value, 'UTF-8'));

    return strtr($value, [
        'Á' => 'A',
        'À' => 'A',
        'Â' => 'A',
        'Ã' => 'A',
        'Ä' => 'A',
        'É' => 'E',
        'Ê' => 'E',
        'Í' => 'I',
        'Ó' => 'O',
        'Ô' => 'O',
        'Õ' => 'O',
        'Ú' => 'U',
        'Ç' => 'C',
    ]);
}
