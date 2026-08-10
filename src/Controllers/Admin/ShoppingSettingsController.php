<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Shopping\ShoppingRepository;
use PDOException;

final class ShoppingSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $repository = $this->app->make(ShoppingRepository::class);
        $session = $this->app->make(Session::class);

        return Response::view('admin/shopping-settings/index', [
            'user' => $auth->user(),
            'success' => $session->pullFlash('success'),
            'error' => $session->pullFlash('error'),
            'marketSections' => $repository->simpleOptions('market_sections'),
            'rooms' => $repository->simpleOptions('rooms'),
            'people' => $repository->simpleOptions('people'),
            'vehicleAreas' => $repository->simpleOptions('vehicle_areas'),
            'vehicles' => $repository->vehicles(),
            'brands' => $this->brands(),
        ]);
    }

    public function saveSimple(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $kind = (string) $request->input('kind');
        $id = $this->nullableInt($request->input('id'));
        $name = mb_strtoupper(trim((string) $request->input('name')));
        $allowed = ['market_sections', 'rooms', 'people', 'vehicle_areas'];

        if (!in_array($kind, $allowed, true) || $name === '') {
            $this->flash('error', 'Cadastro inválido.');
            return Response::redirect('/admin/shopping-settings');
        }

        try {
            $savedId = $this->app->make(ShoppingRepository::class)->saveSimpleOption($kind, $id, $name);
            $this->audit('shopping_setting_saved', 'shopping_' . $kind, $savedId, ['name' => $name]);
            $this->flash('success', 'Configuração salva.');
        } catch (PDOException) {
            $this->flash('error', 'Não foi possível salvar. Verifique se o nome já existe.');
        }

        return Response::redirect('/admin/shopping-settings');
    }

    public function toggleSimple(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $kind = (string) $request->input('kind');
        $id = (int) $request->input('id');
        $active = (string) $request->input('active') === '1';
        $allowed = ['market_sections', 'rooms', 'people', 'vehicle_areas'];

        if (in_array($kind, $allowed, true) && $id > 0) {
            $this->app->make(ShoppingRepository::class)->setSimpleOptionActive($kind, $id, $active);
            $this->audit($active ? 'shopping_setting_enabled' : 'shopping_setting_disabled', 'shopping_' . $kind, $id);
            $this->flash('success', $active ? 'Cadastro reativado.' : 'Cadastro desativado.');
        }

        return Response::redirect('/admin/shopping-settings');
    }

    public function saveVehicle(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = $this->nullableInt($request->input('id'));
        $data = [
            'name' => mb_strtoupper(trim((string) $request->input('name'))),
            'model' => trim((string) $request->input('model')) ?: null,
            'brand' => trim((string) $request->input('brand')) ?: null,
            'model_year' => $this->nullableInt($request->input('model_year')),
            'manufacture_year' => $this->nullableInt($request->input('manufacture_year')),
            'renavam' => trim((string) $request->input('renavam')) ?: null,
            'plate' => mb_strtoupper(trim((string) $request->input('plate'))) ?: null,
        ];

        if ($data['name'] === '') {
            $this->flash('error', 'Informe o nome do veículo.');
            return Response::redirect('/admin/shopping-settings');
        }

        try {
            $savedId = $this->app->make(ShoppingRepository::class)->saveVehicle($id, $data);
            $this->audit('shopping_vehicle_saved', 'shopping_vehicle', $savedId, ['name' => $data['name']]);
            $this->flash('success', 'Veículo salvo.');
        } catch (PDOException) {
            $this->flash('error', 'Não foi possível salvar o veículo. Verifique se o nome já existe.');
        }

        return Response::redirect('/admin/shopping-settings');
    }

    public function toggleVehicle(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $active = (string) $request->input('active') === '1';

        if ($id > 0) {
            $this->app->make(ShoppingRepository::class)->setVehicleActive($id, $active);
            $this->audit($active ? 'shopping_vehicle_enabled' : 'shopping_vehicle_disabled', 'shopping_vehicle', $id);
            $this->flash('success', $active ? 'Veículo reativado.' : 'Veículo desativado.');
        }

        return Response::redirect('/admin/shopping-settings');
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_SHOPPING_SETTINGS)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = (int) $value;

        return $int > 0 ? $int : null;
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

    private function brands(): array
    {
        return [
            'Chevrolet',
            'Fiat',
            'Ford',
            'Honda',
            'Hyundai',
            'Jeep',
            'Nissan',
            'Renault',
            'Toyota',
            'Volkswagen',
        ];
    }
}
