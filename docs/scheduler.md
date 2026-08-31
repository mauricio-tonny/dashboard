# Scheduler

O dashboard usa um único CRON do sistema operacional para acionar o scheduler interno da aplicação.

## Comando do CRON

Configure o servidor para executar a cada minuto:

```bash
* * * * * cd /var/www/dashboard.oficinadodev.com.br/html && php bin/schedule_run.php >> storage/logs/scheduler.log 2>&1
```

O comando apenas dispara o motor interno. Cada rotina decide se está pendente, respeitando frequência, lock e status no banco.

## Migração de Servidor

Ao migrar o projeto para outro servidor, confirme estes pontos:

1. O `.env` está configurado.
2. O banco foi instalado ou atualizado com `php bin/install_database.php`.
3. O diretório `storage/logs` existe e tem permissão de escrita.
4. O CRON único foi cadastrado para o usuário correto do servidor.
5. O scheduler roda manualmente com `php bin/schedule_run.php`.

Para validar o log:

```bash
tail -n 50 storage/logs/scheduler.log
```

## Tabelas

- `scheduled_tasks`: cadastro e estado atual de cada rotina.
- `scheduled_task_runs`: histórico de execuções, sucesso/falha, mensagem, duração e metadados.

## Primeira Rotina

- `market.ensure_next_list`: garante a lista de mercado do próximo mês uma vez ao dia e reaproveita a notificação do Discord quando uma lista nova é criada.

## Importação da Planilha

- `spreadsheet.import`: importa a planilha financeira sincronizada para o banco, quando estiver ativa em `/system/sync`.

O painel `/system/sync` permite configurar se a rotina está ativa, o intervalo e a janela permitida. Por padrão, a janela sugerida é de segunda a sexta, das 08:00 às 18:00. Execuções fora da janela são registradas como `skipped` em `scheduled_task_runs`, sem tentar importar a planilha.

Essa tela também mostra o fluxo visual da sincronização: envio pelo Windows/OneDrive, arquivo recebido no servidor e última importação no banco. A comparação usa o último callback do script Windows, a data de modificação do arquivo configurado em `EXCEL_FILE` e o último log de `spreadsheet.import`, facilitando identificar se a falha está no envio local ou no processamento do servidor. O envio local é incremental: quando o hash da planilha não muda, o Windows registra `unchanged` e não reenvia o arquivo.

O callback do Windows usa a rota `POST /system/sync/windows-callback`, protegida por `WINDOWS_SYNC_CALLBACK_TOKEN`. Quando o status recebido é `uploaded`, o scheduler antecipa a próxima execução de `spreadsheet.import`, sem criar um CRON separado.

A task executa internamente:

```bash
php bin/import_spreadsheet.php --apply
```

O importador e idempotente: os lancamentos usam `source_system = spreadsheet` e `source_key` baseado em aba/linha da planilha. Novas execucoes gravam somente lancamentos novos ou alterados; quando a planilha esta igual, a execucao fica registrada em `import_runs`, mas nao duplica o historico em `entry_sources`.

As notificacoes do Discord para essa rotina ficam em `/system/discord` e podem ser habilitadas separadamente para:

- importacao com lancamentos novos/alterados;
- importacao sem alteracoes na planilha.

## Compatibilidade

O comando antigo `php bin/ensure_next_market_list.php` continua disponível, mas agora reaproveita a mesma task usada pelo scheduler.
