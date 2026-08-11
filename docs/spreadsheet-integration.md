# Integração com a Planilha

## Objetivo

Usar a planilha financeira como fonte inicial dos dados, importar seu conteúdo para MariaDB e permitir que o dashboard consulte o banco com segurança, auditoria e melhor performance.

## Caminho Recomendado

1. Manter a planilha fora da área pública do servidor.
2. Sincronizar o arquivo original do OneDrive local do Windows para o servidor via `scp`.
3. Salvar uma cópia temporária em `IMPORT_TEMP_DIR`.
4. Processar o `.xlsx` com uma biblioteca própria para planilhas.
5. Importar fornecedores, clientes, categorias, lançamentos e status para tabelas relacionais.
6. Registrar cada execução em logs de importação e auditoria.
7. Executar sincronizações recorrentes pelo scheduler interno.

## Sobre Microsoft Graph

O Microsoft Graph não será tratado como dependência padrão do projeto neste momento. Ele é mais adequado quando há conta corporativa/organizacional, permissões administrativas e uma aplicação registrada no ambiente Microsoft Entra ID.

Para o cenário atual, a prioridade é uma integração mais simples e portátil, sem exigir estrutura empresarial. Se no futuro houver uma conta Microsoft corporativa, o Graph pode voltar como alternativa avançada.

## Variáveis Envolvidas

- `APP_KEY`: chave usada para criptografar o link salvo no banco.
- `EXCEL_FILE`: caminho local da planilha no servidor.
- `SHARED_EXCEL_URL`: link compartilhado para baixar a planilha, quando essa estratégia for usada.
- `IMPORT_TEMP_DIR`: diretório temporário usado durante a importação.
- `IMPORT_TIMEZONE`: fuso usado para datas de importação e logs.

## Sincronização Pelo Windows

Como o link público do OneDrive pode entregar uma página intermediária em vez do `.xlsx`, o caminho mais previsível é usar o computador Windows como ponte segura:

1. O aplicativo do OneDrive mantém a planilha original sincronizada localmente.
2. A tarefa agendada do Windows roda `scripts/sync-spreadsheet.ps1`.
3. O script calcula o hash local e só envia o arquivo quando houver alteração.
4. O envio é feito via `scp` para `storage/private/spreadsheets/financeiro.xlsx`.
5. O dashboard usa `EXCEL_FILE` para ler sempre a última cópia recebida.

Para configurar:

```powershell
Copy-Item scripts\spreadsheet-sync.example.ps1 scripts\spreadsheet-sync.local.ps1
notepad scripts\spreadsheet-sync.local.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\sync-spreadsheet.ps1 -Force
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\install-spreadsheet-sync-task.ps1
```

O arquivo `scripts/spreadsheet-sync.local.ps1` fica fora do Git. Ele guarda caminhos específicos da máquina, chave SSH, porta e destino remoto.

## Configuração no Painel

A tela `/system/spreadsheet` permite salvar o link compartilhado da planilha. O valor é criptografado antes de ser persistido no banco e aparece apenas mascarado na interface.

Antes de salvar o link, configure `APP_KEY` no `.env`:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

O link salvo deve ter permissão de leitura. Para a primeira fase, o sistema apenas valida acesso e prepara o caminho para importação, sem alterar a planilha original.

## Regras do Parser

- A aba `BASE` deve alimentar cadastros auxiliares como fornecedores, clientes e categorias.
- As abas mensais devem ser convertidas para um formato único de lançamentos.
- Abas anuais de relatório devem ser ignoradas pelo parser principal.
- Fórmulas da planilha não devem ser quebradas durante etapas de leitura ou escrita.
- Toda importação precisa gerar histórico suficiente para auditoria e diagnóstico.

## Escrita Segura

A escrita de volta na planilha só deve ser implementada com estes cuidados:

- Backup antes de cada alteração.
- Registro de quem alterou, quando alterou e o que alterou.
- Validação para impedir gravação em abas fora do padrão.
- Controle contra conflito com edição manual simultânea.
- Preferência por gravar primeiro no banco e sincronizar a planilha por uma task do scheduler.

## Próxima Implementação

1. Mapear o layout real da planilha.
2. Definir a origem inicial do arquivo no servidor.
3. Criar o importador do `.xlsx`.
4. Criar o parser da aba `BASE`.
5. Criar o parser de uma aba mensal piloto.
6. Persistir os dados normalizados em MariaDB.
7. Agendar consulta e envio pelo scheduler interno.
