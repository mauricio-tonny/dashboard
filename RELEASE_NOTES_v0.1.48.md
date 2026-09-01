**Detalhes da Versao:**

- Criado o relatorio Extrato Karina em Relatorios, com total de debitos, total de creditos, saldo e movimentacoes mais recentes no topo.
- Incluida importacao da aba KARINA da planilha para o banco, com idempotencia para evitar duplicidade e normalizacao de categorias.
- Adicionada permissao configuravel para acesso ao Extrato Karina na matriz de permissoes.
- Ajustado o resumo por categoria do Extrato Karina para exibir apenas movimentacoes e valor.
- Implementada sessao unica por usuario, encerrando a sessao anterior quando houver novo login em outro dispositivo ou navegador.
- Adicionado redirecionamento automatico para login apos inatividade conforme `SESSION_IDLE_TIMEOUT_MINUTES`.
- Atualizada a versao da aplicacao para 0.1.48.

**Proximas Implementacoes:**

- Melhoria na seguranca da aplicacao.
- Inclusao de tag do Analytics para acompanhamento.
- Criar fluxo de retorno para a planilha quando uma alteracao for feita pelo sistema.
