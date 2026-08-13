**Detalhes da Versao:**
- Criada a primeira tela real de `Contas a pagar`, consumindo os lancamentos importados da planilha.
- Adicionados filtros por mes inicial/final e selecao multipla de categorias em painel recolhivel.
- Implementados totalizadores de despesas do periodo, falta pagar, ja pago e ultimas parcelas.
- Ajustada a regra de `Falta pagar` para somar pendencias anteriores somente nesse indicador.
- Criada tabela de despesas por periodo com status, categoria, fornecedor, parcela e valor.
- Adicionados grafico vertical de despesas por categoria e resumo por categoria.
- Atualizada a dashboard para exibir `Contas a pagar` com dados reais do proximo periodo e pendencias abertas.
- Atualizada a dashboard com `Despesas anual` real por mes.
- Atualizada a dashboard com `Despesas por DR anual` em grafico pizza por categoria.
- Ajustado o card `Para casa` da dashboard para usar scroll interno e ocupar menos altura.
- Importador passa a ler a coluna `G` a partir de `JUL- 26`: `OK` marca o lancamento como pago.
- Corrigida a interpretacao de parcelas para ignorar datas entre parenteses, como `(01/04)`.
- Atualizado `APP_VERSION` para `0.1.35`.

**Validacoes:**
- Deploy aplicado no servidor.
- Reimportacao executada sem duplicar lancamentos.
- `php -l` validado nos arquivos PHP alterados durante a implementacao.
- Consultas reais conferidas para dashboard, totalizadores, despesas anuais e DR anual.

**Proximas Melhorias:**
- Criar relatorios para `Contas a pagar`.
- Atuar no modulo `Recebido`.
- Criar relatorios para `Recebido`.
- Planejar e atuar na tela de `Contatos` (Fornecedores e Clientes).
- Criar fluxo de retorno para a planilha quando uma alteracao for feita pelo sistema.
