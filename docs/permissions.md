# Permissoes

## Perfis

O sistema trabalha com tres perfis principais:

- `viewer`: Visualizador
- `editor`: Editor
- `admin`: Administrador

As permissoes foram modeladas de forma granular para permitir, no futuro, excecoes por usuario sem precisar criar muitos perfis novos.

## Visualizador

Pode:

- Acessar o sistema.
- Ver o valor total de despesas do mes atual.
- Ver totais estimados dos proximos meses.
- Ver relatorio por DR/categoria.
- Acessar e usar listas de compras.
- Visualizar fornecedores e clientes.

Nao pode:

- Lancar despesa.
- Lancar credito.
- Ver custo individual por despesa.
- Ver valor total de entrada/credito.

## Editor

Pode fazer tudo que o Visualizador faz e tambem:

- Lancar debitos.
- Lancar creditos.
- Visualizar despesas individuais.
- Visualizar creditos separadamente e seus totalizadores.
- Confirmar pagamento de despesas.
- Visualizar relatorio mensal.
- Visualizar relatorio anual.
- Visualizar relatorio por periodo.
- Acessar e usar listas de compras.
- Criar, editar, desativar e reativar fornecedores e clientes.

## Administrador

Pode fazer tudo que Editor e Visualizador fazem e tambem:

- Criar usuarios.
- Editar usuarios.
- Bloquear usuarios.
- Excluir usuarios. Nesta fase, por seguranca e auditoria, a exclusao foi implementada como bloqueio/desativacao.
- Vincular usuario a perfil ou permissao especifica.
- Acionar rotina de backup manualmente.
- Habilitar ou desabilitar notificacao do Discord.
- Inserir ou alterar webhook do Discord.
- Inserir, editar ou excluir URL da planilha.
- Visualizar logs de auditoria pelo painel administrativo.
- Gerenciar configuracoes do modulo de compras.

Alteracoes de perfil ou permissao de usuario devem exigir confirmacao de senha do administrador.

## Permissoes Tecnicas

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
