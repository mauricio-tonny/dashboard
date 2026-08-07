# Integracao com Microsoft Graph

## Objetivo

Usar o Microsoft Graph para acessar com seguranca a planilha financeira hospedada no OneDrive, evitando dependencia de link publico compartilhado.

## Estrategia Recomendada

1. Registrar uma aplicacao no portal Azure.
2. Configurar autenticacao OAuth 2.0.
3. Autorizar a aplicacao a acessar apenas o drive e o arquivo necessarios.
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
- Abas mensais: principal fonte de lancamentos.
- Abas anuais de relatorio: inicialmente ignoradas pelo parser principal.

## Regras do Parser

### Aba `BASE`

- Ler somente os intervalos relevantes.
- Normalizar nomes de categorias e fornecedores.
- Validar duplicidades e campos vazios.

### Abas mensais

- Detectar pelo padrao de nomenclatura adotado por voce.
- Mapear colunas para um formato unico de lancamento.
- Diferenciar valores previstos, pagos, receitas e despesas conforme seu modelo real.

## Escrita Segura

Nao recomendo escrita imediata direto no arquivo remoto sem esses controles:

- Backup antes de cada alteracao.
- Registro de quem alterou, quando alterou e o que alterou.
- Validacao para impedir gravacao em abas fora do padrao.
- Controle para evitar conflito com edicao manual simultanea.

## Sequencia de Implementacao

1. Integracao com login Microsoft e obtenção de token.
2. Leitura do arquivo no OneDrive.
3. Download temporario do `.xlsx`.
4. Parser da aba `BASE`.
5. Parser de uma aba mensal piloto.
6. Dashboard somente leitura.
7. Escrita controlada de novos lancamentos.

## Dados que Precisaremos Confirmar

- Padrao exato do nome das abas mensais.
- Nome e ordem das colunas em uma aba mensal.
- Estrutura da aba `BASE`.
- Se existem formulas que nao podem ser quebradas ao regravar o arquivo.
