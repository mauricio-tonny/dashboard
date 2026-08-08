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
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::VIEW_SHOPPING)) {
            return new Response('Acesso negado.', 403);
        }

        $repository = $this->app->make(ShoppingRepository::class);
        $selectedListId = (int) $request->input('market_list_id', 0);
        $lists = $repository->marketLists();

        if ($selectedListId === 0 && $lists !== []) {
            $selectedListId = (int) $lists[0]['id'];
        }

        $selectedList = $selectedListId > 0 ? $repository->marketList($selectedListId) : null;

        return Response::view('shopping/index', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'nextMonth' => $repository->nextMonth(),
            'marketLists' => $lists,
            'selectedMarketList' => $selectedList,
            'marketItems' => $selectedList === null ? [] : $repository->marketItems((int) $selectedList['id']),
            'homeItems' => $repository->wishItems('home'),
            'familyItems' => $repository->wishItems('family'),
            'vehicleItems' => $repository->wishItems('vehicle'),
            'rooms' => $repository->simpleOptions('rooms', true),
            'people' => $repository->simpleOptions('people', true),
            'vehicles' => $repository->vehicles(true),
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

        return Response::redirect('/shopping?market_list_id=' . $id);
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
            return Response::redirect('/shopping?market_list_id=' . $listId);
        }

        $id = $this->app->make(ShoppingRepository::class)->addMarketItem($listId, $name, $section, $auth->user()?->id);
        $this->audit('shopping_market_item_created', 'shopping_market_item', $id, ['name' => $name]);
        $this->flash('success', 'Item adicionado ao mercado.');

        return Response::redirect('/shopping?market_list_id=' . $listId);
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

        return Response::redirect('/shopping?market_list_id=' . $listId);
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

        return Response::redirect('/shopping?market_list_id=' . $listId);
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

        return Response::redirect('/shopping?market_list_id=' . $listId);
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

        return Response::redirect('/shopping?market_list_id=' . $id);
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
            return Response::redirect('/shopping');
        }

        $id = $this->app->make(ShoppingRepository::class)->addWishItem($data, $auth->user()?->id);
        $this->audit('shopping_wish_item_created', 'shopping_wish_item', $id, ['type' => $type]);
        $this->flash('success', 'Item adicionado.');

        return Response::redirect('/shopping');
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

        return Response::redirect('/shopping');
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

        return Response::redirect('/shopping');
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

        return Response::redirect('/shopping');
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
