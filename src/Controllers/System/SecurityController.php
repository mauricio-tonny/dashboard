<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\System\SecuritySettingsRepository;

final class SecurityController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $session = $this->app->make(Session::class);

        return Response::view('system/security/index', [
            'user' => $auth->user(),
            'settings' => $this->app->make(SecuritySettingsRepository::class)->settings(),
            'success' => $session->pullFlash('success'),
            'error' => $session->pullFlash('error'),
        ]);
    }

    public function save(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $session = $this->app->make(Session::class);
        $repository = $this->app->make(SecuritySettingsRepository::class);
        $before = $repository->settings();
        $recaptchaEnabled = (string) $request->input('recaptcha_is_enabled') === '1';
        $recaptchaSiteKey = trim((string) $request->input('recaptcha_site_key'));
        $recaptchaSecretKey = trim((string) $request->input('recaptcha_secret_key'));
        $keepRecaptchaSecretKey = (string) $request->input('keep_recaptcha_secret_key') === '1';
        $recaptchaMinScore = (float) str_replace(',', '.', (string) $request->input('recaptcha_min_score', '0.5'));
        $analyticsEnabled = (string) $request->input('analytics_is_enabled') === '1';
        $analyticsMeasurementId = strtoupper(trim((string) $request->input('analytics_measurement_id')));

        if ($recaptchaEnabled && ($recaptchaSiteKey === '' || (!$keepRecaptchaSecretKey && $recaptchaSecretKey === ''))) {
            $session->flash('error', 'Informe a Site Key e a Secret Key para ativar o reCAPTCHA.');
            return Response::redirect('/system/security');
        }

        if ($recaptchaMinScore < 0 || $recaptchaMinScore > 1) {
            $session->flash('error', 'O score mínimo do reCAPTCHA deve ficar entre 0 e 1.');
            return Response::redirect('/system/security');
        }

        if ($analyticsEnabled && !SecuritySettingsRepository::isValidMeasurementId($analyticsMeasurementId)) {
            $session->flash('error', 'Informe um Measurement ID válido do GA4, como G-XXXXXXXXXX.');
            return Response::redirect('/system/security');
        }

        $repository->save([
            'recaptcha_is_enabled' => $recaptchaEnabled,
            'recaptcha_site_key' => $recaptchaSiteKey,
            'recaptcha_secret_key' => $recaptchaSecretKey,
            'keep_recaptcha_secret_key' => $keepRecaptchaSecretKey,
            'recaptcha_min_score' => $recaptchaMinScore,
            'analytics_is_enabled' => $analyticsEnabled,
            'analytics_measurement_id' => $analyticsMeasurementId,
        ]);

        $after = $repository->settings();
        $this->app->make(AuditLogger::class)->log('security_settings_updated', 'security_settings', 1, $auth->user(), [
            'ip' => request_ip($_SERVER),
            'recaptcha_enabled_before' => (bool) ((int) ($before['recaptcha_is_enabled'] ?? 0)),
            'recaptcha_enabled_after' => (bool) ((int) ($after['recaptcha_is_enabled'] ?? 0)),
            'recaptcha_site_key_changed' => (string) ($before['recaptcha_site_key'] ?? '') !== (string) ($after['recaptcha_site_key'] ?? ''),
            'recaptcha_secret_key_changed' => $recaptchaSecretKey !== '',
            'recaptcha_min_score_before' => (string) ($before['recaptcha_min_score'] ?? ''),
            'recaptcha_min_score_after' => (string) ($after['recaptcha_min_score'] ?? ''),
            'analytics_enabled_before' => (bool) ((int) ($before['analytics_is_enabled'] ?? 0)),
            'analytics_enabled_after' => (bool) ((int) ($after['analytics_is_enabled'] ?? 0)),
            'analytics_measurement_id_before' => (string) ($before['analytics_measurement_id'] ?? ''),
            'analytics_measurement_id_after' => (string) ($after['analytics_measurement_id'] ?? ''),
        ]);

        $session->flash('success', 'Configurações de segurança e Analytics salvas.');

        return Response::redirect('/system/security');
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_SECURITY_SETTINGS)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }
}
