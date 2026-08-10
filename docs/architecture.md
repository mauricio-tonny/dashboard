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
2. O importador salva uma copia temporária.
3. O parser le a aba `BASE`.
4. O parser percorre as abas mensais validas.
5. Os dados sao normalizados e gravados no banco com `upsert`.
6. O dashboard consulta apenas o banco.
7. Um `cron` executa sincronizacoes periódicas.

## Fases do Projeto

### Fase 1

- Estrutura inicial da aplicação.
- Autenticação e papeis.
- Banco preparado.
- Importação em modo leitura.

### Fase 2

- Dashboard inicial.
- Relatórios mensais.
- Contas previstas para o próximo mês.

### Fase 3

- Cadastro de novos lançamentos pelo sistema.
- Auditoria detalhada.
- Sincronização mais inteligente.

### Fase 4

- Banco como fonte principal.
- Planilha usada apenas como legado, validação ou backup.

## Modelagem Conceitual

- `users`: acesso ao sistema.
- `roles`: perfis de acesso.
- `vendors`: fornecedores.
- `categories`: categorias financeiras.
- `entries`: lançamentos financeiros consolidados.
- `entry_sources`: rastreio da origem de cada registro importado.
- `import_runs`: execuções de sincronização.
- `audit_logs`: alterações sensíveis dentro do sistema.

## Regras Importantes

- Nunca consultar a planilha diretamente para montar telas.
- Toda consulta de dashboard deve sair do banco.
- Toda importação precisa gerar log de execução.
- Toda escrita manual no sistema deve ser auditada.
- O parser precisa ignorar abas anuais de relatório no inicio.

## Segurança

- Não publicar a planilha em área pública do servidor.
- Validar MIME, tamanho e extensao do arquivo importado.
- Limitar usuários por papel.
- Proteger sessoes e credenciais via variáveis de ambiente.
- Usar `robots.txt` e cabeçalhos `X-Robots-Tag`, lembrando que isso não substitui autenticação.

## Sobre o Visual

Vamos trabalhar com Bootstrap e um tema baseado em variáveis CSS inspirado na Oficina do DEV:

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
