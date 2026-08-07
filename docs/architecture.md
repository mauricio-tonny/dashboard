# Arquitetura Hibrida

## Objetivo

Usar a planilha como origem inicial dos dados, importar o conteudo para MariaDB e fazer o dashboard operar sobre o banco.

## Stack

- PHP 8.3
- MariaDB
- HTML
- CSS
- Bootstrap
- JavaScript

## Fluxo Principal

1. O sistema baixa a planilha a partir de um link compartilhado.
2. O importador salva uma copia temporaria.
3. O parser le a aba `BASE`.
4. O parser percorre as abas mensais validas.
5. Os dados sao normalizados e gravados no banco com `upsert`.
6. O dashboard consulta apenas o banco.
7. Um `cron` executa sincronizacoes periodicas.

## Fases do Projeto

### Fase 1

- Estrutura inicial da aplicacao.
- Autenticacao e papeis.
- Banco preparado.
- Importacao em modo leitura.

### Fase 2

- Dashboard inicial.
- Relatorios mensais.
- Contas previstas para o proximo mes.

### Fase 3

- Cadastro de novos lancamentos pelo sistema.
- Auditoria detalhada.
- Sincronizacao mais inteligente.

### Fase 4

- Banco como fonte principal.
- Planilha usada apenas como legado, validacao ou backup.

## Modelagem Conceitual

- `users`: acesso ao sistema.
- `roles`: perfis de acesso.
- `vendors`: fornecedores.
- `categories`: categorias financeiras.
- `entries`: lancamentos financeiros consolidados.
- `entry_sources`: rastreio da origem de cada registro importado.
- `import_runs`: execucoes de sincronizacao.
- `audit_logs`: alteracoes sensiveis dentro do sistema.

## Regras Importantes

- Nunca consultar a planilha diretamente para montar telas.
- Toda consulta de dashboard deve sair do banco.
- Toda importacao precisa gerar log de execucao.
- Toda escrita manual no sistema deve ser auditada.
- O parser precisa ignorar abas anuais de relatorio no inicio.

## Seguranca

- Nao publicar a planilha em area publica do servidor.
- Validar MIME, tamanho e extensao do arquivo importado.
- Limitar usuarios por papel.
- Proteger sessoes e credenciais via variaveis de ambiente.
- Usar `robots.txt` e cabecalhos `X-Robots-Tag`, lembrando que isso nao substitui autenticacao.

## Sobre o Visual

Vamos trabalhar com Bootstrap e um tema baseado em variaveis CSS inspirado na Oficina do DEV:

- base clara para leitura de dashboard
- preto/azul muito escuro para identidade tecnica
- verde como cor principal de acao e destaque
- cinzas frios para bordas, textos secundarios e superficies

A paleta fica centralizada no layout para permitir ajustes finos quando tivermos acesso aos arquivos de CSS do site original.

## Ponto de Decisao Futuro

Quando chegarmos nas primeiras telas reais do dashboard, eu vou te sinalizar para definirmos:

- menu e navegacao
- cards principais
- filtros
- graficos
- comportamento para perfil `viewer` e `editor`
