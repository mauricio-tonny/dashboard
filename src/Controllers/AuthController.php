<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\LoginAttemptRepository;
use App\Domain\System\RecaptchaVerifier;
use App\Domain\System\SecuritySettingsRepository;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        $session = $this->app->make(Session::class);

        return Response::view('auth/login', [
            'error' => $session->pullFlash('error'),
        ]);
    }

    public function login(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $audit = $this->app->make(AuditLogger::class);
        $attempts = $this->app->make(LoginAttemptRepository::class);
        $email = (string) $request->input('email', '');
        $ipAddress = request_ip($_SERVER);
        $userAgent = (string) $request->server('HTTP_USER_AGENT', '');

        if ($attempts->isBlocked($email, $ipAddress)) {
            $audit->log('login_blocked', 'auth', null, null, [
                'email' => $email,
                'ip' => $ipAddress,
                'user_agent' => mb_substr($userAgent, 0, 255),
                'reason' => 'too_many_failed_attempts',
            ]);

            return Response::view('auth/login', [
                'error' => 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.',
            ], 429);
        }

        $securitySettings = $this->app->make(SecuritySettingsRepository::class)->settings();
        $recaptchaResult = $this->app->make(RecaptchaVerifier::class)->verify(
            (string) $request->input('recaptcha_token', ''),
            'login',
            $ipAddress,
            $securitySettings
        );

        if (!($recaptchaResult['success'] ?? false)) {
            $attempts->record($email, $ipAddress, $userAgent, false);
            $audit->log('login_failed', 'auth', null, null, [
                'email' => $email,
                'ip' => $ipAddress,
                'user_agent' => mb_substr($userAgent, 0, 255),
                'reason' => 'recaptcha_failed',
                'recaptcha_score' => $recaptchaResult['score'] ?? null,
                'recaptcha_action' => $recaptchaResult['action'] ?? null,
            ]);

            return Response::view('auth/login', [
                'error' => 'Não foi possível validar a segurança do login. Tente novamente.',
            ], 422);
        }

        $success = $auth->attempt(
            $email,
            (string) $request->input('password', '')
        );

        $attempts->record($email, $ipAddress, $userAgent, $success);

        if (!$success) {
            $audit->log('login_failed', 'auth', null, null, [
                'email' => $email,
                'ip' => $ipAddress,
                'user_agent' => mb_substr($userAgent, 0, 255),
                'reason' => 'invalid_credentials',
            ]);

            return Response::view('auth/login', [
                'error' => 'Credenciais inválidas.',
            ], 422);
        }

        $audit->log('login_success', 'auth', null, $auth->user(), [
            'ip' => $ipAddress,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'recaptcha_score' => $recaptchaResult['score'] ?? null,
        ]);

        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);
        $user = $auth->user();

        $this->app->make(AuditLogger::class)->log('logout', 'auth', null, $user, [
            'ip' => request_ip($_SERVER),
            'user_agent' => mb_substr((string) $request->server('HTTP_USER_AGENT', ''), 0, 255),
        ]);
        $auth->logout();

        return Response::redirect('/login');
    }
}
