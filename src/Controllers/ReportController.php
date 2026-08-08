<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
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

    public function market(Request $request): Response
    {
        $auth = $this->authorize();

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
            'title' => 'Relatorios',
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

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::VIEW_CATEGORY_REPORT)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function dateInput(string $value, string $fallback): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m-d') : $fallback;
    }
}
