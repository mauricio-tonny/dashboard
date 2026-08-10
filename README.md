# Dashboard Financeiro Pessoal

Aplicação PHP 8.3 para registrar movimentacoes financeiras, importar dados de uma planilha Excel hospedada no OneDrive e gerar relatórios, graficos e estimativas com base no histórico auditado desde 2015.

## Objetivos

- Autenticação com login e senha.
- Controle de acesso por perfil.
- Inclusão e edição de lançamentos no mês atual e meses futuros.
- Leitura da planilha Excel como fonte de dados principal.
- Relatórios, indicadores e estimativas financeiras.
- Base segura para uso domestico e portfolio.

## Arquitetura Inicial

- `public/`: ponto de entrada HTTP.
- `src/Core`: infraestrutura básica como roteamento, sessão, resposta e autenticação.
- `src/Controllers`: controladores web.
- `src/Domain`: regras de negocio.
- `src/Infrastructure`: acesso a dados, incluindo adaptadores para Excel.
- `src/Support`: funcoes utilitarias.
- `database/`: schema inicial MariaDB e futuras migracoes.
- `storage/`: arquivos de aplicação, logs e dados locais.

## Perfis de Acesso

- `admin`: gerencia usuários, configurações e acesso total.
- `editor`: pode inserir e editar lançamentos autorizados.
- `viewer`: apenas consulta painéis, relatórios e previsoes.

## Segurança Planejada

- Senhas com `password_hash`.
- Sessão regenerada no login.
- Middleware de autenticação e autorização.
- Validação centralizada de entrada.
- Configuração fora do código via variáveis de ambiente.
- Planilha armazenada fora do diretorio publico.
- Logs de auditoria para alterações sensíveis.

## Integração com Excel e Banco

A estrategia principal passa a ser uma arquitetura hibrida:

1. Ler a planilha compartilhada no OneDrive.
2. Importar os dados para MariaDB.
3. Usar o banco como fonte principal do dashboard.
4. Manter uma rotina de sincronização via `cron`.

A base inicial já separa a aplicação por interfaces para permitir três etapas:

1. Captura do arquivo compartilhado.
2. Processamento do `.xlsx`.
3. Persistencia relacional para consultas, auditoria e evolucao futura.

Para producao, a melhor opção tende a ser:

- Banco MariaDB isolado da camada web.
- Importacoes controladas, com trilha de auditoria e logs de sincronização.
- Backups automaticos da planilha e do banco.
- Sessão, autenticação e autorização tratadas na aplicação.
- Evolucao gradual até o banco virar a fonte principal.

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

Para criar ou atualizar um usuário:

```bash
php bin/create_user.php --name="Nome" --email=email@dominio.com --role=viewer --password="senha-temporária"
```

## Proximos Passos Recomendados

1. Definir o layout exato da planilha atual.
2. Escolher se a escrita será direta no `.xlsx` ou via sincronização.
3. Adicionar `composer`, cliente HTTP e `phpoffice/phpspreadsheet`.
4. Criar o importador da planilha compartilhada.
5. Criar o parser da aba `BASE` e das abas mensais.
6. Implementar persistencia real em MariaDB.
7. Implementar sincronização por `cron`.
8. Criar o primeiro dashboard com resumo mensal, contas a pagar e previsão do próximo mês.

## Documentacao de Integração

- Visao geral da arquitetura hibrida: [docs/architecture.md](D:/GITHUB/mauricio-tonny/dashboard/docs/architecture.md)
- Fluxo previsto para OneDrive e Microsoft Graph: [docs/microsoft-graph.md](D:/GITHUB/mauricio-tonny/dashboard/docs/microsoft-graph.md)
- Configuração sugerida do NGINX: [docs/nginx.md](D:/GITHUB/mauricio-tonny/dashboard/docs/nginx.md)
- Matriz de perfis e permissões: [docs/permissions.md](D:/GITHUB/mauricio-tonny/dashboard/docs/permissions.md)
- Logs de auditoria: [docs/audit-logs.md](D:/GITHUB/mauricio-tonny/dashboard/docs/audit-logs.md)
- Gestao de usuários: [docs/admin-users.md](D:/GITHUB/mauricio-tonny/dashboard/docs/admin-users.md)
- Módulo de compras: [docs/shopping.md](D:/GITHUB/mauricio-tonny/dashboard/docs/shopping.md)
- Scheduler e CRON único: [docs/scheduler.md](D:/GITHUB/mauricio-tonny/dashboard/docs/scheduler.md)
- Interface e navegacao: [docs/interface-navigation.md](D:/GITHUB/mauricio-tonny/dashboard/docs/interface-navigation.md)
- Schema inicial do banco: [database/schema.sql](D:/GITHUB/mauricio-tonny/dashboard/database/schema.sql)
