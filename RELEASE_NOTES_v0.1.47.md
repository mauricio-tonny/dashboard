**Detalhes da Versão:**
- Unificado o fluxo visual de sincronização da planilha em um único bloco na tela Sistema > Sincronização.
- Adicionada comparação entre Windows/OneDrive, arquivo recebido no servidor e importação no banco.
- Criado registro de eventos do sincronizador Windows no servidor.
- Adicionado callback seguro para o Windows informar eventos `uploaded`, `unchanged`, `failed` e `skipped`.
- Antecipada a próxima importação interna quando o Windows informa um novo upload da planilha.
- Mantido fallback por data, tamanho e hash do arquivo recebido quando ainda não houver callback do Windows.
- Atualizado o script PowerShell para registrar eventos no dashboard sem interromper o upload em caso de falha no callback.
- Adicionada documentação da integração ponta a ponta entre Windows e scheduler interno.
- Atualizada a versão da aplicação para 0.1.47.

**Próximas Implementações:**
- Implementar Extrato Karina (Crédito x Débito).
- Criar fluxo de retorno para a planilha quando uma alteracao for feita pelo sistema.
