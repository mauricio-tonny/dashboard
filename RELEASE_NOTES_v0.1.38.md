**Detalhes da Versao:**

- Criada a tela `/finance/receivable` para visualizacao do modulo A receber.
- Adicionados totalizadores de recebidos por periodo, separando Salario Mauricio, Salario Karina e outras entradas.
- Criada lista de entradas do periodo com grafico e resumo por origem.
- Padronizada a ordenacao de recebidos, priorizando Mauricio pela relevancia no sistema.
- Padronizada a label da Karina como `SALARIO KARINA`.
- Implementada separacao automatica de formulas simples de recebidos, mantendo o maior valor como salario e classificando os menores como `OUTROS MAURICIO` ou `OUTROS KARINA`.
- Ajustado o relatorio Fluxo de Caixa com cores para entrada/saida e saldo positivo/negativo.
- Removidas as colunas Origem/Categoria e Status do relatorio Fluxo de Caixa para aproximar a visualizacao de um extrato.
- Atualizada a documentacao da integracao da planilha com as regras de recebidos e formulas.

**Proximas Melhorias:**

- Criar relatorios para Recebido.
- Planejar e atuar na tela de Contatos (Fornecedores e Clientes).
- Criar fluxo de retorno para a planilha quando uma alteracao for feita pelo sistema.
