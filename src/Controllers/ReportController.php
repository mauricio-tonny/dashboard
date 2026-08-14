<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Finance\FinanceEntryRepository;
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
            Permission::VIEW_CASHFLOW_REPORT,
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
