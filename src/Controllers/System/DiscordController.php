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
use App\Domain\System\DiscordNotificationRepository;
use App\Domain\System\DiscordNotifier;

final class DiscordController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        return Response::view('system/discord/index', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'settings' => $this->app->make(DiscordNotificationRepository::class)->settings(),
        ]);
    }

    public function save(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $enabled = (string) $request->input('is_enabled') === '1';
        $webhookUrl = trim((string) $request->input('webhook_url'));
        $notifyMarketListCreated = (string) $request->input('notify_market_list_created') === '1';

        if ($enabled && $webhookUrl === '') {
            $this->flash('error', 'Informe o webhook do Discord para ativar as notificações.');
            return Response::redirect('/system/discord');
        }

        if ($webhookUrl !== '' && !str_starts_with($webhookUrl, 'https://discord.com/api/webhooks/')) {
            $this->flash('error', 'Webhook inválido. Use uma URL oficial do Discord.');
            return Response::redirect('/system/discord');
        }

        $this->app->make(DiscordNotificationRepository::class)->save(
            $enabled,
            $webhookUrl === '' ? null : $webhookUrl,
            $notifyMarketListCreated
        );
        $this->app->make(AuditLogger::class)->log('discord_settings_updated', 'discord_settings', 1, $auth->user(), [
            'enabled' => $enabled,
            'notify_market_list_created' => $notifyMarketListCreated,
        ]);
        $this->flash('success', 'Configurações do Discord salvas.');

        return Response::redirect('/system/discord');
    }

    public function test(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $settings = $this->app->make(DiscordNotificationRepository::class)->settings();
        $webhookUrl = trim((string) ($settings['webhook_url'] ?? ''));

        if ($webhookUrl === '') {
            $this->flash('error', 'Salve um webhook do Discord antes de enviar o teste.');
            return Response::redirect('/system/discord');
        }

        $sent = $this->app->make(DiscordNotifier::class)->testWebhook($webhookUrl);
        $this->app->make(AuditLogger::class)->log('discord_webhook_tested', 'discord_settings', 1, $auth->user(), [
            'success' => $sent,
        ]);
        $this->flash(
            $sent ? 'success' : 'error',
            $sent ? 'Teste enviado para o Discord.' : 'Não foi possível enviar o teste para o Discord. Confira o webhook salvo.'
        );

        return Response::redirect('/system/discord');
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_DISCORD_NOTIFICATIONS)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function flash(string $type, string $message): void
    {
        $this->app->make(Session::class)->flash($type, $message);
    }
}
