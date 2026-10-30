# v0.1.52

## Detalhes da Versão

- Implementa o retorno de despesas cadastradas no sistema para as abas mensais da planilha, com fila persistente e processamento separado do formulário de cadastro.
- Separa as ações administrativas de buscar a planilha e enviar alterações, com confirmação e registro de auditoria.
- Adiciona tarefa de retorno ao scheduler, executada fora da janela configurada de importação.
- Registra o hash da planilha no cadastro da alteração e bloqueia o envio em caso de divergência, evitando reaplicar automaticamente dados sobre uma versão alterada.
- Integra ao sincronizador Windows o recebimento da planilha atualizada pelo sistema, com detecção de alterações locais concorrentes e pausa quando o arquivo estiver em uso.
- Reutiliza linhas vazias das abas mensais e preserva as modalidades esperadas pela planilha, como A VISTA, PARCELADO e ULTIMA PARC.
- Melhora o formulário de lançamentos com seleção de fornecedores e categorias cadastrados, máscara monetária e explicação da data de vencimento e da aba de destino.
- Adiciona parcelamento com valor por parcela, distribuição entre os meses e identificação da última parcela; inclui recorrência com data final opcional, limitada às abas disponíveis.
- Reorganiza o cadastro com dois campos por linha no desktop, uma coluna no mobile, identificação visual de despesa e receita e botões padronizados.
- Mantém o usuário no cadastro após salvar e apresenta mensagem de confirmação para facilitar lançamentos consecutivos.
- Exibe data de emissão e número da nota nos anexos do Mercado e recupera esses metadados para as notas existentes.
- Bloqueia notas duplicadas pela chave fiscal ou pelo hash do arquivo, inclusive entre listas diferentes, com restrições no banco, identificação da lista original e auditoria da tentativa.
- Corrige a leitura de PDFs com o valor do produto antes da quantidade e com produtos divididos entre páginas, desconsiderando cabeçalhos e rodapés da impressão.
- Confere a quantidade de produtos declarada no PDF e interrompe importações incompletas, informando quantos itens foram reconhecidos.
- Permite tentar novamente a importação de anexos armazenados que falharam, com transação e proteção contra reprocessamento de notas já importadas.
- Inclui verificações de regressão para PDFs e para o bloqueio de notas duplicadas.

## Próximas Implementações

- Implementar relatório de referência para Mercado.
- Implementar campo URL nas listas de compras para casa, veículo e família.
- Implementar buscador de preços e tabela de preços para o buscador.
- Implementar aviso no Discord quando um menor preço for encontrado.
