# PORTAL-D02 — Pagamentos, Parcelamento e Execução de Serviços Adicionais do Portal Imagine

**Status:** **APROVAÇÃO PARCIAL EM 02/11/2026**: condição de início de execução aprovada pelo responsável (resposta “ok” à pergunta explícita). **Restante da política financeira está pendente.**  
**Fase:** somente planejamento e documentação. **Não autoriza cobrança real, contratação, programação, comunicação comercial, emissão de faturas ou alteração operacional**.
**Escopo:** serviços extras/adicionais e orçamentos específicos solicitados por clientes no **Portal Imagine**, **não** implantação base de Onepage/Landing Page, mensalidade normal do site, contratação de CRM ou benefícios do CRM.
**Referências:** [PORTAL-D01 — aceite expresso](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [PORTAL-01 — matriz de pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md), contratos e políticas financeiras ainda sujeitos a revisão ao final do planejamento.

## 1. Valores e formas anteriormente definidos — preservar

- **Orçamento adicional até R$ 270,00:** pagamento **à vista**.
- **Orçamento adicional acima de R$ 270,00:** cliente poderá optar entre **à vista ou parcelamento em até 3 vezes**, sujeito às regras financeiras e contratuais que ainda serão definidas.
- **Orçamento pré-validado pela equipe Imagine** é submetido a **aceite eletrônico expresso pelo cliente ou representante autorizado**, conforme PORTAL-D01.
- Essa política não estabelece juros, parcelamento automático, descontos, meio de pagamento, vencimentos ou termos jurídicos por simples inferência.

## 2. Decisão funcional expressamente aprovada — marco para iniciar execução

**O serviço adicional só poderá ser liberado para execução depois que os dois eventos seguintes forem verificados:**

1. **Aceite eletrônico válido** do orçamento exato, incluindo escopo, versão, preço e condições apresentadas, por cliente autenticado e autorizado (PORTAL-D01).
2. **Confirmação financeira do recebimento**:
   - **à vista:** confirmação do pagamento integral devido para iniciar o serviço adicional;
   - **parcelado:** confirmação do pagamento efetivo da **primeira parcela** do orçamento aprovado.

- **Aceite isolado não libera execução**, nem autoriza débito automático.
- **Pagamento não comprovado** por conciliação/validação confiável não libera execução; comprovante enviado isoladamente pelo cliente não equivale necessariamente à liquidação confirmada.
- **Confirmado aceite + recebimento necessário**, o Portal pode liberar a ordem de serviço para a equipe Imagine ou para agentes dentro das permissões e procedimentos de produção já homologados. A liberação financeira não substitui checagens de escopo, qualidade, autorização de mudança, versão nem eventuais aprovações técnicas.
- Registrar vínculos auditáveis entre **pedido/orçamento/versão aceitos, cliente autorizado, transação/primeira parcela, confirmação e ordem de serviço**, com prevenção de processamento duplicado (idempotência).
- **Não criar regra de execução antecipada por urgência, confiança no cliente, exceção automática ou promessa de pagamento** sem aprovação posterior específica.
- O marco acima vale para o **início da execução do adicional**. **Não altera marcos de pagamento da implantação do site, o dia 90 da mensalidade ou a concessão do CRM Básico**.

## 3. Exemplos esclarecedores

| Orçamento extra | Forma escolhida | Evento mínimo para liberar execução |
|---|---|---|
| R$ 180,00 | À vista | Aceite expresso + R$ 180,00 recebidos e confirmados |
| R$ 270,00 | À vista | Aceite expresso + R$ 270,00 recebidos e confirmados |
| R$ 300,00 | À vista | Aceite expresso + R$ 300,00 recebidos e confirmados |
| R$ 300,00 | Até 3 parcelas | Aceite expresso + **primeira parcela recebida e confirmada**, segundo plano efetivamente contratado; vencimentos/saldos sujeitos à próxima decisão |

Os valores e exemplos não aprovam juros, eventual divisão em quantias iguais, taxas ou descontos; essas condições devem aparecer claramente no orçamento final antes do aceite.

## 4. Pendências que NÃO foram aprovadas neste “ok”

1. **Parcelamento:** se será **sem juros para o cliente**, com juros/taxa explicitamente informados ou dependente do meio de pagamento; como distribuir parcelas e lidar com custos do adquirente.
2. **Meios de pagamento:** cartão, Pix, boleto e eventuais outros; conciliação, expiração e disponibilidade por modalidade.
3. **Vencimentos e prazos:** validade do orçamento, prazo para pagamento da primeira parcela, vencimentos das demais, início da contagem dos prazos de entrega e expiração da ordem de serviço.
4. **Atraso posterior:** consequências de inadimplência de parcela subsequente para serviços iniciados/em andamento/concluídos, aviso, suspensão quando contratualmente permitida, cobrança e renegociação.
5. **Desistência, contestação e reembolso:** tratamento de trabalhos não iniciados, parcialmente entregues ou concluídos, estorno/chargeback e reversão segura, à luz da legislação aplicável.
6. **Autorizações excepcionais:** casos de gratuidade, crédito, serviços contratuais já cobertos, compensações e procedimentos de liberação manual, com alçadas e auditoria.
7. **Contrato, termos e compliance:** impostos/documentos fiscais, retenção dos comprovantes, apresentação de informações ao cliente, reconciliação, antifraude e revisão jurídica.

## 5. Próxima decisão proposta — parcelamento dos adicionais acima de R$ 270

**Proposta a deliberar, NÃO aprovada:** oferecer **até três parcelas sem juros ao cliente**, com **valor total do orçamento já contemplando os custos previstos de processamento**, sem acrescentar encargos inesperados ao final; discriminar no orçamento o valor à vista e os valores/vencimentos de cada parcela, sem conceder desconto automático para pagamento à vista, salvo política posterior. Essa proposta **não decide** tecnologia de cobrança, antecipação financeira, taxas do adquirente nem fluxo de vencimentos.

**Pergunta de continuação:** aprova oferecer os adicionais acima de R$ 270,00 em **até 3 parcelas sem juros**, com total fechado e informado previamente no orçamento?

**Sem testes, software, contratação ou execução financeira realizados.**
