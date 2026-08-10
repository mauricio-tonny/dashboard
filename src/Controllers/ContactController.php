<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Contacts\ContactRepository;
use PDOException;

final class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_CONTACTS);

        if ($auth instanceof Response) {
            return $auth;
        }

        $type = (string) $request->input('type', '');
        $type = in_array($type, ['vendor', 'client'], true) ? $type : null;
        $session = $this->app->make(Session::class);

        return Response::view('contacts/index', [
            'user' => $auth->user(),
            'contacts' => $this->app->make(ContactRepository::class)->all($type),
            'selectedType' => $type,
            'states' => $this->states(),
            'success' => $session->pullFlash('success'),
            'error' => $session->pullFlash('error'),
        ]);
    }

    public function store(Request $request): Response
    {
        return $this->save($request);
    }

    public function update(Request $request): Response
    {
        return $this->save($request, (int) $request->input('id'));
    }

    public function toggle(Request $request): Response
    {
        $auth = $this->authorize(Permission::MANAGE_CONTACTS);

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $active = (string) $request->input('active') === '1';
        $this->app->make(ContactRepository::class)->setActive($id, $active);
        $this->audit($active ? 'contact_enabled' : 'contact_disabled', $id);
        $this->app->make(Session::class)->flash('success', $active ? 'Contato reativado.' : 'Contato desativado.');

        return Response::redirect('/contacts');
    }

    private function save(Request $request, ?int $id = null): Response
    {
        $auth = $this->authorize(Permission::MANAGE_CONTACTS);

        if ($auth instanceof Response) {
            return $auth;
        }

        $data = [
            'is_vendor' => (string) $request->input('is_vendor') === '1' ? 1 : 0,
            'is_client' => (string) $request->input('is_client') === '1' ? 1 : 0,
            'first_name' => trim((string) $request->input('first_name')),
            'last_name' => trim((string) $request->input('last_name')) ?: null,
            'document' => trim((string) $request->input('document')) ?: null,
            'phone' => trim((string) $request->input('phone')) ?: null,
            'email' => trim((string) $request->input('email')) ?: null,
            'address' => trim((string) $request->input('address')) ?: null,
            'city' => trim((string) $request->input('city')),
            'state' => mb_strtoupper(trim((string) $request->input('state'))),
        ];
        $data['type'] = $data['is_vendor'] === 1 ? 'vendor' : 'client';

        if (($data['is_vendor'] === 0 && $data['is_client'] === 0) || $data['first_name'] === '' || $data['city'] === '' || !in_array($data['state'], $this->states(), true)) {
            $this->app->make(Session::class)->flash('error', 'Preencha nome, classificação, cidade e UF corretamente.');
            return Response::redirect('/contacts');
        }

        try {
            $savedId = $this->app->make(ContactRepository::class)->save($id, $data, $auth->user()?->id);
            $this->audit($id === null ? 'contact_created' : 'contact_updated', $savedId, [
                'is_vendor' => $data['is_vendor'],
                'is_client' => $data['is_client'],
                'name' => $data['first_name'],
            ]);
            $this->app->make(Session::class)->flash('success', $id === null ? 'Contato criado.' : 'Contato atualizado.');
        } catch (PDOException) {
            $this->app->make(Session::class)->flash('error', 'Não foi possível salvar o contato.');
        }

        return Response::redirect('/contacts');
    }

    private function authorize(Permission $permission): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can($permission)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function audit(string $action, ?int $entityId = null, array $metadata = []): void
    {
        $auth = $this->app->make(AuthService::class);
        $this->app->make(AuditLogger::class)->log($action, 'contact', $entityId, $auth->user(), $metadata);
    }

    private function states(): array
    {
        return ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    }
}
