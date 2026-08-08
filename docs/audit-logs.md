# Logs de Auditoria

## Objetivo

Registrar acoes relevantes dos usuarios para que o administrador consiga acompanhar o uso do sistema pelo painel, sem precisar consultar o banco diretamente.

## Eventos Iniciais

- `login_success`: login realizado com sucesso.
- `login_failed`: tentativa de login com credenciais invalidas.
- `logout`: saida manual do sistema.
- `session_timeout`: saida automatica por inatividade.

## Painel Administrativo

O painel fica em:

```text
/admin/audit-logs
```

Somente usuarios com a permissao `view_audit_logs` podem acessar.

## Retencao

A retencao padrao e de 90 dias, configuravel por:

```env
AUDIT_LOG_RETENTION_DAYS=90
```

O instalador do banco remove registros mais antigos que o limite configurado quando executado:

```bash
php bin/install_database.php
```

## Inatividade

O logout automatico por inatividade usa:

```env
SESSION_IDLE_TIMEOUT_MINUTES=15
```

Quando o limite e ultrapassado, o sistema registra `session_timeout` e encerra a sessao.

