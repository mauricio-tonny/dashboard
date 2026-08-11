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
    'focus-category::',
    'focus-pending',
    'print-base',
]);
$file = (string) ($options['file'] ?? ($_ENV['EXCEL_FILE'] ?? ''));
$limit = max(1, (int) ($options['limit'] ?? 20));
$focusCategory = isset($options['focus-category']) ? normalizeText((string) $options['focus-category']) : '';
$focusPending = isset($options['focus-pending']);

if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Planilha não encontrada. Configure EXCEL_FILE ou use --file=/caminho/arquivo.xlsx.\n");
    exit(1);
}

$spreadsheet = IOFactory::load($file);
$base = readBase($spreadsheet->getSheetByName('BASE'));

if (isset($options['print-base'])) {
    printBaseItems('Fornecedores oficiais', $base['vendors']);
    printBaseItems('Categorias oficiais', $base['categories']);
    exit(0);
}

$referenceMonth = new DateTimeImmutable('first day of this month 00:00:00');
$stats = [
    'entries' => 0,
    'vendor_exact' => 0,
    'vendor_rule' => 0,
    'vendor_pending' => 0,
    'category_exact' => 0,
    'category_rule' => 0,
    'category_pending' => 0,
    'installments' => 0,
    'last_installments' => 0,
    'paid' => 0,
    'open' => 0,
];
$pendingVendors = [];
$pendingCategories = [];
$cardColors = [];
$lastInstallments = [];
$focusedCategories = [];

foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
    $classification = classifySheet($worksheet->getTitle());

    if ($classification['kind'] !== 'monthly') {
        continue;
    }

    $columns = detectMonthlyColumns($worksheet, $classification['columns']);
    $highestRow = $worksheet->getHighestDataRow();
    $status = $classification['reference'] < $referenceMonth ? 'paid' : 'open';
    $stats[$status]++;

    for ($row = 2; $row <= $highestRow; $row++) {
        $description = asText(cellValue($worksheet, $columns['description'] . $row));
        $amount = cellValue($worksheet, $columns['amount'] . $row);

        if ($description === '' || !is_numeric($amount)) {
            continue;
        }

        $stats['entries']++;
        $rawVendor = asText(cellValue($worksheet, $columns['vendor'] . $row));
        $rawCategory = asText(cellValue($worksheet, $columns['category'] . $row));
        $modality = isset($columns['modality']) ? asText(cellValue($worksheet, $columns['modality'] . $row)) : '';
        $vendor = normalizeVendor($rawVendor, $classification['reference'], $worksheet, $row, $base);
        $category = normalizeCategory($rawCategory, $description, $base);
        $installment = parseInstallment($description, $modality);

        if ($focusCategory !== '' && normalizeText($rawCategory) === $focusCategory && (!$focusPending || $category['status'] === 'pending')) {
            addFocusedCategory($focusedCategories, $rawCategory, $description, $rawVendor, (float) $amount, $worksheet->getTitle(), $row);
        }

        $stats['vendor_' . $vendor['status']]++;
        $stats['category_' . $category['status']]++;

        if ($vendor['status'] === 'pending') {
            addPending($pendingVendors, $rawVendor, $worksheet->getTitle(), $row, $description);
        }

        if ($category['status'] === 'pending') {
            addPending($pendingCategories, $rawCategory, $worksheet->getTitle(), $row, $description);
        }

        if (isCardVendor($rawVendor) && $classification['reference'] < new DateTimeImmutable('2023-03-01')) {
            $color = rowColor($worksheet, $row);
            $period = cardPeriod($classification['reference']);
            $cardColors[$period][$color]['count'] = ($cardColors[$period][$color]['count'] ?? 0) + 1;
            if (!isset($cardColors[$period][$color]['samples'])) {
                $cardColors[$period][$color]['samples'] = [[
                    'sheet' => $worksheet->getTitle(),
                    'row' => $row,
                    'description' => $description,
                ]];
            }
        }

        if ($installment !== null) {
            $stats['installments']++;

            if ($installment['is_last']) {
                $stats['last_installments']++;

                if (count($lastInstallments) < $limit) {
                    $lastInstallments[] = [
                        'sheet' => $worksheet->getTitle(),
                        'row' => $row,
                        'description' => $description,
                        'vendor' => $vendor['value'],
                        'amount' => (float) $amount,
                        'installment' => "{$installment['current']}/{$installment['total']}",
                    ];
                }
            }
        }
    }
}

echo "Arquivo: {$file}\n";
echo 'SHA256: ' . hash_file('sha256', $file) . "\n\n";
echo "BASE\n";
echo "- Fornecedores oficiais: " . count($base['vendors']) . "\n";
echo "- Categorias oficiais: " . count($base['categories']) . "\n\n";
echo "Normalização\n";
echo "- Lançamentos numéricos analisados: {$stats['entries']}\n";
echo "- Abas mensais pagas por regra: {$stats['paid']}\n";
echo "- Abas mensais abertas por regra: {$stats['open']}\n";
echo "- Fornecedor exato: {$stats['vendor_exact']}\n";
echo "- Fornecedor por regra: {$stats['vendor_rule']}\n";
echo "- Fornecedor pendente: {$stats['vendor_pending']}\n";
echo "- Categoria exata: {$stats['category_exact']}\n";
echo "- Categoria por regra: {$stats['category_rule']}\n";
echo "- Categoria pendente: {$stats['category_pending']}\n";
echo "- Parcelas detectadas por texto/modalidade: {$stats['installments']}\n";
echo "- Últimas parcelas detectadas: {$stats['last_installments']}\n\n";
printPending('Fornecedores pendentes', $pendingVendors, $limit);
printPending('Categorias/REF pendentes', $pendingCategories, $limit);
printFocusedCategories($focusCategory, $focusedCategories, $limit);
printCardColors($cardColors, $limit);
printSamples('Amostras de últimas parcelas', $lastInstallments);

function readBase(?Worksheet $worksheet): array
{
    if ($worksheet === null) {
        return ['vendors' => [], 'categories' => []];
    }

    $vendors = readBaseColumn($worksheet, 'A');
    $categories = readBaseColumn($worksheet, 'C');

    return [
        'vendors' => $vendors,
        'categories' => $categories,
    ];
}

function readBaseColumn(Worksheet $worksheet, string $column): array
{
    $items = [];

    for ($row = 2; $row <= $worksheet->getHighestDataRow($column); $row++) {
        $value = asText(cellValue($worksheet, $column . $row));

        if ($value === '') {
            continue;
        }

        $items[normalizeText($value)] = $value;
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
        return ['status' => 'rule', 'value' => 'DESP. EMPRESA', 'reason' => 'description_rule'];
    }

    if (str_contains($normalizedDescription, 'ROUPA') || str_contains($normalizedDescription, 'DAFITI')) {
        return ['status' => 'rule', 'value' => 'VESTUÁRIO', 'reason' => 'description_rule'];
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

function addFocusedCategory(array &$items, string $rawCategory, string $description, string $rawVendor, float $amount, string $sheet, int $row): void
{
    $key = normalizeDescriptionGroup($description);
    $items[$key]['description_group'] = $key;
    $items[$key]['raw_category'] = $rawCategory;
    $items[$key]['count'] = ($items[$key]['count'] ?? 0) + 1;
    $items[$key]['total_amount'] = ($items[$key]['total_amount'] ?? 0.0) + $amount;

    if (!isset($items[$key]['samples'])) {
        $items[$key]['samples'] = [[
            'sheet' => $sheet,
            'row' => $row,
            'description' => $description,
            'raw_vendor' => $rawVendor,
            'amount' => $amount,
        ]];
    }
}

function printFocusedCategories(string $focusCategory, array $items, int $limit): void
{
    if ($focusCategory === '') {
        return;
    }

    uasort($items, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
    echo "Foco categoria/REF: {$focusCategory}\n";

    foreach (array_slice($items, 0, $limit) as $item) {
        $item['total_amount'] = round((float) $item['total_amount'], 2);
        echo '- ' . json_encode($item, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n";
}

function normalizeDescriptionGroup(string $description): string
{
    $value = normalizeText($description);
    $value = preg_replace('/\b\d{1,3}\s+\/\s+\d{1,3}\b/', 'PARCELA', $value) ?? $value;
    $value = preg_replace('/\b\d{1,2}\s+DE\s+\d{1,2}\b/', 'PARCELA', $value) ?? $value;

    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
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

function printBaseItems(string $title, array $items): void
{
    echo "{$title}\n";

    foreach ($items as $item) {
        echo "- {$item}\n";
    }

    echo "\n";
}

function printCardColors(array $cardColors, int $limit): void
{
    echo "Cartões antigos por período/cor\n";

    foreach ($cardColors as $period => $colors) {
        echo "- {$period}\n";

        foreach (array_slice($colors, 0, $limit) as $color => $data) {
            echo '  - ' . json_encode([
                'color' => $color,
                'count' => $data['count'],
                'sample' => $data['samples'][0] ?? null,
            ], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }

    echo "\n";
}

function printSamples(string $title, array $samples): void
{
    echo "{$title}\n";

    foreach ($samples as $sample) {
        echo '- ' . json_encode($sample, JSON_UNESCAPED_UNICODE) . "\n";
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
