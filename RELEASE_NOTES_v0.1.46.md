**Detalhes da Versão:**
- Renomeada a tela Sistema > Tempo de sincronização para Sistema > Sincronização.
- Adicionado alerta visual quando a última sincronização estiver muito antiga.
- Padronizada a avaliação do alerta considerando a janela configurada e o fuso America/Sao_Paulo.
- Adicionada ação de sincronização manual pelo painel.
- Restrita a sincronização manual apenas ao perfil Administrador.
- Adicionada confirmação antes de executar a sincronização manual.
- Mantido bloqueio contra execuções simultâneas durante a sincronização manual.
- Registrada a sincronização manual nos logs técnicos e na auditoria.
- Atualizada a documentação com a nova nomenclatura da tela.
- Atualizada a versão da aplicação para 0.1.46.

**Próximas Implementações:**
- Criar fluxo de retorno para a planilha quando uma alteracao for feita pelo sistema.
- Exibir diferença entre envio da planilha pelo Windows e importação interna do servidor em um fluxo visual.
- Integrar a sincronização Windows com o scheduler interno do servidor.
