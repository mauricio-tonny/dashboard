# Módulo de compras

O módulo de compras fica organizado em telas separadas:

```text
/shopping
/shopping/market
/shopping/home
/shopping/family
/shopping/vehicle
```

## Permissões

- `view_shopping`: permite acessar o módulo.
- `manage_shopping`: permite criar listas, inserir, editar, remover e marcar itens.
- `manage_shopping_settings`: permite acessar as configurações administrativas de compras.

Nesta fase, todos os perfis autenticados podem usar as listas de compras. Apenas administradores podem alterar configurações.

## Mercado

A lista de mercado e mensal. Ao criar uma lista, o sistema sugere automaticamente o próximo mês.

Campos do item:

- Nome do item.
- Sessão cadastrada em configuração de compras.
- Quantidade.
- Valor unitario.
- Sub total calculado pelo sistema quando houver valor unitario.

Durante a compra, o usuário pode confirmar cada item. Ao finalizar, informa o valor total da compra em reais. A lista finalizada fica travada para evitar novas alterações, anexos ou uma segunda finalizacao. Somente administradores podem remover a finalizacao para ajustes.

Quando o subtotal dos itens for maior que o valor final da compra, o sistema registra a diferenca como desconto da lista: `subtotal dos itens - total final`.

Ao finalizar manualmente uma lista, a data da compra e obrigatória. Quando uma nota XML/PDF e importada, a data da compra da lista e preenchida automaticamente com a data da nota.

Uma lista de mercado so pode ser finalizada quando possuir ao menos um item e valor total maior que zero.

Administradores podem excluir uma lista mensal de mercado inteira. A exclusão remove os itens e registros de notas vinculadas a lista.

Também é possível vincular NFC-e/NF-e a lista de mercado por três caminhos:

- Upload de XML, com importação automatica dos itens.
- Upload de PDF da consulta pública, com importação automatica quando o texto do DANFE for extraível.
- Upload de imagem, apenas como anexo para apoio futuro em relatórios.
- Cadastro da chave de acesso de 44 digitos, quando o XML não estiver disponível.

Quando XML ou PDF sao importados com sucesso, o sistema salva a data da compra e a chave de acesso extraidas da nota. O arquivo fisico permanece armazenado para conferência e download posterior, inclusive quando a importação for concluída com sucesso.

Para notas antigas que ainda tenham arquivo fisico armazenado, o comando `php bin/backfill_market_invoice_metadata.php` tenta preencher data da compra e chave de acesso sem reimportar os itens.

Formatos aceitos:

- PDF.
- XML.
- JPG/JPEG.
- PNG.

Limite inicial: 5 MB por arquivo.

No cadastro por chave de acesso, o sistema valida o dígito verificador e extrai UF, ano/mês, CNPJ emitente, modelo, série, número, tipo de emissão, código numérico e dígito verificador. Para NFC-e do Paraná, o sistema também salva o link da consulta pública da SEFA/PR. Esta opção não baixa o XML automaticamente, pois o download completo normalmente depende de disponibilização do emissor ou de certificado digital.

Na leitura de XML, o sistema trata a lista como uma lista genérica de intenção de compra. Exemplo: o usuário pode cadastrar `Arroz` ou `Pacote 5Kg Arroz`, sem informar marca. Ao importar o XML, o sistema procura itens similares na lista do mês selecionado e atualiza quantidade, valor unitario e subtotal quando encontra correspondencia. Itens da nota que não existirem na lista sao incluidos automaticamente.

O sistema também usa um DE/PARA inicial de sessão para classificar itens óbvios. Exemplos: arroz entra em `DESPENSA`, sabao em po entra em `LIMPEZA`, leite entra em `LEITES E DERIVADOS`.

O parser inicial de XML considera a estrutura mais comum de NF-e/NFC-e:

- `det/prod/xProd`: nome do produto.
- `det/prod/qCom`: quantidade.
- `det/prod/vUnCom`: valor unitario.
- `det/prod/vProd`: valor total do item.
- `ICMSTot/vNF`: valor total da nota.

O parser inicial de PDF considera o layout textual da consulta pública da NFC-e do Paraná, usando:

- Nome do produto e código.
- `Qtde.` como quantidade.
- `Vl. Unit.` como valor unitario.
- `Vl. Total` como subtotal do item.
- `Valor a pagar R$` como total final da lista.

Para evitar enviar dados domesticos para servicos externos, a primeira versao usa um marcador visual com a inicial do item no lugar de foto automatica. Futuramente devemos evoluir para upload local, catalogo próprio de imagens ou associacao manual de foto por item.

## Para casa

Lista única para itens da casa.

Campos:

- Nome do item.
- Cômodo.
- Valor previsto.
- Prioridade de 0 a 10 selecionada em dropdown.

## Para a família

Lista única para itens da família.

Campos:

- Nome do item.
- Para quem.
- Valor previsto.

## Para o veículo

Lista única para itens de veículo.

Campos:

- Nome do item.
- Para qual veículo.
- Área.
- Valor previsto.

Os itens sao agrupados por veículo. Ao baixar um item como comprado, o usuário informa a data da compra para manter histórico.

## Configuração compras

O painel administrativo fica em:

```text
/admin/shopping-settings
```

Cadastros disponíveis:

- Sessoes do mercado.
- Cômodos.
- Pessoas.
- Veículos.
- Áreas do veículo.

Excluir configurações foi implementado como desativação para preservar histórico das listas antigas.
