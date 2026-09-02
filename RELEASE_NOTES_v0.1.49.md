**Detalhes da Versao:**

- Criada a area Sistema > Seguranca e Analytics para configuracoes administrativas.
- Adicionada configuracao de Google Analytics 4 por Measurement ID, carregando a tag apenas quando habilitada.
- Adicionada configuracao de reCAPTCHA v3 invisivel para proteger o login contra bots.
- Implementado rate limit de login por IP/e-mail para reduzir tentativas abusivas.
- Adicionada tela Atualizar senha para usuarios autenticados alterarem a propria senha.
- Melhorada a auditoria de login, falha de login, bloqueio, logout, inatividade, sessao substituida e alteracao de senha com IP e user-agent.
- Ajustado o layout da tela de Seguranca e Analytics e da tela Atualizar senha.
- Atualizada a versao da aplicacao para 0.1.49.

**Proximas Implementacoes:**

- Criar fluxo de retorno para a planilha quando uma alteracao for feita pelo sistema.
