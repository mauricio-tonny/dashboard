# v0.1.51

## Detalhes da Versão

- Adiciona o relatório financeiro por modalidade, com filtro por período e comparação entre despesas à vista, parceladas, últimas parcelas, recorrentes e não identificadas.
- Inclui gráfico comparativo e tabela detalhada com lançamentos, total, valores pagos e valores em aberto por modalidade.
- Adiciona a permissão customizável de visualização do relatório por modalidade na Matriz de Permissões.
- Registra o acesso ao novo relatório na auditoria do sistema.
- Corrige a sincronização da planilha para remover do sistema lançamentos financeiros que deixaram de existir nas abas mensais.
- Preserva lançamentos manuais e não remove dados de abas mensais que não estejam presentes no arquivo importado.
- Atualiza os metadados da sincronização e as notificações do Discord para informar lançamentos removidos separadamente dos atualizados.

## Próximas Implementações

- Criar fluxo de retorno para a planilha quando uma alteração for feita pelo sistema.
