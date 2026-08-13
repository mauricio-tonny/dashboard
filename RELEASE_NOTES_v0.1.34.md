**Detalhes da Versao:**
- Reformulado o layout da tela `/system/discord` para um fluxo vertical mais simples de configurar.
- Substituido o dropdown de status por um switch ON/OFF mais claro.
- Webhook, eventos e teste do Discord agora aparecem somente quando as notificacoes estao habilitadas.
- Ajustada a tela para crescer melhor conforme novos eventos de notificacao forem adicionados.
- Atualizado `APP_VERSION` para `0.1.34`.

**Validacoes:**
- Deploy aplicado no servidor.
- `php -l` executado com sucesso em `views/system/discord/index.php` e `views/layout.php`.
- Rota `/system/discord` validada no servidor com resposta protegida por login.

**Proximos Passos:**
- Avancar nas telas internas do financeiro usando a base importada da planilha.
- Iniciar a modelagem visual de contas a pagar e contas a receber.
