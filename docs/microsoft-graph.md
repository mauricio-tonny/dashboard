# Integração com Microsoft Graph

## Objetivo

Usar o Microsoft Graph para acessar com segurança a planilha financeira hospedada no OneDrive, evitando dependencia de link publico compartilhado.

## Estrategia Recomendada

1. Registrar uma aplicação no portal Azure.
2. Configurar autenticação OAuth 2.0.
3. Autorizar a aplicação a acessar apenas o drive e o arquivo necessarios.
4. Buscar o arquivo Excel no OneDrive pelo `driveId` e `itemId`.
5. Baixar o `.xlsx` temporariamente para processamento com PHP.
6. Processar a aba `BASE` e as abas mensais.
7. Para escrita, atualizar o arquivo com trilha de auditoria e mecanismo de bloqueio logico.

## Por Que Esse Caminho

- Evita expor a planilha por link publico.
- Permite autenticar de forma profissional e segura.
- Facilita auditoria, renovacao de credenciais e revogacao de acesso.
- Gera uma arquitetura melhor para portfolio.

## Modelagem Inicial das Abas

- `BASE`: cadastro de fornecedores, categorias, indicadores e listas auxiliares.
- Abas mensais: principal fonte de lançamentos.
- Abas anuais de relatório: inicialmente ignoradas pelo parser principal.

## Regras do Parser

### Aba `BASE`

- Ler somente os intervalos relevantes.
- Normalizar nomes de categorias e fornecedores.
- Validar duplicidades e campos vazios.

### Abas mensais

- Detectar pelo padrão de nomenclatura adotado por você.
- Mapear colunas para um formato único de lancamento.
- Diferenciar valores previstos, pagos, receitas e despesas conforme seu modelo real.

## Escrita Segura

Não recomendo escrita imediata direto no arquivo remoto sem esses controles:

- Backup antes de cada alteração.
- Registro de quem alterou, quando alterou e o que alterou.
- Validação para impedir gravação em abas fora do padrão.
- Controle para evitar conflito com edição manual simultanea.

## Sequencia de Implementacao

1. Integração com login Microsoft e obtenção de token.
2. Leitura do arquivo no OneDrive.
3. Download temporário do `.xlsx`.
4. Parser da aba `BASE`.
5. Parser de uma aba mensal piloto.
6. Dashboard somente leitura.
7. Escrita controlada de novos lançamentos.

## Dados que Precisaremos Confirmar

- Padrão exato do nome das abas mensais.
- Nome e ordem das colunas em uma aba mensal.
- Estrutura da aba `BASE`.
- Se existem fórmulas que não podem ser quebradas ao regravar o arquivo.
