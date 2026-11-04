# CRM-CORE-P0-08 — Básico público/cortesia, múltiplas organizações, trial e direitos comerciais (proposta v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE08-D01, D02 e D03 APROVADAS COMO BASE DOCUMENTAL**, conforme [registro expresso de decisão](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md). **CORE-P0-08 PARCIAL/ABERTO**, CORE08-D04 a D10 e demais bloqueios técnicos permanecem abertos. As referências abaixo a “PROPOSTA/AINDA NÃO APROVADAS” para D01–D03 descrevem a redação histórica anterior ao aceite e são superadas pela decisão posterior.  
**Fase:** somente planejamento, redação e versionamento GitHub. **Sem programação/Codex, testes executáveis, criação de tenants/contas/assinaturas, cobranças, emissão fiscal, pagamentos reais, disparos de avisos, provisionamento ou contratação.**  
**Objetivo:** reconciliar a organização CRM/tenant, contratos Sites, seleção de conta CRM, plano Básico público e benefício condicional, gratuidade administrativa, trial por CNPJ e ciclos distintos de pagamento/suspensão/cancelamento sem conceder quotas, acesso ou descontos por inferência.

## 1. Fontes de precedência (decisões históricas preservadas, sem novo aceite tácito)

1. [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), [CORE01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md), [CORE07 D01–D03](CRM-DEC-aprovacao-CORE-P0-07-D01-D03-base-convites-assentos-equipes-2026-11-04.md): identidade global, memberships por tenant, uma organização CRM = um tenant lógico, assentos por organização, Básico com **1 administrador autenticado e MFA**; CNPJ não é chave de isolamento.
2. [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) **aceita como base de reconciliação**, não fechamento técnico/comercial: planos Básico/Essencial/Profissional/Empresarial, Básico R$ 69, Essencial R$ 89 de referência atual, demais valores/assentos pendentes; trial de 14 dias, **um por CNPJ**, sem cartão, e-mail e telefone/OTP, retenção de trial 60 dias, sem novo trial implícito por site.
3. [LEG-02 — Planos e trial](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md), [LEG-13 — Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md): direitos/limites específicos de cada modalidade, sem herança de recursos superiores; trial 2 usuários/20 leads/2 funis/2 automações/20 MB e créditos IA específicos, não transferíveis automaticamente ao Básico.
4. [IN-117 — seleção de destino](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md) e [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md): ao contratar site elegível, o cliente seleciona organização CRM existente autorizada **ou** nova organização segregada; o mesmo CNPJ pode ter várias organizações Básico, se cada uma possuir vínculo contratual elegível.
5. [IN-118 — Portabilidade](IN-118-central-backup-portabilidade-dados.md): exportar/importar/restaurar/backup essenciais por autoatendimento, respeitando direitos; mudança de conta vinculada via chamado, Imagine executa desvinculação + integração técnica gratuitamente, **não transfere dados entre organizações** por consequência.
6. [IN-119](IN-119-gestao-cortesia-crm-basico.md), [IN-120](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121](IN-121-notificacoes-reativacao-automatica-cortesia.md), [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md): cortesia condicionada a contrato de site elegível; **no máximo uma cortesia Básico efetiva por organização**, sem acumular créditos/mensalidades; transição formal de 30 dias; avisos dias **0/15/25/29/30**; nenhuma contratação paga automática por expiração.
7. [PORTAL-COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md), [PORTAL-COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md): elegibilidade da cortesia configurada **por serviço/campanha de novas vendas**, expressa no contrato e não revogada retroativamente; site suspenso após tolerância de 7 dias de inadimplência bloqueia operação da cortesia **somente se único vínculo elegível**; cancelamento comum do último site dá 30 dias operacionais, cancelamento após suspensão por dívida dá 30 dias restritos.
8. [CRM-REV-02 decisões de gratuidade administrativa](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md): gratuidade administrativa de **qualquer plano** por administrador Imagine competente, sem requisito de site, e concessão **separada** de adicionais elegíveis; vigências de gratuidade com políticas próprias. Não confundir essa concessão com benefício automático de Site ou fatura de R$0, e não cobrar automaticamente após seu término.
9. [CORE02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md), [CORE03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md), [CORE04 D01–D03](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md), [CORE05 D01–D03](CRM-DEC-aprovacao-CORE-P0-05-D01-D03-base-suporte-excepcional-2026-11-04.md), [CORE06 D01–D03](CRM-DEC-aprovacao-CORE-P0-06-D01-D03-base-auditoria-2026-11-04.md): verificação cumulativa de direitos/estado, resultado oficial somente pelo módulo dono, segregação de suporte e auditoria por categoria; sem grants ou decisões automatizadas sem contrato.

**Precedência crítica:** IN-127-R01–R06 preserva sites **estáticos** sem CRM autorizado apenas sob condições aprovadas; **formulários não podem captar leads quando não houver CRM oficialmente vinculado/autorizado**, inclusive páginas em cache. Não reabrir esta regra por analogia ao Básico ou ao trial. A retenção e filas de captação não se confundem com retenção da assinatura CRM.

## 2. Entidades e distinções essenciais — referência lógica, não schema

| Conceito | Quem decide/prova | Limite |
|---|---|---|
| Identidade global | IAM/CORE01/07 | Login único pode acessar tenants independentes apenas se houver membership ativa |
| Empresa jurídica/CNPJ | Cadastro/contratos | Identifica titularidade e histórico de trial; **não unifica tenants, permissões, benefícios ou bases** |
| Organização CRM / tenant | CRM como proprietário da organização | Dados, direitos, quota, assinatura e auditoria por organização |
| Contrato de site/landing | Portal Imagine, contrato e campanha vigentes | O contrato pode ou não ser elegível à cortesia; não é assinatura CRM |
| Vínculo site ↔ CRM | Integração oficial versionada, titularidade e autorização | Cada site possui destino CRM autorizado; mudança por chamado, sem migração implícita |
| Plano público Básico | Catálogo CRM | Contratável independentemente de site; preço de planejamento **R$69/mês** (revisável) |
| Benefício Básico cortesia | Regras de elegibilidade do contrato de site → entitlement por organização | Reduz a mensalidade básica elegível sem criar plano falso, nem somar múltiplas cortesias |
| Assinatura CRM paga | FIN/CON CRM, confirmação oficial de pagamento/contratação | Independente de dívida/cancelamento de site; pagamento não presumido por HTTP/ACK |
| Gratuidade administrativa Imagine | Administração Geral competente, ato próprio de concessão | Independente de site/trial e separada de gratuidade de adicionais |
| Trial CRM | Registro único por CNPJ, elegibilidade e verificação OTP | **Uma utilização por CNPJ**, 14 dias, sem cartão, quotas próprias |
| Assento/membership | CORE07 + direito do plano | Básico 1 usuário humano autenticado e MFA, independentemente de profissionais da agenda |

**Invariante:** nenhuma criação por evento duplicado, por mesmo e-mail, mesmo CNPJ, segundo site, campanha ou pagamento técnico inconclusivo pode gerar tenant/cortesia/assinatura/trial duplicados.

## 3. CORE08-D01 — Distinção lógico-contratual de organização, Básico, cortesia e trial (PROPOSTA)

Aceitar como base conceitual um **modelo explícito de estados e direitos por organização** no qual:

1. **Organização CRM é um tenant**; contratos de Sites e benefício comercial são **vínculos distintos** do tenant. Uma pessoa pode ter memberships em várias organizações e o mesmo CNPJ pode ser titular de diversas organizações legítimas.
2. O **Básico público** tem preço próprio e contratação independente de site; a **cortesia** não é um plano separado nem transforma o CRM em gratuito por tempo indeterminado sem vínculo elegível.
3. **Um trial por CNPJ** é regra específica de avaliação; não se renova automaticamente por novo site, nova organização, novo e-mail de usuário ou benefício Básico. **Trial** não é sinônimo de Básico; quotas/limites e retenções não podem ser intercambiados.
4. Cada organização possui seus próprios direitos, limites de usuário e dados; dois sites em uma organização não duplicam assentos, armazenamento, créditos de IA, recursos ou mensalidade cortesia.
5. O Portal Imagine e o CRM Imagine possuem contratos e cobrança independentes; gratuidade administrativa de planos e adicionais segue atos próprios, não decorre automaticamente de site/cortesia ou de permissões de suporte/IA.

**Não se aprova com D01:** schema físico, tipo de trial, preço final, desconto de plano superior, número pago de assentos, quantidade ilimitada de organizações ou procedimento de titularidade.

## 4. CORE08-D02 — Prova de elegibilidade e vínculo Site↔organização CRM (PROPOSTA)

Propor referência lógica para assegurar que somente **contrato efetivo e elegível** sustente a cortesia, respeitando escolha expressa de destino CRM e idempotência:

- Na contratação de site/landing elegível, pedir ao cliente **organização CRM existente sob autorização** ou **nova organização CRM segregada**. Concluir vínculo com validação de titularidade, situação contratual e autorização do destino, independentemente de CNPJ igual ou e-mail idêntico.
- **Uma cortesia efetiva por organização** quando existir ao menos um site contratualmente elegível e efetivamente válido. **Dois sites → mesmo CRM = uma cortesia**; **dois sites → duas organizações elegíveis = até duas cortesias**, uma por conta. Perda de um site não retira benefício se outro sustentador permanece ativo.
- Elegibilidade é **configurável por serviço/campanha para novos contratos**, com condições informadas/aceitas. Edição posterior não extingue retroativamente benefício legítimo já concedido.
- Mudança de CRM vinculado ao site exige **chamado**, verificação de origem/destino e **nova integração técnica gratuita** pela Imagine; dados entre organizações devem ser migrados pelo cliente via ferramentas IN-118, não movidos automaticamente por essa operação.
- Não ativar captura de formulário para destino sem CRM **oficialmente vinculado/autorizado**; não permitir destino alternativo implícito, conta por e-mail/CNPJ ou armazenamento oculto no Portal. Site estático sem CRM segue IN127-R06, sem formulários ativos.
- Recalcular direitos de ambas as organizações afetadas após vínculo/desvínculo, cancelamento, suspensão, quitação e mudança de campanha conforme contratos, **sem ativar benefício/cobrança/gratuidade por simples evento HTTP**. Distinguir evento registrado, autorização, efeito financeiro oficial e direito efetivamente aplicado.
- Não converter site, Básico ou trial em plano superior por inferência e não usar elegibilidade de cortesia para conceder MFA, assentos, dados ou papéis extras.

**Não se aprova com D02:** contrato técnico exato, número máximo de organizações elegíveis, fluxos de suspeita de fraude, concorrência, compensações, contagem de contratos ou conflito de benefícios/gratuidade administrativa.

## 5. CORE08-D03 — Ciclo lógico de trial, assinatura e cortesia, sem cobranças automáticas (PROPOSTA)

Propor **separação inequívoca dos estados de cada domínio**:

### 5.1 Trial, plano pago e benefício — tratamentos distintos

- **Trial:** 14 dias, um por CNPJ, sem cartão e com verificações de e-mail+telefone; ao expirar, bloquear operação trial conforme regra vigente; retenção própria **60 dias após expiração** e critérios de conversão/reativação específicos. Novo contrato Sites não cria segundo trial.
- **CRM pago independente:** contrato e condições próprias. Inadimplência do site **não suspende CRM pago**. Para assinatura CRM paga, regra histórica de **7 dias de tolerância**, suspensão depois e cancelamento aos **30 dias de atraso**, com registros e reconciliação financeira; critérios executáveis ainda FIN/CON.
- **Básico cortesia sustentado:** funciona com direitos do Básico se algum contrato Sites ativo e elegível sustentar a organização; múltiplos sites elegíveis não geram múltiplas cortesias na mesma organização.
- **Assinatura CRM formalmente cancelada:** retenção geral de dados de **120 dias após o cancelamento formal do próprio CRM**, observadas políticas próprias e LGPD. Nem site cancelado nem mera suspensão iniciam automaticamente esse prazo.

### 5.2 Matriz de eventos já aprovada e preservada

| Evento legitimado | Referência de efeito |
|---|---|
| Um entre vários sites elegíveis é cancelado, ainda resta outro sustentador válido | Manter cortesia/funcionamento da organização, sem iniciar transição |
| **Cancelamento comum** do último site elegível | Começar **transição de 30 dias a partir do cancelamento formal**, **CRM operacional normal**, avisos padrão dias **0/15/25/29/30**; sem nova contratação, ao fim suspender operação normal e oferecer acesso restrito e portabilidade |
| Site único sustentador **suspenso por inadimplência após 7 dias de tolerância** | Bloquear operação do Básico cortesia enquanto persistir falta de sustentação, com acesso restrito para regularizar/portar dados; não aplicar automaticamente a transição de 30 dias só pela suspensão |
| Último site é **formalmente cancelado por inadimplência após suspensão** | Iniciar a **transição de 30 dias** no cancelamento formal **com acesso operacional restrito**; comunicar sem prometer uso normal, permitir exportação e contratação expressa |
| Nova elegibilidade legítima é restabelecida | Reavaliar e reativar benefício condicionalmente, cancelando suspensões/avisos que perderam causa, sem duplicar benefício ou gerar cobrança |
| Cliente contrata Básico pago voluntariamente | Manter dados/organização e continuidade sob contrato pago; **nunca cobrar automaticamente por fim da cortesia** |
| Cliente usa plano superior | Cobrança e direitos do plano contratado; não abater automaticamente cortesia Básico nem criar créditos cumulativos |
| Gratuidade administrativa de qualquer plano | Aplicar somente ao tenant e vigência do ato administrativo válido; não ser revogada por cancelamento de site sem regra própria |
| CRM pago é cancelado | Retenção de 120 dias após cancelamento formal do CRM, distinta do fim de cortesia/site |
| Site estático sem CRM autorizado | Publicação permitida conforme IN127-R06, **proibida a captação por formulário** até destino autorizado, inclusive proteção no backend |

**Alertas e UX:** indicar claramente plano, valor público, benefício válido, estado da assinatura, razões de restrição, data/marco correto do evento e possibilidade de autoatendimento/portabilidade. Os canais exatos/idioma e templates continuam pendentes.

**Não se aprova com D03:** cronograma técnico de scheduler, cobrança em gateway, cálculo prorata, reconciliação oficial de PSP, estados físicos, reativação em fraude, expurgo real, notificações implementadas ou novos prazos.

## 6. Exemplos operacionais para entendimento — sem execução

1. Cliente contrata site A com cortesia elegível e escolhe organização CRM X: X pode receber um benefício do Básico, se contrato e vínculo válidos.
2. Mesmo cliente contrata site B também elegível e seleciona **X**: a cortesia de X continua **uma**, sem segundo assento ou franquia acumulada.
3. Se site B seleciona nova organização CRM Y autorizada: Y pode receber **uma cortesia própria**, ainda que X e Y tenham o mesmo CNPJ; isso não gera novo trial.
4. Cliente do exemplo já usou trial: qualquer tentativa de repetir trial com nova organização/email é negada por elegibilidade baseada no CNPJ e histórico oficial.
5. Site A é cancelado, mas site B permanece em X e elegível: nenhuma transição da cortesia X se inicia apenas por A.
6. Cliente com Básico público pago não depende de um site; eventual inadimplência do site não bloqueia seu plano CRM pago.
7. Cliente com site suspenso por dívida e depois formalmente cancelado: transição de 30 dias **restrita**, sem retorno artificial ao uso pleno.
8. Cliente solicita transferir o site para outro CRM: Imagine altera integração gratuitamente por chamado; o cliente exporta/importa dados se desejar migrá-los, sem operação invisível pela Imagine.
9. Administrador Imagine concede plano gratuitamente por ato separado: essa gratuidade não é marcada como cortesia de Site e não some por simples fim do contrato de Site.
10. Conta em suspensão por fim da cortesia ainda oferece acesso autenticado de exportação e recontratação conforme IN-118/120; não confundir com eliminação de registros.

## 7. Cenários para homologação FUTURA — NÃO EXECUTADOS

| ID | Tentativa / condição | Resultado documentado esperado |
|---|---|---|
| CORE08-T01 | Mesmo CNPJ em duas organizações elegíveis por dois sites distintos | Duas organizações e benefícios independentes; sem contaminação de dados |
| CORE08-T02 | Dois sites elegíveis ligados à mesma organização | Uma cortesia, sem cumulação de quotas/créditos |
| CORE08-T03 | Novo site de CNPJ com trial já consumido | Não conceder segundo trial |
| CORE08-T04 | Evento de pagamento/vínculo chega duplicado ou fora de ordem | Não criar tenant, contrato ou cortesia duplicados; reconciliar |
| CORE08-T05 | Site único sustentador suspenso por dívida após tolerância | Restringir apenas cortesia dependente; preservar CRM pago e outras contas sustentadas |
| CORE08-T06 | Site último é cancelado ordinariamente | Iniciar 30 dias de operação plena e avisos no marco formal |
| CORE08-T07 | Último site cancelado por dívida após suspensão | Iniciar 30 dias **restritos**, sem reativar operação |
| CORE08-T08 | Cliente não contrata CRM pago ao término da cortesia | Suspender operação sem cobrança automática; manter portabilidade |
| CORE08-T09 | Sistema tenta aplicar retenção CRM de 120 dias ao cancelamento de Site | Negar; relógio começa no cancelamento formal CRM |
| CORE08-T10 | Vínculo muda por chamado para organização B | Reavaliar A e B; não copiar dados automaticamente nem redirecionar lead para conta não autorizada |
| CORE08-T11 | Landing estática publicada sem CRM vinculado | Formulário bloqueado no backend e em qualquer versão/cached page |
| CORE08-T12 | Administrador de tenant Básico tenta segundo usuário | Negar quota 1/1 e preservar MFA |
| CORE08-T13 | Contrato novo sem elegibilidade de campanha tenta cortesia | Não conceder sem direito oficial, sem revogar contratos anteriores elegíveis |
| CORE08-T14 | Admin Imagine concede plano gratuitamente sem site | Aplicar ato próprio conforme alçada, sem simular contrato Sites ou promover poderes de suporte |
| CORE08-T15 | Tentativa de reativação automática sobre assinatura paga, restrição legal ou titularidade incerta | Verificar autoridade e contratos antes; falhar seguro sem cobrança ou acesso indevido |
| CORE08-T16 | Trial expirado é confundido com cortesia do novo site | Manter objetos e regimes independentes; converter somente sob autorização legítima |

## 8. Decisões submetidas a aceite — AINDA NÃO APROVADAS

- **CORE08-D01 — PROPOSTA:** aceitar a **separação lógico-contratual entre identidade, CNPJ, organização CRM/tenant, assinatura paga, Básico público, cortesia de site, gratuidade administrativa e trial**, preservando uma utilização de trial por CNPJ e direitos independentes por tenant.
- **CORE08-D02 — PROPOSTA:** aceitar o **modelo lógico de elegibilidade por contrato Sites, serviço/campanha e destino CRM expressamente escolhido**, com no máximo uma cortesia Básico por organização, possíveis organizações distintas pelo mesmo CNPJ mediante vínculos elegíveis, mudança de vínculo via chamado e sem migração automática.
- **CORE08-D03 — PROPOSTA:** aceitar a **distinção conceitual dos ciclos de trial, CRM pago, cortesia, suspensão, cancelamento e reativação**, incluindo as modalidades de transição 30 dias comuns/restritas, os avisos já aprovados e a regra de não conversão/cobrança automática; retenção de 60 dias trial e 120 dias após cancelamento formal CRM não se confundem.

## 9. CORE08-D04 a D10 — pendências que continuarão abertas

| ID | Especificação/decisão não homologada | Responsável/dependência |
|---|---|---|
| **CORE08-D04** | Entidades físicas/versionadas e contratos formais Site↔CRM, CNPJ, titularidade, organização, elegibilidade, assinatura, gratuidade, trial, estados e idempotência | CORE01-D06/D07, FIN/CON, Portal/INT |
| **CORE08-D05** | Precedência exata de benefícios simultâneos (site, pagamento, gratuidade administrativa, adicional grátis, promoção), elegibilidade ativa/suspensa/temporária e disputas | FIN/CON, GRAT-P, CRM-REV02 P01..P12 |
| **CORE08-D06** | Prova de contrato/campanha, vínculo e troca de destino, antifraude, limites de organizações, criação/ativação idempotentes, concorrência e prevenção de contas fantasmas | IN-117/122, CORE03, INT P0, Segurança |
| **CORE08-D07** | Máquina de estados, timestamps oficiais/fusos, ordenação de eventos, confirmação financeira, efeito em serviços, reativação e reconciliação operacional e de filas | FIN/CON, INT CT03/04, CORE03, IN-119..122 |
| **CORE08-D08** | Direito de conta em estado restrito (portabilidade/backup, login MFA, suporte, cobrança), comunicação/campanhas, avisos 0/15/25/29/30 e exceções, LGPD | CORE04/05/06, IN-118/121, UX/Privacidade |
| **CORE08-D09** | Cotas finais por plano pago, assentos/adicionais, compatibilidade com trial, preços/benefícios a revisar ao fim, downgrade e quotas de anexos/IA | CRM-REV02, CORE07-D05, FIN/CON |
| **CORE08-D10** | Matriz final de casos negativos/positivos, owners e homologação com CORE/INT/FIN/Portal, G01–G06 e gates globais | CORE-P0-10, INT P0-OWN/SEC/CON/ACC, IN-168 |

**Também continuam abertos:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE05-D04..D10; CORE06-D04..D10; CORE07-D04..D10; CORE-P0-09/10; INT-P0-OWN/SEC/CON/ACC, IN-168 ARQ-01/02/03, SEG-01/02, AGT-01; G01..G06; revisão comercial global e aceite final. **Não declarar qualquer P0 encerrado por aprovação parcial.**

## 10. Continuidade e controle de mudança

Após eventual aceite expresso de CORE08-D01..D03, elaborar registro de aprovação e prosseguir para **CORE-P0-09 — cofre, segredos e identidades de serviço** sem iniciar implementação. Mesmo quando a base documental D01–D03 for aceita, **D04..D10 e gates permanecem abertos**.

**Proibido:** programação/Codex, testes reais, provisionamento de contas e assinaturas, alterações em Portal/CRM/provedor, pagamentos, emissão fiscal, compra de licenças, limpeza de dados, disparo de notificações e criação de credenciais reais. Apenas planejamento documental versionado.

## 11. Aditamento de aprovação parcial — 04/11/2026

O responsável aprovou **CORE08-D01, D02 e D03 como base documental**, nos limites de [CRM-DEC](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md). Separações entre trial único por CNPJ, Básico público, cortesia de Site, múltiplas organizações, assinatura paga e gratuidade administrativa foram preservadas; vínculos de Site e transições de cancelamento/suspensão continuam sujeitos à fonte oficial de cada contrato, sem cobrança automática, perda indevida de dados ou abertura de segundo trial. **D04–D10 não foram aprovadas**, assim como todas as dependências técnicas CORE/INT/FIN/SEG/ARQ/G01..G06. Seguir somente a documentação CORE-P0-09, sem implementação ou testes reais.
