<?php

declare(strict_types=1);

namespace App\Domain\System;

final class DiscordNotifier
{
    public function __construct(private DiscordNotificationRepository $repository)
    {
    }

    public function marketListCreated(string $monthLabel, bool $automatic): void
    {
        $settings = $this->repository->settings();

        if (
            ((int) ($settings['is_enabled'] ?? 0)) !== 1
            || ((int) ($settings['notify_market_list_created'] ?? 0)) !== 1
        ) {
            return;
        }

        $webhookUrl = trim((string) ($settings['webhook_url'] ?? ''));

        if ($webhookUrl === '') {
            return;
        }

        $origin = $automatic ? 'automaticamente' : 'pelo painel';
        $this->send($webhookUrl, [
            'username' => 'Dashboard Oficina do DEV',
            'content' => "Lista de mercado de {$monthLabel} criada {$origin}. Já deixei tudo pronto para voces planejarem as compras com calma.",
        ]);
    }

    public function testWebhook(string $webhookUrl): bool
    {
        return $this->send($webhookUrl, [
            'username' => 'Dashboard Oficina do DEV',
            'content' => 'Teste de webhook do Discord enviado pelo painel do dashboard.',
        ]);
    }

    private function send(string $webhookUrl, array $payload): bool
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
                'timeout' => 4,
                'ignore_errors' => true,
            ],
        ]);

        @file_get_contents($webhookUrl, false, $context);
        $headers = $http_response_header ?? [];
        $statusLine = $headers[0] ?? '';

        return preg_match('/^HTTP\/\S+\s+2\d\d\b/', $statusLine) === 1;
    }
}
