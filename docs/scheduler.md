# Scheduler

O dashboard usa um único CRON do sistema operacional para acionar o scheduler interno da aplicação.

## Comando do CRON

Configure o servidor para executar a cada minuto:

```bash
* * * * * cd /var/www/dashboard.oficinadodev.com.br/html && php bin/schedule_run.php
```

O comando apenas dispara o motor interno. Cada rotina decide se está pendente, respeitando frequência, lock e status no banco.

## Tabelas

- `scheduled_tasks`: cadastro e estado atual de cada rotina.
- `scheduled_task_runs`: histórico de execuções, sucesso/falha, mensagem, duração e metadados.

## Primeira rotina

- `market.ensure_next_list`: garante a lista de mercado do próximo mês uma vez ao dia e reaproveita a notificação do Discord quando uma lista nova e criada.

## Compatibilidade

O comando antigo `php bin/ensure_next_market_list.php` continua disponível, mas agora reaproveita a mesma task usada pelo scheduler.
