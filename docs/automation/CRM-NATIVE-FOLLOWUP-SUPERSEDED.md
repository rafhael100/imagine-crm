# Cancelamento imediato de acompanhamentos invalidados

Base: PR #639, `403a2b735e77bd72b529e296b20e63eab1c00859`.
Estado desta alteração: candidato de desenvolvimento privado; validação HTTP/SQL
deve ser confirmada no CI da nova SHA. Não representa homologação operacional.

## Problema e comportamento corrigido

O motor já recusava um acompanhamento quando o lead mudava de versão ou etapa.
Entretanto, essa recusa só ocorria depois do vencimento e de um novo processamento.
Um lead perdido, ganho ou editado podia manter uma execução inválida como
“Agendada” durante horas, embora ela nunca pudesse gerar uma tarefa.

Uma alteração efetiva do lead agora cancela imediatamente os seus acompanhamentos
pendentes, na mesma transação que persiste a alteração. Se a nova etapa exige
acompanhamento, o motor enfileira a nova execução depois desse cancelamento.
O ator, a sessão causal, o comando e a chave de execução antigos permanecem intactos.
A auditoria registra o ator autenticado que alterou o lead e os cancelamentos.

O escopo é a empresa validada e o lead alterado. Execuções de outros leads e de
outras empresas ficam preservadas. Estados `completed`, `failed` e `cancelled`
não são modificados; tarefas já criadas continuam disponíveis. O salvamento sem
mudanças corrigido em #639 retorna antes da invalidação. Nenhum privilégio de
cancelar manualmente execuções válidas foi acrescentado.

## Validação no fluxo existente

O cenário adicional de `scripts/ops/test-private-crm-http.py` usa autenticação,
MFA, CSRF, rotas e PostgreSQL reais da instalação descartável existente:

- Execução concluída seguida de fechamento do lead: histórico e tarefa preservados.
- Dois leads com acompanhamento futuro: alterar um não invalida o outro.
- Avanço `qualified` → `proposal`: cancela o comando anterior e cria uma única
  nova execução ligada à versão atual, ao mesmo ator e à sessão causal atual.
- Fechamento do lead e edição real de título: cancelamento antes do vencimento,
  sem processamento externo, sem criação antecipada de tarefa.
- Nova tentativa de processamento: nenhum efeito dos comandos cancelados.
- Snapshot de outra empresa: nenhuma alteração.
- Falha real de INSERT da nova execução: rollback de lead, cancelamento anterior,
  ausência da nova execução, auditoria e tarefas; repetição após restaurar a
  permissão conclui a mudança uma única vez.

O erro de banco retorna HTTP 503 pelo handler privado existente. O teste também
exige um novo registro sanitizado `QueryException`, código SQLSTATE `42501`,
com origem em `NativeFollowupEngine.php`, entre as leituras anteriores e
posteriores à request. Os logs são inspecionados sem imprimir o seu conteúdo.
Uma falha genérica antes de chegar ao motor não satisfaz essa contraprova.

A contraprova de rollback usa somente os modos CLI
`automation-deny-enqueue` / `automation-restore-enqueue`. O harness exige
`CRM_PRIVATE_PROJECT=imagine-crm-journey-ci` e o opt-in explícito
`CRM_PRIVATE_CI_FAULT_PROFILE=native-followup-enqueue-v1`. Cria um marcador 0600
no diretório privado já montado nos tools e propaga as flags somente ao processo
de ferramentas. O PHP também exige ambiente `staging`, instalação `synthetic-v1`,
owner/banco esperados, ingresso privado fechado e marcador válido. O perfil
não depende da propagação automática de `GITHUB_ACTIONS` por `sudo`/Docker.

O privilégio INSERT preexistente é conferido antes da falha e restaurado em
`finally`; o marcador é removido depois dessa restauração. Se a restauração
falhar, o teste falha e mantém o marcador para recuperação, antes do teardown
do ambiente descartável. Não há endpoint de falha no aplicativo nem alteração
de schema ou privilégio operacional por essa implementação. O workflow deve
fornecer o opt-in explícito somente ao ensaio HTTP descartável.

## Limites

Dados e identidades continuam sintéticos. Não foram criados worker operacional,
nova ação de negócio, migration, integração externa ou permissão de produção.
Permanecem os gates humanos e de homologação existentes; sem merge, deploy,
VPS, despesas ou dados reais.
