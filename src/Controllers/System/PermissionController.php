<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AdminUserRepository;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use App\Domain\Auth\RolePermissionMap;
use App\Domain\Auth\User;
use App\Domain\Auth\UserPermissionRepository;

final class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $currentUser = $this->authorize($auth);

        if ($currentUser instanceof Response) {
            return $currentUser;
        }

        $repository = $this->app->make(UserPermissionRepository::class);
        $users = $repository->users();
        $selectedUserId = (int) $request->input('user_id', $users[0]['id'] ?? 0);
        $selectedUser = $selectedUserId > 0 ? $this->app->make(AdminUserRepository::class)->find($selectedUserId) : null;

        if ($selectedUser === null && $users !== []) {
            $selectedUser = $users[0];
            $selectedUserId = (int) $selectedUser['id'];
        }

        $session = $this->app->make(Session::class);

        return Response::view('system/permissions/index', [
            'user' => $currentUser,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'selectedUserId' => $selectedUserId,
            'permissions' => $repository->permissions(),
            'overrides' => $selectedUserId > 0 ? $repository->overridesByUser($selectedUserId) : [],
            'rolePermissions' => $selectedUser === null ? [] : array_map(
                static fn (Permission $permission): string => $permission->value,
                RolePermissionMap::permissionsFor(Role::from((string) $selectedUser['role']))
            ),
            'success' => $session->pullFlash('success'),
            'error' => $session->pullFlash('error'),
        ]);
    }

    public function save(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $currentUser = $this->authorize($auth);

        if ($currentUser instanceof Response) {
            return $currentUser;
        }

        $session = $this->app->make(Session::class);
        $targetUserId = (int) $request->input('user_id');
        $targetUser = $this->app->make(AdminUserRepository::class)->find($targetUserId);

        if ($targetUser === null) {
            $session->flash('error', 'Usuario nao encontrado.');
            return Response::redirect('/system/permissions');
        }

        if (!$auth->verifyPassword($currentUser, (string) $request->input('admin_password'))) {
            $session->flash('error', 'Senha de confirmacao invalida.');
            return Response::redirect('/system/permissions?user_id=' . $targetUserId);
        }

        $effects = is_array($request->input('effects')) ? $request->input('effects') : [];
        $effects = array_filter(
            $effects,
            static fn (mixed $effect): bool => in_array($effect, ['allow', 'deny'], true)
        );

        if ($currentUser->id === $targetUserId) {
            foreach ([Permission::MANAGE_USERS, Permission::CHANGE_USER_ROLES] as $criticalPermission) {
                if (($effects[$criticalPermission->value] ?? null) === 'deny') {
                    $session->flash('error', 'Voce nao pode bloquear as proprias permissoes administrativas criticas.');
                    return Response::redirect('/system/permissions?user_id=' . $targetUserId);
                }
            }
        }

        $this->app->make(UserPermissionRepository::class)->saveOverrides($targetUserId, $effects);
        $this->app->make(AuditLogger::class)->log('user_permissions_updated', 'user', $targetUserId, $currentUser, [
            'target_email' => $targetUser['email'] ?? null,
            'overrides' => $effects,
        ]);

        $session->flash('success', 'Permissoes atualizadas com sucesso.');

        return Response::redirect('/system/permissions?user_id=' . $targetUserId);
    }

    private function authorize(AuthService $auth): User|Response
    {
        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        $user = $auth->user();

        if (!$user?->can(Permission::CHANGE_USER_ROLES)) {
            return new Response('Acesso negado.', 403);
        }

        return $user;
    }
}
