# Permissões

## Perfis

O sistema trabalha com três perfis principais:

- `viewer`: Visualizador
- `editor`: Editor
- `admin`: Administrador

As permissões foram modeladas de forma granular para permitir, no futuro, excecoes por usuário sem precisar criar muitos perfis novos.

## Visualizador

Pode:

- Acessar o sistema.
- Ver o valor total de despesas do mês atual.
- Ver totais estimados dos proximos meses.
- Ver relatório por DR/categoria.
- Acessar e usar listas de compras.
- Visualizar fornecedores e clientes.

Não pode:

- Lancar despesa.
- Lancar credito.
- Ver custo individual por despesa.
- Ver valor total de entrada/credito.

## Editor

Pode fazer tudo que o Visualizador faz e também:

- Lancar debitos.
- Lancar creditos.
- Visualizar despesas individuais.
- Visualizar creditos separadamente e seus totalizadores.
- Confirmar pagamento de despesas.
- Visualizar relatório mensal.
- Visualizar relatório anual.
- Visualizar relatório por período.
- Acessar e usar listas de compras.
- Criar, editar, desativar e reativar fornecedores e clientes.

## Administrador

Pode fazer tudo que Editor e Visualizador fazem e também:

- Criar usuários.
- Editar usuários.
- Bloquear usuários.
- Excluir usuários. Nesta fase, por segurança e auditoria, a exclusão foi implementada como bloqueio/desativação.
- Vincular usuário a perfil ou permissão especifica.
- Acionar rotina de backup manualmente.
- Habilitar ou desabilitar notificação do Discord.
- Inserir ou alterar webhook do Discord.
- Inserir, editar ou excluir URL da planilha.
- Visualizar logs de auditoria pelo painel administrativo.
- Gerenciar configurações do módulo de compras.

Alterações de perfil ou permissão de usuário devem exigir confirmação de senha do administrador.

## Permissões Tecnicas

- `view_dashboard`
- `view_expense_totals`
- `view_future_expense_totals`
- `view_category_report`
- `create_expense`
- `create_income`
- `view_individual_expenses`
- `view_income_totals`
- `confirm_expense_payment`
- `view_monthly_report`
- `view_annual_report`
- `view_period_report`
- `view_shopping`
- `manage_shopping`
- `manage_shopping_settings`
- `view_contacts`
- `manage_contacts`
- `view_audit_logs`
- `manage_users`
- `change_user_roles`
- `manage_backups`
- `manage_discord_notifications`
- `manage_spreadsheet_url`
