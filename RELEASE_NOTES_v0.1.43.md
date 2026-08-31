**Detalhes da Versão:**
- Corrigido o total final da lista de mercado quando há múltiplas notas no mesmo mês.
- Adicionado armazenamento do valor total por nota de mercado para permitir somatória correta por lista.
- Recalculados os totais das listas de mercado a partir das notas importadas.
- Integrada a base de fornecedores importados da planilha com a tela de Contatos.
- Adicionados indicadores de contatos, fornecedores, clientes e fornecedores vindos da planilha.
- Incluídos filtros de busca e tipo na tela de Contatos.
- Expandido o cadastro de contatos com endereço completo: CEP, endereço, número, bairro, complemento, cidade e UF.
- Adicionadas máscaras para CPF/CNPJ, telefone e CEP.
- Adicionado autocomplete de endereço via ViaCEP ao preencher o CEP.
- Melhorado o visual do seletor de classificação Fornecedor/Cliente.
- Ajustado o alinhamento dos botões nos modais.
- Simplificada a grid de Contatos, removendo colunas pouco úteis e separando contatos inativos em uma seção recolhida.

**Próximas Implementações:**
- Criar fluxo de retorno para a planilha quando uma alteracao for feita pelo sistema.
- Exibir no painel administrativo o status da última sincronização da planilha.
- Permitir configurar a janela de sincronização pelo painel.
- Registrar tentativas ignoradas de sincronização em uma tela amigável de logs técnicos.
- Integrar a sincronização Windows com o scheduler interno do servidor.
