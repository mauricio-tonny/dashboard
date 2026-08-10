**Detalhes da Versão:**
- Criado botão de teste do webhook do Discord no painel de configurações.
- Registrado o teste do webhook do Discord nos logs de auditoria.
- Implementado scheduler interno com execução centralizada por um único CRON.
- Criadas tabelas para controle de tarefas agendadas, locks e histórico de execuções.
- Migrada a rotina de criação automática da lista de mercado para o novo scheduler.
- Mantida compatibilidade com o comando antigo `php bin/ensure_next_market_list.php`.
- Instalado CRON único do dashboard no servidor para executar `php bin/schedule_run.php`.
- Normalizadas acentuações e cedilha em textos de interface, mensagens, logs e documentação.

**Próximas Implementações:**
- Iniciar integração com a planilha compartilhada.
- Criar tasks do scheduler para consulta e sincronização da planilha.
- Mapear bases de fornecedores/clientes e categorias a partir da planilha.
