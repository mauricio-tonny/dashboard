<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
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
        $name = trim((string) $request->input('name'));
        $section = trim((string) $request->input('section'));

        if ($listId <= 0 || $name === '' || $section === '') {
            $this->flash('error', 'Informe item e sessao.');
            return Response::redirect('/shopping/market?market_list_id=' . $listId);
        }

        $id = $this->app->make(ShoppingRepository::class)->addMarketItem($listId, $name, $section, $auth->user()?->id);
        $this->audit('shopping_market_item_created', 'shopping_market_item', $id, ['name' => $name]);
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
        $name = trim((string) $request->input('name'));
        $section = trim((string) $request->input('section'));

        if ($id > 0 && $name !== '' && $section !== '') {
            $this->app->make(ShoppingRepository::class)->updateMarketItem($id, $name, $section);
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

        $storedName = $listId . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
        $target = $directory . '/' . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            $this->flash('error', 'Nao foi possivel salvar o arquivo enviado.');
            return Response::redirect('/shopping/market?market_list_id=' . $listId);
        }

        $id = $this->app->make(ShoppingRepository::class)->addMarketInvoice(
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
        $this->flash('success', 'Nota anexada a lista de mercado.');

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
