# CRM F3-135 — Revalidação de destinatário no envio de relatório agendado

**Data:** 11/11/2026. **Escopo:** Core PHP e PHPUnit apenas em DEV; sem scheduler, destinatários reais ou links de relatório. **Requisitos:** CRM-REV-06 VEN-R27, VEN-T12/T13/T18, VEN-P12.

## Pré-validação implementada

\`SyntheticScheduledReportDeliveryPreflight\` compara duas **alegações fictícias**: informações de quando um relatório teria sido agendado e de quando seria entregue. Cada fotografia contém instante UTC canônico, bit de acesso do destinatário e versão ilustrativa da política. O agendamento inclui ainda uma lista fechada de 1..3 códigos de campo seguros (stage, score, claimed_value_cents); a entrega inclui estado ilustrativo do relatório (ready, revoked, expired, missing).

A comparação identifica bloqueios quando:
- o destinatário não tinha acesso alegado no momento do agendamento;
- **perdeu acesso no momento da entrega**, mesmo que o agendamento anterior fosse permitido;
- a política mudou entre o agendamento e a entrega;
- o relatório no instante da entrega está declarado revogado, expirado ou inexistente.

Os bloqueios independentes recipient_identity_unverified, tenant_scope_unverified, recipient_permissions_unverified e delivery_channel_unverified nunca são removidos, nem se todas as alegações da amostra forem favoráveis. A ordem dos instantes é validada e todos os campos extras ou sensíveis (email, telefone, nome etc.) são recusados.

## Evidências

\`SyntheticScheduledReportDeliveryPreflightTest\` verifica revogação de permissão após agendamento, atualização de política, relatório revogado/expirado/inexistente, valores de acesso aparentemente positivos sem liberação, instantes iguais, ordem UTC inválida, lista de campos duplicados, consulta a campo sensível e saída sem datas, IDs ou conteúdo de relatórios.

A resposta contém apenas quantidade de campos solicitados, estado alegado do relatório, flags de mudança e **códigos tipados de bloqueio**. Nunca devolve o pedido de campo, nome do destinatário, e-mail, relatório, token ou link real.

## Limite de autoridade

Esta é **uma simulação que mostra quando é necessário revalidar**, não uma checagem real de autorização. Os booleanos informados pelo chamador não provam o acesso de ninguém. Em todos os cenários \`revalidation_verified\`, \`recipient_identity_verified\`, \`tenant_scope_verified\`, \`recipient_permissions_verified\`, \`transport_verified\`, \`authorized\`, \`can_read_report\`, \`can_export\`, \`can_generate_link\`, \`can_deliver\`, \`can_notify\`, \`can_persist\` permanecem \`false\`.

A entrega real de relatórios depende de sessão autenticada, isolamento por tenant, permissões de campo verificadas na fonte, consulta segura, retenção, destinatário autorizado no **instante de entrega**, assinatura/expiração de URL e revisão CORE/SEG/DBA/LGPD/VEN-P12. Sem API, SQL, IAM/ALLOW, mail, cron, n8n, dados reais, custos, VPS, main ou produção.
