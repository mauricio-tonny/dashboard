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
            Role::VALIDATOR => [
                Permission::VIEW_DASHBOARD,
                Permission::VIEW_EXPENSE_TOTALS,
                Permission::VIEW_FUTURE_EXPENSE_TOTALS,
                Permission::VIEW_CATEGORY_REPORT,
                Permission::VIEW_CATEGORY_CHART_REPORT,
                Permission::VIEW_VENDOR_REPORT,
                Permission::VIEW_PAID_VS_RECEIVED_REPORT,
                Permission::VIEW_FINANCIAL_PLAN_REPORT,
                Permission::VIEW_CASHFLOW_REPORT,
                Permission::VIEW_MODALITY_REPORT,
                Permission::VIEW_MARKET_REPORT,
                Permission::VIEW_INDIVIDUAL_EXPENSES,
                Permission::VIEW_INCOME_TOTALS,
                Permission::VIEW_MONTHLY_REPORT,
                Permission::VIEW_ANNUAL_REPORT,
                Permission::VIEW_PERIOD_REPORT,
                Permission::VIEW_SHOPPING,
                Permission::VIEW_CONTACTS,
            ],
            Role::VIEWER => [
                Permission::VIEW_DASHBOARD,
                Permission::VIEW_EXPENSE_TOTALS,
                Permission::VIEW_FUTURE_EXPENSE_TOTALS,
                Permission::VIEW_CATEGORY_REPORT,
                Permission::VIEW_CATEGORY_CHART_REPORT,
                Permission::VIEW_VENDOR_REPORT,
                Permission::VIEW_PAID_VS_RECEIVED_REPORT,
                Permission::VIEW_FINANCIAL_PLAN_REPORT,
                Permission::VIEW_CASHFLOW_REPORT,
                Permission::VIEW_MODALITY_REPORT,
                Permission::VIEW_MARKET_REPORT,
                Permission::VIEW_SHOPPING,
                Permission::MANAGE_SHOPPING,
                Permission::VIEW_CONTACTS,
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
                Permission::MANAGE_CONTACTS,
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
                Permission::MANAGE_CATEGORY_BUDGET_TARGETS,
                Permission::MANAGE_FAMILY_PROFILE,
                Permission::MANAGE_FINANCIAL_GOALS,
                Permission::MANAGE_SECURITY_SETTINGS,
                Permission::VIEW_KARINA_STATEMENT_REPORT,
            ],
        };
    }

    public static function definitions(): array
    {
        return [
            Permission::VIEW_DASHBOARD->value => ['Visualizar dashboard', 'Permite acessar a área autenticada do sistema.'],
            Permission::VIEW_EXPENSE_TOTALS->value => ['Ver total de despesas', 'Permite visualizar o total de despesas do mês atual.'],
            Permission::VIEW_FUTURE_EXPENSE_TOTALS->value => ['Ver despesas futuras', 'Permite visualizar totais estimados dos próximos meses.'],
            Permission::VIEW_CATEGORY_REPORT->value => ['Relatório por categoria', 'Permite visualizar a lista de despesas por categoria e período.'],
            Permission::VIEW_CATEGORY_CHART_REPORT->value => ['Relatório gráfico por categoria', 'Permite visualizar o gráfico de despesas por categoria.'],
            Permission::VIEW_VENDOR_REPORT->value => ['Relatório por fornecedor', 'Permite visualizar despesas agrupadas por fornecedor.'],
            Permission::VIEW_PAID_VS_RECEIVED_REPORT->value => ['Relatório Pago x Recebido', 'Permite visualizar o comparativo de entradas e saídas por período.'],
            Permission::VIEW_FINANCIAL_PLAN_REPORT->value => ['Plano financeiro', 'Permite visualizar diagnóstico financeiro, pontos de corte e plano de evolução patrimonial.'],
            Permission::VIEW_CASHFLOW_REPORT->value => ['Relatório Fluxo de Caixa', 'Permite visualizar o extrato de entradas, saídas e saldo acumulado.'],
            Permission::VIEW_MODALITY_REPORT->value => ['Relatório por modalidade', 'Permite comparar despesas à vista, parceladas, últimas parcelas e recorrentes por período.'],
            Permission::VIEW_KARINA_STATEMENT_REPORT->value => ['Extrato Karina', 'Permite visualizar o extrato de crédito x débito da Karina.'],
            Permission::VIEW_MARKET_REPORT->value => ['Relatório de Mercado', 'Permite visualizar os relatórios de compras de mercado.'],
            Permission::CREATE_EXPENSE->value => ['Lançar débitos', 'Permite inserir novas despesas.'],
            Permission::CREATE_INCOME->value => ['Lançar créditos', 'Permite inserir entradas de dinheiro.'],
            Permission::VIEW_INDIVIDUAL_EXPENSES->value => ['Ver despesas individuais', 'Permite visualizar valores individuais por despesa.'],
            Permission::VIEW_INCOME_TOTALS->value => ['Ver créditos', 'Permite visualizar créditos separadamente e seus totalizadores.'],
            Permission::CONFIRM_EXPENSE_PAYMENT->value => ['Confirmar pagamentos', 'Permite confirmar pagamento de despesas.'],
            Permission::VIEW_MONTHLY_REPORT->value => ['Ver relatório mensal', 'Permite visualizar relatórios mensais.'],
            Permission::VIEW_ANNUAL_REPORT->value => ['Ver relatório anual', 'Permite visualizar relatórios anuais.'],
            Permission::VIEW_PERIOD_REPORT->value => ['Ver relatório por período', 'Permite visualizar relatórios por período customizado.'],
            Permission::VIEW_SHOPPING->value => ['Ver compras', 'Permite acessar listas de compras.'],
            Permission::MANAGE_SHOPPING->value => ['Gerenciar compras', 'Permite criar listas, inserir itens, marcar itens e informar valor total.'],
            Permission::MANAGE_SHOPPING_SETTINGS->value => ['Configurar compras', 'Permite gerenciar cômodos, pessoas, veículos e áreas do módulo de compras.'],
            Permission::VIEW_CONTACTS->value => ['Ver contatos', 'Permite acessar fornecedores e clientes.'],
            Permission::MANAGE_CONTACTS->value => ['Gerenciar contatos', 'Permite criar, editar, desativar e reativar fornecedores e clientes.'],
            Permission::VIEW_AUDIT_LOGS->value => ['Ver logs de auditoria', 'Permite visualizar logs de acesso e ações de usuários pelo painel.'],
            Permission::MANAGE_USERS->value => ['Gerenciar usuários', 'Permite criar, editar, bloquear e excluir usuários.'],
            Permission::CHANGE_USER_ROLES->value => ['Alterar perfil de usuário', 'Permite vincular usuários a perfis ou permissões, exigindo confirmação de senha.'],
            Permission::MANAGE_BACKUPS->value => ['Acionar backup manual', 'Permite executar rotina de backup manualmente.'],
            Permission::MANAGE_DISCORD_NOTIFICATIONS->value => ['Gerenciar Discord', 'Permite habilitar, desabilitar e configurar webhook do Discord.'],
            Permission::MANAGE_SPREADSHEET_URL->value => ['Gerenciar URL da planilha', 'Permite inserir, editar ou excluir a URL da planilha.'],
            Permission::MANAGE_CATEGORY_BUDGET_TARGETS->value => ['Gerenciar metas por categoria', 'Permite configurar metas percentuais por categoria/DR.'],
            Permission::MANAGE_FAMILY_PROFILE->value => ['Gerenciar perfil familiar', 'Permite cadastrar composição familiar, pets e região para contextualizar o plano financeiro.'],
            Permission::MANAGE_FINANCIAL_GOALS->value => ['Gerenciar objetivos financeiros', 'Permite cadastrar metas patrimoniais usadas no plano financeiro.'],
            Permission::MANAGE_SECURITY_SETTINGS->value => ['Gerenciar segurança e analytics', 'Permite configurar reCAPTCHA, Analytics e controles de segurança.'],
        ];
    }
}
