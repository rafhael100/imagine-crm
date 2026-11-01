# IN-121 — Notificações e Reativação Automática da Cortesia do CRM Básico

**Status:** APROVADA funcionalmente em 01/11/2026 pelo responsável do projeto.  
**Etapa:** planejamento/documentação; NÃO iniciar programação.  
**Dependências:** IN-118 (backup, exportação, importação e portabilidade); IN-119 (elegibilidade e 30 dias de transição); IN-120 (suspensão após a transição e acesso restrito).

## Objetivo
Automatizar os avisos sobre o encerramento da cortesia do CRM Básico e permitir o restabelecimento automático do benefício quando a conta voltar a ter site Imagine elegível, sem ativação de cobrança indevida.

## Regras aprovadas

1. Ao identificar o encerramento do último vínculo de site elegível que sustenta a cortesia de uma conta CRM, iniciar o período de transição de **30 dias**, conforme IN-119/IN-120.
2. Enviar notificações relativas à transição pelo **painel do CRM** e por **e-mail**, registrando o histórico dos comunicados e os respectivos estados.
3. Calendário de avisos:
   - **Dia 0:** comunicar o fim da elegibilidade e o início da transição de 30 dias;
   - **Dia 15:** lembrar a opção de continuar no plano CRM Básico pago;
   - **Dia 25:** avisar que faltam cinco dias para o término;
   - **Dia 29:** último aviso preventivo;
   - **Dia 30:** suspender a conta se não houver assinatura paga válida nem nova elegibilidade de cortesia.
4. Durante os 30 dias, a conta permanece funcionando normalmente, preservando recursos e dados do plano, conforme IN-120.
5. Caso o cliente contrate expressamente o CRM Básico pago (preço público de referência atual **R$ 69,00/mês**), preservar continuidade e dados e encerrar o fluxo de expiração da cortesia. **Não efetuar cobrança ou contratação automática apenas pelo fim da cortesia.**
6. Quando uma conta CRM tiver novamente um **site Imagine elegível**, reavaliar a elegibilidade e **restabelecer automaticamente a cortesia**, desde que não haja impedimento contratual aplicável, sem duplicação de benefícios nem cobrança automática.
7. Quando a elegibilidade for restabelecida antes do término dos 30 dias, cancelar os avisos pendentes e a suspensão decorrente daquele evento. Quando ocorrer após suspensão, permitir reativação automática do benefício somente mediante validações de titularidade, vínculo e situação contratual.
8. Preservar a auditabilidade: mudança de estado, motivo, momento, origem e destinatários das notificações, entregas e falhas, confirmação de contratação, reativação e eventuais bloqueios.
9. Não confundir estado de cortesia com estado de assinatura ou com cancelamento formal; conservar a política aprovada de **120 dias de retenção a partir do cancelamento formal**, e não da suspensão (IN-120).
10. Enquanto a conta estiver suspensa pelo término da cortesia, manter acesso autenticado e restrito às opções aprovadas de contratação/reativação e **exportação/download dos dados** (IN-118/IN-120).

## Controles importantes para especificação técnica

- Calcular os dias em relação ao marco oficial de transição da IN-119 e usar fuso horário consistente.
- Mensagens e links de contratação com termos claros; não induzir aceitação tácita nem criar assinatura sem consentimento.
- Verificar a elegibilidade imediatamente antes do disparo dos avisos e da suspensão, evitando suspender conta já requalificada ou paga.
- Processar notificações e restabelecimentos de maneira idempotente, com recuperação de falhas e controle de concorrência.
- Disponibilizar histórico e motivos para consulta pela equipe autorizada; restringir dados de destinatários e conteúdo sensível.
- Não estender automaticamente cortesia a contas diferentes sem verificar a vinculação correta de cada site.
- Definir tratamento específico de contratos em inadimplência, conflitos de titularidade, conta formalmente cancelada, contas que possuam outros produtos ativos e benefícios promocionais incompatíveis.

## Distinção entre aprovado e pendente

**Aprovado:** calendário Dia 0/15/25/29/30, comunicação por painel e e-mail, suspensão condicional após a transição, histórico, identificação e restabelecimento automático da cortesia quando houver novo site elegível e nenhuma restrição contratual.  

**Pendente de detalhamento técnico:** templates e escalonamento de notificação, fuso/marco oficial, regras antifraude, idempotência, casos limítrofes de reativação e integração exata com contratos/assinaturas.

**Não autoriza programação.**
