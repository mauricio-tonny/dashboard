**Detalhes da Versão:**
- Revisada a tarefa agendada `Dashboard Spreadsheet Sync` do Windows.
- Confirmado que o agendamento anterior rodava a cada 30 minutos sem limite de janela diária.
- Atualizado o instalador da tarefa para agendar execuções apenas de segunda a sexta.
- Ajustada a janela de sincronização para iniciar às 08:00 e encerrar antes das 18:00, com última execução prevista às 17:30.
- Adicionada proteção no próprio script de sincronização para ignorar execuções fora da janela permitida quando não estiver em modo `-Force`.
- Mantida a execução manual com `-Force` para testes controlados fora da janela.
- Documentada a nova regra de horário no README e na documentação de integração da planilha.
- Atualizada a versão da aplicação para 0.1.42.

**Próximas Implementações:**
- Exibir no painel administrativo o status da última sincronização da planilha.
- Permitir configurar a janela de sincronização pelo painel.
- Registrar tentativas ignoradas de sincronização em uma tela amigável de logs técnicos.
- Integrar a sincronização Windows com o scheduler interno do servidor.
