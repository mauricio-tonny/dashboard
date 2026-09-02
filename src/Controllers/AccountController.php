<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AdminUserRepository;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\User;

final class AccountController extends Controller
{
    public function password(Request $request): Response
    {
        $user = $this->authorize();

        if ($user instanceof Response) {
            return $user;
        }

        $session = $this->app->make(Session::class);

        return Response::view('account/password', [
            'user' => $user,
            'success' => $session->pullFlash('success'),
            'error' => $session->pullFlash('error'),
        ]);
    }

    public function updatePassword(Request $request): Response
    {
        $user = $this->authorize();

        if ($user instanceof Response) {
            return $user;
        }

        $session = $this->app->make(Session::class);
        $auth = $this->app->make(AuthService::class);
        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');
        $confirmation = (string) $request->input('new_password_confirmation');

        if (!$auth->verifyPassword($user, $currentPassword)) {
            $session->flash('error', 'Senha atual inválida.');
            return Response::redirect('/account/password');
        }

        if (mb_strlen($newPassword) < 8) {
            $session->flash('error', 'A nova senha deve ter pelo menos 8 caracteres.');
            return Response::redirect('/account/password');
        }

        if ($newPassword !== $confirmation) {
            $session->flash('error', 'A confirmação da nova senha não confere.');
            return Response::redirect('/account/password');
        }

        if ($user->id === null) {
            $session->flash('error', 'Não foi possível identificar o usuário autenticado.');
            return Response::redirect('/account/password');
        }

        $this->app->make(AdminUserRepository::class)->updatePassword($user->id, $newPassword);
        $this->app->make(AuditLogger::class)->log('password_changed', 'user', $user->id, $user, [
            'ip' => request_ip($_SERVER),
            'user_agent' => mb_substr((string) $request->server('HTTP_USER_AGENT', ''), 0, 255),
        ]);
        $session->flash('success', 'Senha alterada com sucesso.');

        return Response::redirect('/account/password');
    }

    private function authorize(): User|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        $user = $auth->user();

        return $user ?? Response::redirect('/login');
    }
}
