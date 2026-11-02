# IN-147 — Exclusão de Leads Não Entregues Após 18 Dias

**Status:** APROVADA funcionalmente em 01/11/2026 por decisão expressa do responsável: **“passou os 18 dias pode excluir”**. A proposta anterior de quarentena adicional de 30 dias **não foi aprovada**.  
**Fase:** somente planejamento e documentação; **não autoriza iniciar programação nem excluir dados reais agora**.  
**Escopo:** cópias temporárias de submissões de formulários Imagine que tenham sido persistidas nas APIs/filas de integração, mas **não tiveram entrega confirmada ao CRM Imagine**.  
**Referências:** IN-124 a IN-129 (APIs, filas, prazos e limpeza), IN-130 (incidentes), IN-138 (autonomia controlada), IN-143 a IN-146 (recuperação, failover e reconciliação).

## Decisão principal aprovada

**Leads que permanecerem não entregues ao CRM após completar os 18 dias de retenção operacional poderão ser excluídos automaticamente das filas e do armazenamento temporário de integração. Não criar quarentena extra de 30 dias nem retenção operacional adicional automática.**

Esta política configura uma **autorização explícita de descarte por prazo**, sujeita às salvaguardas abaixo. Ela NÃO altera registros já armazenados no CRM, nem autoriza apagar dados de outro serviço, cliente ou banco.

## Ciclo aprovado — consolidação

| Momento, contado da primeira persistência durável do envio | Tratamento |
|---|---|
| **Dias 0–3** | Tentativas normais de entrega e diagnóstico automático. |
| **Dias 3–18** | Fila de falhas/exceções por mais 15 dias, com reprocessamentos permitidos, alertas, investigação técnica e intervenção autorizada. |
| **72 horas antes do vencimento** | Aviso preventivo à administração, com identificação, estado, falha e possibilidade de resolver ou exportar enquanto o payload existir. |
| **Ao completar 18 dias sem entrega confirmada** | **Alerta crítico**, fim das tentativas automáticas, verificação final de estado e elegibilidade ao descarte, exclusão automática permitida após vencimento quando não houver retenção excepcional necessária; registrar o resultado. |
| **Após o descarte** | Manter **auditoria técnica minimizada por 90 dias**, conforme IN-128, sem conservar o conteúdo completo do formulário por esse motivo. |

A data de referência é o instante da **primeira persistência durável confirmada** em qualquer das APIs. Retentativas, ida e volta da contingência, mudanças entre filas ou reabertura de incidente **não reiniciam o prazo**.

## Pré-condições e salvaguardas do descarte

1. **Alcance exato:** descartar somente o payload temporário de envios cujo prazo de 18 dias se esgotou **sem comprovação de entrega ao CRM**, e cujas cópias de integração e réplicas estejam identificadas e elegíveis. Não excluir o lead se já houver confirmação de entrega ao CRM.
2. **Verificação final determinística:** antes de excluir, conferir ID idempotente, relógio do envio, estado consolidado de entrega, mensagens em trânsito, respostas tardias e réplicas principal/contingência para evitar decisão baseada em estado desatualizado.
3. **Avisos obrigatórios:** preservar aviso com 72h de antecedência e alerta crítico ao vencimento, conforme IN-128. Registrar envio/tentativas de notificação; entrega de uma mensagem não equivale a leitura humana.
4. **Transparência, sem exclusão silenciosa:** a exclusão ocorre em razão de **política previamente aprovada**, com evento de auditoria, motivo “prazo de 18 dias sem entrega”, momento, identificador do envio, serviço/cliente afetado e situação final. O painel pode apresentar o estado **“não entregue — expirado e excluído da fila”**.
5. **Sem fila adicional:** não manter o conteúdo em quarentena por mais 30 dias; eventual cópia em backup técnico ou retenção por obrigação legal segue política própria e não deve ser usada como meio informal de extensão da fila operacional.
6. **Exceções legais e bloqueios de preservação:** não eliminar payload sob obrigação legal de guarda, ordem de preservação ou incidente de integridade que impeça atestar segurança do descarte; registrar motivo e aplicar política específica autorizada, considerando LGPD e bases legais aplicáveis. A regra normal é excluir ao vencer o prazo.
7. **Não contar como sucesso:** um envio expirado e eliminado deve permanecer classificado como **não entregue** nos indicadores. Nunca inventar entrega ou atribuir ao CRM responsabilidade de registros que não recebeu.
8. **Integridade e consistência:** exclusão deve ser idempotente, auditada e compatível com redundância, filas, replicação, idempotência e recuperação; evitar apagar apenas uma cópia e ressuscitar o envio inadvertidamente por reprocessamento de outra.
9. **Segurança:** registrar no histórico minimizado somente dados técnicos necessários; não guardar senhas, tokens, documentos ou conteúdo completo de formulários em logs, notificações ou memórias dos agentes.
10. **Aprendizado e prevenção:** o Agente de Integrações e o Supervisor Técnico devem contabilizar falhas persistentes e registrar a causa/medidas de melhoria, **sem reter dados pessoais do lead apenas para aprendizado**.

## Relação com política de leads entregues (IN-128)

Quando o CRM **confirma a entrega**, aplica-se a regra específica de limpar a cópia temporária **24 horas após a confirmação**, mantendo o dado principal no CRM. **A regra de 18 dias trata exclusivamente de envios que nunca tiveram entrega confirmada.**

## Administração, suporte e recuperação

- Até a expiração, responsáveis autorizados podem diagnosticar, reprocessar e exportar os envios, conforme permissões e controles aprovados.
- Após a exclusão do payload temporário, **não prometer recuperação nem permitir reenvio de conteúdo já removido**; o histórico técnico minimizado ajuda na análise e auditoria, não substitui os dados completos.
- Incidentes graves podem continuar abertos para corrigir a causa, mesmo depois que os envios correspondentes tenham expirado; relatórios devem indicar quantidade e impacto de perdas de oportunidade comercial.
- A configuração do cronograma de execução e o tratamento de réplicas, backups e eventuais períodos de indisponibilidade do mecanismo de limpeza serão especificados tecnicamente antes da implementação.

## Pendências técnicas e de conformidade

- Processo atômico de reconciliação, vencimento e limpeza distribuída, com consistência entre APIs e filas.
- Mecanismos de retenção excepcional por obrigação legal/preservação, sem confundir com quarentena operacional.
- Frequência de execução do job, definição de fuso/UTC, atraso máximo aceitável para limpeza e observabilidade.
- Política de expiração em backups e restaurações, evitando ressuscitar registros expirados ou alegar eliminação instantânea das cópias de segurança.
- Conteúdo exato do aviso de 72h, do alerta crítico e dos indicadores administrativos.
- Testes de borda: entrega confirmada simultânea ao vencimento, réplicas atrasadas, relógios divergentes, recuperação de fila e falha do mecanismo de limpeza.

**Aprovado:** nenhuma quarentena de 30 dias; autorização para excluir automaticamente o conteúdo temporário de leads não entregues **após completar 18 dias**, com avisos e auditoria técnica de 90 dias, respeitando exceções legalmente exigidas.  
**Pendente:** mecanismos técnicos e de conformidade antes de implementar.  
**Programação:** não iniciada.
