# Dashboard Financeiro Pessoal

Aplicacao PHP 8.3 para registrar movimentacoes financeiras, importar dados de uma planilha Excel hospedada no OneDrive e gerar relatorios, graficos e estimativas com base no historico auditado desde 2015.

## Objetivos

- Autenticacao com login e senha.
- Controle de acesso por perfil.
- Inclusao e edicao de lancamentos no mes atual e meses futuros.
- Leitura da planilha Excel como fonte de dados principal.
- Relatorios, indicadores e estimativas financeiras.
- Base segura para uso domestico e portfolio.

## Arquitetura Inicial

- `public/`: ponto de entrada HTTP.
- `src/Core`: infraestrutura basica como roteamento, sessao, resposta e autenticacao.
- `src/Controllers`: controladores web.
- `src/Domain`: regras de negocio.
- `src/Infrastructure`: acesso a dados, incluindo adaptadores para Excel.
- `src/Support`: funcoes utilitarias.
- `database/`: schema inicial MariaDB e futuras migracoes.
- `storage/`: arquivos de aplicacao, logs e dados locais.

## Perfis de Acesso

- `admin`: gerencia usuarios, configuracoes e acesso total.
- `editor`: pode inserir e editar lancamentos autorizados.
- `viewer`: apenas consulta paines, relatorios e previsoes.

## Seguranca Planejada

- Senhas com `password_hash`.
- Sessao regenerada no login.
- Middleware de autenticacao e autorizacao.
- Validacao centralizada de entrada.
- Configuracao fora do codigo via variaveis de ambiente.
- Planilha armazenada fora do diretorio publico.
- Logs de auditoria para alteracoes sensiveis.

## Integracao com Excel e Banco

A estrategia principal passa a ser uma arquitetura hibrida:

1. Ler a planilha compartilhada no OneDrive.
2. Importar os dados para MariaDB.
3. Usar o banco como fonte principal do dashboard.
4. Manter uma rotina de sincronizacao via `cron`.

A base inicial ja separa a aplicacao por interfaces para permitir tres etapas:

1. Captura do arquivo compartilhado.
2. Processamento do `.xlsx`.
3. Persistencia relacional para consultas, auditoria e evolucao futura.

Para producao, a melhor opcao tende a ser:

- Banco MariaDB isolado da camada web.
- Importacoes controladas, com trilha de auditoria e logs de sincronizacao.
- Backups automaticos da planilha e do banco.
- Sessao, autenticacao e autorizacao tratadas na aplicacao.
- Evolucao gradual ate o banco virar a fonte principal.

## Rodando Localmente

Com PHP 8.2+ instalado:

```bash
php -S localhost:8000 -t public
```

Depois abra `http://localhost:8000`.

## Preparando o Banco

Depois de preencher o `.env` no servidor:

```bash
php bin/install_database.php
```

Para criar ou atualizar um usuario:

```bash
php bin/create_user.php --name="Nome" --email=email@dominio.com --role=viewer --password="senha-temporaria"
```

## Proximos Passos Recomendados

1. Definir o layout exato da planilha atual.
2. Escolher se a escrita sera direta no `.xlsx` ou via sincronizacao.
3. Adicionar `composer`, cliente HTTP e `phpoffice/phpspreadsheet`.
4. Criar o importador da planilha compartilhada.
5. Criar o parser da aba `BASE` e das abas mensais.
6. Implementar persistencia real em MariaDB.
7. Implementar sincronizacao por `cron`.
8. Criar o primeiro dashboard com resumo mensal, contas a pagar e previsao do proximo mes.

## Documentacao de Integracao

- Visao geral da arquitetura hibrida: [docs/architecture.md](D:/GITHUB/mauricio-tonny/dashboard/docs/architecture.md)
- Fluxo previsto para OneDrive e Microsoft Graph: [docs/microsoft-graph.md](D:/GITHUB/mauricio-tonny/dashboard/docs/microsoft-graph.md)
- Configuracao sugerida do NGINX: [docs/nginx.md](D:/GITHUB/mauricio-tonny/dashboard/docs/nginx.md)
- Matriz de perfis e permissoes: [docs/permissions.md](D:/GITHUB/mauricio-tonny/dashboard/docs/permissions.md)
- Logs de auditoria: [docs/audit-logs.md](D:/GITHUB/mauricio-tonny/dashboard/docs/audit-logs.md)
- Gestao de usuarios: [docs/admin-users.md](D:/GITHUB/mauricio-tonny/dashboard/docs/admin-users.md)
- Schema inicial do banco: [database/schema.sql](D:/GITHUB/mauricio-tonny/dashboard/database/schema.sql)
