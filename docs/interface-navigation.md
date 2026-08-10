# Interface e navegacao

Depois do login, a aplicação passa a usar uma navegacao lateral no desktop e uma topbar compacta no mobile.

## Abas principais

- Dashboard
- Financeiro
- Compras
- Contatos
- Relatórios
- Sistema

## Dashboard

A tela inicial exibe:

- Resumo de contas a pagar no próximo mês.
- Resumo da lista de mercado do próximo mês.
- Ultimos 10 itens pendentes da lista Para Casa.
- Grafico anual de despesas preparado para receber dados reais.

Os dados financeiros avancados ainda ficam zerados até a integração da planilha/banco financeiro.

## Financeiro

O menu financeiro concentra:

- A pagar.
- A receber.
- Novo lancamento.

As telas A pagar e A receber foram posicionadas como paginas-base para detalhamento futuro.

## Compras

O menu Compras foi dividido em:

- Visao geral.
- Mercado.
- Para casa.
- Para a família.
- Para o veículo.

A tela Mercado permite cadastrar itens com sessão, quantidade, valor unitario e subtotal, além de vincular NFC-e/NF-e por XML, PDF/imagem ou chave de acesso. Arquivos XML e PDFs textuais da consulta pública sao importados automaticamente para atualizar itens semelhantes ou incluir produtos novos.

## Contatos

A tela `/contacts` permite cadastrar fornecedores e clientes com:

- Nome.
- Sobrenome.
- CPF/CNPJ.
- Telefone.
- E-mail.
- Endereço.
- Cidade.
- UF.

Um contato pode ser fornecedor, cliente ou ambos.

## Sistema

O menu Sistema concentra as configurações administrativas:

- Usuários.
- Logs.
- Backup.
- Tempo de sincronização.
- Cadastro de Categoria (DR).
- Discord.
- Gerenciar planilha.
- Configuração compras, incluindo sessoes do mercado.
