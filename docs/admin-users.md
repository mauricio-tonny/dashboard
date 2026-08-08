# Gestao de usuarios

O painel de usuarios fica em:

```text
/admin/users
```

## Permissao

Somente usuarios com a permissao `manage_users` podem acessar a tela. Hoje essa permissao pertence ao perfil `admin`.

## Recursos implementados

- Listar usuarios cadastrados.
- Criar usuario com nome, e-mail, perfil e senha temporaria.
- Editar nome, e-mail e perfil.
- Alterar senha de um usuario de forma opcional.
- Bloquear e desbloquear usuarios.
- Impedir que o administrador bloqueie o proprio usuario.
- Exigir senha do administrador para criar, editar, alterar perfil, alterar senha ou bloquear/desbloquear usuario.

## Auditoria

As acoes administrativas sao registradas em `audit_logs`:

- `user_created`
- `user_updated`
- `user_blocked`
- `user_unblocked`

## Exclusao

A exclusao fisica de usuarios nao foi implementada de proposito nesta fase. Para preservar historico e rastreabilidade, o caminho seguro e bloquear/desativar o usuario.
