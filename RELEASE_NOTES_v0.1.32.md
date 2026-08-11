**Detalhes da Versão:**
- Criado o importador real da planilha em `php bin/import_spreadsheet.php`, com suporte a `--dry-run` para validar tudo antes de gravar no banco.
- Adicionadas as tabelas de importação financeira ao instalador e schema do banco, incluindo lançamentos, fontes dos lançamentos e histórico de execuções.
- Implementada leitura estruturada dos layouts históricos da planilha, respeitando as mudanças de colunas entre 2015, 2023 e 2026.
- Normalizadas as categorias legadas com base na aba `BASE` e em regras confirmadas manualmente para descrições antigas.
- Criado suporte para identificar parcelas no histórico, incluindo contas no formato `1/10`, `2/10` e últimas parcelas.
- Adicionado relatório de foco pendente no analisador para listar somente categorias ou fornecedores ainda não normalizados.
- Zeradas as categorias pendentes no `dry-run` da planilha atual: `5309` lançamentos importáveis, `0` categorias pendentes e banco preservado sem gravação.
- Atualizada a documentação da integração da planilha com instruções do importador e validação segura.

**Próximas Implementações:**
- Normalizar fornecedores pendentes, começando por `NOTA`, `CARTAO`, `INTER`, `UTFPR`, `DENTISTA`, `SPOTIFY` e demais grupos.
- Rodar nova validação completa em `--dry-run` após normalizar fornecedores.
- Definir o momento seguro para executar o primeiro `--apply` e alimentar o banco.
- Criar as primeiras telas financeiras usando os dados importados.
