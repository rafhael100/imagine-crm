# FUNC-P0-01/S01-R07 — EVID04: Matriz de competências, donos e aprovadores por contrato (v0.1)

**Data:** 06/11/2026. **Estado:** MAPA DOCUMENTAL DE COMPETÊNCIAS CANDIDATAS; **nenhum responsável nominal designado ou aceite formal comprovado**.  
**Base:** [EVID03 — matriz comercial](CRM-FUNC-P0-01-S01-R07-EVID03-matriz-eventos-comerciais-fonte-autoritativa-2026-11-06.md), [R06 — precedência](CRM-FUNC-P0-01-S01-R06-responsaveis-precedencia-pendencias-homologacao-proposta-v0.1.md), [R07 — lacunas](CRM-FUNC-P0-01-S01-R07-divergencias-prontidao-homologacao-proposta-v0.1.md) e fichas individuais R02-C01..C08.
**Limite:** documentos de planejamento não designam pessoas reais. Papel profissional sugerido não se torna owner/aprovador sem indicação e aceite expresso em decisão separada.

## 1. Modelo de atribuição para futura aprovação

**Proposta de convenção:** `R` redige/produz a especificação, `A` detém autoridade para aceite da regra em seu domínio, `C` deve ser consultado antes do aceite, `I` apenas acompanha. É **RACI candidato**, não nomeação. Nenhum owner candidato de regras IAM substitui o owner do fato financeiro; Segurança/LGPD possuem avaliações próprias quando aplicáveis.

| Contrato | R — elaboração candidata | A — aceite de domínio candidato, **a confirmar** | C — revisão indispensável candidata | Status |
|---|---|---|---|---|
| **C01 — sessões/MFA** | IAM, CORE04 | responsável oficial por IAM/segurança de autenticação | SEG-01/02, DBA quando sessão tocar RLS, privacidade | **SEM OWNER NOMEADO** |
| **C02 — principal/tenant/membership** | CORE01, CORE07 | responsável pela identidade e participação por organização | CORE02, SEG, administração tenant | **SEM OWNER NOMEADO** |
| **C03 — RLS/revogação** | CORE01/03 + DBA | responsável de arquitetura/DB para RLS **e** responsável CORE para controle na aplicação (aceites distintos) | SEG-01/02, IAM, qualidade | **SEM OWNER NOMEADO** |
| **C04 — campos mínimos** | Produto/UX S01, CORE01/02 | proprietário dos dados organização/membership | Segurança, privacidade/LGPD, QA | **SEM OWNER NOMEADO** |
| **C05 — fatos comerciais** | FIN/CON CRM + produto comercial Portal | FIN/CON para assinatura CRM; proprietário Portal para contratos de site (aceites distintos) | INT, CORE08, Jurídico/LGPD | **SEM OWNER NOMEADO** |
| **C06 — direitos/entitlements** | CORE02/08 + módulos | proprietário da política de autorização, e dono de produto/plano para a modalidade comercial | SEG, FIN/CON, LGPD conforme campo | **SEM OWNER NOMEADO** |
| **C07 — reconciliação/concorrência** | CORE03, INT, FIN/CON | dono do fato de negócio (CRM/Portal) **e** responsável técnico de integração (decisões distintas) | Segurança, auditoria, qualidade | **SEM OWNER NOMEADO** |
| **C08 — auditoria/retencão** | CORE06, auditoria, privacidade | responsáveis de auditoria/privacidade e Segurança conforme categoria | LGPD/Jurídico, donos de dados, arquitetura | **SEM OWNER NOMEADO** |

**Distinção crítica:** `A` na tabela significa apenas *alçada funcional candidata*. Não representa um aceite recebido. Em alguns contratos são necessários **múltiplos pareceres independentes** e não um único “A” capaz de dispensar os demais.

## 2. Matriz de responsabilidade do fato por domínio (para EVID03)

| Fato ou decisão | Autoridade empresarial candidata | Função da integração | Controle de uso de direitos | Divergência que deve ser resolvida |
|---|---|---|---|---|
| Autenticação e MFA de pessoa | IAM + SEG | Sem governar a identidade humana | CORE02/SEG por ação | Política de sessão e revogação |
| Tenant corrente/membership | CORE01/07 | Não determina organização pelo payload do webhook | CORE02 + RLS | Versão da membership e troca segura |
| Trial, contratação e pagamento CRM | FIN/CON CRM | Transporte, integridade/correlação técnica, **sem confirmar pagamento por ACK** | CORE08 + CORE02 sob política específica | Regras 14/60, 7/30/120 e autoridade PSP |
| Contrato, campanha e suspensão de site | Portal Imagine | Transporte comprovado com correlação | CRM valida contrato/site/org/direito da cortesia | Sete dias do Portal e causa do cancelamento |
| Elegibilidade cortesia e reativação | Portal atesta contrato; CRM calcula benefício da **conta correta** | Entrega idempotente e reconciliação | CORE08/02/SEG | Um benefício por CRM, múltiplos sites, manutenção de planos pagos |
| Auditoria e retenção | Auditoria/privacidade/LGPD por categoria | Entrega evidência mínima sem payload desnecessário | CORE06/SEG | Prazos 7/30/90/180 dias de logs vs 12 meses/5 anos de auditoria e 120 dias CRM |

**Duas fronteiras não negociáveis:** status comercial/benefício nunca prova vínculo humano; a UI S01 não inclui contratos, pagamentos, assinatura, campanhas ou status de outros usuários.

## 3. Registro proposto para confirmar designações (C01..C08 continuam pendentes)

Para cada contrato, abrir ficha de nomeação com:
- `contract_id`, `domain`, `organization_or_team`, `responsible_person_or_role`, `acceptance_authority`, `security_review_owner`, `privacy_review_owner`, `source_policy_version`, `decision_date`, `evidence_reference`, `conflict_of_interest_check`;
- assinatura/aceite expresso do responsável pela **área competente**, registro do parecer de SEG e parecer LGPD quando aplicável;
- declaração de que a escolha de um papel não concede automaticamente acesso a dados ou poderes para alterar políticas;
- tratamento quando a mesma pessoa acumular papéis: manter **pareceres logicamente separados**, sem tratar autoverificação como revisão independente quando SEG/LGPD exigirem separação;
- atualização das fichas C01..C08 **somente após** confirmações verificadas. A presente tabela não ocupa os campos com nomes inferidos.

## 4. Pendências concretas a resolver antes de qualquer homologação

| ID | O que falta | Decisão a obter | Gate afetado |
|---|---|---|---|
| **OWN-01** | pessoa/posição habilitada a aprovar IAM e sessões | nomeação/aceite formal, incluindo substituto e limites | C01/C02 e CORE04/SEG |
| **OWN-02** | autoridade de arquitetura/DBA e política RLS | responsáveis distintos por banco/aplicação e risco | C03 e CORE01/SEG |
| **OWN-03** | proprietário do nome de organização e vínculo | classificador e aprovador dos dois campos; LGPD | C04 e CORE02/LGPD |
| **OWN-04** | responsável FIN/CON do CRM e responsável comercial do Portal | quem prova o contrato, cobrança e prazo de cada produto | C05/C07 e FIN/CON/Portal |
| **OWN-05** | proprietário de autorização por recurso e plano | autoridade para matriz CORE02/08 sem ALLOW executável | C06 e CORE02/08 |
| **OWN-06** | owner de reconciliação e incidentes em integrações | competência sobre origem, retry, reversão, duplicidade | C07 e INT/G01..G06 |
| **OWN-07** | owner de privacidade, retenção e acesso à auditoria | revisão LGPD/SEG por categoria e base legal | C08 e CORE06/LGPD |
| **OWN-08** | autoridade QA para evidências futuras | aprovação de roteiro e autorização **posterior e distinta** de execução | C01..C08 e CORE10/DEV-GATE |

## 5. Encaminhamento e condições de NO-GO

- Fase seguinte útil: propor **designações formais de responsabilidade** por competência — sem precisar que existam oito pessoas distintas, desde que segregação de avaliações mandatórias seja observada — e reconciliar EVID03/OWN-04/OWN-06 com as normas FIN/CON e Portal antes de homologar quaisquer contratos.
- Não nomear automaticamente a direção, os autores do GitHub ou o Codex como aprovadores técnicos ou como responsáveis por LGPD.
- Se não houver responsável tecnicamente competente designado ou evidência de aceitação, **marcar como pendente**; não interpretar silêncio, “ok” genérico ou aprovação documental anterior como homologação.
- **Nenhum código, ALLOW, SQL, teste funcional, mudança de CI, VPS/produção, PR #2/#21, `main` ou branch DEV está autorizado por esta ficha.**

**Status final:** C01..C08 = PENDENTES; **67 CORE, SEG-01/02, AUT, ARQ/AGT, INT G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS**.