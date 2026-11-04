# CRM-CORE-P0-08 — Básico público/cortesia, múltiplas organizações, trial por CNPJ e elegibilidade de Sites (v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL, AINDA NÃO APROVADA. CORE-P0-08 ABERTO; CORE08-D01 a D03 aguardam aceite; D04–D10 seguem em aberto.**  
**Objetivo:** separar identidade global, pessoa jurídica/CNPJ, organização CRM (tenant), teste gratuito, plano Básico público, benefício de cortesia por Site Imagine elegível e assinatura paga, garantindo vínculos auditáveis, segurança e continuidade sem cobrar, duplicar vantagem, iniciar trial ou combinar dados por suposição.  
**Fase:** só documentação e revisão do planejamento. **Sem programação, testes executados, provisionamento, criação de conta/benefício real, cobrança, contratos ou alteração de infraestrutura.**

## 1. Base normativa já aprovada e precedência

| Fonte | Conteúdo preservado e fronteira |
|---|---|
| [CRM-REV-02 — preços, trial, assinaturas e Básico](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) | Base documental aceita, **REV02-D01..12 e CRM-REV02-P01..12 ainda abertas**; Básico R$ 69, Essencial R$ 89, Profissional R$ 149 e Empresarial R$ 299 como **valores de referência sujeitos à revisão conjunta**; cobrança CRM independente do Portal; plano Básico **público e contratável**, cortesia condicional e trial separado |
| [LEG-02 — Planos, trial e assinaturas](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md) | Trial **14 dias**, sem cartão, **CNPJ obrigatório, um trial por CNPJ**, e-mail e telefone verificados por OTP; fim do trial bloqueia operação e mantém dados **60 dias** conforme regras; no trial de referência: 2 usuários, 20 leads, 2 funis, 2 automações, 20 MB, API pública desligada; quotas do trial **não** são do Básico |
| [LEG-13 — Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | Básico **1 usuário humano autenticado por organização**, Administrador da Empresa com MFA; **1 GB de arquivos** por tenant, dados estruturados fora da franquia; sem quota rígida de leads/contatos no Básico, conforme IN-024; profissional agendável é quota separada; plano pago e cortesia aplicam mesma matriz conforme contrato |
| [IN-117 — Novo Site e organização CRM](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md) | Portal exige escolha expressa de **organização CRM existente autorizada** ou **novo tenant independente**; novo contrato de Site elegível pode sustentar nova organização Básico com cortesia, **inclusive mesmo CNPJ**, sem exigir segundo login; múltiplos Sites na mesma organização **não somam quotas** |
| [IN-119](IN-119-gestao-cortesia-crm-basico.md), [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md) | **Uma única cortesia por organização** com pelo menos um site ativo elegível; uma empresa pode ter várias organizações elegíveis distintas, com benefício em cada; não acumular créditos nem abater automaticamente plano superior |
| [IN-120](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121](IN-121-notificacoes-reativacao-automatica-cortesia.md) | Cancelamento do último Site elegível pode iniciar **30 dias** de transição, avisos dia **0/15/25/29/30**, exportação/portabilidade e contratação paga **expressa**, sem cobrar automaticamente; dados CRM sujeitos a 120 dias **após cancelamento formal do CRM**, não da mera suspensão |
| [PORTAL-COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md), [PORTAL-COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md) | Benefício para novas vendas **configurável por serviço/campanha** com contrato; site suspenso por inadimplência após tolerância de **7 dias** pode suspender CRM cortesia sustentado **só por ele**, preservando outros sites elegíveis e CRM pago. Cancelamento **comum do último site**: 30 dias operação normal. Cancelamento **após suspensão por inadimplência do último site**: 30 dias **com acesso operacional restrito**, desde o cancelamento formal; portabilidade e contratação paga continuam disponíveis |
| [IN-118](IN-118-central-backup-portabilidade-dados.md), [IN-123](IN-123-painel-integracoes-sites-crm.md) | Ferramentas de exportação/importação/backup do próprio CRM, sem depender do suporte; status de vínculo e elegibilidade por Portal/CRM; mudança de CRM vinculado ao site **por chamado**, integração técnica gratuita, **sem migração de dados históricos** pela Imagine por esse ato |
| [Concessão administrativa de gratuidade](CRM-DEC-gratuidade-administrativa-qualquer-plano.md) | Administrador Geral Imagine autorizado pode conceder gratuidade em qualquer plano **sem site** por decisão administrativa distinta; **GRAT-P01..08** abertos e não subordinados automaticamente à dívida/cancelamento de Site |
| [CORE-P0-01 a 07 aceites](CRM-CORE-P0-ETAPA1-painel-fechamento.md) | Identidade global, tenant e membership, RLS PostgreSQL + Laravel, direitos por plano/papel/ação, contexto assíncrono, MFA, suporte excepcional, auditoria por categoria, convites/assentos e revogação; todos os CORE-P0 anteriores **parciais**, com D04+ em aberto |
| [IN-127-R01–R06](IN-127-R01-R06-ratificacoes-2026-11-03.md) | Site **estático** sem CRM pode ser publicado com aceite expresso; **formulário não pode captar/ser recebido sem destino CRM oficial vinculado e autorizado**, verificado no backend; nenhuma integração presume destino por CNPJ/e-mail, nem cria depósito alternativo de leads |

**Precedência crítica:** as decisões posteriores e específicas IN-117/119/120/121/122, COM-08/09 e IN-127-R01..R06 prevalecem sobre formulações antigas incompatíveis (incluindo início prematuro da retenção de 120 dias). Planejamento comercial e técnico final de planos, receitas, quotas e eventos ainda não está homologado; não converter uma minuta de CORE em nova condição comercial.

## 2. Dimensões que não podem ser confundidas

| Entidade lógica | Regra de identidade/competência | Proibição |
|---|---|---|
| `legal_entity_ref` / CNPJ | Identifica pessoa jurídica/titularidade para contrato, trial e elegibilidade | **Não** define tenant único, credencial de login ou direito de acessar outro ambiente |
| `identity_ref` | Login global CRM reutilizável; vínculos multiempresa independentes | Mesmo login não implica dados, papel, plano, cota nem cortesia compartilhados |
| `tenant_ref` | Organização CRM isolada, com recursos, dados, permissões, capacidade, status e direitos financeiros próprios | Vários sites no mesmo tenant não multiplicam vagas, armazenamento ou automações |
| `trial_claim_ref` | Direito ao **único teste de 14 dias por CNPJ**, independente do Básico/cortesia | Não reiniciar trial criando outro tenant ou contratando segundo site |
| `plan_ref` | Básico, Essencial, Profissional, Empresarial com catálogo público e direitos correspondentes | Não criar “quinto plano Básico Cortesia”; plano superior não ganha desconto implícito |
| `crm_subscription_ref` | Assinatura CRM paga com faturamento e ciclo próprios | Não cobrar CRM pelo site nem suspender CRM pago por dívida exclusiva do Portal |
| `site_contract_ref` + `site_ref` | Site/landing com produto, campanha, contrato, versão, status e motivo de suspensão/cancelamento do Portal | Mesmo CNPJ, domínio ou link sem contrato elegível **não concede cortesia** |
| `site_to_tenant_binding_ref` | Seleção expressa/autorizada de destino por site, versionada e auditável | Não migrar dados antigos ou redirecionar leads pendentes automaticamente |
| `cortesia_entitlement_ref` | Benefício condicional de **uma cortesia Básico por tenant** sustentada por site(s) elegíveis | Não acumular descontos/créditos por site, não faturar R$ 0 como segunda mensalidade |
| `admin_gratuity_ref` | Gratuidade extraordinária autorizada por Admin Imagine em plano específico, sem site | Não encerrar junto com Site ou tratar como cortesia automática |
| `membership_ref` | Participação da identidade em organização, com assento/papel/MFA | Um site não cria um segundo usuário logado no mesmo Básico |

Os nomes são **referências documentais**, não tabelas ou schema homologados. A quantidade de organizações elegíveis não constitui licença para criar ambientes ilimitados sem contratos e verificações de titularidade.

## 3. CORE08-D01 — Trial independente do benefício por site e dos tenants (PROPOSTA)

**Proposta:** adotar como base lógica duas trilhas de elegibilidade **com chaves e ciclos separados**:

1. **Trial:** concessão experimental por CNPJ elegível e identidade verificada, com duração de **14 dias** sem cartão e limitação **uma vez por CNPJ**. Verificação de contato por OTP, quotas experimentais, fim da operação e janela própria de **60 dias** conforme regras já aprovadas. Não usar `tenant_id` novo como motivo para repetir trial, nem usar um contrato de Site para prorrogar trial automaticamente.
2. **Cortesia Site:** benefício de plano Básico por contrato elegível de Site vinculado a organização específica, **inclusive nova organização para mesmo CNPJ**. Sua concessão não significa emitir novo trial, nem altera direitos/tamanho de um trial anterior.
3. O sistema deve distinguir CNPJ verificado, contrato Site, tenant, status de benefício, histórico de trial e aceites. Não aprovar trial múltiplo porque há várias contas nem negar cortesia de Site legítimo porque o CNPJ já utilizou trial.
4. Plano Básico **público** permanece contratável sem Site por preço comercial vigente; o benefício é a condição financeira de uma organização elegível, não uma edição especial do software.
5. Converter trial em plano pago ou cortesia e encadear dados/tenants **depende de contrato funcional específico**, sem conversão/mesclagem automática ou pagamento sem consentimento.
6. Quotas comerciais do **trial de 20 leads** não se aplicam ao Básico público ou cortesia. Os recursos finais de planos continuam pendentes de revisão de CRM-REV-02.

## 4. CORE08-D02 — Destino CRM por contrato, cortesia única por tenant (PROPOSTA)

**Proposta:** adotar a matriz lógica de **contrato elegível + Site/serviço/campanha + escolha autorizada de organização** como única fonte de benefício automático:

1. Na contratação de novo Site/landing, o Portal exige escolha expressa: **vincular a um tenant existente permitido** OU **criar tenant novo e isolado**, com direitos próprios; mesmo login pode administrar vários tenants.
2. Novo contrato de Site elegível pode sustentar **novo Básico cortesia mesmo com o mesmo CNPJ**. Uma organização mantém **no máximo uma cortesia efetiva**, independentemente do número de sites elegíveis vinculados.
3. Antes de ativar benefício ou formulários, verificar contrato oficial pago/elegível conforme condições, política de serviço/campanha **da oferta aceita**, titularidade, direitos de destino, consistência e idempotência. **Não escolher tenant automaticamente pelo CNPJ, e-mail ou “última conta”.**
4. Site estático sem destino CRM pode ser publicado apenas conforme IN-127-R06, com aceite expresso e **sem captação/formulários funcionais** até autorização do destino no backend.
5. Registrar versionamento de destino e origem `site_ref`, `form_ref`, campanha e contrato em cada recepção válida; na troca de vínculo, os leads futuros usam destino novo após efetivação, **sem reescrever leads anteriores ou redirecionar payload pendente de outro tenant**.
6. Alteração posterior de vínculo **via chamado**, gratuita somente na desvinculação e nova integração técnica pela Imagine. Histórico e migração de dados entre tenants são do cliente via export/import autoatendido IN-118, com permissões e aceite.
7. Se um site deixa de sustentar o tenant mas outro elegível permanece, manter cortesia daquela organização. Não alterar outra organização do mesmo CNPJ por evento não relacionado.

**Exemplos de direito (já decididos):** 1 Site elegível→1 organização = 1 cortesia; 2 Sites elegíveis→1 organização = 1 cortesia; 2 Sites elegíveis→2 organizações autorizadas = 2 cortesias (uma por tenant), mesmo CNPJ. Isso **não** significa trial renovado nem múltiplas quotas na mesma organização.

## 5. CORE08-D03 — Estados de cortesia versus pago; cancelamento comum e inadimplência (PROPOSTA)

**Proposta:** adotar como mapa conceitual de precedência **sem aprovar state machine física nem prazos inéditos**:

| Evento / situação | Regra funcional já aprovada e reconciliada | Cuidado de segurança/competência |
|---|---|---|
| Organização possui CRM **pago ativo** e Site é bloqueado/cancelado | Preservar assinatura e operação paga por contrato próprio, sem cobrança dupla e sem suspensão por dívida do Site | CRM é fonte da assinatura paga; Portal é fonte do contrato Site |
| Organização Básico cortesia possui outro Site ativo elegível | Manter uma cortesia e acesso elegível; sem transição do último site | Reavaliar **todos** os vínculos por tenant antes de bloqueio |
| Último Site sustentador é **suspenso por inadimplência**, após tolerância de 7 dias do Portal | Suspender acesso operacional à cortesia quando essa dependência for a única, mantendo dados e vias de regularização/portabilidade | **Suspensão temporária ≠ cancelamento formal**; não iniciar período de 30 dias por mera suspensão |
| Último Site elegível sofre **cancelamento comum** | Transição de **30 dias com operação normal** do Básico; avisos dia 0/15/25/29/30 | Contar desde cancelamento formal; não cobrar por silêncio |
| Último Site é **cancelado formalmente após suspensão por inadimplência** | Transição de **30 dias com operação restrita**; exportação/download e contratação de plano CRM pago permanecem possíveis | Não restaurar funções comuns só porque houve cancelamento; distinguir causa real e estado anterior |
| Transição se encerra sem vínculo elegível nem CRM pago | Suspender funções operacionais mantendo acesso seguro para contratação/portabilidade conforme IN-120 | Não apagar dados na suspensão, não gerar cobrança recorrente automaticamente |
| Novo Site elegível/restabelecimento legítimo | Reavaliar e reativar benefício quando contrato e tenant estiverem elegíveis, respeitando segurança e pendências | Não duplicar cortesia nem reabrir benefício perdido por pagamento tardio sem novo evento válido |
| Conta CRM é **cancelada formalmente** | Prazo geral de **120 dias após cancelamento do próprio CRM**; retenção/exclusão segundo lei e contratos | Cancelar Site ou suspender cortesia **não** inicia esses 120 dias |
| Administrador Geral Imagine concede gratuidade especial para qualquer plano | Regime administrativo próprio, independente de site/empresa em promoção; cláusulas e alçadas GRAT-P abertas | Evento do Site não revoga gratuidade administrativa |
| Trial chega ao dia 14 sem contratação/benefício elegível | Bloqueio operacional e retenção experimental de **60 dias**, conforme contrato do trial | Não aplicar 30 dias de cortesia nem 120 dias de cancelamento pago automaticamente |

### Notificações e revalidação

- Preservar avisos **dia 0, 15, 25, 29 e 30** por painel/e-mail na transição, com texto condizente com acesso normal ou restrito. A suspensão por dívida antes de cancelamento segue comunicação própria, não usa o cronograma de 30 dias indevidamente.
- Cada decisão de bloquear, reativar, converter ou cobrar precisa consultar **estado oficial recente** de todos os sites elegíveis, do plano pago, dos benefícios e da versão da política; integração técnica, ACK e n8n não substituem comprovação oficial do domínio dono.
- Preservar exportação/portabilidade autorizada por IN-118, respeitando legitimidade, restrições legais e limitação de recursos; sem expor outros tenants.
- Não gerar pagamento, NFS-e, upgrade, cancelamento ou benefício pela mera existência do evento técnico.
- Integrar eventos idempotentes entre Portal, Sites e CRM conforme CORE-P0-03 e INT P0; esquemas/erros/timers/exceções ainda D04..D10.

## 6. Cenários negativos de homologação futura — NÃO EXECUTADOS

| ID | Tentativa / caso | Critério de referência |
|---|---|---|
| CORE08-T01 | Segundo trial criado para o mesmo CNPJ com outra organização/site | Negar novo trial; não negar benefício legítimo do contrato Site |
| CORE08-T02 | Segundo Site elegível vinculado ao mesmo tenant cria dupla cortesia/quota | Manter uma cortesia e quotas únicas |
| CORE08-T03 | Dois contratos de sites distintos elegíveis e escolha expressa criam dois tenants para mesmo CNPJ | Permitir contas segregadas, uma cortesia em cada, sujeito a contratos e legitimidade |
| CORE08-T04 | Site suspenso por dívida, mas outro Site sustenta a mesma organização | Não bloquear cortesia nem CRM pago |
| CORE08-T05 | Site cancelado normalmente com último vínculo | Preservar 30 dias de operação normal e avisos, sem cobrança automática |
| CORE08-T06 | Site previamente suspenso por dívida é cancelado formalmente | Preservar 30 dias restritos, portabilidade e contratação paga; sem reinício antecipado |
| CORE08-T07 | Cancelamento do Site inicia retenção de 120 dias da conta CRM | Negar relógio incorreto; retenção inicia no cancelamento formal CRM |
| CORE08-T08 | Visitante tenta enviar lead por formulário de site estático sem CRM vinculado | Backend não recebe/aceita lead sem destino autorizado, conforme IN-127-R06 |
| CORE08-T09 | Mesmo CNPJ tem tenants A/B e evento de site A afeta assinatura/benefício B | Negar contaminação cross-tenant |
| CORE08-T10 | Portal envia notificação duplicada e CRM cria 2 tenants/benefícios | Idempotência; preservar evento e vínculo único válidos |
| CORE08-T11 | Benefício do Básico expira e sistema ativa cartão/PIX automaticamente | Negar cobrança sem contratação expressa |
| CORE08-T12 | Trial encerra, mas fluxo aplica quota de 20 leads ao Básico pago/cortesia | Negar; quotas por produto/estado corretos |
| CORE08-T13 | Mudança posterior de Site para tenant B redireciona leads antigos pendentes de A | Negar redirecionamento; novos envios só após confirmação do destino B |
| CORE08-T14 | Admin Imagine concede gratuidade de Empresarial e Site termina | Não revogar gratuidade administrativa por causa do Site |
| CORE08-T15 | Suspensão de cortesia impede exportação legítima e portabilidade | Preservar acesso restrito seguro e IN-118 |
| CORE08-T16 | Cliente paga site depois de cancelado mas não recontrata/reactiva oficialmente | Não recriar benefício por mero pagamento tardio; exigir evento contratual válido |
| CORE08-T17 | Acesso ao tenant B é concedido apenas porque login/CNPJ constam de tenant A | Negar sem membership e autorização própria |
| CORE08-T18 | Conta Básico cortesia tenta adicionar segundo usuário como “profissional agendável” | Negar quota de login 1/1, sem bloquear profissional legítimo de Agenda |

Roteiros são especificações para futuro CORE-P0-10/INT-HT; não há execução de testes ou mudança em produção.

## 7. Decisões propostas para ACEITE — NÃO APROVADAS

- **CORE08-D01 — PROPOSTA:** aceitar como base documental a **separação entre trial único por CNPJ e organizações Básico/cortesia por contrato de Site elegível**, sem impor “um único tenant por CNPJ” nem abrir novos trials por Site.
- **CORE08-D02 — PROPOSTA:** aceitar a **matriz de vínculo explícito Site/contrato/organização, no máximo uma cortesia por tenant e vários tenants com cortesia sob um mesmo CNPJ quando respaldados por contratos diferentes**, preservando escolha nova/existente e não migração automática.
- **CORE08-D03 — PROPOSTA:** aceitar como base lógica a **precedência do CRM pago, sustentação por outro Site, transição de 30 dias comum versus restrita por inadimplência, regras de exportação/reativação e retenções distintas (trial 60 dias, CRM 120 dias após cancelamento formal)**, sem alterar condições já aprovadas ou fixar state machine física.

## 8. CORE08-D04 a D10 — BLOQUEIOS QUE DEVEM CONTINUAR ABERTOS

| Código | Contrato / decisão ainda pendente | Dependência |
|---|---|---|
| **CORE08-D04** | Modelo físico de CNPJ, titularidade, conta, prova de contrato, assinatura, elegibilidade por serviço/campanha e estratégia de antifraude sem restringir direitos válidos | FIN/CON, Portal, CORE01-D04/D05 |
| **CORE08-D05** | Contratos de eventos Portal↔Sites↔CRM (ativação, escolha, vínculo, cancelamento, atraso, suspensão, recuperação), idempotência/versionamento, concorrência e fonte oficial | INT CT-01..11, CORE03-D04/D07/D08 |
| **CORE08-D06** | Máquina de estados, prioridade e precedência entre trial, plano pago, cortesia, gratuidade administrativa, transição, suspensão, cancelamento e reativação | FIN/CON, GRAT-P01..08, CRM-REV02-P02/P03 |
| **CORE08-D07** | Timers exatos/fuso, verificação de dia 0/15/25/29/30, evento formal e UX, notificações/retentativas, disputas, regularizações e erros de conciliação | IN-119/120/121, COM-08/09, Eventos/Datas |
| **CORE08-D08** | Regras de plano/assentos/recursos ao sair de trial, migrar Básico para pago, downgrade, gratuidade, quotas e armazenamento, sem cobrança implícita | CORE-P0-02/07, CRM-REV02-P01/P02, FIN |
| **CORE08-D09** | Portabilidade e retenção após cancelamento, execução idempotente de suspensão/retomada, operações em fila, proteção contra vazamento e auditoria por categoria | CORE-P0-03/05/06, IN-118/127, LGPD |
| **CORE08-D10** | Rastreabilidade completa de decisões originais, conflitos históricos IN-092/COM-09, contratos por operação, responsáveis, testes negativos e gates de fechamento | CRM-REV02-D01..12/P01..12, INT P0 G01..06, CORE-P0-10 |

**Preservados sem alteração:** CORE01-D04..D07, CORE02-D04..D10, CORE03-D04..D10, CORE04-D04..D10, CORE05-D04..D10, CORE06-D04..D10, CORE07-D04..D10; CORE-P0-09/10; INT P0-OWN/SEC/CON/ACC, IN-168 ARQ/SEG/AGT e G01–G06. Ninguém autorizou contratação, implementação ou fechamento global do planejamento.

## 9. Continuidade

Após eventual aprovação expressa de CORE08-D01, D02 e D03, registrar decisão distinta e seguir **CORE-P0-09 — cofre de segredos, credenciais e identidades técnicas**, sem implementar. Todos os preços/regras e a tabela completa de recursos serão revisados na etapa final comercial já planejada.

**Vedado:** Codex/código, testes reais, infraestrutura, contas, sites e leads em produção, cobrança, contratos, migração de dados, exclusão, despesas, uso efetivo de APIs, provisionamento ou ativação de serviços.
