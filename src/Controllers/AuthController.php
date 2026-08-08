<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return Response::view('auth/login', [
            'error' => null,
        ]);
    }

    public function login(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $audit = $this->app->make(AuditLogger::class);
        $email = (string) $request->input('email', '');

        $success = $auth->attempt(
            $email,
            (string) $request->input('password', '')
        );

        if (!$success) {
            $audit->log('login_failed', 'auth', null, null, [
                'email' => $email,
            ]);

            return Response::view('auth/login', [
                'error' => 'Credenciais invalidas.',
            ], 422);
        }

        $audit->log('login_success', 'auth', null, $auth->user());

        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $user = $auth->user();

        $this->app->make(AuditLogger::class)->log('logout', 'auth', null, $user);
        $auth->logout();

        return Response::redirect('/login');
    }
}
