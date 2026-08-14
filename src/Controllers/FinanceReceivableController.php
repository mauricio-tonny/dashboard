<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Finance\FinanceEntryRepository;

final class FinanceReceivableController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::VIEW_INCOME_TOTALS)) {
            return new Response('Acesso negado.', 403);
        }

        $today = new \DateTimeImmutable('today');
        $defaultMonth = $today->modify('first day of next month')->format('Y-m');
        $startMonth = $this->monthInput((string) $request->input('start_month'), $defaultMonth);
        $endMonth = $this->monthInput((string) $request->input('end_month'), $startMonth);

        if ($startMonth > $endMonth) {
            [$startMonth, $endMonth] = [$endMonth, $startMonth];
        }

        $startDate = (new \DateTimeImmutable($startMonth . '-01'))->format('Y-m-d');
        $endDate = (new \DateTimeImmutable($endMonth . '-01'))->modify('last day of this month')->format('Y-m-d');
        $repository = $this->app->make(FinanceEntryRepository::class);
        $periodStart = new \DateTimeImmutable($startDate);
        $previousMonth = $periodStart->modify('first day of previous month');
        $nextMonth = $periodStart->modify('first day of next month');

        return Response::view('finance/receivable/index', [
            'user' => $auth->user(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'summary' => $repository->incomeSummary($startDate, $endDate),
            'entries' => $repository->incomeRows($startDate, $endDate),
            'incomeSummary' => $repository->incomeByDescription($startDate, $endDate),
            'periodLabel' => $this->periodLabel($startDate, $endDate),
            'previousMonthUrl' => $this->monthUrl($previousMonth),
            'nextWorkMonthUrl' => $this->monthUrl($today->modify('first day of next month')),
            'nextMonthUrl' => $this->monthUrl($nextMonth),
        ]);
    }

    private function monthInput(string $value, string $fallback): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m', $value);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m') : $fallback;
    }

    private function monthUrl(\DateTimeImmutable $month): string
    {
        $query = [
            'start_month' => $month->modify('first day of this month')->format('Y-m'),
            'end_month' => $month->modify('first day of this month')->format('Y-m'),
        ];

        return '/finance/receivable?' . http_build_query($query);
    }

    private function periodLabel(string $startDate, string $endDate): string
    {
        $start = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $this->monthName((int) $start->format('n')) . ' de ' . $start->format('Y');
        }

        return $start->format('d/m/Y') . ' ate ' . $end->format('d/m/Y');
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
