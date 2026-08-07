<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Finance\FinanceService;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        $finance = $this->app->make(FinanceService::class);

        return Response::view('dashboard/index', [
            'user' => $auth->user(),
            'summary' => $finance->monthlySummary(new \DateTimeImmutable('now')),
            'upcoming' => $finance->upcomingForecast(),
        ]);
    }
}

