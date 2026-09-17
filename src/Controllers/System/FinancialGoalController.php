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
use App\Domain\Finance\FinancialGoalRepository;

final class FinancialGoalController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $session = $this->app->make(Session::class);

        return Response::view('system/financial-goals/index', [
            'user' => $auth->user(),
            'success' => $session->pullFlash('success'),
            'error' => $session->pullFlash('error'),
            'goals' => $this->app->make(FinancialGoalRepository::class)->goals(),
        ]);
    }

    public function save(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $startAmount = $this->money((string) $request->input('start_amount'));
        $currentAmount = $this->money((string) $request->input('current_amount'), $startAmount);
        $targetAmount = $this->money((string) $request->input('target_amount'));
        $startDate = $this->date((string) $request->input('start_date'), (new \DateTimeImmutable('today'))->format('Y-m-d'));
        $targetDate = $this->date((string) $request->input('target_date'), '');
        $data = [
            'name' => trim((string) $request->input('name')),
            'goal_type' => $this->allowed((string) $request->input('goal_type'), array_keys($this->types()), 'other'),
            'start_amount' => $startAmount,
            'current_amount' => $currentAmount,
            'target_amount' => $targetAmount,
            'start_date' => $startDate,
            'target_date' => $targetDate,
            'priority_weight' => $this->priority((string) $request->input('priority_weight')),
            'notes' => trim((string) $request->input('notes')) ?: null,
        ];

        if ($data['name'] === '' || $targetAmount <= 0 || $targetDate === '') {
            $this->flash('error', 'Informe nome, valor alvo e data alvo do objetivo.');
            return Response::redirect('/system/financial-goals');
        }

        if ($targetDate < $startDate) {
            $this->flash('error', 'A data alvo precisa ser igual ou posterior a data inicial.');
            return Response::redirect('/system/financial-goals');
        }

        $id = $this->app->make(FinancialGoalRepository::class)->save($this->nullableInt($request->input('id')), $data, $auth->user()?->id);
        $this->audit('financial_goal_saved', 'financial_goal', $id, ['name' => $data['name'], 'goal_type' => $data['goal_type']]);
        $this->flash('success', 'Objetivo financeiro salvo.');

        return Response::redirect('/system/financial-goals');
    }

    public function toggle(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $active = (string) $request->input('active') === '1';

        if ($id > 0) {
            $this->app->make(FinancialGoalRepository::class)->setActive($id, $active);
            $this->audit($active ? 'financial_goal_enabled' : 'financial_goal_disabled', 'financial_goal', $id);
            $this->flash('success', $active ? 'Objetivo reativado.' : 'Objetivo desativado.');
        }

        return Response::redirect('/system/financial-goals');
    }

    public function types(): array
    {
        return [
            'emergency_reserve' => 'Reserva de emergencia',
            'home_down_payment' => 'Entrada da casa',
            'investment' => 'Investimento',
            'debt_payoff' => 'Quitacao de divida',
            'other' => 'Outro objetivo',
        ];
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_FINANCIAL_GOALS)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function money(string $value, float $fallback = 0.0): float
    {
        $normalized = preg_replace('/[^\d,.-]/', '', trim($value)) ?? '';

        if ($normalized === '') {
            return $fallback;
        }

        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? max(0.0, (float) $normalized) : $fallback;
    }

    private function date(string $value, string $fallback): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m-d') : $fallback;
    }

    private function allowed(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function priority(string $value): int
    {
        $int = (int) $value;

        return max(1, min(10, $int));
    }

    private function flash(string $key, string $message): void
    {
        $this->app->make(Session::class)->flash($key, $message);
    }

    private function audit(string $action, string $entityType, ?int $entityId = null, array $metadata = []): void
    {
        $auth = $this->app->make(AuthService::class);
        $this->app->make(AuditLogger::class)->log($action, $entityType, $entityId, $auth->user(), $metadata);
    }
}
