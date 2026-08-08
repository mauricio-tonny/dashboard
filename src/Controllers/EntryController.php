<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Finance\FinanceService;

final class EntryController extends Controller
{
    public function create(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->canEdit()) {
            return new Response('Acesso negado.', 403);
        }

        return Response::view('entries/create', [
            'error' => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        $type = (string) $request->input('type', '');
        $permission = $type === 'income' ? Permission::CREATE_INCOME : Permission::CREATE_EXPENSE;

        if (!$auth->user()?->can($permission)) {
            return new Response('Acesso negado.', 403);
        }

        $payload = $request->only([
            'date',
            'description',
            'category',
            'vendor',
            'type',
            'amount',
        ]);

        $this->app->make(FinanceService::class)->createEntry($payload, $auth->user()->email);

        return Response::redirect('/');
    }
}
