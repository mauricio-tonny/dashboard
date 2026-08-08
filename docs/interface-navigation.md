# Interface e navegacao

Depois do login, a aplicacao passa a usar uma navegacao lateral no desktop e uma topbar compacta no mobile.

## Abas principais

- Dashboard
- Financeiro
- Compras
- Contatos
- Relatorios
- Sistema

## Dashboard

A tela inicial exibe:

- Resumo de contas a pagar no proximo mes.
- Resumo da lista de mercado do proximo mes.
- Ultimos 10 itens pendentes da lista Para Casa.
- Grafico anual de despesas preparado para receber dados reais.

Os dados financeiros avancados ainda ficam zerados ate a integracao da planilha/banco financeiro.

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
- Para a familia.
- Para o veiculo.

A tela Mercado permite cadastrar itens com sessao, quantidade, valor unitario e subtotal, alem de vincular NFC-e/NF-e por XML, PDF/imagem ou chave de acesso. Arquivos XML sao importados automaticamente para atualizar itens semelhantes ou incluir produtos novos.

## Contatos

A tela `/contacts` permite cadastrar fornecedores e clientes com:

- Nome.
- Sobrenome.
- CPF/CNPJ.
- Telefone.
- E-mail.
- Endereco.
- Cidade.
- UF.

Um contato pode ser fornecedor, cliente ou ambos.

## Sistema

O menu Sistema concentra as configuracoes administrativas:

- Usuarios.
- Logs.
- Backup.
- Tempo de sincronizacao.
- Cadastro de Categoria (DR).
- Discord.
- Gerenciar planilha.
- Configuracao compras, incluindo sessoes do mercado.
