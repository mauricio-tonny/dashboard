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
use App\Domain\Finance\CategoryBudgetTargetRepository;

final class CategoryBudgetTargetController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        return Response::view('system/category-budget-targets/index', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'targets' => $this->app->make(CategoryBudgetTargetRepository::class)->targets(),
            'baseIncomeAmount' => $this->app->make(CategoryBudgetTargetRepository::class)->baseIncomeAmount(),
        ]);
    }

    public function save(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $baseIncome = $this->money((string) $request->input('budget_base_income'));
        $targets = [];

        foreach ((array) $request->input('targets', []) as $rawTarget) {
            $target = $this->normalizeTarget((array) $rawTarget, $baseIncome);

            if ($target === null) {
                $this->flash('error', 'Revise os percentuais e valores informados. Use percentuais entre 0 e 100.');
                return Response::redirect('/system/category-budget-targets');
            }

            $targets[] = $target;
        }

        $residualError = $this->residualError($targets, $baseIncome);

        if ($residualError !== null) {
            $this->flash('error', $residualError);
            return Response::redirect('/system/category-budget-targets');
        }

        $this->app->make(CategoryBudgetTargetRepository::class)->saveMany($targets, $auth->user()?->id, $baseIncome);
        $this->app->make(AuditLogger::class)->log('category_budget_targets_updated', 'category_budget_targets', null, $auth->user(), [
            'targets' => count($targets),
            'base_income_amount' => $baseIncome,
        ]);
        $this->flash('success', 'Metas por categoria salvas com sucesso.');

        return Response::redirect('/system/category-budget-targets');
    }

    private function normalizeTarget(array $rawTarget, ?float $baseIncome): ?array
    {
        $categoryId = (int) ($rawTarget['category_id'] ?? 0);
        $isActive = (string) ($rawTarget['is_active'] ?? '0') === '1';
        $controlType = (string) ($rawTarget['control_type'] ?? 'maximum');
        $targetPercent = $this->percent((string) ($rawTarget['target_percent'] ?? ''));
        $desiredAmount = $this->money((string) ($rawTarget['desired_amount'] ?? ''));
        $referenceMin = $this->percent((string) ($rawTarget['reference_min_percent'] ?? ''));
        $referenceMax = $this->percent((string) ($rawTarget['reference_max_percent'] ?? ''));

        if ($targetPercent === null && $desiredAmount !== null && $baseIncome !== null && $baseIncome > 0) {
            $targetPercent = round(($desiredAmount / $baseIncome) * 100, 2);
        }

        if ($desiredAmount === null && $targetPercent !== null && $baseIncome !== null && $baseIncome > 0) {
            $desiredAmount = round($baseIncome * ($targetPercent / 100), 2);
        }

        if ($categoryId <= 0 || !in_array($controlType, ['maximum', 'minimum', 'track'], true)) {
            return null;
        }

        if ($targetPercent !== null && ($targetPercent < 0 || $targetPercent > 100)) {
            return null;
        }

        foreach ([$referenceMin, $referenceMax] as $referencePercent) {
            if ($referencePercent !== null && ($referencePercent < 0 || $referencePercent > 100)) {
                return null;
            }
        }

        return [
            'category_id' => $categoryId,
            'is_active' => $isActive,
            'control_type' => $controlType,
            'target_percent' => $targetPercent,
            'desired_amount' => $desiredAmount,
            'reference_key' => mb_substr(trim((string) ($rawTarget['reference_key'] ?? 'custom')), 0, 80),
            'reference_label' => mb_substr(trim((string) ($rawTarget['reference_label'] ?? '')), 0, 160),
            'reference_min_percent' => $referenceMin,
            'reference_max_percent' => $referenceMax,
            'notes' => mb_substr(trim((string) ($rawTarget['notes'] ?? '')), 0, 255),
        ];
    }

    private function residualError(array $targets, ?float $baseIncome): ?string
    {
        $activeTargets = array_values(array_filter($targets, static fn (array $target): bool => (bool) $target['is_active']));

        if ($activeTargets === []) {
            return null;
        }

        if ($baseIncome === null || $baseIncome <= 0) {
            return 'Informe a renda base para fechar o planejamento das metas.';
        }

        $distributed = array_reduce(
            $activeTargets,
            static fn (float $carry, array $target): float => $carry + (float) ($target['desired_amount'] ?? 0),
            0.0
        );
        $residual = round($baseIncome - $distributed, 2);

        if (abs($residual) <= 0.01) {
            return null;
        }

        $formattedResidual = 'R$ ' . number_format(abs($residual), 2, ',', '.');

        return $residual > 0
            ? "Ainda existe {$formattedResidual} sem distribuir entre as categorias ativas."
            : "As metas ultrapassam a renda base em {$formattedResidual}.";
    }

    private function percent(string $value): ?float
    {
        $value = trim(str_replace('%', '', $value));

        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);

        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }

    private function money(string $value): ?float
    {
        $value = preg_replace('/[^\d,.-]+/u', '', $value) ?? '';

        if ($value === '') {
            return null;
        }

        $normalized = str_contains($value, ',')
            ? str_replace(['.', ','], ['', '.'], $value)
            : str_replace(',', '', $value);

        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_CATEGORY_BUDGET_TARGETS)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function flash(string $type, string $message): void
    {
        $this->app->make(Session::class)->flash($type, $message);
    }
}
