<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Finance\FamilyProfileRepository;
use App\Domain\Finance\FinancialGoalRepository;
use App\Domain\Finance\FinanceEntryRepository;
use App\Domain\Finance\KarinaStatementRepository;
use App\Domain\Shopping\ShoppingRepository;

final class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        return Response::view('reports/home', [
            'user' => $auth->user(),
        ]);
    }

    public function categories(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_CATEGORY_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $context = $this->financeReportContext($request);

        return Response::view('reports/categories', [
            'user' => $auth->user(),
            ...$context,
        ]);
    }

    public function categoryChart(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_CATEGORY_CHART_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $context = $this->financeReportContext($request);

        return Response::view('reports/category-chart', [
            'user' => $auth->user(),
            ...$context,
        ]);
    }

    public function vendors(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_VENDOR_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $context = $this->financeReportContext($request);

        return Response::view('reports/vendors', [
            'user' => $auth->user(),
            ...$context,
        ]);
    }

    public function paidVsReceived(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_PAID_VS_RECEIVED_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $context = $this->financeReportContext($request);

        return Response::view('reports/paid-vs-received', [
            'user' => $auth->user(),
            ...$context,
        ]);
    }

    public function financialPlan(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_FINANCIAL_PLAN_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $context = $this->financeReportContext($request);
        $monthlyNeedOverride = $this->moneyInput((string) $request->input('monthly_need'), 0.0);
        $familyProfile = $this->app->make(FamilyProfileRepository::class)->summary();
        $goals = $this->app->make(FinancialGoalRepository::class)->activeGoals();
        $reserveAmount = $this->reserveAmountFromGoals($goals);
        $plan = $this->financialPlanSummary($context, $reserveAmount, $monthlyNeedOverride, $familyProfile, $goals);

        return Response::view('reports/financial-plan', [
            'user' => $auth->user(),
            ...$context,
            'reserveAmount' => $reserveAmount,
            'monthlyNeed' => $monthlyNeedOverride,
            'familyProfile' => $familyProfile,
            'financialGoals' => $goals,
            'plan' => $plan,
        ]);
    }

    public function cashflow(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_CASHFLOW_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $context = $this->financeReportContext($request);

        return Response::view('reports/cashflow', [
            'user' => $auth->user(),
            ...$context,
        ]);
    }

    public function modality(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_MODALITY_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $context = $this->financeReportContext($request);
        $context['modalitySummary'] = $this->app->make(FinanceEntryRepository::class)->expensesByModality($context['startDate'], $context['endDate']);

        return Response::view('reports/modality', [
            'user' => $auth->user(),
            ...$context,
        ]);
    }

    public function karinaStatement(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_KARINA_STATEMENT_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $repository = $this->app->make(KarinaStatementRepository::class);

        return Response::view('reports/karina-statement', [
            'user' => $auth->user(),
            'summary' => $repository->summary(),
            'rows' => $repository->rows(),
            'categorySummary' => $repository->byCategory(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function financeReportContext(Request $request): array
    {
        $today = new \DateTimeImmutable('today');
        $defaultStartMonth = $today->modify('first day of january')->format('Y-m');
        $defaultEndMonth = $today->format('Y-m');
        $startMonth = $this->monthInput((string) $request->input('start_month'), $defaultStartMonth);
        $endMonth = $this->monthInput((string) $request->input('end_month'), $defaultEndMonth);

        if ($startMonth > $endMonth) {
            [$startMonth, $endMonth] = [$endMonth, $startMonth];
        }

        $startDate = (new \DateTimeImmutable($startMonth . '-01'))->format('Y-m-d');
        $endDate = (new \DateTimeImmutable($endMonth . '-01'))->modify('last day of this month')->format('Y-m-d');
        $categoryIds = $this->intListInput($request->input('category_ids', $request->input('category_id')));
        $vendorIds = $this->intListInput($request->input('vendor_ids', $request->input('vendor_id')));
        $repository = $this->app->make(FinanceEntryRepository::class);

        return [
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodLabel' => $this->monthPeriodLabel($startMonth, $endMonth),
            'categoryIds' => $categoryIds,
            'vendorIds' => $vendorIds,
            'categories' => $repository->expenseCategories(),
            'vendors' => $repository->expenseVendors(),
            'expenseSummary' => $repository->expenseReportSummary($startDate, $endDate, $categoryIds, $vendorIds),
            'expenseRows' => $repository->expenseReportRows($startDate, $endDate, $categoryIds, $vendorIds),
            'categorySummary' => $repository->expensesByCategoryReport($startDate, $endDate, $categoryIds, $vendorIds),
            'vendorSummary' => $repository->expensesByVendor($startDate, $endDate, $categoryIds, $vendorIds),
            'descriptionSummary' => $repository->expensesByDescriptionReport($startDate, $endDate, $categoryIds, $vendorIds),
            'paidVsReceivedRows' => $repository->paidVsReceivedByMonth($startDate, $endDate),
            'cashflowRows' => $repository->cashflowRows($startDate, $endDate),
        ];
    }

    public function market(Request $request): Response
    {
        $auth = $this->authorize(Permission::VIEW_MARKET_REPORT);

        if ($auth instanceof Response) {
            return $auth;
        }

        $today = new \DateTimeImmutable('now');
        $startDate = $this->dateInput((string) $request->input('start_date'), $today->modify('first day of january')->format('Y-m-d'));
        $endDate = $this->dateInput((string) $request->input('end_date'), $today->format('Y-m-d'));

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $marketRows = $this->app->make(ShoppingRepository::class)->marketReport($startDate, $endDate);
        $total = array_reduce($marketRows, static fn (float $carry, array $row): float => $carry + (float) $row['total_amount'], 0.0);
        $average = $marketRows === [] ? 0.0 : $total / count($marketRows);
        $max = $marketRows === [] ? 0.0 : max(array_map(static fn (array $row): float => (float) $row['total_amount'], $marketRows));
        $min = $marketRows === [] ? 0.0 : min(array_map(static fn (array $row): float => (float) $row['total_amount'], $marketRows));

        return Response::view('reports/index', [
            'user' => $auth->user(),
            'title' => 'Relatórios',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'marketRows' => $marketRows,
            'marketSummary' => [
                'total' => $total,
                'average' => $average,
                'max' => $max,
                'min' => $min,
            ],
        ]);
    }

    private function authorize(?Permission $permission = null): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        $user = $auth->user();

        if ($permission !== null && !$user?->can($permission)) {
            return new Response('Acesso negado.', 403);
        }

        if ($permission === null && !$this->canViewAnyReport($user)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function canViewAnyReport(mixed $user): bool
    {
        if (!is_object($user) || !method_exists($user, 'can')) {
            return false;
        }

        foreach ($this->reportPermissions() as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Permission[]
     */
    private function reportPermissions(): array
    {
        return [
            Permission::VIEW_CATEGORY_REPORT,
            Permission::VIEW_CATEGORY_CHART_REPORT,
            Permission::VIEW_VENDOR_REPORT,
            Permission::VIEW_PAID_VS_RECEIVED_REPORT,
            Permission::VIEW_FINANCIAL_PLAN_REPORT,
            Permission::VIEW_CASHFLOW_REPORT,
            Permission::VIEW_MODALITY_REPORT,
            Permission::VIEW_KARINA_STATEMENT_REPORT,
            Permission::VIEW_MARKET_REPORT,
        ];
    }

    private function dateInput(string $value, string $fallback): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m-d') : $fallback;
    }

    private function monthInput(string $value, string $fallback): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m', $value);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m') : $fallback;
    }

    private function intListInput(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $ids = [];

        foreach ($values as $item) {
            $number = (int) $item;

            if ($number > 0) {
                $ids[] = $number;
            }
        }

        return array_values(array_unique($ids));
    }

    private function moneyInput(string $value, float $fallback): float
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return $fallback;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', $normalized) ?? '';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (!is_numeric($normalized)) {
            return $fallback;
        }

        return max(0.0, (float) $normalized);
    }

    private function financialPlanSummary(array $context, float $reserveAmount, float $monthlyNeed, array $familyProfile, array $goals): array
    {
        $monthlyRows = $context['paidVsReceivedRows'] ?? [];
        $categoryRows = $context['categorySummary'] ?? [];
        $descriptionRows = $context['descriptionSummary'] ?? [];
        $start = new \DateTimeImmutable((string) $context['startDate']);
        $end = new \DateTimeImmutable((string) $context['endDate']);
        $months = max(1, ((int) $start->diff($end)->format('%y') * 12) + (int) $start->diff($end)->format('%m') + 1);
        $income = array_reduce($monthlyRows, static fn (float $carry, array $row): float => $carry + (float) $row['income_amount'], 0.0);
        $expenses = array_reduce($monthlyRows, static fn (float $carry, array $row): float => $carry + (float) $row['expense_amount'], 0.0);
        $estimatedExpenses = array_reduce($monthlyRows, static fn (float $carry, array $row): float => $carry + (float) ($row['estimated_expense_amount'] ?? 0), 0.0);
        $confirmedExpenses = array_reduce($monthlyRows, static fn (float $carry, array $row): float => $carry + (float) ($row['confirmed_expense_amount'] ?? $row['expense_amount']), 0.0);
        $balance = $income - $expenses;
        $confirmedBalance = $income - $confirmedExpenses;
        $averageIncome = $income / $months;
        $averageExpenses = $expenses / $months;
        $averageEstimatedExpenses = $estimatedExpenses / $months;
        $averageConfirmedExpenses = $confirmedExpenses / $months;
        $averageBalance = $balance / $months;
        $averageConfirmedBalance = $confirmedBalance / $months;
        $savingsRate = $income > 0 ? ($balance / $income) * 100 : 0.0;
        $essentialMonthlyNeed = $this->essentialMonthlyNeed($categoryRows, $months);
        $effectiveMonthlyNeed = $monthlyNeed > 0 ? $monthlyNeed : $essentialMonthlyNeed;
        $reserveMonths = $effectiveMonthlyNeed > 0 ? $reserveAmount / $effectiveMonthlyNeed : 0.0;
        $reserveTarget = $effectiveMonthlyNeed * 6;
        $pressureRows = $this->financialPressureRows($categoryRows, $income, $months);
        $descriptionPressureRows = $this->financialDescriptionRows($descriptionRows, $income, $months);
        $reference = $this->familyBudgetReference($familyProfile);
        $cutPotential = array_reduce($pressureRows, static fn (float $carry, array $row): float => $carry + (float) $row['suggested_cut_amount'], 0.0);
        $projectedMonthlyBalance = $averageBalance + $cutPotential;
        $goalRows = $this->financialGoalRows($goals, max(0.0, $projectedMonthlyBalance));

        return [
            'months' => $months,
            'income_amount' => $income,
            'expense_amount' => $expenses,
            'confirmed_expense_amount' => $confirmedExpenses,
            'estimated_expense_amount' => $estimatedExpenses,
            'balance_amount' => $balance,
            'confirmed_balance_amount' => $confirmedBalance,
            'average_income_amount' => $averageIncome,
            'average_expense_amount' => $averageExpenses,
            'average_confirmed_expense_amount' => $averageConfirmedExpenses,
            'average_estimated_expense_amount' => $averageEstimatedExpenses,
            'average_balance_amount' => $averageBalance,
            'average_confirmed_balance_amount' => $averageConfirmedBalance,
            'savings_rate' => $savingsRate,
            'essential_monthly_need_amount' => $essentialMonthlyNeed,
            'monthly_need_override_amount' => $monthlyNeed,
            'reserve_months' => $reserveMonths,
            'reserve_target_amount' => $reserveTarget,
            'reserve_gap_amount' => max(0.0, $reserveTarget - $reserveAmount),
            'pressure_rows' => $pressureRows,
            'description_pressure_rows' => $descriptionPressureRows,
            'family_reference' => $reference,
            'family_reference_rows' => $this->familyReferenceRows($categoryRows, $reference, $income),
            'monthly_cut_potential_amount' => $cutPotential,
            'projected_monthly_balance_amount' => $projectedMonthlyBalance,
            'goal_rows' => $goalRows,
            'roadmap' => $this->financialRoadmap($averageBalance, $cutPotential, $reserveAmount, $reserveTarget, $goalRows),
        ];
    }

    private function reserveAmountFromGoals(array $goals): float
    {
        foreach ($goals as $goal) {
            if (($goal['goal_type'] ?? '') === 'emergency_reserve') {
                return (float) ($goal['current_amount'] ?? 0);
            }
        }

        return 0.0;
    }

    private function financialPressureRows(array $categoryRows, float $periodIncome, int $months): array
    {
        $rows = [];

        foreach ($categoryRows as $row) {
            $amount = (float) ($row['total_amount'] ?? 0);
            $category = (string) ($row['category_name'] ?? 'Sem categoria');
            $profile = $this->categoryProfile($category);
            $monthlyAmount = $amount / max(1, $months);
            $incomeShare = $periodIncome > 0 ? ($amount / $periodIncome) * 100 : 0.0;
            $suggestedCutRate = $profile['cut_rate'];
            $suggestedCut = $monthlyAmount * $suggestedCutRate;

            $rows[] = [
                ...$row,
                'profile_label' => $profile['label'],
                'action_label' => $profile['action'],
                'income_share' => $incomeShare,
                'monthly_amount' => $monthlyAmount,
                'suggested_cut_amount' => $suggestedCut,
                'pressure_score' => $incomeShare + ($suggestedCutRate * 35),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => ((float) $b['pressure_score']) <=> ((float) $a['pressure_score']));

        return array_slice($rows, 0, 10);
    }

    private function financialDescriptionRows(array $descriptionRows, float $periodIncome, int $months): array
    {
        $grouped = [];

        foreach ($descriptionRows as $row) {
            $amount = (float) ($row['total_amount'] ?? 0);
            $profile = $this->categoryProfile((string) ($row['category_name'] ?? ''));
            $description = $this->normalizedPressureDescription((string) ($row['description'] ?? 'Sem descrição'));
            $category = (string) ($row['category_name'] ?? 'Sem categoria');
            $modality = trim((string) ($row['modality'] ?? ''));
            $key = mb_strtoupper($description . '|' . $category);
            $installmentTotal = (int) ($row['installment_total'] ?? 0);
            $installmentCurrent = (int) ($row['installment_current'] ?? 0);
            $isInstallment = $installmentTotal > 1 || $installmentCurrent > 0;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'description' => $description,
                    'category_name' => $category,
                    'modality_values' => [],
                    'total_amount' => 0.0,
                    'paid_amount' => 0.0,
                    'open_amount' => 0.0,
                    'estimated_amount' => 0.0,
                    'entries_count' => 0,
                    'installment_min' => null,
                    'installment_max' => null,
                    'installment_total' => 0,
                    'has_installment' => false,
                    'has_open' => false,
                    'has_estimated' => false,
                    'profile_label' => $profile['label'],
                    'action_label' => $profile['action'],
                ];
            }

            $grouped[$key]['total_amount'] += $amount;
            $grouped[$key]['paid_amount'] += (float) ($row['paid_amount'] ?? 0);
            $grouped[$key]['open_amount'] += (float) ($row['open_amount'] ?? 0);
            $grouped[$key]['estimated_amount'] += (float) ($row['estimated_amount'] ?? 0);
            $grouped[$key]['entries_count'] += (int) ($row['entries_count'] ?? 0);
            $grouped[$key]['has_installment'] = $grouped[$key]['has_installment'] || $isInstallment;
            $grouped[$key]['has_open'] = $grouped[$key]['has_open'] || (float) ($row['open_amount'] ?? 0) > 0;
            $grouped[$key]['has_estimated'] = $grouped[$key]['has_estimated'] || (float) ($row['estimated_amount'] ?? 0) > 0;
            $grouped[$key]['installment_total'] = max((int) $grouped[$key]['installment_total'], $installmentTotal);

            if ($installmentCurrent > 0) {
                $grouped[$key]['installment_min'] = $grouped[$key]['installment_min'] === null ? $installmentCurrent : min((int) $grouped[$key]['installment_min'], $installmentCurrent);
                $grouped[$key]['installment_max'] = $grouped[$key]['installment_max'] === null ? $installmentCurrent : max((int) $grouped[$key]['installment_max'], $installmentCurrent);
            }

            if ($modality !== '') {
                $grouped[$key]['modality_values'][$modality] = true;
            }

            if ($isInstallment) {
                $grouped[$key]['action_label'] = 'Acompanhar a recorrencia/parcelamento e evitar nova compra similar';
            }
        }

        $rows = [];

        foreach ($grouped as $row) {
            $monthlyAmount = (float) $row['total_amount'] / max(1, $months);
            $incomeShare = $periodIncome > 0 ? ((float) $row['total_amount'] / $periodIncome) * 100 : 0.0;
            $installmentLabel = '';

            if ($row['has_installment'] === true) {
                $min = $row['installment_min'];
                $max = $row['installment_max'];
                $total = (int) $row['installment_total'];
                $installmentLabel = $min !== null && $max !== null && $total > 1
                    ? 'Parcelas ' . (int) $min . '-' . (int) $max . '/' . $total
                    : 'Parcelado';
            }

            $flags = [];
            if ($row['has_open'] === true) {
                $flags[] = 'em aberto';
            }
            if ($row['has_estimated'] === true) {
                $flags[] = 'estimado';
            }

            $rows[] = [
                'profile_label' => (string) $row['profile_label'],
                'action_label' => (string) $row['action_label'],
                'income_share' => $incomeShare,
                'monthly_amount' => $monthlyAmount,
                'modality_label' => implode(' / ', array_keys($row['modality_values'])) . ($flags === [] ? '' : ' | ' . implode(', ', $flags)),
                'installment_label' => $installmentLabel,
                'pressure_score' => $incomeShare + ($row['has_installment'] === true ? 12 : 0) + ($row['has_open'] === true ? 8 : 0),
                ...$row,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => ((float) $b['pressure_score']) <=> ((float) $a['pressure_score']));

        return array_slice($rows, 0, 12);
    }

    private function normalizedPressureDescription(string $description): string
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $description));
        $normalized = (string) preg_replace('/\s*[-|]?\s*(?:parcela\s*)?\d{1,2}\s*\/\s*\d{1,2}\s*$/i', '', $normalized);

        return trim($normalized) !== '' ? trim($normalized) : 'Sem descrição';
    }

    private function financialGoalRows(array $goals, float $availableMonthlyAmount): array
    {
        $rows = [];
        $today = new \DateTimeImmutable('today');
        $totalWeight = array_reduce($goals, static function (int $carry, array $goal): int {
            return $carry + max(1, (int) ($goal['priority_weight'] ?? 1));
        }, 0);

        foreach ($goals as $goal) {
            $currentAmount = (float) ($goal['current_amount'] ?? 0);
            $targetAmount = (float) ($goal['target_amount'] ?? 0);
            $remainingAmount = max(0.0, $targetAmount - $currentAmount);
            $targetDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($goal['target_date'] ?? ''));
            $daysRemaining = $targetDate instanceof \DateTimeImmutable ? (int) $today->diff($targetDate)->format('%r%a') : 0;
            $monthsRemaining = max(1, (int) ceil(max(0, $daysRemaining) / 30.44));
            $requiredMonthlyAmount = $remainingAmount / $monthsRemaining;
            $weight = max(1, (int) ($goal['priority_weight'] ?? 1));
            $allocatedMonthlyAmount = $totalWeight > 0 ? $availableMonthlyAmount * ($weight / $totalWeight) : 0.0;
            $projectedAmount = $currentAmount + ($allocatedMonthlyAmount * $monthsRemaining);
            $projectedGap = max(0.0, $targetAmount - $projectedAmount);
            $estimatedMonths = $allocatedMonthlyAmount > 0 && $remainingAmount > 0 ? (int) ceil($remainingAmount / $allocatedMonthlyAmount) : null;
            $estimatedDate = $estimatedMonths !== null ? $today->modify('+' . $estimatedMonths . ' months')->format('Y-m-d') : null;
            $progressPercent = $targetAmount > 0 ? min(100.0, ($currentAmount / $targetAmount) * 100) : 0.0;
            $status = 'needs_adjustment';

            if ($remainingAmount <= 0) {
                $status = 'reached';
            } elseif ($daysRemaining < 0) {
                $status = 'overdue';
            } elseif ($allocatedMonthlyAmount >= $requiredMonthlyAmount) {
                $status = 'on_track';
            }

            $rows[] = [
                ...$goal,
                'current_amount' => $currentAmount,
                'target_amount' => $targetAmount,
                'remaining_amount' => $remainingAmount,
                'months_remaining' => $monthsRemaining,
                'required_monthly_amount' => $requiredMonthlyAmount,
                'allocated_monthly_amount' => $allocatedMonthlyAmount,
                'projected_amount' => $projectedAmount,
                'projected_gap_amount' => $projectedGap,
                'estimated_months' => $estimatedMonths,
                'estimated_date' => $estimatedDate,
                'progress_percent' => $progressPercent,
                'status' => $status,
            ];
        }

        return $rows;
    }

    private function essentialMonthlyNeed(array $categoryRows, int $months): float
    {
        $total = 0.0;

        foreach ($categoryRows as $row) {
            if ($this->categoryProfile((string) ($row['category_name'] ?? ''))['is_essential'] !== true) {
                continue;
            }

            $total += (float) ($row['total_amount'] ?? 0);
        }

        return $total / max(1, $months);
    }

    private function familyBudgetReference(array $familyProfile): array
    {
        $settings = $familyProfile['settings'] ?? [];
        $region = (string) ($settings['region'] ?? 'brasil');
        $area = (string) ($settings['residence_area'] ?? 'urban');
        $regional = [
            'brasil' => ['alimentacao' => 17.5, 'habitacao' => 36.6, 'transporte' => 18.1, 'saude' => 8.0, 'educacao' => 4.7],
            'norte' => ['alimentacao' => 21.0, 'habitacao' => 36.4, 'transporte' => 16.6, 'saude' => 5.4, 'educacao' => 3.2],
            'nordeste' => ['alimentacao' => 22.0, 'habitacao' => 32.4, 'transporte' => 16.2, 'saude' => 8.0, 'educacao' => 4.7],
            'sudeste' => ['alimentacao' => 15.8, 'habitacao' => 39.0, 'transporte' => 17.5, 'saude' => 8.5, 'educacao' => 5.1],
            'sul' => ['alimentacao' => 17.1, 'habitacao' => 35.7, 'transporte' => 20.6, 'saude' => 7.3, 'educacao' => 3.7],
            'centro_oeste' => ['alimentacao' => 16.6, 'habitacao' => 33.4, 'transporte' => 21.0, 'saude' => 8.0, 'educacao' => 4.7],
        ];
        $urbanRural = [
            'urban' => ['alimentacao' => 16.9, 'habitacao' => 37.1, 'transporte' => 17.9, 'saude' => 8.0, 'educacao' => 4.9],
            'rural' => ['alimentacao' => 23.8, 'habitacao' => 30.9, 'transporte' => 20.0, 'saude' => 8.0, 'educacao' => 2.3],
        ];
        $base = $regional[$region] ?? $regional['brasil'];

        if (isset($urbanRural[$area])) {
            foreach ($base as $key => $percent) {
                $base[$key] = (($percent * 2) + $urbanRural[$area][$key]) / 3;
            }
        }

        $children = (int) ($familyProfile['child_count'] ?? 0);
        $pets = (int) ($familyProfile['pet_count'] ?? 0);
        $base['alimentacao'] += min(4.0, $children * 1.5);
        $base['educacao'] += min(5.0, $children * 1.8);
        $base['saude'] += min(3.0, $children * 0.8);
        $base['pets'] = min(6.0, $pets * 2.5);

        return $base;
    }

    private function familyReferenceRows(array $categoryRows, array $reference, float $periodIncome): array
    {
        $groups = [
            'alimentacao' => ['ALIMENTACAO', 'ALIMENTAÇÃO', 'MERCADO', 'RESTAURANTE', 'IFOOD', 'DELIVERY'],
            'habitacao' => ['ALUGUEL', 'CONDOMINIO', 'CONDOMÍNIO', 'IPTU', 'CASA', 'ENERGIA', 'LUZ', 'AGUA', 'ÁGUA', 'INTERNET'],
            'transporte' => ['TRANSPORTE', 'COMBUSTIVEL', 'COMBUSTÍVEL', 'VEICULO', 'VEÍCULO', 'CARRO', 'MOTO', 'UBER'],
            'saude' => ['SAUDE', 'SAÚDE', 'FARMACIA', 'FARMÁCIA', 'PLANO', 'MEDICO', 'MÉDICO'],
            'educacao' => ['EDUCACAO', 'EDUCAÇÃO', 'ESCOLA', 'FACULDADE', 'CURSO'],
            'pets' => ['PET', 'VETERINARIO', 'VETERINÁRIO', 'RACAO', 'RAÇÃO'],
        ];
        $actual = array_fill_keys(array_keys($groups), 0.0);

        foreach ($categoryRows as $row) {
            $name = mb_strtoupper((string) ($row['category_name'] ?? ''));

            foreach ($groups as $group => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($name, $needle)) {
                        $actual[$group] += (float) ($row['total_amount'] ?? 0);
                        continue 3;
                    }
                }
            }
        }

        $labels = [
            'alimentacao' => 'Alimentacao',
            'habitacao' => 'Habitacao',
            'transporte' => 'Transporte',
            'saude' => 'Saude',
            'educacao' => 'Educacao',
            'pets' => 'Pets',
        ];
        $rows = [];

        foreach ($labels as $key => $label) {
            $actualPercent = $periodIncome > 0 ? ($actual[$key] / $periodIncome) * 100 : 0.0;
            $targetPercent = (float) ($reference[$key] ?? 0);
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'actual_percent' => $actualPercent,
                'reference_percent' => $targetPercent,
                'difference_percent' => $actualPercent - $targetPercent,
            ];
        }

        return $rows;
    }

    private function categoryProfile(string $category): array
    {
        $name = mb_strtoupper($category);
        $fixed = ['ALUGUEL', 'CONDOMINIO', 'IPTU', 'ENERGIA', 'AGUA', 'LUZ', 'INTERNET', 'TELEFONE', 'SAUDE', 'PLANO'];
        $debt = ['CARTAO', 'CREDITO', 'EMPRESTIMO', 'FINANCIAMENTO', 'PARCELA', 'JUROS'];
        $flexible = ['IFOOD', 'RESTAURANTE', 'DELIVERY', 'LAZER', 'SHOPPING', 'ROUPA', 'ASSINATURA', 'MERCADO', 'VIAGEM'];

        foreach ($debt as $keyword) {
            if (str_contains($name, $keyword)) {
                return ['label' => 'Divida/parcelamento', 'action' => 'Renegociar, quitar ou congelar novas parcelas', 'cut_rate' => 0.18, 'is_essential' => true];
            }
        }

        foreach ($flexible as $keyword) {
            if (str_contains($name, $keyword)) {
                return ['label' => 'Ajustavel', 'action' => 'Cortar ou trocar por limite semanal', 'cut_rate' => 0.15, 'is_essential' => $keyword === 'MERCADO'];
            }
        }

        foreach ($fixed as $keyword) {
            if (str_contains($name, $keyword)) {
                return ['label' => 'Fixo essencial', 'action' => 'Renegociar contrato ou reduzir consumo', 'cut_rate' => 0.05, 'is_essential' => true];
            }
        }

        $essential = ['EDUCACAO', 'EDUCAÇÃO', 'ESCOLA', 'CRECHE', 'FARMACIA', 'FARMÁCIA', 'MEDICO', 'MÉDICO', 'PET', 'VETERINARIO', 'VETERINÁRIO', 'RACAO', 'RAÇÃO', 'SEGURO', 'IMPOSTO'];

        foreach ($essential as $keyword) {
            if (str_contains($name, $keyword)) {
                return ['label' => 'Essencial recorrente', 'action' => 'Acompanhar reajuste e recorrencia', 'cut_rate' => 0.04, 'is_essential' => true];
            }
        }

        return ['label' => 'Monitorar', 'action' => 'Auditar recorrencia e necessidade', 'cut_rate' => 0.08, 'is_essential' => false];
    }

    private function financialRoadmap(float $averageBalance, float $cutPotential, float $reserveAmount, float $reserveTarget, array $goalRows): array
    {
        $projected = $averageBalance + $cutPotential;
        $largestGoalGap = array_reduce($goalRows, static fn (float $carry, array $row): float => max($carry, (float) ($row['projected_gap_amount'] ?? 0)), 0.0);

        return [
            ['period' => '3 meses', 'focus' => 'Estancar vazamentos', 'target' => max(0.0, $cutPotential * 3), 'text' => 'Congelar novas parcelas, atacar as maiores categorias ajustaveis e separar dinheiro no dia do recebimento.'],
            ['period' => '6 meses', 'focus' => 'Repor margem mensal', 'target' => max(0.0, $projected * 6), 'text' => 'Transformar cortes em aporte automatico e criar uma meta minima de sobra mensal.'],
            ['period' => '12 meses', 'focus' => 'Aumentar lastro', 'target' => max(0.0, $projected * 12), 'text' => $reserveAmount >= $reserveTarget ? 'Manter a reserva e priorizar os objetivos com maior prazo/impacto.' : 'Completar a reserva antes de assumir compromissos maiores.'],
            ['period' => '24 meses', 'focus' => 'Metas patrimoniais', 'target' => min($largestGoalGap, max(0.0, $projected * 24)), 'text' => 'Revisar prioridade dos objetivos ativos e recalibrar aporte sempre que a renda ou aluguel mudar.'],
        ];
    }

    private function monthPeriodLabel(string $startMonth, string $endMonth): string
    {
        $start = new \DateTimeImmutable($startMonth . '-01');
        $end = new \DateTimeImmutable($endMonth . '-01');

        if ($startMonth === $endMonth) {
            return $this->monthName((int) $start->format('n')) . ' de ' . $start->format('Y');
        }

        return $this->monthName((int) $start->format('n')) . '/' . $start->format('Y')
            . ' ate '
            . $this->monthName((int) $end->format('n')) . '/' . $end->format('Y');
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Marco',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ][$month] ?? '';
    }
}
