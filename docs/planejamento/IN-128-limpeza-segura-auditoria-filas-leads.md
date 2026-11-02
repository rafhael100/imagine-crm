# IN-128 — Limpeza Segura e Auditoria das Filas de Leads

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026.  
**Fase:** planejamento/documentação, sem autorização para implementar.  
**Depende de:** IN-124 (APIs redundantes e persistência), IN-126 (monitoramento/reprocessamento) e IN-127 (retenção de até 18 dias, em fases de 3+15 dias).

## Objetivo

Eliminar cópias temporárias de leads que já foram entregues de forma confirmada; manter auditoria técnica suficiente para diagnóstico; e prevenir o desaparecimento silencioso de leads não entregues ao final do prazo de retenção.

## Regras aprovadas

1. **Lead entregue:** após confirmação de persistência/recebimento pelo CRM, manter a cópia temporária no serviço de integração por mais **24 horas** e então realizar limpeza segura, salvo obrigação legítima de retenção excepcional. A limpeza dessa cópia não exclui o lead armazenado no CRM.
2. **Histórico técnico:** conservar registros de auditoria por **90 dias**, em formato minimizado, sem reproduzir conteúdos de formulários ou dados pessoais desnecessários. A retenção técnica é independente da cópia temporária do payload e não significa manter dados completos por 90 dias.
3. **Pendências:** reenvio normal por até **3 dias** e manutenção por mais **15 dias na fila de falhas**, totalizando **18 dias**, conforme IN-127. Reenvios não renovam o relógio da retenção.
4. **Alerta preventivo:** quando um lead ainda não entregue estiver a **72 horas** do vencimento dos 18 dias, emitir aviso à administração Imagine com identificação do envio e diagnóstico do erro, sem expor segredos ou dados pessoais em notificações.
5. **No vencimento dos 18 dias:** emitir **alerta crítico**, interromper novas tentativas automáticas, realizar verificação final de entrega/estado e, se elegível, permitir **exclusão automática do conteúdo temporário**, mantendo registro técnico minimizado conforme IN-147.
6. **Descarte autorizado (IN-147):** após completar 18 dias sem entrega confirmada ao CRM, o conteúdo temporário **pode ser excluído automaticamente** por política específica, com verificação final, avisos, auditoria minimizada e exceções de guarda legal. **Não há quarentena adicional de 30 dias nem exigência de aprovação individual para cada descarte.**
7. **Administração:** funcionários autorizados poderão consultar situação, diagnóstico e histórico e exportar dados ou registros ainda disponíveis em conformidade com as permissões e a política de privacidade; o envio de dados pessoais em exportações deve ser controlado e auditado.
8. **Segregação e segurança:** isolar por cliente/conta/site, proteger dados temporários e históricos de auditoria, restringir acesso ao necessário, e registrar intervenções e reprocessamentos de modo rastreável.

## Relação com o ciclo de entrega

| Evento | Ação |
|---|---|
| Lead confirmado no CRM | Manter cópia temporária por 24h e limpar de forma segura |
| Lead não entregue, dia 0 a 3 | Tentativas automáticas normais |
| Lead não entregue, dia 3 a 18 | Fila de exceções/erros e recuperação conforme IN-127 |
| Lead não entregue, dia 15 | Alerta de que faltam 72 horas para o limite |
| Lead não entregue, dia 18 | Alerta crítico, interromper tentativas, reconciliar o estado e permitir exclusão automática segura após o prazo (IN-147) |
| Eventos e tentativas | Auditoria técnica minimizada por 90 dias |

Observação: os dias são relativos ao marco de persistência durável estabelecido na IN-127; a operacionalização de horários e fuso será especificada na arquitetura técnica.

## Salvaguardas e pendências de especificação

- **Política de descarte final aprovada posteriormente na IN-147:** o payload de envios não entregues poderá ser excluído automaticamente após os 18 dias, com alertas e auditoria e sem quarentena adicional de 30 dias. A implementação detalhada e exceções de guarda legal permanecem para especificação.
- Definir verificações determinísticas para descarte após 18 dias, tratamento de concorrência/estado de entrega, auditoria, alertas e exceções legais/LGPD antes da implantação; não exigir revisão humana por envio como regra.
- Diferenciar confirmação de entrega efetiva ao CRM de mera resposta intermediária da API; impedir a limpeza de dados em entregas incertas.
- Definir como a retenção de 24h lida com réplicas nas APIs principal/secundária, backups técnicos, filas de erro e consistência de status.
- Ajustar política de limpeza segura à arquitetura de persistência, proteção de dados, restabelecimento após incidente e exigências legais.
- Reconciliar minimização do histórico de 90 dias com eventuais prazos maiores de auditoria exigidos pela plataforma/legislação, caso se aplique.
- Definir permissões para exportar registros pendentes e limites de acesso a conteúdos potencialmente sensíveis.
- Configurar e testar alertas de 72h, alerta crítico, suspensão das tentativas automáticas e procedimentos de correção.

## Situação

**Aprovado:** cópia temporária entregue por 24h; auditoria técnica por 90 dias; aviso 72h antes da expiração; alerta crítico e interrupção de reenvio automático no dia 18; não excluir silenciosamente pendências; consulta/exportação autorizada.  
**Pendente:** detalhes técnicos e de conformidade da eliminação automática autorizada na IN-147; não há pendência de aprovação do prazo de descarte.  
**Programação:** não iniciada.


## Atualização documental — decisão IN-147 (01/11/2026)

Aprovação posterior do responsável: **“passou os 18 dias pode excluir”**. Por isso, a política consolidada para envios não entregues é **3 dias de fila normal + 15 dias de falhas**, aviso 72 horas antes, alerta crítico no vencimento e **permissão para excluir automaticamente o payload temporário após completar 18 dias**, com conferência do estado, auditoria minimizada de 90 dias e exceções legais. **Não criar quarentena extra de 30 dias.** Veja [IN-147](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md). Esta atualização prevalece sobre eventuais trechos históricos anteriores.
