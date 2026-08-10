# Gestao de usuários

O painel de usuários fica em:

```text
/admin/users
```

## Permissão

Somente usuários com a permissão `manage_users` podem acessar a tela. Hoje essa permissão pertence ao perfil `admin`.

## Recursos implementados

- Listar usuários cadastrados.
- Criar usuário com nome, e-mail, perfil e senha temporária.
- Editar nome, e-mail e perfil.
- Alterar senha de um usuário de forma opcional.
- Bloquear e desbloquear usuários.
- Impedir que o administrador bloqueie o próprio usuário.
- Exigir senha do administrador para criar, editar, alterar perfil, alterar senha ou bloquear/desbloquear usuário.

## Auditoria

As ações administrativas sao registradas em `audit_logs`:

- `user_created`
- `user_updated`
- `user_blocked`
- `user_unblocked`

## Exclusão

A exclusão física de usuários não foi implementada de propósito nesta fase. Para preservar histórico e rastreabilidade, o caminho seguro e bloquear/desativar o usuário.
