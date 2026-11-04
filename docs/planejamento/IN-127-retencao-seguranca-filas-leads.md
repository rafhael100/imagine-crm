# IN-127 — Retenção, Reenvio e Diagnóstico das Filas de Leads

> **ATUALIZAÇÃO DOCUMENTAL — ratificações posteriores (03/11/2026):** as decisões IN127-R01 a R06 foram ratificadas individualmente e estão consolidadas em [IN-127-R01-R06 — Ratificações normativas](IN-127-R01-R06-ratificacoes-2026-11-03.md). A regra de 3+15 dias significa **72h + 360h = 432h contínuas a partir da recepção durável T0**. Este documento histórico permanece preservado; em matérias modificadas, consultar a ratificação posterior. **RF-PEND-01 e homologação técnica continuam pendentes.**

**Estado:** APROVADA a retenção máxima de **18 dias totais (3 + 15 dias consecutivos)**, o reenvio e o diagnóstico automático. **Complementos posteriores:** IN-128 define limpeza/auditoria e IN-147 autoriza exclusão automática de payload não entregue após os 18 dias.  
**Data:** 01/11/2026.  
**Etapa:** planejamento/documentação; **não autoriza implementação**.  
**Dependências:** IN-124 (APIs principal e contingência, armazenamento durável), IN-125 (formulários) e IN-126 (monitoramento e recuperação).

## Decisões expressamente aprovadas

1. **Leads aguardando entrega — até 3 dias:** os dados recebidos e persistidos por qualquer uma das duas APIs ficam em fila de entrega, com **novas tentativas automáticas** quando a entrega ao CRM não tiver sido confirmada.
2. **Leads com falha — até 15 dias na fila de falhas:** envios que não forem entregues durante a etapa normal deverão permanecer recuperáveis na fila de exceções/falhas por **15 dias**. A fila deve permitir reprocessamento automático quando o problema for recuperável e reprocessamento manual por equipe autorizada quando necessário.
3. **Exibir o erro que está ocorrendo:** cada tentativa malsucedida deve ter diagnóstico pesquisável na área administrativa do Portal Imagine, inclusive tipo de erro, mensagem clara, componente envolvido, data e hora da tentativa, número de tentativas, status atual e próxima tentativa quando aplicável.
4. **Alertas:** problemas persistentes devem gerar avisos administrativos; nenhuma ocorrência pode desaparecer da visualização apenas porque uma tentativa falhou.
5. **Segurança dos diagnósticos:** registrar códigos técnicos/HTTP e causas úteis ao suporte sem expor chaves de API, tokens, senhas, conteúdo sensível de formulários ou dados pessoais desnecessários nos logs.
6. **Evitar duplicidades:** usar identificador único por envio e idempotência ao retentar, alternar entre APIs, fazer reconciliação ou reprocessamento manual.

## Prazo total e contagem — APROVADOS EXPRESSAMENTE

O responsável confirmou **até 18 dias no total, divididos em duas etapas consecutivas**:

1. **Dias 0 a 3:** fila normal de entrega com tentativas automáticas, até **3 dias** a partir da confirmação de persistência do envio por uma das APIs.
2. **Dias 3 a 18:** caso o envio ainda não tenha sido entregue, mover para a **fila de falhas/exceções por mais 15 dias**. A correção do problema permite reprocessamento automático quando viável, com reprocessamento manual autorizado disponível.
3. **Dia 18:** fim do prazo máximo da permanência nessas duas filas para um envio não entregue; avisos e descarte foram definidos posteriormente na IN-128 e na IN-147. **O descarte automático após 18 dias está autorizado pela IN-147, desde que auditado e após verificação final**, sem exclusão silenciosa.

O **marco temporal de referência** é a primeira persistência durável confirmada do identificador único de envio, preservado mesmo se o processamento alternar entre a API principal e a secundária. Reenvios, alterações de estado e transferências entre filas não reiniciam o prazo.

Erros permanentes detectados antes do terceiro dia podem ser sinalizados imediatamente para diagnóstico e correção, sem antecipar a expiração máxima de 18 dias. Em falhas temporárias, repetir em intervalos progressivos. Em erros permanentes, de autenticação ou configuração, registrar a causa e evitar tentativas agressivas até resolução. Em qualquer caso, exibir código/categoria/descrição do erro e histórico no Portal Imagine.

## Exemplo de visualização administrativa

| Informação | Exemplo ilustrativo |
|---|---|
| Site/formulário | site-exemplo / Contato |
| Identificador | ID único de envio |
| Situação | Falha na entrega |
| Motivo | Falha de autenticação com CRM (HTTP 401) |
| Última tentativa | Data e hora |
| Tentativas | Quantidade |
| Próxima ação | Corrigir credenciais; retentar após validação |
| Ações autorizadas | Ver histórico, diagnosticar, reprocessar |

## Pontos não aprovados e pendentes de detalhamento

- **Após entrega bem-sucedida:** prazo de 24 horas para limpeza da cópia temporária **aprovado na IN-128**; os detalhes de limpeza em ambas as APIs permanecem para especificação técnica.
- **Histórico/auditoria:** retenção técnica minimizada por 90 dias **aprovada na IN-128**, com restrição a dados pessoais e ressalvas de obrigações legais.
- **Descarte ao fim da fila de falhas:** avisos 72 horas antes e alerta crítico/interrupção de retentativas no vencimento **aprovados na IN-128**. **IN-147 aprova exclusão automática segura após 18 dias sem entrega confirmada**, sem quarentena de 30 dias; continuam pendentes os mecanismos técnicos e exceções legais, sem descarte silencioso.
- Frequência e limites das tentativas; classificação de erros temporários e permanentes; tratamento de contas CRM suspensas ou de alterações do vínculo CRM durante a retenção.
- Armazenamento criptografado, backups de filas, retenção de conteúdo pessoal, limites de volume, métricas e custos.
- Os **18 dias consecutivos (3 + 15)** estão aprovados; restam detalhes técnicos de fuso, horários, avisos preventivos e funcionamento sob incidentes.

## Restrições permanentes
- Sites Imagine **não possuem banco de dados próprio**.
- API principal e API de contingência ficam em infraestruturas independentes, com armazenamento persistente próprio, conforme IN-124.
- Apenas confirmar o envio ao visitante **após persistência durável por uma das APIs**.
- A área administrativa do Portal Imagine gerencia diagnósticos, alertas, filas e reprocessamento, mas sua indisponibilidade não impede recepção pelas APIs.
- Falha simultânea das duas APIs pode impedir o recebimento; não prometer garantia absoluta de perda zero.

**Status funcional:** novas decisões aprovadas e documentadas; parâmetros pendentes separados. **Programação não iniciada.**


## Complemento posterior — IN-128

Ver [IN-128 — Limpeza Segura e Auditoria das Filas de Leads](IN-128-limpeza-segura-auditoria-filas-leads.md) para os prazos aprovados de limpeza de cópias entregues (24 horas), histórico técnico (90 dias), aviso de expiração (72 horas de antecedência) e alerta crítico/interrupção do reenvio automático no dia 18. A exclusão automática dos leads não entregues após o prazo de 18 dias foi autorizada na [IN-147 — Exclusão de Leads Não Entregues Após 18 Dias](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md), mediante verificação e registro auditável.


## Atualização documental — decisão IN-147 (01/11/2026)

Aprovação posterior do responsável: **“passou os 18 dias pode excluir”**. Por isso, a política consolidada para envios não entregues é **3 dias de fila normal + 15 dias de falhas**, aviso 72 horas antes, alerta crítico no vencimento e **permissão para excluir automaticamente o payload temporário após completar 18 dias**, com conferência do estado, auditoria minimizada de 90 dias e exceções legais. **Não criar quarentena extra de 30 dias.** Veja [IN-147](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md). Esta atualização prevalece sobre eventuais trechos históricos anteriores.
