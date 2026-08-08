# Modulo de compras

O modulo de compras fica em:

```text
/shopping
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
- Sessao.

Durante a compra, o usuario pode marcar cada item como pego. Ao finalizar, informa o valor total da compra.

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

- Comodos.
- Pessoas.
- Veiculos.
- Areas do veiculo.

Excluir configuracoes foi implementado como desativacao para preservar historico das listas antigas.
