<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;

final class NavigationPageController extends Controller
{
    public function financePayable(Request $request): Response
    {
        return $this->placeholder('A pagar', 'Financeiro', 'Visualize e confirme contas a pagar por mes. A regra detalhada sera definida na integracao financeira.', Permission::VIEW_EXPENSE_TOTALS);
    }

    public function financeReceivable(Request $request): Response
    {
        return $this->placeholder('A receber', 'Financeiro', 'Visualize e confirme contas a receber por mes. A regra detalhada sera definida na integracao financeira.', Permission::VIEW_INCOME_TOTALS);
    }

    public function reports(Request $request): Response
    {
        return $this->placeholder('Relatorios', 'Relatorios', 'Area reservada para relatorios mensal, anual, por periodo e indicadores personalizados.', Permission::VIEW_CATEGORY_REPORT);
    }

    public function systemBackup(Request $request): Response
    {
        return $this->placeholder('Backup', 'Sistema', 'Area reservada para acionar backup manualmente.', Permission::MANAGE_BACKUPS);
    }

    public function systemSync(Request $request): Response
    {
        return $this->placeholder('Tempo de sincronizacao', 'Sistema', 'Configuracao futura para desligado, 1h, 2h, 4h ou 5h.', Permission::MANAGE_SPREADSHEET_URL);
    }

    public function systemCategories(Request $request): Response
    {
        return $this->placeholder('Cadastro de Categoria (DR)', 'Sistema', 'Area reservada para gerenciar categorias/DR.', Permission::MANAGE_SPREADSHEET_URL);
    }

    public function systemDiscord(Request $request): Response
    {
        return $this->placeholder('Discord', 'Sistema', 'Area reservada para habilitar notificacoes e cadastrar webhook.', Permission::MANAGE_DISCORD_NOTIFICATIONS);
    }

    public function systemSpreadsheet(Request $request): Response
    {
        return $this->placeholder('Gerenciar planilha', 'Sistema', 'Area reservada para cadastrar, editar ou remover a URL da planilha.', Permission::MANAGE_SPREADSHEET_URL);
    }

    private function placeholder(string $title, string $section, string $description, Permission $permission): Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can($permission)) {
            return new Response('Acesso negado.', 403);
        }

        return Response::view('placeholder/index', [
            'user' => $auth->user(),
            'title' => $title,
            'section' => $section,
            'description' => $description,
        ]);
    }
}
