# PORTAL-COM-09 — Transição da cortesia CRM após cancelamento do site por inadimplência

**Status:** **DECISÃO FUNCIONAL APROVADA em 02/11/2026.** O responsável respondeu **“sim”** à pergunta expressa: no cancelamento de site anteriormente suspenso por inadimplência, manter **30 dias de transição do CRM Básico cortesia com acesso operacional bloqueado**, liberando **exportação/download de dados e contratação expressa de plano CRM pago**. Para cancelamento comum do último site elegível, preservar os **30 dias de CRM normalmente funcional** aprovados nas IN-119 a IN-121.
**Fase:** exclusivamente planejamento/documentação; não autoriza programação, cobrança ou bloqueio real.
**Referências:** [PORTAL-COM-08 — suspensão e cortesia](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md), [IN-119](IN-119-gestao-cortesia-crm-basico.md), [IN-120](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121](IN-121-notificacoes-reativacao-automatica-cortesia.md), [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md), [IN-118](IN-118-central-backup-portabilidade-dados.md), [PORTAL-D04](PORTAL-D04-cobranca-recorrente-sites.md), [PORTAL-01](PORTAL-01-matriz-pendencias-plataforma-sites.md).

## 1. Dois cenários aprovados

| Evento | Prazo de transição | Funcionamento e direitos do CRM Básico cortesia |
|---|---|---|
| Cancelamento **comum** do **último** site elegível, sem suspensão anterior de cortesia por inadimplência vinculada ao cancelamento | **30 dias** | **Operação normal** do Básico, com avisos, acesso aos dados e oportunidade de assinar plano pago |
| Site é **suspenso por inadimplência** e depois **formalmente cancelado**, sendo seu vínculo o **último** que sustentava a cortesia | **30 dias a partir do evento formal de cancelamento** | **Operação permanece bloqueada** durante a transição; acesso seguro e restrito para **exportar/baixar dados, obter informações de regularização e contratar/ativar plano pago expressamente** |
| Outro site ativo e elegível ainda sustenta a mesma conta | Não iniciar transição por cancelamento de apenas um site | **Preservar cortesia operacional** |
| Conta já tem CRM contratado e pago independentemente do site | Não interromper plano pago por cancelamento do site | **Preservar assinatura e operação paga** |

A **suspensão temporária do site por inadimplência após 7 dias de tolerância** e o **cancelamento formal do site** são eventos distintos. Não começar a contar a transição de 30 dias na primeira falha de cobrança, na notificação ou na suspensão de 7 dias; o marco de 30 dias será a **perda formal do último vínculo elegível por cancelamento** (data/hora/fuso a especificar).

## 2. Regras operacionais da exceção aprovada

1. **Não reabrir o CRM cortesia operacionalmente só porque o site suspenso foi cancelado**: preservar o estado bloqueado por inadimplência durante os 30 dias da transição.
2. **Acesso restrito e autenticado** nesse período para consulta da situação, **portabilidade/exportação/download de dados e backups permitidos** conforme IN-118, e adesão expressa ao CRM Básico pago ou outro plano, observadas permissões e retenções legais. Não excluir dados pela suspensão.
3. **Assinatura paga só com aceite expresso e condições financeiras próprias.** Se o cliente escolher e contratar validamente o CRM pago, restaurar funcionalidades da assinatura contratada, após verificações, independentemente de manter um site cancelado. **Não gerar cobrança CRM automaticamente**.
4. **Novo vínculo elegível:** se outro site ativo sustentar validamente a conta, reavaliar e recuperar cortesia conforme IN-121/122, sem duplicá-la e sem bloqueios indevidos; contratos CRM pagos existentes têm prioridade.
5. **Avisos da transição:** preservar referência aprovada **dia 0, 15, 25, 29 e 30** da IN-121, com conteúdo explícito de que o CRM já está restrito por inadimplência e de que a contratação paga e a portabilidade estão disponíveis. Não enviar mensagem de “CRM operacional normal” nesse cenário.
6. **Ao fim dos 30 dias:** caso não exista assinatura CRM paga nem outro vínculo elegível, manter conta suspensa com acesso restrito de reativação/portabilidade, segundo IN-120; **sem deletar dados, sem cobrar automaticamente**.
7. **Retenção de 120 dias:** a regra geral do CRM conta **após cancelamento formal da própria conta CRM**, e **não** do cancelamento do site, da perda do benefício ou da mera suspensão operacional. Evitar iniciar retenção prematuramente.
8. **Auditoria e conciliação:** registrar estado contratual do site, causa legítima do cancelamento, data formal, quais contratos sustentam o benefício, tipo de transição, início/fim, avisos, tentativas de pagamento, adesão ao pago, reativação, acesso a dados e operações de recuperação. Aplicar idempotência para não alternar estados erradamente.

## 3. Cancelamento comum: permanece a regra IN-119/120/121

Na perda do **último site elegível por cancelamento comum**, sem suspensão por inadimplência que cause esse encerramento, **continuar a operar o CRM Básico durante os 30 dias**. O cliente pode utilizar todos os recursos normais da cortesia, exportar dados e contratar plano pago expressamente. Após 30 dias sem nova elegibilidade ou assinatura paga válida, aplicar suspensão e acesso restrito, sem cobrança automática.

**Importante:** não inferir que qualquer atraso de pagamento transforma cancelamento comum em cancelamento por inadimplência; classificar pelo **motivo efetivo do evento** e pelo vínculo da suspensão prévia.

## 4. Pendências não abrangidas pela aprovação

- **Quando o site suspenso será formalmente cancelado** (quantos dias após a suspensão de 7 dias): não definido pelo “sim”.
- Fluxo no caso de pagamento contestado, regularização tardia, reversão de cancelamento, renegociação ou recontratação do site.
- Calcular com precisão dia 0/30, fuso, regras de notificação e indisponibilidade do sistema; homologar títulos e mensagens.
- Definir os detalhes técnicos e jurídicos de acesso restrito para exportação e backup, suporte e controle de dados.
- Não reabrir o direito à cortesia de clientes antigos pela mudança da política administrativa de novas campanhas; preservar termos válidos.
- Revisão jurídica das cláusulas de bloqueio, transição, direito de portabilidade e cancelamento antes do lançamento.

**Conclusão:** aprovada a **exceção de 30 dias com CRM operacional bloqueado após cancelamento decorrente de inadimplência**, preservando os **30 dias normais** de cancelamento comum. Dados protegidos; nenhum plano pago suspenso indevidamente; nenhuma programação ou cobrança autorizada.


## 5. Complemento COM-10 — reativação pós-cancelamento (02/11/2026)

O responsável aprovou possibilidade de **solicitar reativação de site formalmente cancelado na janela comercial padrão de 60 dias, ajustável pelo administrador e condicionada a backups efetivamente recuperáveis, com cobrança de taxa específica de reativação (preço ainda não definido)**. Caso a nova contratação seja válida e seu serviço/campanha **conceda CRM Básico cortesia**, reavaliar e recuperar o benefício conforme IN-119–122, cessando quando cabível o estado de transição/restrição da conta correta. **Não reabrir automaticamente cortesia por pagamento tardio, nem suspender CRM pago independente ou benefício sustentado por outro site.** Garantia técnica de recuperação, prazo de backup, preço novo e reativação após expirar a janela ainda exigem definição. Ver [COM-10](PORTAL-COM-10-proposta-reativacao-site-apos-cancelamento.md). Sem programação.
