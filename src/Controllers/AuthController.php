<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
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

        $success = $auth->attempt(
            (string) $request->input('email', ''),
            (string) $request->input('password', '')
        );

        if (!$success) {
            return Response::view('auth/login', [
                'error' => 'Credenciais invalidas.',
            ], 422);
        }

        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        $this->app->make(AuthService::class)->logout();

        return Response::redirect('/login');
    }
}

