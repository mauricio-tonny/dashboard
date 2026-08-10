# Dashboard Financeiro Pessoal

Aplicação PHP 8.3 para registrar movimentações financeiras, importar dados de uma planilha Excel e gerar relatórios, gráficos e estimativas com base no histórico auditado desde 2015.

## Objetivos

- Autenticação com login e senha.
- Controle de acesso por perfil.
- Inclusão e edição de lançamentos no mês atual e meses futuros.
- Leitura da planilha Excel como fonte de dados principal durante a fase de migração.
- Relatórios, indicadores e estimativas financeiras.
- Base segura para uso doméstico e portfólio.

## Arquitetura Inicial

- `public/`: ponto de entrada HTTP.
- `src/Core`: infraestrutura básica como roteamento, sessão, resposta e autenticação.
- `src/Controllers`: controladores web.
- `src/Domain`: regras de negócio.
- `src/Infrastructure`: acesso a dados, incluindo adaptadores para Excel.
- `src/Support`: funções utilitárias.
- `database/`: schema MariaDB e futuras migrações.
- `storage/`: arquivos da aplicação, logs e dados locais.

## Perfis de Acesso

- `admin`: gerencia usuários, configurações e acesso total.
- `editor`: pode inserir e editar lançamentos autorizados.
- `viewer`: apenas consulta painéis, relatórios e previsões.

## Segurança Planejada

- Senhas com `password_hash`.
- Sessão regenerada no login.
- Middleware de autenticação e autorização.
- Validação centralizada de entrada.
- Configuração fora do código via variáveis de ambiente.
- Planilha armazenada fora do diretório público.
- Logs de auditoria para alterações sensíveis.

## Integração com Excel e Banco

A estratégia principal é uma arquitetura híbrida:

1. Obter a planilha por arquivo local controlado ou link compartilhado configurado no servidor.
2. Importar os dados para MariaDB.
3. Usar o banco como fonte principal do dashboard.
4. Manter rotinas de sincronização pelo scheduler interno acionado por um único CRON.

Para produção, a melhor opção tende a ser:

- Banco MariaDB isolado da camada web.
- Importações controladas, com trilha de auditoria e logs de sincronização.
- Backups automáticos da planilha e do banco.
- Sessão, autenticação e autorização tratadas na aplicação.
- Evolução gradual até o banco virar a fonte principal.

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

Para gerar a chave usada na criptografia de segredos, como o link da planilha:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Salve o valor gerado em `APP_KEY` no `.env`.

Para criar ou atualizar um usuário:

```bash
php bin/create_user.php --name="Nome" --email=email@dominio.com --role=viewer --password="senha-temporária"
```

## Configurando o CRON

Em ambiente de produção ou migração de servidor, configure apenas um CRON do sistema operacional para acionar o scheduler interno:

```bash
* * * * * cd /var/www/dashboard.oficinadodev.com.br/html && php bin/schedule_run.php >> storage/logs/scheduler.log 2>&1
```

Esse comando roda a cada minuto, mas cada rotina decide internamente quando deve executar, respeitando frequência, lock e status no banco.

Após configurar ou migrar o servidor, valide com:

```bash
php bin/schedule_run.php
tail -n 50 storage/logs/scheduler.log
```

Detalhes completos: [docs/scheduler.md](docs/scheduler.md).

## Próximos Passos Recomendados

1. Definir o layout exato da planilha atual.
2. Escolher o fluxo de sincronização da planilha: arquivo local controlado, upload manual ou link compartilhado.
3. Adicionar `composer`, cliente HTTP e `phpoffice/phpspreadsheet`.
4. Criar o importador da planilha compartilhada.
5. Criar o parser da aba `BASE` e das abas mensais.
6. Implementar persistência real em MariaDB.
7. Criar tasks do scheduler para leitura e envio de dados da planilha.
8. Criar o primeiro dashboard com resumo mensal, contas a pagar e previsão do próximo mês.

## Documentação de Integração

- Visão geral da arquitetura híbrida: [docs/architecture.md](docs/architecture.md)
- Estratégia de integração com a planilha: [docs/spreadsheet-integration.md](docs/spreadsheet-integration.md)
- Configuração sugerida do NGINX: [docs/nginx.md](docs/nginx.md)
- Matriz de perfis e permissões: [docs/permissions.md](docs/permissions.md)
- Logs de auditoria: [docs/audit-logs.md](docs/audit-logs.md)
- Gestão de usuários: [docs/admin-users.md](docs/admin-users.md)
- Módulo de compras: [docs/shopping.md](docs/shopping.md)
- Scheduler e CRON único: [docs/scheduler.md](docs/scheduler.md)
- Interface e navegação: [docs/interface-navigation.md](docs/interface-navigation.md)
- Schema inicial do banco: [database/schema.sql](database/schema.sql)
