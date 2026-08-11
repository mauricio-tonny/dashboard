**Detalhes da Versão:**
- Normalizados fornecedores legados da planilha com regras explícitas de DE/PARA confirmadas.
- Adicionada regra de fornecedores por descrição para casos históricos como `NOTA` de combustível, `GETNET`, repasses ao Maycon e notas específicas.
- Ampliado o reconhecimento de cartões antigos por cor, incluindo o mapeamento das cores restantes para `BANCO INTER`.
- Incluído suporte ao padrão `BANCO ACORDO - n/36` / `BANCO ACORDA - n/36` como fornecedor `SANTANDER`.
- Adicionado filtro `--focus-vendor` ao analisador da planilha para investigar fornecedores pendentes por grupo de descrição.
- Validado no servidor em `--dry-run`, mantendo o banco intacto.
- Reduzidos fornecedores pendentes de `290` para `6`, mantendo categorias pendentes em `0`.

**Próximas Implementações:**
- Confirmar se a sincronização da planilha removeu os 6 fornecedores pendentes ajustados manualmente.
- Rodar novo `php bin/import_spreadsheet.php --dry-run --limit=30` antes de qualquer gravação.
- Se o `dry-run` estiver limpo, executar o primeiro `--apply` para alimentar o banco.
- Começar a validar os dados importados para orientar as telas financeiras.
