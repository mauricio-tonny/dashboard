<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use App\Domain\Finance\FinanceService;
use App\Domain\Finance\FinanceEntryRepository;
use App\Domain\System\SpreadsheetWritebackRepository;

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

        $repository = $this->app->make(FinanceEntryRepository::class);

        return Response::view('entries/create', [
            'user' => $auth->user(),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'categories' => $repository->availableCategories(),
            'vendors' => $repository->availableVendors(),
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
            'modality',
            'installment_count',
            'recurrence_end_date',
        ]);
        $payload['amount'] = $this->money((string) ($payload['amount'] ?? ''));

        if ($payload['amount'] <= 0) {
            $this->app->make(Session::class)->flash('error', 'Informe um valor maior que zero.');

            return Response::redirect('/entries/create');
        }

        $installmentCount = 1;

        if ($type === 'expense' && trim((string) ($payload['modality'] ?? '')) === 'PARCELADO') {
            $installmentCount = (int) ($payload['installment_count'] ?? 0);

            if ($installmentCount < 2 || $installmentCount > 120) {
                $this->app->make(Session::class)->flash('error', 'Informe entre 2 e 120 parcelas.');

                return Response::redirect('/entries/create');
            }
        }

        if ($type === 'expense' && trim((string) ($payload['modality'] ?? '')) === 'RECORRENTE') {
            $recurrenceEndDate = trim((string) ($payload['recurrence_end_date'] ?? ''));
            $startDate = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($payload['date'] ?? ''));

            if ($recurrenceEndDate !== '') {
                $endDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $recurrenceEndDate);

                if (!$endDate instanceof \DateTimeImmutable || !$startDate instanceof \DateTimeImmutable || $endDate < $startDate) {
                    $this->app->make(Session::class)->flash('error', 'A data final da recorrência deve ser igual ou posterior ao vencimento.');

                    return Response::redirect('/entries/create');
                }
            }
        }

        $this->app->make(FinanceService::class)->createEntry($payload, $auth->user()->email);

        $session = $this->app->make(Session::class);

        if ($type === 'expense' && $auth->user()->hasRole(Role::ADMIN)) {
            $writebackRepository = $this->app->make(SpreadsheetWritebackRepository::class);
            $writebackPayloads = $this->installmentPayloads($payload, $installmentCount);
            $writebackIds = [];

            foreach ($writebackPayloads as $writebackPayload) {
                $writebackIds[] = $writebackRepository->queueExpense($writebackPayload, $auth->user()->id);
            }

            $this->app->make(AuditLogger::class)->log('spreadsheet_writeback_queued', 'spreadsheet_writeback', $writebackIds[0] ?? null, $auth->user(), [
                'operation' => 'create',
                'status' => 'pending',
                'installments' => count($writebackPayloads),
            ]);
            $session->flash('success', count($writebackPayloads) > 1
                ? 'Despesa parcelada cadastrada. A sincronização ocorrerá em segundo plano.'
                : 'Despesa cadastrada. A sincronização ocorrerá em segundo plano.');
        } else {
            $session->flash('success', 'Lançamento cadastrado com sucesso.');
        }

        return Response::redirect('/entries/create');
    }

    private function money(string $value): float
    {
        $value = trim(str_replace(['R$', ' '], '', $value));

        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) preg_replace('/[^0-9.-]/', '', $value);
    }

    private function installmentPayloads(array $payload, int $installmentCount): array
    {
        if ($installmentCount === 1) {
            return [$payload];
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($payload['date'] ?? ''));

        if (!$date instanceof \DateTimeImmutable) {
            return [$payload];
        }

        $items = [];

        for ($index = 1; $index <= $installmentCount; $index++) {
            $installmentDate = $date->modify('first day of this month')->modify('+' . ($index - 1) . ' months');
            $items[] = [
                ...$payload,
                'date' => $installmentDate->format('Y-m-d'),
                'description' => trim((string) $payload['description']) . " - {$index}/{$installmentCount}",
                'amount' => (float) $payload['amount'],
                'modality' => $index === $installmentCount ? 'ULTIMA PARC.' : 'PARCELADO',
            ];
        }

        return $items;
    }
}
