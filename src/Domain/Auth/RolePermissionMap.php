<?php

declare(strict_types=1);

namespace App\Domain\Auth;

final class RolePermissionMap
{
    public static function grants(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::permissionsFor($role), true);
    }

    /**
     * @return Permission[]
     */
    public static function permissionsFor(Role $role): array
    {
        return match ($role) {
            Role::VIEWER => [
                Permission::VIEW_DASHBOARD,
                Permission::VIEW_EXPENSE_TOTALS,
                Permission::VIEW_FUTURE_EXPENSE_TOTALS,
                Permission::VIEW_CATEGORY_REPORT,
                Permission::VIEW_SHOPPING,
                Permission::MANAGE_SHOPPING,
            ],
            Role::EDITOR => [
                ...self::permissionsFor(Role::VIEWER),
                Permission::CREATE_EXPENSE,
                Permission::CREATE_INCOME,
                Permission::VIEW_INDIVIDUAL_EXPENSES,
                Permission::VIEW_INCOME_TOTALS,
                Permission::CONFIRM_EXPENSE_PAYMENT,
                Permission::VIEW_MONTHLY_REPORT,
                Permission::VIEW_ANNUAL_REPORT,
                Permission::VIEW_PERIOD_REPORT,
            ],
            Role::ADMIN => [
                ...self::permissionsFor(Role::EDITOR),
                Permission::VIEW_AUDIT_LOGS,
                Permission::MANAGE_USERS,
                Permission::CHANGE_USER_ROLES,
                Permission::MANAGE_SHOPPING_SETTINGS,
                Permission::MANAGE_BACKUPS,
                Permission::MANAGE_DISCORD_NOTIFICATIONS,
                Permission::MANAGE_SPREADSHEET_URL,
            ],
        };
    }

    public static function definitions(): array
    {
        return [
            Permission::VIEW_DASHBOARD->value => ['Visualizar dashboard', 'Permite acessar a area autenticada do sistema.'],
            Permission::VIEW_EXPENSE_TOTALS->value => ['Ver total de despesas', 'Permite visualizar o total de despesas do mes atual.'],
            Permission::VIEW_FUTURE_EXPENSE_TOTALS->value => ['Ver despesas futuras', 'Permite visualizar totais estimados dos proximos meses.'],
            Permission::VIEW_CATEGORY_REPORT->value => ['Ver relatorio por DR', 'Permite visualizar relatorios consolidados por categoria/DR.'],
            Permission::CREATE_EXPENSE->value => ['Lancar debitos', 'Permite inserir novas despesas.'],
            Permission::CREATE_INCOME->value => ['Lancar creditos', 'Permite inserir entradas de dinheiro.'],
            Permission::VIEW_INDIVIDUAL_EXPENSES->value => ['Ver despesas individuais', 'Permite visualizar valores individuais por despesa.'],
            Permission::VIEW_INCOME_TOTALS->value => ['Ver creditos', 'Permite visualizar creditos separadamente e seus totalizadores.'],
            Permission::CONFIRM_EXPENSE_PAYMENT->value => ['Confirmar pagamentos', 'Permite confirmar pagamento de despesas.'],
            Permission::VIEW_MONTHLY_REPORT->value => ['Ver relatorio mensal', 'Permite visualizar relatorios mensais.'],
            Permission::VIEW_ANNUAL_REPORT->value => ['Ver relatorio anual', 'Permite visualizar relatorios anuais.'],
            Permission::VIEW_PERIOD_REPORT->value => ['Ver relatorio por periodo', 'Permite visualizar relatorios por periodo customizado.'],
            Permission::VIEW_SHOPPING->value => ['Ver compras', 'Permite acessar listas de compras.'],
            Permission::MANAGE_SHOPPING->value => ['Gerenciar compras', 'Permite criar listas, inserir itens, marcar itens e informar valor total.'],
            Permission::MANAGE_SHOPPING_SETTINGS->value => ['Configurar compras', 'Permite gerenciar comodos, pessoas, veiculos e areas do modulo de compras.'],
            Permission::VIEW_AUDIT_LOGS->value => ['Ver logs de auditoria', 'Permite visualizar logs de acesso e acoes de usuarios pelo painel.'],
            Permission::MANAGE_USERS->value => ['Gerenciar usuarios', 'Permite criar, editar, bloquear e excluir usuarios.'],
            Permission::CHANGE_USER_ROLES->value => ['Alterar perfil de usuario', 'Permite vincular usuarios a perfis ou permissoes, exigindo confirmacao de senha.'],
            Permission::MANAGE_BACKUPS->value => ['Acionar backup manual', 'Permite executar rotina de backup manualmente.'],
            Permission::MANAGE_DISCORD_NOTIFICATIONS->value => ['Gerenciar Discord', 'Permite habilitar, desabilitar e configurar webhook do Discord.'],
            Permission::MANAGE_SPREADSHEET_URL->value => ['Gerenciar URL da planilha', 'Permite inserir, editar ou excluir a URL da planilha.'],
        ];
    }
}
