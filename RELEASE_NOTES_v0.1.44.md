**Detalhes da Versão:**
- Implementado painel real em Sistema > Tempo de sincronização.
- Exibido status da última sincronização da planilha, próxima tentativa, último resultado e falhas consecutivas.
- Implementada configuração da rotina pelo painel: ativar/desativar, intervalo e janela horária.
- Movido controle da importação automática da planilha do .env para o banco/painel administrativo.
- Registradas execuções ignoradas fora da janela permitida como status skipped em scheduled_task_runs.
- Criada tabela amigável de logs técnicos das últimas execuções da task spreadsheet.import.
- Atualizado scheduler interno para respeitar segunda a sexta e janela configurada antes de importar.
- Preservada no servidor a rotina ativa com intervalo de 30 minutos e janela 08:00-18:00.
- Atualizada documentação do scheduler e da integração da planilha.
- Atualizada a versão da aplicação para 0.1.44.
- Validado no servidor com atualização do banco e lint PHP.

**Próximas Implementações:**
- Permitir acionar uma sincronização manual pelo painel com confirmação.
- Exibir diferença entre envio da planilha pelo Windows e importação interna do servidor em um fluxo visual.
- Criar alerta visual quando a última sincronização estiver muito antiga.
- Permitir exportar logs técnicos da sincronização em CSV.
