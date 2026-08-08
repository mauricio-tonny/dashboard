<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Shopping\AccessKeyParser;
use App\Domain\Shopping\MarketInvoiceXmlParser;
use App\Domain\Shopping\ShoppingRepository;

final class ShoppingController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorizeView();

        if ($auth instanceof Response) {
            return $auth;
        }

        return Response::view('shopping/index', [
            'user' => $auth->user(),
        ]);
    }

    public function market(Request $request): Response
    {
        $auth = $this->authorizeView();

        if ($auth instanceof Response) {
            return $auth;
        }

        $repository = $this->app->make(ShoppingRepository::class);
        $selectedListId = (int) $request->input('market_list_id', 0);
        $lists = $repository->marketLists();

        if ($selectedListId === 0 && $lists !== []) {
            $selectedListId = (int) $lists[0]['id'];
        }

        $selectedList = $selectedListId > 0 ? $repository->marketList($selectedListId) : null;

        return Response::view('shopping/market', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'nextMonth' => $repository->nextMonth(),
            'marketLists' => $lists,
            'selectedMarketList' => $selectedList,
            'marketItems' => $selectedList === null ? [] : $repository->marketItems((int) $selectedList['id']),
            'marketInvoices' => $selectedList === null ? [] : $repository->marketInvoices((int) $selectedList['id']),
            'marketSections' => $repository->simpleOptions('market_sections', true),
        ]);
    }

    public function home(Request $request): Response
    {
        return $this->wishPage('home', 'Para casa', 'Comodo', 'room_id', 'rooms', true);
    }

    public function family(Request $request): Response
    {
        return $this->wishPage('family', 'Para a familia', 'Para quem', 'person_id', 'people');
    }

    public function vehicle(Request $request): Response
    {
        $auth = $this->authorizeView();

        if ($auth instanceof Response) {
            return $auth;
        }

        $repository = $this->app->make(ShoppingRepository::class);

        return Response::view('shopping/wish', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'title' => 'Para o veiculo',
            'type' => 'vehicle',
            'optionLabel' => 'Para qual veiculo',
            'optionField' => 'vehicle_id',
            'options' => $repository->vehicles(true),
            'items' => $repository->wishItems('vehicle'),
            'hasPriority' => false,
            'vehicleAreas' => $repository->simpleOptions('vehicle_areas', true),
        ]);
    }

    public function createMarketList(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $repository = $this->app->make(ShoppingRepository::class);
        $id = $repository->findOrCreateMarketList((string) $request->input('reference_month'), $auth->user()?->id);
        $this->audit('shopping_market_list_saved', 'shopping_market_list', $id);
        $this->flash('success', 'Lista de mercado preparada.');

        return Response::redirect('/shopping/market?market_list_id=' . $id);
    }

    public function addMarketItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $listId = (int) $request->input('list_id');
        $data = $this->marketItemPayload($request);

        if ($listId <= 0 || $data === null) {
            $this->flash('error', 'Informe nome, sessao e quantidade.');
            return Response::redirect('/shopping/market?market_list_id=' . $listId);
        }

        $id = $this->app->make(ShoppingRepository::class)->addMarketItem(['list_id' => $listId, ...$data], $auth->user()?->id);
        $this->audit('shopping_market_item_created', 'shopping_market_item', $id, ['name' => $data['name']]);
        $this->flash('success', 'Item adicionado ao mercado.');

        return Response::redirect('/shopping/market?market_list_id=' . $listId);
    }

    public function updateMarketItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $listId = (int) $request->input('list_id');
        $data = $this->marketItemPayload($request);

        if ($id > 0 && $data !== null) {
            $this->app->make(ShoppingRepository::class)->updateMarketItem($id, $data);
            $this->audit('shopping_market_item_updated', 'shopping_market_item', $id);
        }

        return Response::redirect('/shopping/market?market_list_id=' . $listId);
    }

    public function toggleMarketItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $listId = (int) $request->input('list_id');
        $checked = (string) $request->input('checked') === '1';
        $this->app->make(ShoppingRepository::class)->toggleMarketItem($id, $checked);
        $this->audit($checked ? 'shopping_market_item_checked' : 'shopping_market_item_unchecked', 'shopping_market_item', $id);

        return Response::redirect('/shopping/market?market_list_id=' . $listId);
    }

    public function deleteMarketItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $listId = (int) $request->input('list_id');
        $this->app->make(ShoppingRepository::class)->deleteMarketItem($id);
        $this->audit('shopping_market_item_deleted', 'shopping_market_item', $id);

        return Response::redirect('/shopping/market?market_list_id=' . $listId);
    }

    public function finishMarketList(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('list_id');
        $amount = $this->money((string) $request->input('total_amount'));
        $this->app->make(ShoppingRepository::class)->finishMarketList($id, $amount);
        $this->audit('shopping_market_list_finished', 'shopping_market_list', $id, ['total_amount' => $amount]);
        $this->flash('success', 'Lista finalizada com valor total.');

        return Response::redirect('/shopping/market?market_list_id=' . $id);
    }

    public function uploadMarketInvoice(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $listId = (int) $request->input('list_id');
        $file = $_FILES['invoice'] ?? null;

        if ($listId <= 0 || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Selecione um arquivo valido de NFC-e/NF-e.');
            return Response::redirect('/shopping/market?market_list_id=' . $listId);
        }

        $originalName = basename((string) $file['name']);
        $extension = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'xml', 'jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowedExtensions, true) || (int) $file['size'] > 5 * 1024 * 1024) {
            $this->flash('error', 'Arquivo invalido. Use PDF, XML, JPG ou PNG com ate 5MB.');
            return Response::redirect('/shopping/market?market_list_id=' . $listId);
        }

        $directory = base_path('storage/shopping-invoices');

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        chmod($directory, 0775);

        $storedName = $listId . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
        $target = $directory . '/' . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            $this->flash('error', 'Nao foi possivel salvar o arquivo enviado.');
            return Response::redirect('/shopping/market?market_list_id=' . $listId);
        }

        chmod($target, 0664);

        $repository = $this->app->make(ShoppingRepository::class);
        $id = $repository->addMarketInvoice(
            $listId,
            $originalName,
            $storedName,
            (string) ($file['type'] ?? 'application/octet-stream'),
            (int) $file['size'],
            $auth->user()?->id
        );
        $this->audit('shopping_market_invoice_uploaded', 'shopping_market_invoice', $id, [
            'list_id' => $listId,
            'original_name' => $originalName,
        ]);

        if ($extension === 'xml') {
            try {
                $summary = $this->importMarketInvoiceXml($target, $listId, $auth->user()?->id);
                $this->audit('shopping_market_invoice_imported', 'shopping_market_invoice', $id, $summary);
                $this->flash('success', sprintf(
                    'Nota anexada e XML importado: %d itens atualizados, %d itens incluidos e %d itens lidos.',
                    $summary['updated'],
                    $summary['created'],
                    $summary['read']
                ));
            } catch (\Throwable $exception) {
                $this->audit('shopping_market_invoice_import_failed', 'shopping_market_invoice', $id, [
                    'error' => $exception->getMessage(),
                ]);
                $this->flash('error', 'Nota anexada, mas nao foi possivel importar o XML: ' . $exception->getMessage());
            }

            return Response::redirect('/shopping/market?market_list_id=' . $listId);
        }

        $this->flash('success', 'Nota anexada a lista de mercado.');

        return Response::redirect('/shopping/market?market_list_id=' . $listId);
    }

    public function storeMarketAccessKey(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $listId = (int) $request->input('list_id');

        if ($listId <= 0) {
            $this->flash('error', 'Selecione uma lista de mercado antes de informar a chave.');
            return Response::redirect('/shopping/market');
        }

        try {
            $data = (new AccessKeyParser())->parse((string) $request->input('access_key'));
            $id = $this->app->make(ShoppingRepository::class)->addMarketInvoiceAccessKey($listId, $data, $auth->user()?->id);
            $this->audit('shopping_market_invoice_access_key_saved', 'shopping_market_invoice', $id, $data);
            $this->flash('success', 'Chave de acesso salva. Use o link de consulta publica para conferir a NFC-e; o XML completo depende de disponibilizacao do emissor ou certificado digital.');
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
        }

        return Response::redirect('/shopping/market?market_list_id=' . $listId);
    }

    public function addWishItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $type = (string) $request->input('type');
        $data = $this->wishPayload($request, $type);

        if ($data === null) {
            $this->flash('error', 'Preencha os campos obrigatorios da lista.');
            return Response::redirect($this->wishRedirect($type));
        }

        $id = $this->app->make(ShoppingRepository::class)->addWishItem($data, $auth->user()?->id);
        $this->audit('shopping_wish_item_created', 'shopping_wish_item', $id, ['type' => $type]);
        $this->flash('success', 'Item adicionado.');

        return Response::redirect($this->wishRedirect($type));
    }

    public function updateWishItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $type = (string) $request->input('type');
        $data = $this->wishPayload($request, $type);

        if ($id > 0 && $data !== null) {
            $this->app->make(ShoppingRepository::class)->updateWishItem($id, $data);
            $this->audit('shopping_wish_item_updated', 'shopping_wish_item', $id, ['type' => $type]);
        }

        return Response::redirect($this->wishRedirect($type));
    }

    public function toggleWishItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $purchased = (string) $request->input('purchased') === '1';
        $this->app->make(ShoppingRepository::class)->toggleWishItem($id, $purchased);
        $this->audit($purchased ? 'shopping_wish_item_purchased' : 'shopping_wish_item_reopened', 'shopping_wish_item', $id);

        return Response::redirect($this->wishRedirect((string) $request->input('type', 'home')));
    }

    public function deleteWishItem(Request $request): Response
    {
        $auth = $this->authorizeManage();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $this->app->make(ShoppingRepository::class)->deleteWishItem($id);
        $this->audit('shopping_wish_item_deleted', 'shopping_wish_item', $id);

        return Response::redirect($this->wishRedirect((string) $request->input('type', 'home')));
    }

    private function authorizeView(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::VIEW_SHOPPING)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function wishPage(string $type, string $title, string $optionLabel, string $optionField, string $optionKind, bool $hasPriority = false): Response
    {
        $auth = $this->authorizeView();

        if ($auth instanceof Response) {
            return $auth;
        }

        $repository = $this->app->make(ShoppingRepository::class);

        return Response::view('shopping/wish', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'title' => $title,
            'type' => $type,
            'optionLabel' => $optionLabel,
            'optionField' => $optionField,
            'options' => $repository->simpleOptions($optionKind, true),
            'items' => $repository->wishItems($type),
            'hasPriority' => $hasPriority,
            'vehicleAreas' => [],
        ]);
    }

    private function authorizeManage(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_SHOPPING)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function wishPayload(Request $request, string $type): ?array
    {
        if (!in_array($type, ['home', 'family', 'vehicle'], true)) {
            return null;
        }

        $name = trim((string) $request->input('name'));

        if ($name === '') {
            return null;
        }

        return [
            'type' => $type,
            'name' => $name,
            'room_id' => $type === 'home' ? $this->nullableInt($request->input('room_id')) : null,
            'person_id' => $type === 'family' ? $this->nullableInt($request->input('person_id')) : null,
            'vehicle_id' => $type === 'vehicle' ? $this->nullableInt($request->input('vehicle_id')) : null,
            'vehicle_area_id' => $type === 'vehicle' ? $this->nullableInt($request->input('vehicle_area_id')) : null,
            'estimated_amount' => $this->money((string) $request->input('estimated_amount')),
            'priority' => $type === 'home' ? max(0, min(10, (int) $request->input('priority', 0))) : null,
        ];
    }

    private function marketItemPayload(Request $request): ?array
    {
        $repository = $this->app->make(ShoppingRepository::class);
        $name = trim((string) $request->input('name'));
        $sectionId = (int) $request->input('section_id');
        $quantity = $this->decimal((string) $request->input('quantity'));
        $section = $sectionId > 0 ? $repository->simpleOption('market_sections', $sectionId, true) : null;

        if ($name === '' || $section === null || $quantity === null || $quantity <= 0) {
            return null;
        }

        $unitAmount = $this->money((string) $request->input('unit_amount'));
        $amount = $this->money((string) $request->input('amount'));
        $subtotal = $unitAmount === null ? $amount : round($quantity * $unitAmount, 2);

        return [
            'name' => $name,
            'section_id' => $sectionId,
            'section' => (string) $section['name'],
            'quantity' => $quantity,
            'unit_amount' => $unitAmount,
            'amount' => $amount,
            'subtotal_amount' => $subtotal,
        ];
    }

    private function importMarketInvoiceXml(string $file, int $listId, ?int $userId): array
    {
        $repository = $this->app->make(ShoppingRepository::class);
        $invoice = (new MarketInvoiceXmlParser())->parse($file);
        $sections = $repository->simpleOptions('market_sections', true);
        $items = $repository->marketItems($listId);
        $created = 0;
        $updated = 0;

        if ($invoice['items'] === []) {
            throw new \RuntimeException('nenhum produto encontrado no XML.');
        }

        foreach ($invoice['items'] as $invoiceItem) {
            $section = $this->sectionForProduct((string) $invoiceItem['name'], $sections);
            $match = $this->findSimilarMarketItem((string) $invoiceItem['name'], $items);
            $sectionId = isset($section['id']) && (int) $section['id'] > 0 ? (int) $section['id'] : null;
            $data = [
                'name' => $match === null ? (string) $invoiceItem['name'] : (string) $match['name'],
                'section_id' => $match === null || (int) ($match['section_id'] ?? 0) === 0 ? $sectionId : (int) $match['section_id'],
                'section' => $match === null || (string) ($match['section'] ?? '') === '' ? (string) $section['name'] : (string) $match['section'],
                'quantity' => (float) $invoiceItem['quantity'],
                'unit_amount' => $invoiceItem['unit_amount'],
                'amount' => $invoiceItem['amount'],
                'subtotal_amount' => $invoiceItem['subtotal_amount'],
            ];

            if ($match === null) {
                $newId = $repository->addMarketItem(['list_id' => $listId, ...$data], $userId);
                $repository->toggleMarketItem($newId, true);
                $items[] = ['id' => $newId, 'list_id' => $listId, 'is_checked' => 1, ...$data];
                $created++;
                continue;
            }

            $repository->updateMarketItem((int) $match['id'], $data);
            $repository->toggleMarketItem((int) $match['id'], true);
            $items = array_map(
                static fn (array $item): array => (int) $item['id'] === (int) $match['id']
                    ? [...$item, ...$data, 'is_checked' => 1]
                    : $item,
                $items
            );
            $updated++;
        }

        if ($invoice['total_amount'] !== null) {
            $repository->updateMarketListTotal($listId, (float) $invoice['total_amount']);
        }

        return [
            'read' => count($invoice['items']),
            'created' => $created,
            'updated' => $updated,
            'total_amount' => $invoice['total_amount'],
            'issuer' => $invoice['issuer'],
            'issued_at' => $invoice['issued_at'],
            'access_key' => $invoice['access_key'],
        ];
    }

    private function sectionForProduct(string $name, array $sections): array
    {
        $byName = [];

        foreach ($sections as $section) {
            $byName[$this->normalizeText((string) $section['name'])] = $section;
        }

        $normalized = $this->normalizeText($name);
        $map = [
            'LIMPEZA' => ['SABAO', 'DETERGENTE', 'AMACIANTE', 'DESINFETANTE', 'AGUA SANITARIA', 'LIMPADOR', 'ESPONJA', 'MULTIUSO', 'ALVEJANTE'],
            'CARNES' => ['CARNE', 'FRANGO', 'BOVINO', 'SUINO', 'LINGUICA', 'PEIXE', 'SALSICHA', 'HAMBURGUER', 'PICANHA', 'PATINHO', 'ACEM'],
            'ENLATADOS' => ['ENLATADO', 'MILHO', 'ERVILHA', 'SARDINHA', 'ATUM', 'EXTRATO', 'MOLHO TOMATE'],
            'BEBIDAS' => ['REFRIGERANTE', 'SUCO', 'AGUA', 'CERVEJA', 'VINHO', 'ENERGETICO', 'CHA'],
            'LEITES E DERIVADOS' => ['LEITE', 'IOGURTE', 'QUEIJO', 'REQUEIJAO', 'MANTEIGA', 'CREME DE LEITE', 'LEITE CONDENSADO'],
            'HIGIENE E BELEZA' => ['SHAMPOO', 'CONDICIONADOR', 'SABONETE', 'CREME DENTAL', 'PASTA DENTE', 'ESCOVA', 'DESODORANTE', 'ABSORVENTE', 'PAPEL HIGIENICO'],
            'BEBE E INFANTIL' => ['FRALDA', 'LENCOS UMEDECIDOS', 'MAMADEIRA', 'CHUPETA', 'BEBE', 'INFANTIL'],
            'PADARIA' => ['PAO', 'BISNAGUINHA', 'BOLO', 'TORRADA', 'SONHO', 'BROA'],
            'DOCES' => ['CHOCOLATE', 'BALA', 'DOCE', 'BISCOITO', 'BOLACHA', 'SORVETE', 'GELATINA'],
            'CASA' => ['PILHA', 'LAMPADA', 'FILTRO', 'SACO LIXO', 'PANO', 'UTENSILIO'],
            'PET' => ['RACAO', 'PET', 'CACHORRO', 'GATO', 'AREIA HIGIENICA', 'BIFINHO'],
            'COMIDAS PRONTAS' => ['LASANHA', 'PIZZA', 'CONGELADO', 'PRONTO', 'MARMITA', 'SALGADO'],
            'DESPENSA' => ['ARROZ', 'FEIJAO', 'MACARRAO', 'ACUCAR', 'CAFE', 'FARINHA', 'OLEO', 'AZEITE', 'SAL', 'TEMPERO', 'AVEIA', 'CEREAL'],
        ];

        foreach ($map as $sectionName => $keywords) {
            $section = $byName[$this->normalizeText($sectionName)] ?? null;

            if ($section === null) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $this->normalizeText($keyword))) {
                    return $section;
                }
            }
        }

        return $byName['DESPENSA'] ?? ($sections[0] ?? ['id' => null, 'name' => 'DESPENSA']);
    }

    private function findSimilarMarketItem(string $invoiceName, array $items): ?array
    {
        $invoiceNormalized = $this->normalizeText($invoiceName);
        $invoiceTokens = $this->tokens($invoiceNormalized);
        $best = null;
        $bestScore = 0.0;

        foreach ($items as $item) {
            $itemNormalized = $this->normalizeText((string) $item['name']);
            $itemTokens = $this->tokens($itemNormalized);

            if ($itemTokens === []) {
                continue;
            }

            $intersection = array_intersect($itemTokens, $invoiceTokens);
            $tokenScore = count($intersection) / max(1, count($itemTokens));
            similar_text($itemNormalized, $invoiceNormalized, $similarity);
            $containsBonus = str_contains($invoiceNormalized, $itemNormalized) || str_contains($itemNormalized, $invoiceNormalized) ? 0.35 : 0.0;
            $score = ($tokenScore * 0.65) + (($similarity / 100) * 0.25) + $containsBonus;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $item;
            }
        }

        return $bestScore >= 0.72 ? $best : null;
    }

    private function tokens(string $normalized): array
    {
        $ignored = ['KG', 'G', 'GR', 'ML', 'L', 'LT', 'UN', 'UND', 'PCT', 'PC', 'PACOTE', 'CAIXA', 'CX', 'FD', 'FARDO', 'TIPO'];
        $tokens = preg_split('/\s+/', $normalized) ?: [];

        return array_values(array_filter($tokens, static function (string $token) use ($ignored): bool {
            return mb_strlen($token) >= 3
                && !is_numeric($token)
                && !in_array($token, $ignored, true)
                && !preg_match('/^\d+(KG|G|ML|L|UN)$/', $token);
        }));
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtoupper($value, 'UTF-8');
        $value = strtr($value, [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
        ]);
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? $value;

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function money(string $value): ?float
    {
        $normalized = str_replace(['.', ','], ['', '.'], trim($value));

        return $normalized === '' ? null : (float) $normalized;
    }

    private function decimal(string $value): ?float
    {
        $normalized = str_replace(',', '.', trim($value));

        return $normalized === '' ? null : (float) $normalized;
    }

    private function wishRedirect(string $type): string
    {
        return match ($type) {
            'family' => '/shopping/family',
            'vehicle' => '/shopping/vehicle',
            default => '/shopping/home',
        };
    }

    private function flash(string $key, string $message): void
    {
        $this->app->make(Session::class)->flash($key, $message);
    }

    private function audit(string $action, string $entityType, ?int $entityId = null, array $metadata = []): void
    {
        $auth = $this->app->make(AuthService::class);
        $this->app->make(AuditLogger::class)->log($action, $entityType, $entityId, $auth->user(), $metadata);
    }
}
