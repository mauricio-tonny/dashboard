<?php

declare(strict_types=1);

use App\Core\Database;
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
    'dry-run',
    'apply',
    'limit::',
]);
$file = (string) ($options['file'] ?? ($_ENV['EXCEL_FILE'] ?? ''));
$dryRun = !isset($options['apply']);
$limit = max(1, (int) ($options['limit'] ?? 20));

if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Planilha não encontrada. Configure EXCEL_FILE ou use --file=/caminho/arquivo.xlsx.\n");
    exit(1);
}

if (!class_exists(IOFactory::class)) {
    fwrite(STDERR, "PhpSpreadsheet não está disponível. Rode composer install.\n");
    exit(1);
}

$startedAt = new DateTimeImmutable('now');
$spreadsheet = IOFactory::load($file);
$fileHash = (string) hash_file('sha256', $file);
$base = readBase($spreadsheet->getSheetByName('BASE'));
$referenceMonth = new DateTimeImmutable('first day of this month 00:00:00');
$rows = [];
$stats = [
    'base_vendors' => count($base['vendors']),
    'base_categories' => count($base['categories']),
    'entries_seen' => 0,
    'entries_importable' => 0,
    'entries_skipped' => 0,
    'vendor_pending' => 0,
    'category_pending' => 0,
    'last_installments' => 0,
];
$pendingVendors = [];
$pendingCategories = [];

foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
    $classification = classifySheet($worksheet->getTitle());

    if ($classification['kind'] !== 'monthly') {
        continue;
    }

    $columns = detectMonthlyColumns($worksheet, $classification['columns']);
    $highestRow = $worksheet->getHighestDataRow();
    $status = $classification['reference'] < $referenceMonth ? 'paid' : 'open';

    for ($row = 2; $row <= $highestRow; $row++) {
        $description = asText(cellValue($worksheet, $columns['description'] . $row));
        $amount = cellValue($worksheet, $columns['amount'] . $row);
        $stats['entries_seen']++;

        if ($description === '' || !is_numeric($amount)) {
            $stats['entries_skipped']++;
            continue;
        }

        $rawVendor = asText(cellValue($worksheet, $columns['vendor'] . $row));
        $rawCategory = asText(cellValue($worksheet, $columns['category'] . $row));
        $observation = isset($columns['observation']) ? asText(cellValue($worksheet, $columns['observation'] . $row)) : '';
        $modality = isset($columns['modality']) ? asText(cellValue($worksheet, $columns['modality'] . $row)) : '';
        $vendor = normalizeVendor($rawVendor, $classification['reference'], $worksheet, $row, $base);
        $category = normalizeCategory($rawCategory, $description, $base);
        $installment = parseInstallment($description, $modality);
        $sourceKey = 'spreadsheet:' . $worksheet->getTitle() . ':row:' . $row;
        $rawPayload = [
            'sheet' => $worksheet->getTitle(),
            'row' => $row,
            'layout' => $classification['layout'],
            'columns' => $columns,
            'description' => $description,
            'raw_vendor' => $rawVendor,
            'normalized_vendor' => $vendor,
            'raw_category' => $rawCategory,
            'normalized_category' => $category,
            'observation' => $observation,
            'modality' => $modality,
            'row_color' => rowColor($worksheet, $row),
            'installment' => $installment,
            'amount' => (float) $amount,
            'status' => $status,
        ];
        $sourceHash = hash('sha256', json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        if ($vendor['status'] === 'pending') {
            $stats['vendor_pending']++;
            addPending($pendingVendors, $rawVendor, $worksheet->getTitle(), $row, $description);
        }

        if ($category['status'] === 'pending') {
            $stats['category_pending']++;
            addPending($pendingCategories, $rawCategory, $worksheet->getTitle(), $row, $description);
        }

        if (($installment['is_last'] ?? false) === true) {
            $stats['last_installments']++;
        }

        $rows[] = [
            'source_key' => $sourceKey,
            'source_hash' => $sourceHash,
            'worksheet_name' => $worksheet->getTitle(),
            'worksheet_row' => $row,
            'competence_month' => $classification['reference']->format('Y-m-01'),
            'entry_date' => $classification['reference']->format('Y-m-01'),
            'type' => 'expense',
            'description' => mb_substr($description, 0, 255),
            'amount' => (float) $amount,
            'status' => $status,
            'vendor_name' => $vendor['status'] === 'pending' ? null : $vendor['value'],
            'category_name' => $category['status'] === 'pending' ? null : $category['value'],
            'modality' => $modality === '' ? null : mb_substr($modality, 0, 40),
            'legacy_reference' => $rawCategory === '' ? null : mb_substr($rawCategory, 0, 120),
            'installment_current' => $installment['current'] ?? null,
            'installment_total' => $installment['total'] ?? null,
            'is_last_installment' => ($installment['is_last'] ?? false) ? 1 : 0,
            'raw_payload' => $rawPayload,
        ];
        $stats['entries_importable']++;
    }
}

echo ($dryRun ? "DRY-RUN\n" : "APPLY\n");
echo "Arquivo: {$file}\n";
echo "SHA256: {$fileHash}\n";
echo "Fornecedores BASE: {$stats['base_vendors']}\n";
echo "Categorias BASE: {$stats['base_categories']}\n";
echo "Linhas vistas: {$stats['entries_seen']}\n";
echo "Lançamentos importáveis: {$stats['entries_importable']}\n";
echo "Linhas ignoradas: {$stats['entries_skipped']}\n";
echo "Fornecedores pendentes: {$stats['vendor_pending']}\n";
echo "Categorias pendentes: {$stats['category_pending']}\n";
echo "Últimas parcelas: {$stats['last_installments']}\n\n";
printPending('Top fornecedores pendentes', $pendingVendors, $limit);
printPending('Top categorias/REF pendentes', $pendingCategories, $limit);

if ($dryRun) {
    echo "Nada foi gravado. Use --apply para importar no banco.\n";
    exit(0);
}

$database = new Database();
$pdo = $database->connection();
$pdo->beginTransaction();

try {
    $importRunId = createImportRun($pdo, $file, $fileHash, $startedAt);
    $vendorIds = upsertLookup($pdo, 'vendors', array_values($base['vendors']));
    $categoryIds = upsertLookup($pdo, 'categories', array_values($base['categories']));
    $importedVendors = count($vendorIds);
    $importedCategories = count($categoryIds);

    foreach ($rows as $row) {
        if ($row['vendor_name'] !== null && !isset($vendorIds[normalizeText($row['vendor_name'])])) {
            $vendorIds += upsertLookup($pdo, 'vendors', [$row['vendor_name']]);
        }

        if ($row['category_name'] !== null && !isset($categoryIds[normalizeText($row['category_name'])])) {
            $categoryIds += upsertLookup($pdo, 'categories', [$row['category_name']]);
        }
    }

    $entryStatement = $pdo->prepare(
        'INSERT INTO entries
            (category_id, vendor_id, entry_date, due_date, competence_month, type, description, amount, status,
             modality, legacy_reference, installment_current, installment_total, is_last_installment,
             source_system, source_key, source_hash, imported_at)
         VALUES
            (:category_id, :vendor_id, :entry_date, NULL, :competence_month, :type, :description, :amount, :status,
             :modality, :legacy_reference, :installment_current, :installment_total, :is_last_installment,
             "spreadsheet", :source_key, :source_hash, NOW())
         ON DUPLICATE KEY UPDATE
            category_id = VALUES(category_id),
            vendor_id = VALUES(vendor_id),
            entry_date = VALUES(entry_date),
            competence_month = VALUES(competence_month),
            type = VALUES(type),
            description = VALUES(description),
            amount = VALUES(amount),
            status = VALUES(status),
            modality = VALUES(modality),
            legacy_reference = VALUES(legacy_reference),
            installment_current = VALUES(installment_current),
            installment_total = VALUES(installment_total),
            is_last_installment = VALUES(is_last_installment),
            source_hash = VALUES(source_hash),
            imported_at = NOW()'
    );
    $sourceStatement = $pdo->prepare(
        'INSERT INTO entry_sources (entry_id, import_run_id, worksheet_name, worksheet_row, raw_payload)
         VALUES (:entry_id, :import_run_id, :worksheet_name, :worksheet_row, :raw_payload)'
    );
    $entryIdStatement = $pdo->prepare(
        'SELECT id FROM entries WHERE source_system = "spreadsheet" AND source_key = :source_key LIMIT 1'
    );

    foreach ($rows as $row) {
        $vendorId = $row['vendor_name'] === null ? null : ($vendorIds[normalizeText($row['vendor_name'])] ?? null);
        $categoryId = $row['category_name'] === null ? null : ($categoryIds[normalizeText($row['category_name'])] ?? null);
        $entryStatement->execute([
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'entry_date' => $row['entry_date'],
            'competence_month' => $row['competence_month'],
            'type' => $row['type'],
            'description' => $row['description'],
            'amount' => $row['amount'],
            'status' => $row['status'],
            'modality' => $row['modality'],
            'legacy_reference' => $row['legacy_reference'],
            'installment_current' => $row['installment_current'],
            'installment_total' => $row['installment_total'],
            'is_last_installment' => $row['is_last_installment'],
            'source_key' => $row['source_key'],
            'source_hash' => $row['source_hash'],
        ]);
        $entryIdStatement->execute(['source_key' => $row['source_key']]);
        $entryId = (int) $entryIdStatement->fetchColumn();
        $sourceStatement->execute([
            'entry_id' => $entryId,
            'import_run_id' => $importRunId,
            'worksheet_name' => $row['worksheet_name'],
            'worksheet_row' => $row['worksheet_row'],
            'raw_payload' => json_encode($row['raw_payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    finishImportRun($pdo, $importRunId, 'success', count($rows), $importedCategories, $importedVendors, $stats);
    $pdo->commit();

    echo "Importação concluída.\n";
    echo "Import run ID: {$importRunId}\n";
    echo "Lançamentos gravados/atualizados: " . count($rows) . "\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, "Erro ao importar: {$exception->getMessage()}\n");
    exit(1);
}

function createImportRun(PDO $pdo, string $file, string $fileHash, DateTimeImmutable $startedAt): int
{
    $statement = $pdo->prepare(
        'INSERT INTO import_runs (source_type, source_reference, file_hash, status, started_at)
         VALUES ("spreadsheet_file", :source_reference, :file_hash, "running", :started_at)'
    );
    $statement->execute([
        'source_reference' => $file,
        'file_hash' => $fileHash,
        'started_at' => $startedAt->format('Y-m-d H:i:s'),
    ]);

    return (int) $pdo->lastInsertId();
}

function finishImportRun(PDO $pdo, int $id, string $status, int $entries, int $categories, int $vendors, array $stats): void
{
    $statement = $pdo->prepare(
        'UPDATE import_runs
         SET status = :status,
             finished_at = NOW(),
             imported_entries = :entries,
             imported_categories = :categories,
             imported_vendors = :vendors,
             notes = :notes
         WHERE id = :id'
    );
    $statement->execute([
        'id' => $id,
        'status' => $status,
        'entries' => $entries,
        'categories' => $categories,
        'vendors' => $vendors,
        'notes' => json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
}

function upsertLookup(PDO $pdo, string $table, array $names): array
{
    $items = [];
    $statement = $pdo->prepare(
        "INSERT INTO {$table} (name, slug, source_hash)
         VALUES (:name, :slug, :source_hash)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            source_hash = VALUES(source_hash),
            is_active = 1"
    );
    $select = $pdo->prepare("SELECT id, name FROM {$table}");

    foreach (array_unique(array_filter(array_map('trim', $names))) as $name) {
        $statement->execute([
            'name' => $name,
            'slug' => slugify($name),
            'source_hash' => hash('sha256', $name),
        ]);
    }

    $select->execute();

    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $items[normalizeText((string) $row['name'])] = (int) $row['id'];
    }

    return $items;
}

function readBase(?Worksheet $worksheet): array
{
    if ($worksheet === null) {
        return ['vendors' => [], 'categories' => []];
    }

    return [
        'vendors' => readBaseColumn($worksheet, 'A'),
        'categories' => readBaseColumn($worksheet, 'C'),
    ];
}

function readBaseColumn(Worksheet $worksheet, string $column): array
{
    $items = [];

    for ($row = 2; $row <= $worksheet->getHighestDataRow($column); $row++) {
        $value = asText(cellValue($worksheet, $column . $row));

        if ($value !== '') {
            $items[normalizeText($value)] = $value;
        }
    }

    return $items;
}

function normalizeVendor(string $rawVendor, DateTimeImmutable $reference, Worksheet $worksheet, int $row, array $base): array
{
    $normalized = normalizeText($rawVendor);

    if ($normalized === '') {
        return ['status' => 'pending', 'value' => '', 'reason' => 'empty'];
    }

    if (isset($base['vendors'][$normalized])) {
        return ['status' => 'exact', 'value' => $base['vendors'][$normalized], 'reason' => 'base'];
    }

    if ($normalized === 'MARCIO') {
        return ['status' => 'rule', 'value' => 'MARCIO (PAI)', 'reason' => 'explicit_alias'];
    }

    $vendorAliases = [
        'CAIXA ECONOMICA' => ['C E F', 'C.E.F.'],
        'CAIXA' => ['C E F', 'C.E.F.'],
        'DEP CAIXA' => ['C E F', 'C.E.F.'],
        'MAYCON' => ['MAYCON IRMAO', 'MAYCON (IRMÃO)'],
        'BV FINANC' => ['BV FINANCEIRA', 'BV FINANCEIRA'],
        'PREFEITURA' => ['PREF CP', 'PREF. CP'],
        'PREFEITURA MUNICIP' => ['PREF CP', 'PREF. CP'],
        'DETRAN' => ['DETRAN PR', 'DETRAN PR'],
        'YOUSE' => ['YOUSE SEGURO', 'YOUSE (SEGURO)'],
        'DORIVAL' => ['DORIVAL ACAD', 'DORIVAL ACAD.'],
    ];

    if (isset($vendorAliases[$normalized])) {
        [$vendorKey, $fallback] = $vendorAliases[$normalized];
        return ['status' => 'rule', 'value' => lookupBaseValue($base['vendors'], $vendorKey, $fallback), 'reason' => 'explicit_alias'];
    }

    if (isCardVendor($rawVendor) && $reference < new DateTimeImmutable('2023-03-01')) {
        $period = cardPeriod($reference);
        $color = rowColor($worksheet, $row);

        if ($period === '2015-03_to_2018-12') {
            return ['status' => 'rule', 'value' => 'RIACHUELO', 'reason' => 'card_period'];
        }

        if (in_array($color, ['FF9933FF', 'FF7030A0'], true)) {
            return ['status' => 'rule', 'value' => 'NUBANK', 'reason' => 'card_color'];
        }

        if ($color === 'FFF8CBAD') {
            return ['status' => 'rule', 'value' => 'RIACHUELO', 'reason' => 'card_color'];
        }

        if (in_array($color, ['FFBFBFBF', 'FFA6A6A6', 'FFD9D9D9'], true)) {
            return ['status' => 'rule', 'value' => 'C6 BANK', 'reason' => 'card_color'];
        }

        if ($period === '2020-09_to_2022-05') {
            return ['status' => 'rule', 'value' => 'NUBANK', 'reason' => 'card_period_default'];
        }
    }

    return ['status' => 'pending', 'value' => $rawVendor, 'reason' => 'no_match'];
}

function normalizeCategory(string $rawCategory, string $description, array $base): array
{
    $normalized = normalizeText($rawCategory);
    $normalizedDescription = normalizeText($description);

    if ($normalized !== '' && isset($base['categories'][$normalized])) {
        return ['status' => 'exact', 'value' => $base['categories'][$normalized], 'reason' => 'base'];
    }

    $descriptionRule = normalizeCategoryByDescription($normalizedDescription, $base);
    if ($descriptionRule !== null) {
        return $descriptionRule;
    }

    if (str_contains($normalizedDescription, 'BANCO ACORDO')) {
        return ['status' => 'rule', 'value' => lookupBaseValue($base['categories'], 'DESP EMPRESA', 'DESP. EMPRESA'), 'reason' => 'description_rule'];
    }

    if (str_contains($normalizedDescription, 'ROUPA') || str_contains($normalizedDescription, 'DAFITI')) {
        return ['status' => 'rule', 'value' => lookupBaseValue($base['categories'], 'VESTUARIO', 'VESTUÁRIO'), 'reason' => 'description_rule'];
    }

    return ['status' => 'pending', 'value' => $rawCategory, 'reason' => 'legacy_reference_or_no_match'];
}

function normalizeCategoryByDescription(string $normalizedDescription, array $base): ?array
{
    $rules = [
        [['SUP CIDADE CANCAO', 'SUP CANCAO', 'SUP CID CANCAO', 'CANCAO', 'BOX ATACADISTA', 'SUP LONDRINA', 'MOLINIS SUP', 'SUP CONDOR', 'ACOUGUE CARNE', 'ACOUGUE SAO JOSE', 'AMERICANAS', 'REFRIGERANTE CONQUISTA'], 'MERCADO', 'MERCADO'],
        [['SUP CENTER', 'SUPERMERCADO', 'MAXI LIMPEZA', 'DISTRIBUIDORA PRIMAVERA', 'DISTRIB PRIMAV', 'ECOBRILHO'], 'MERCADO', 'MERCADO'],
        [['SPOTIFY'], 'STREAMING', 'STREAMING'],
        [['PADOKA', 'IFOOD', 'LANCHE', 'VADECO', 'SANTA PIZZA', 'COSTELA GRILL', 'DOG KING', 'PIZZARIA DOM PARMELO', 'DOM PARMELO', 'DOM PARMELLO', 'BUONN FRATELLO PIZZARIA', 'GELA BOCA', 'DELLA PAZZETTI', 'PIZZARIA DELLA NONA', 'PIZZARIA DELLA NONNA', 'DELLA NONA', 'PADARIA JOIA', 'PADARIA', 'JOIA', 'RESTAURANTE', 'BURGER KING', 'BUENOS BURGER', 'DI BURGER', 'CHAPAO BURGER', 'COSTELA', 'COSTELLA', 'VILA ITALIA', 'REST COSTELA', 'STRASSBERG', 'HANGAR', 'PIZZARIA VILA ITALIA', 'BUENO BURGER', 'TODO TORTO', 'REST DOM JOAQUIM', 'CROASSONHO', 'CROASONHO', 'PIZZARIA FORNINHO', 'LA MAFIA HAMBURG', 'LOS BRAGAS', 'PIZZARIA CAPRIOLLI', 'PIZZARIA NOVA PACHECO', 'PIZZA POPEDI', 'PIZZA', 'PEDACOS DE AMOR', 'GELOBEL LANC DON PABLO', 'GELOBEL', 'SUBWAY', 'PIZZARIA LAS VEGAS', 'KOJO', 'BEBIDAS', 'CHIQUINHO', 'TROPICANA', 'ESTACAO PROCOPIO', 'HACHIMITSU', 'FOOD TRUCK', 'SORVETERIA KI DELICIA', 'BENDROLL', 'BEND ROLL', 'BEND E ROLL', 'PAO E VINHO FRANGO ASSADO', 'CHULETA MALUCA STEAKBAR', 'CAFE DO PARQUE', 'PETISCARIA DIMIRSU', 'RICKS STREET FOOD INBOX', 'REST TRADI MINEIRA', 'REST TRADICAO MINEIRA', 'RANCHO DOS PAMPAS REST', 'MC DONALDS SORVETE', 'DISTRIBUIDORA MARCAO CERV KARINA', 'TOGETHER', 'DOCE HISTORIA CAFE', 'ACAI WAVE', 'ACAI', 'BARILOCHE ALMOCO', 'PONTO GRILL BOULEVARD'], 'ALIMENTACAO', 'ALIMENTAÇÃO'],
        [['AUTO POSTO', 'GASOLINA', 'ETANOL', 'ALCOOL', 'COMBUSTIVEL', 'PALOMA', 'POSTO IPIRANGA CP', 'POSTO JB'], 'COMBUSTIVEL', 'COMBUSTÍVEL'],
        [['YOUSE SEGURO', 'SEGURO CARRO', 'SEGURO MOTO'], 'SEGURO VEICULAR', 'SEGURO VEICULAR'],
        [['CLASSICS BARBEARIA', 'BARBEARIA'], 'CUIDADOS PESSOAIS', 'CUIDADOS PESSOAIS'],
        [['CLASSICS', 'BELLA CENTER', 'OCULOS', 'SUPLEMENTO', 'ACADEMIA EXTREME', 'DON CANEDO'], 'CUIDADOS PESSOAIS', 'CUIDADOS PESSOAIS'],
        [['PETSHOP CANTINHOS'], 'PET', 'PET'],
        [['PETITE BOX', 'LEITE MAITE', 'NISSEI MAITE', 'FRALDA PAMPERS', 'MALUKINHA KIDS'], 'INFANTIL', 'INFANTIL'],
        [['FARMACIA', 'DENTISTA', 'SAUDE', 'REMEDIO', 'KIT MANICURE ISA', 'JOAO LIMA INJECAO ISADORA', 'LUCIA PODOLOGA'], 'SAUDE', 'SAÚDE'],
        [['NISSEI'], 'SAUDE', 'SAÚDE'],
        [['CREDITO CELULAR', 'PLANO TIM', 'PLANO CLARO'], 'TELEFONIA', 'TELEFONIA'],
        [['CELULAR MAURICIO'], 'TELEFONIA', 'TELEFONIA'],
        [['INTERNET BRASILNET', 'INTERNET VISAONET'], 'INTERNET', 'INTERNET'],
        [['IPVA'], 'IPVA', 'IPVA'],
        [['POS GRADUACAO', 'UNIFIL', 'FACULDADE', 'INGLES'], 'APRENDIZADO', 'APRENDIZADO'],
        [['NOVA MARIVAL', 'CERTIF PITAGORAS', 'APOSTILA ST MICROCOPY', 'MAT ESCOLAR MARIVAL', 'MAT ESCOLAR ISADORA', 'CADERNO ISADORA', 'LIVRARIAS CURITIBA'], 'EDUCACAO', 'EDUCACAO'],
        [['FIES'], 'FINANC ESTUDANTIL', 'FINANC. ESTUDANTIL'],
        [['PREVIDENCIA', 'APOSENT'], 'APOSENTADORIA', 'APOSENTADORIA'],
        [['ALUGUEL'], 'ALUGUEL', 'ALUGUEL'],
        [['CARRO 47', 'CARRO 48'], 'FINANC VEICULAR', 'FINANC. VEICULAR'],
        [['RIZZO'], 'ESTACIONAMENTO', 'ESTACIONAMENTO'],
        [['UBER', 'EQUIPS AIRSOFT', 'CATUAI ESTACIONAMENTO', 'BANCO IMOBILIARIO', 'CINEMA BOULEVARD', 'CINEMARK', 'BRITOS ROLE', 'RACA NEGRA', '2800 DESPESA', 'SIMPLA EVENTO GOOGLE', 'VIACAO GARCIA', 'TRANSITO ESTACIONAMENTO', 'VIAGEM', 'PLATINUM'], 'DIVERSAO PASSEIO', 'DIVERSÃO/PASSEIO'],
        [['MAKITA', 'CAPACETE MERCADO LIVRE', 'SAO JOAO CARTAO UMIDIF', 'MERCADO LIVRE MALETA', 'LUVA MERC LIVRE', 'POLTRONA MARCIO', 'MANINHO CAPACETE', 'JOGO DE FACA', 'JOGO DE PANELA', 'FURADEIRA', 'ALIANCA NAMORO', 'KIT JARDINAGEM MARA', 'KIT CROCHET MARA', 'PRESENTE MARA', 'PRESENTE ISADORA', 'PRESENTE MAE DA ISA', 'MOCHILA ISADORA', 'KIT AGULHA MARA', 'SERRA COPO WD40', 'KIT POLIMENTO', 'KIT CHAVE CATRACA', 'FERRAMENTAS ELETROTRAFO', 'PRESENTE TAI', 'AMORT CADEIRA UNIMAQ', 'AMIGO SECRETO E PERFUME', 'PRESENTE AMIGO SECRETO', 'ELETROBARROS RODIZIO SOFA', 'LUMINARIA MESA', 'ACM SUPORTE ESPELHO', 'TRENA A LASER PAULO', 'SMARTPHONE KARINA', 'KIT BROCA CHINA', 'BANDOLEIRA', 'LANTERNA TATICA', 'RADIO BAOFENG', 'RELOGIO CHINA', 'FLORES SHOPPING', 'CAPACETE 1 3', 'CAPACETE 2 3', 'CAPACETE 3 3', 'ALGO DO MAYCON', 'MATERIAIS ELETRICOS', 'PGTO CHICOTE MAURICIO', 'TV LG SMART 49 LED 4K', 'PLIMOR', 'CAMISAS MAYCON', 'SOM QUARTO', 'CABO SOM'], 'AQUISICAO', 'AQUISIÇÃO'],
        [['TELHANORTE', 'DEP SAO LUIS', 'DEP SAO LUIZ', 'BOX BANHEIRO', 'CATIVA TELA MOSQUITEIRA', 'ELETROTRAFO RESISTENCIA CHUV', 'ELETROBARROS RESISTENCIA', 'MAT ELETRICO KARINA'], 'REFORMA MANUT', 'REFORMA/MANUT.'],
        [['MICROSIS', 'THAIS CRISTINA DE LIMA'], 'DESP EMPRESA', 'DESP. EMPRESA'],
        [['DEPOSITO BANCO', 'DEPOSITO BANCO CONJU', 'NUBANK RENDIMENTO'], 'INVESTIMENTO', 'INVESTIMENTO'],
        [['ANUIDADE CARTAO', 'ANUIDADE', 'TARIFA MERCADO PAGO'], 'INVESTIMENTO', 'INVESTIMENTO'],
        [['CARTAO MARCIO'], 'EMPRESTIMO', 'EMPRÉSTIMO'],
        [['MOTOR VIRAGO', 'BATERIA VIRAGO', 'PECAS VIRAGO', 'CENTER CAR', 'MOTO JANEIRO FEV', 'CORRENTE MOTO', 'RELE VIRAGO', 'VOLANTE SAIDA DE AR COIFA', 'SUFILM GOL', 'CONNECT GOL', 'PARACHOQUE GOL', 'KIT BOTAO GOL', 'CALOTA GOL', 'BORRACHA DO GOL KAYABA', 'PLACA DE PARTIDA VIRAGO', 'TUTI CARBURADOR', 'EMBREAGENS COPROCAR', 'ITIBAN VER 60K', 'WM MOTOPECAS', 'MANUTENCAO GOL EMBRAGENS COPRO', 'OLEO VIRAGO', 'FILTRO DE OLEO VIRAGO', 'MANOPLA COIFA COCKPIT', 'ENGRENAGEM BOMBA DE OLEO', 'SENSOR DE OLEO VIRAGO', 'JOGO DE JUNTA VIRAGO', 'KIT BIELA VIRAGO', 'KIT PISTAO BIZ 100 ANEL', 'CANETINHA PNEU', 'MANINHO CARBURADOR', 'COLETOR VIRAGO', 'MOTO MAYCON', 'CDI VIRAGO', 'CABO AC REPARO PROMOTOS', 'BOBINA VIRAGO', 'CABO AC VIRAGO', 'FILTRO ESPORTIVO VIR', 'RACE CUSTOM VIRAGO', 'RACECUSTOM', 'GRELHA VIRAGO', 'RELACAO VIRAGO WM MOTO', 'FAROL FREIO VIRAGO', 'EIXO QUADRO VIRAGO', 'KIT RAIO VIRAGO', 'KIT SETA VIRAGO', 'ELETRICA VIRAGO', 'PNEU VIRAGO', 'CABO DO FREIO TRAS', 'PNEU CG 150', 'MANINHO REF VIRAGO', 'CBR 450SR MANINHO', 'ALI EXPRESS ADAPTADOR RETROVISOR', 'KIT ADESIVO ML', 'KIT LATERAL ML', 'KIT EMBLEMA ML'], 'MANUT VEICULO', 'MANUT. VEICULO'],
        [['BIPE SNIPER', 'STEAM DLC ETS2', 'DLC ETS2', 'LEFT 4 DEAD', 'ONG ORAR F75', 'ONG RETORNAR HD 883', 'PRISTON TALE', 'EURO TRUCK COMPLETO', 'EURO TRUCK GOING EAST', 'JOGO CITIES SKYLINES', 'VOLANTE SCANIA', 'LOGITECH G27', 'LUNETA SNIPER', 'KIT MILITAR', 'KIT CAPACETE AIRSOFT', 'FARDA AIRSOFT', 'GTA V'], 'INVEST MAURICIO', 'INVEST. MAURICIO'],
        [['TNS CABOS', 'TNS FONTE PC SONECA', 'FONTE 500W EVA', 'CAPACITOR ELETROLITICO', 'FLAT NOTEBOOK FABRETTI', 'FLAT NOTEBOOK DELL', 'CHIP IPHONE GVEI', 'TECLADO NOTE GUI SEMP', 'TECLADO NOTE KAREN', 'REGISTROBR', 'ONEDRIVE', 'MODEM ROTEADOR 3G', 'ACCESS POINT INTELBRAS', 'BATERIA NOBREAK', 'FONE AKG', 'HOSPEDAGEM MAYCON', 'MOCHILA ACER', 'PLACA DE VIDEO', 'INVERTER NOTEBOOK DELL FERNANDA', 'ANTENA EXTERNA', 'HD SSD', 'SSD'], 'TECNOLOGIA', 'TECNOLOGIA'],
        [['CAMISETA PITICAS', 'KIT CUECA', 'KIT COTURNO', 'TENIS CINTO MEIA', 'UNIFORME MARISA MODAS', 'LUVA MAURICIO', 'KIT TENIS', 'KIT CAMISA', 'KIT SHORT', 'TENIS', 'MEIA LUPO', 'CALCA MOLETOM', 'MOLETOM FUEL TECH', 'BLUSA KARINA', 'JALECO KARINA', 'TUDO10 BIKINI LINGUI PORTA', 'LENCOL CAMA'], 'VESTUARIO', 'VESTUÁRIO'],
        [['RIACHUELO'], 'VESTUARIO', 'VESTUÁRIO'],
        [['LUZ CAMILA TESTE', 'FUNWORK TESTE', 'TESTE BUZZ', 'CONTROLLE BUZZ', 'PORTA DE PAPEL BUZZ', 'ITENS ELETRICOS BUZZ', 'TOK PLENO TESTE BUZZ'], 'BUZZ', 'BUZZ'],
    ];

    foreach ($rules as [$needles, $categoryKey, $fallback]) {
        foreach ($needles as $needle) {
            if (str_contains($normalizedDescription, $needle)) {
                return ['status' => 'rule', 'value' => lookupBaseValue($base['categories'], $categoryKey, $fallback), 'reason' => 'description_rule'];
            }
        }
    }

    return null;
}

function lookupBaseValue(array $items, string $normalizedKey, string $fallback): string
{
    return $items[normalizeText($normalizedKey)] ?? $fallback;
}

function parseInstallment(string $description, string $modality): ?array
{
    $normalizedModality = normalizeText($modality);

    if (preg_match('/(?:^|[^0-9])(\d{1,3})\s*\/\s*(\d{1,3})(?:[^0-9]|$)/', $description, $matches) === 1) {
        $current = (int) $matches[1];
        $total = (int) $matches[2];

        return [
            'current' => $current,
            'total' => $total,
            'is_last' => $current > 0 && $current === $total,
            'source' => 'description',
        ];
    }

    if ($normalizedModality === 'ULTIMA PARC') {
        return [
            'current' => null,
            'total' => null,
            'is_last' => true,
            'source' => 'modality',
        ];
    }

    return null;
}

function addPending(array &$items, string $raw, string $sheet, int $row, string $description): void
{
    $key = normalizeText($raw) ?: '(VAZIO)';
    $items[$key]['raw'] = $raw;
    $items[$key]['count'] = ($items[$key]['count'] ?? 0) + 1;

    if (!isset($items[$key]['samples'])) {
        $items[$key]['samples'] = [[
            'sheet' => $sheet,
            'row' => $row,
            'description' => $description,
        ]];
    }
}

function printPending(string $title, array $items, int $limit): void
{
    uasort($items, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
    echo "{$title}\n";

    foreach (array_slice($items, 0, $limit) as $item) {
        echo '- ' . json_encode($item, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n";
}

function cardPeriod(DateTimeImmutable $reference): string
{
    return match (true) {
        $reference < new DateTimeImmutable('2019-01-01') => '2015-03_to_2018-12',
        $reference < new DateTimeImmutable('2020-09-01') => '2019-01_to_2020-08',
        $reference < new DateTimeImmutable('2022-06-01') => '2020-09_to_2022-05',
        $reference < new DateTimeImmutable('2023-03-01') => '2022-06_to_2023-02',
        default => 'post_dropdown',
    };
}

function isCardVendor(string $vendor): bool
{
    return in_array(normalizeText($vendor), ['CARTAO', 'CARTAO DE CREDITO', 'CARTAO CREDITO'], true);
}

function rowColor(Worksheet $worksheet, int $row): string
{
    $fill = $worksheet->getStyle('A' . $row)->getFill();
    $color = $fill->getStartColor()->getARGB() ?: $fill->getStartColor()->getRGB();

    return strtoupper((string) $color);
}

function classifySheet(string $sheetName): array
{
    $normalized = normalizeText($sheetName);

    if (str_starts_with($normalized, 'RES ') || $normalized === 'KARINA' || $normalized === 'BASE') {
        return ['kind' => 'ignored'];
    }

    if (preg_match('/^([A-Z]{3})\s*(\d{2})$/', $normalized, $matches) !== 1) {
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
        'reference' => $reference,
        'layout' => $layout,
        'columns' => columnsForLayout($layout),
    ];
}

function columnsForLayout(string $layout): array
{
    return match ($layout) {
        'legacy', 'standardized' => [
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

function detectMonthlyColumns(Worksheet $worksheet, array $fallback): array
{
    $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());
    $headers = [];

    for ($column = 1; $column <= $highestColumnIndex; $column++) {
        $letter = Coordinate::stringFromColumnIndex($column);
        $header = normalizeText(asText(cellValue($worksheet, $letter . '1')));

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

    foreach (['OBS' => 'observation', 'MODALIDADE' => 'modality'] as $needle => $field) {
        $column = findHeaderColumn($headers, [$needle]);

        if ($column !== null) {
            $columns[$field] = $column;
        }
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

function cellValue(Worksheet $worksheet, string $coordinate): mixed
{
    $value = $worksheet->getCell($coordinate)->getCalculatedValue();

    if (is_string($value)) {
        return trim($value);
    }

    return $value;
}

function asText(mixed $value): string
{
    return trim((string) ($value ?? ''));
}

function slugify(string $value): string
{
    $slug = strtolower(normalizeText($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;

    return trim($slug, '-') ?: 'item-' . substr(hash('sha256', $value), 0, 12);
}

function normalizeText(string $value): string
{
    $value = trim(mb_strtoupper($value, 'UTF-8'));
    $value = strtr($value, [
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
    $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;

    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}
