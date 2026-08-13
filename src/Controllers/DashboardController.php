<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Finance\FinanceEntryRepository;
use App\Domain\Finance\FinanceService;
use App\Domain\Shopping\ShoppingRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::VIEW_DASHBOARD)) {
            return new Response('Acesso negado.', 403);
        }

        $finance = $this->app->make(FinanceService::class);
        $financeEntries = $this->app->make(FinanceEntryRepository::class);
        $shopping = $this->app->make(ShoppingRepository::class);
        $nextMonth = (new \DateTimeImmutable('first day of next month'))->format('Y-m-01');
        $currentYear = (int) (new \DateTimeImmutable('now'))->format('Y');

        return Response::view('dashboard/index', [
            'user' => $auth->user(),
            'summary' => $finance->monthlySummary(new \DateTimeImmutable('now')),
            'upcoming' => $finance->upcomingForecast(),
            'payableSummary' => $financeEntries->payableDashboardSummary(new \DateTimeImmutable('now')),
            'marketSummary' => $shopping->marketSummaryForMonth($nextMonth),
            'pendingHomeItems' => $shopping->pendingHomeItems(10),
            'annualExpenses' => $financeEntries->annualExpenseSeries($currentYear),
            'annualCategoryExpenses' => $financeEntries->annualExpensesByCategory($currentYear),
        ]);
    }
}
