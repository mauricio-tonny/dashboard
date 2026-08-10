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
use App\Domain\System\SpreadsheetLinkTester;
use App\Domain\System\SpreadsheetSettingsRepository;
use App\Support\Encryption;
use Throwable;

final class SpreadsheetController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        try {
            $settings = $this->app->make(SpreadsheetSettingsRepository::class)->settings();
        } catch (Throwable $exception) {
            $settings = [
                'has_url' => false,
                'masked_url' => '',
            ];
            $this->flash('error', $exception->getMessage());
        }

        return Response::view('system/spreadsheet/index', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'settings' => $settings,
            'encryptionReady' => Encryption::isConfigured(),
        ]);
    }

    public function save(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $url = trim((string) $request->input('spreadsheet_url'));

        if ($url === '') {
            $this->flash('error', 'Informe o link compartilhado da planilha.');
            return Response::redirect('/system/spreadsheet');
        }

        if (!$this->isValidUrl($url)) {
            $this->flash('error', 'Informe uma URL válida começando com http:// ou https://.');
            return Response::redirect('/system/spreadsheet');
        }

        try {
            $this->app->make(SpreadsheetSettingsRepository::class)->save($url);
        } catch (Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            return Response::redirect('/system/spreadsheet');
        }

        $this->app->make(AuditLogger::class)->log('spreadsheet_url_saved', 'spreadsheet_settings', 1, $auth->user(), [
            'url_hash' => hash('sha256', $url),
            'masked_url' => SpreadsheetSettingsRepository::maskUrl($url),
        ]);
        $this->flash('success', 'Link da planilha salvo com segurança.');

        return Response::redirect('/system/spreadsheet');
    }

    public function test(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        try {
            $settings = $this->app->make(SpreadsheetSettingsRepository::class)->settings();
        } catch (Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            return Response::redirect('/system/spreadsheet');
        }

        $url = trim((string) ($settings['url'] ?? ''));

        if ($url === '') {
            $this->flash('error', 'Salve o link da planilha antes de testar.');
            return Response::redirect('/system/spreadsheet');
        }

        $result = $this->app->make(SpreadsheetLinkTester::class)->test($url);
        $this->app->make(AuditLogger::class)->log('spreadsheet_url_tested', 'spreadsheet_settings', 1, $auth->user(), [
            'success' => (bool) $result['success'],
            'url_hash' => hash('sha256', $url),
        ]);
        $this->flash((bool) $result['success'] ? 'success' : 'error', (string) $result['message']);

        return Response::redirect('/system/spreadsheet');
    }

    public function remove(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $this->app->make(SpreadsheetSettingsRepository::class)->remove();
        $this->app->make(AuditLogger::class)->log('spreadsheet_url_removed', 'spreadsheet_settings', 1, $auth->user());
        $this->flash('success', 'Link da planilha removido.');

        return Response::redirect('/system/spreadsheet');
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_SPREADSHEET_URL)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function isValidUrl(string $url): bool
    {
        if (strlen($url) > 2000 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    private function flash(string $type, string $message): void
    {
        $this->app->make(Session::class)->flash($type, $message);
    }
}
