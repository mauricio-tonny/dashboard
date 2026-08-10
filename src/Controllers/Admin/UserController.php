<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AdminUserRepository;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use App\Domain\Auth\User;
use PDOException;
use ValueError;

final class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $currentUser = $this->authorize($auth);

        if ($currentUser instanceof Response) {
            return $currentUser;
        }

        $session = $this->app->make(Session::class);

        return Response::view('admin/users/index', [
            'user' => $currentUser,
            'users' => $this->app->make(AdminUserRepository::class)->all(),
            'roles' => Role::cases(),
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

    public function toggleStatus(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $currentUser = $this->authorize($auth);

        if ($currentUser instanceof Response) {
            return $currentUser;
        }

        $id = (int) $request->input('id');
        $active = (string) $request->input('active') === '1';
        $session = $this->app->make(Session::class);

        if ($currentUser->id === $id && !$active) {
            $session->flash('error', 'Você não pode bloquear o próprio usuário.');
            return Response::redirect('/admin/users');
        }

        if (!$auth->verifyPassword($currentUser, (string) $request->input('admin_password'))) {
            $session->flash('error', 'Senha de confirmação inválida.');
            return Response::redirect('/admin/users');
        }

        $repository = $this->app->make(AdminUserRepository::class);
        $target = $repository->find($id);

        if ($target === null) {
            $session->flash('error', 'Usuário não encontrado.');
            return Response::redirect('/admin/users');
        }

        $repository->setActive($id, $active);
        $this->app->make(AuditLogger::class)->log($active ? 'user_unblocked' : 'user_blocked', 'user', $id, $currentUser, [
            'target_email' => $target['email'],
        ]);

        $session->flash('success', $active ? 'Usuário desbloqueado com sucesso.' : 'Usuário bloqueado com sucesso.');

        return Response::redirect('/admin/users');
    }

    private function save(Request $request, ?int $id = null): Response
    {
        $auth = $this->app->make(AuthService::class);
        $currentUser = $this->authorize($auth);

        if ($currentUser instanceof Response) {
            return $currentUser;
        }

        $session = $this->app->make(Session::class);
        $name = trim((string) $request->input('name'));
        $email = mb_strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        try {
            $role = Role::from((string) $request->input('role'));
        } catch (ValueError) {
            $session->flash('error', 'Perfil informado é inválido.');
            return Response::redirect('/admin/users');
        }

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $session->flash('error', 'Informe nome e e-mail valido.');
            return Response::redirect('/admin/users');
        }

        if ($id === null && $password === '') {
            $session->flash('error', 'Informe uma senha temporária para criar o usuário.');
            return Response::redirect('/admin/users');
        }

        if (!$auth->verifyPassword($currentUser, (string) $request->input('admin_password'))) {
            $session->flash('error', 'Senha de confirmação inválida.');
            return Response::redirect('/admin/users');
        }

        $repository = $this->app->make(AdminUserRepository::class);
        $before = $id === null ? null : $repository->find($id);

        try {
            if ($id === null) {
                $newUserId = $repository->create($name, $email, $role, $password);
                $this->app->make(AuditLogger::class)->log('user_created', 'user', $newUserId, $currentUser, [
                    'target_email' => $email,
                    'role' => $role->value,
                ]);
                $session->flash('success', 'Usuário criado com sucesso.');
            } else {
                $repository->update($id, $name, $email, $role, $password);
                $this->app->make(AuditLogger::class)->log('user_updated', 'user', $id, $currentUser, [
                    'target_email' => $email,
                    'previous_role' => $before['role'] ?? null,
                    'role' => $role->value,
                    'password_changed' => $password !== '',
                ]);
                $session->flash('success', 'Usuário atualizado com sucesso.');
            }
        } catch (PDOException) {
            $session->flash('error', 'Não foi possível salvar. Verifique se o e-mail já está em uso.');
        }

        return Response::redirect('/admin/users');
    }

    private function authorize(AuthService $auth): User|Response
    {
        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        $user = $auth->user();

        if (!$user?->can(Permission::MANAGE_USERS)) {
            return new Response('Acesso negado.', 403);
        }

        return $user;
    }
}
