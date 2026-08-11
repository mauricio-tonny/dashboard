**Detalhes da Versão:**
- Criada configuração segura da planilha em `/system/spreadsheet`, com link criptografado no banco.
- Adicionada `APP_KEY` para criptografia de segredos da aplicação.
- Criada tabela `spreadsheet_settings` para armazenar a configuração da planilha.
- Implementada sincronização da planilha pelo Windows via `scp`, usando o OneDrive local como origem.
- Criada tarefa agendada `Dashboard Spreadsheet Sync` para enviar a planilha ao servidor a cada 30 minutos.
- Ajustada a tarefa agendada para rodar em modo oculto, sem abrir CMD/PowerShell na tela.
- Configurado o servidor para ler a planilha em `storage/private/spreadsheets/financeiro.xlsx`.
- Adicionado Composer e dependência `phpoffice/phpspreadsheet`.
- Criado comando `php bin/inspect_spreadsheet.php` para diagnosticar abas, cabeçalhos, salários, totais e layouts.
- Criado comando `php bin/analyze_spreadsheet_normalization.php` para analisar fornecedores, categorias, parcelas, cartões antigos e pendências de normalização.
- Documentadas as regras iniciais da planilha, incluindo layouts históricos, aba `BASE`, `MODALIDADE`, cartões por cor e comandos de diagnóstico.

**Próximas Implementações:**
- Criar importador real da planilha para alimentar `vendors`, `categories`, `entries`, `entry_sources` e `import_runs`.
- Rodar primeiro em modo `--dry-run` para validar contagens e normalizações.
- Criar telas financeiras de `A pagar` e `A receber` usando os dados importados.
