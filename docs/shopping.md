# Modulo de compras

O modulo de compras fica organizado em telas separadas:

```text
/shopping
/shopping/market
/shopping/home
/shopping/family
/shopping/vehicle
```

## Permissoes

- `view_shopping`: permite acessar o modulo.
- `manage_shopping`: permite criar listas, inserir, editar, remover e marcar itens.
- `manage_shopping_settings`: permite acessar as configuracoes administrativas de compras.

Nesta fase, todos os perfis autenticados podem usar as listas de compras. Apenas administradores podem alterar configuracoes.

## Mercado

A lista de mercado e mensal. Ao criar uma lista, o sistema sugere automaticamente o proximo mes.

Campos do item:

- Nome do item.
- Sessao cadastrada em configuracao de compras.
- Quantidade.
- Valor unitario.
- Valor.
- Sub total calculado pelo sistema quando houver valor unitario.

Durante a compra, o usuario pode marcar cada item como pego. Ao finalizar, informa o valor total da compra.

Tambem e possivel anexar arquivos de NFC-e/NF-e a lista de mercado. Arquivos XML sao interpretados automaticamente na importacao. PDF e imagem ainda ficam armazenados apenas para apoio futuro em relatorios.

Formatos aceitos:

- PDF.
- XML.
- JPG/JPEG.
- PNG.

Limite inicial: 5 MB por arquivo.

Na leitura de XML, o sistema trata a lista como uma lista generica de intencao de compra. Exemplo: o usuario pode cadastrar `Arroz` ou `Pacote 5Kg Arroz`, sem informar marca. Ao importar o XML, o sistema procura itens similares na lista do mes selecionado e atualiza quantidade, valor unitario e subtotal quando encontra correspondencia. Itens da nota que nao existirem na lista sao incluidos automaticamente.

O sistema tambem usa um DE/PARA inicial de sessao para classificar itens obvios. Exemplos: arroz entra em `DESPENSA`, sabao em po entra em `LIMPEZA`, leite entra em `LEITES E DERIVADOS`.

O parser inicial considera a estrutura mais comum de NF-e/NFC-e:

- `det/prod/xProd`: nome do produto.
- `det/prod/qCom`: quantidade.
- `det/prod/vUnCom`: valor unitario.
- `det/prod/vProd`: valor total do item.
- `ICMSTot/vNF`: valor total da nota.

Para evitar enviar dados domesticos para servicos externos, a primeira versao usa um marcador visual com a inicial do item no lugar de foto automatica. Futuramente podemos evoluir para upload local ou catalogo proprio de imagens.

## Para casa

Lista unica para itens da casa.

Campos:

- Nome do item.
- Comodo.
- Valor previsto.
- Prioridade de 0 a 10.

## Para a familia

Lista unica para itens da familia.

Campos:

- Nome do item.
- Para quem.
- Valor previsto.

## Para o veiculo

Lista unica para itens de veiculo.

Campos:

- Nome do item.
- Para qual veiculo.
- Area.
- Valor previsto.

## Configuracao compras

O painel administrativo fica em:

```text
/admin/shopping-settings
```

Cadastros disponiveis:

- Sessoes do mercado.
- Comodos.
- Pessoas.
- Veiculos.
- Areas do veiculo.

Excluir configuracoes foi implementado como desativacao para preservar historico das listas antigas.
