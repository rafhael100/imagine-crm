# FUNC-P0-01/S01-R10-A06 — Sequenciamento de contratos, evidências e gates abertos (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** CONSOLIDAÇÃO DOCUMENTAL PROPOSTA; NÃO APROVADA COMO NOVA POLÍTICA EXECUTÁVEL. Nenhum GO de código, SQL, ALLOW/GRANT, testes, merges ou deploy.

## 1. Motivação e precedência

A aprovação documental [R10-A05-D01..D04 A](CRM-DEC-FUNC-P0-01-S01-R10-A05-D01-D04-alternativas-A-2026-11-06.md) adotou uma matriz de evidências para C01–C04. O objetivo deste novo arquivo é **parar de repetir votos sobre princípios já aprovados** e apresentar uma lista prática das decisões/evidências ainda faltantes antes de pedidos específicos de execução.

Fontes: [R10 — matriz C01..C08](CRM-FUNC-P0-01-S01-R10-matriz-fechamento-evidencias-C01-C08-v0.1-2026-11-06.md), [R10-A05 — checklist C01..C04](CRM-FUNC-P0-01-S01-R10-A05-matriz-evidencias-tecnicas-contratos-C01-C04-proposta-v0.1-2026-11-06.md), [R07 — fichas originais](CRM-FUNC-P0-01-S01-R07-PAINEL-fichas-evidencias-pendencias-v0.1-2026-11-06.md), [R07-EVID03 — fatos comerciais](CRM-FUNC-P0-01-S01-R07-EVID03-matriz-eventos-comerciais-fonte-autoritativa-2026-11-06.md), [R07-EVID04 — autoridades por competência](CRM-FUNC-P0-01-S01-R07-EVID04-matriz-responsaveis-owner-aprovadores-2026-11-06.md).

## 2. Diagnóstico: o que já está decidido e o que realmente falta

- **Decidido somente documentalmente:** diretrizes R10-A02/A03/A04 e o checklist R10-A05; G01..G06 acumuladas pelo mesmo titular interno. Uma pessoa coordena as seis funções, mas isso não fabrica independência de uma revisão de segurança/privacidade/QA.
- **Não homologado:** C01..C08, 67 IDs técnicos CORE, SEG01/02, AUT, ARQ/AGT, INT G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE. Nenhum teste funcional S01-T01..T22 executado nem SQL/ALLOW aprovado.
- **Problema a resolver:** não é falta de um novo voto genérico sobre 'segurança'. É ausência de alguns contratos precisos, autoridade/fonte oficial por dado, revisão especializada quando aplicável, critérios concretos e evidência real no momento apropriado.

## 3. Bloqueadores e artefatos concretos por contrato

| Contrato | Área | Gates/dependências principais | Artefato de documentação faltante | Evidência de futura homologação | Limite atual |
|---|---|---|---|---|---|
| C01 | Autenticação, MFA, sessão | CORE04-D04..D10; SEG01/02 | Modelo de sessão por papel; MFA/step-up; reauth, revogação, recuperação e resposta negativa | Provar futuramente sessão válida/inválida e fator suficiente, com teste e revisão técnica | Não definir sessão física, cookie, TTL ou MFA executável nesta proposta |
| C02 | Principal, tenant e membership | CORE01-D04..D07; CORE07; SEG | Fonte IAM efetiva por pessoa e tenant; estados/versionamento de vínculo, troca e revogação | Provar futuramente isolamento A/B, revogação entre leituras e mesmo CNPJ com tenants distintos | Não derivar membership de contrato/pagamento/site, e não inventar campos físicos |
| C03 | Autorização e isolamento RLS | CORE01/02/03; SEG/DBA; CORE10 | Matriz ação-campo-escopo; role PostgreSQL sem BYPASSRLS; regras de conexão/pool, FKs e cache | Revisão técnica competente de threat model, depois testes SQL/RLS em ambiente autorizado | Não produzir SQL/ALLOW/GRANT nem afirmar que RLS está implementada |
| C04 | Campos CAM01/CAM02 e não enumeração | CORE02-D04/D06; CORE06; LGPD | Confirmação do owner/fonte CAM01 e própria membership para CAM02; classificação e escaping; erros neutros | Provar futuramente projeção só de dois campos, XSS e inexistência de canal alternativo | Não ampliar escopo S01 para plano, colaboradores, CNPJ, pagamentos ou lista |
| C05 | Fatos de negócio CRM/Portal | FIN/CON CRM, Portal, CORE08; INT | Catálogo autoritativo de fatos e relógios; diferenciação trial, assinatura, site, benefício; IN-092 ainda não recuperada | Contrato e evidência oficial por domínio, conciliação e revisão de negócios/jurídica aplicável | Não confundir ACK/webhook com pagamento, contrato formal ou acesso humano |
| C06 | Entitlements por operação | CORE02-D04..D10; CORE08; FIN/CON; LGPD | Matriz de recurso/ação/campo/estado sem grants por plano; acessos mínimos e portabilidade por fluxo próprio | Confirmação empresarial de estados EST05..EST12 e revisão de direitos | Não usar membership ativo ou site elegível como permissão universal |
| C07 | Eventos concorrentes e reconciliação | CORE03; INT/G01..G06; FIN/CON; SEG | Versão/idempotência/ordem e eficácia de eventos por fonte; replays, retomada e inconsistência | Casos futuros duplicados, atrasados, concorrentes, com owner da correção | Não disparar integrações reais nem transferir autoridade de domínio ao n8n |
| C08 | Auditoria, LGPD, retenção | CORE06-D04..D10; IN162; LGPD/SEG | Catálogo dado×finalidade×acesso×retensão×descarte e exceções legais | Parecer especializado quando necessário, registros sanitizados e evidência futura | Não misturar retenção de trial/CRM com prazo de logs, nem guardar tokens/payload excessivo |

## 4. Ordem proposta de trabalho, sem considerar GO técnico

| Ordem | Próximo trabalho | Produto documental verificável | Ainda dependente de |
|---|---|---|---|
| P0-01 | Fechar especificação documental de fronteira de identidade (C01+C02) | Registrar fonte por principal/tenant/membership, invariantes, política MFA por papel e mudança de estado | Ainda faltam CORE01/04/07, revisão SEG e modelo físico |
| P0-02 | Definir contrato de autorização e isolamento (C03) | Matriz ação×recurso×campo×escopo×principal e inventário de riscos de RLS/roles/pool/tenant, em texto | Ainda faltam SQL, papéis reais, revisão competente e testes futuros |
| P0-03 | Delimitar contrato de resposta mínima (C04) | CAM01/CAM02, fonte oficial/owner/retorno negativo e classificação LGPD de informações sensíveis | Ainda faltam catálogo final de campos, interface/DTO, revisão LGPD e provas futuras |
| P1-01 | Reconciliar fatos comerciais e direitos (C05+C06) | EVID03 EV01..15, Portal×CRM, estados de plano, suspensão, portabilidade e relógios distintos | Ainda faltam autoridade formal e decisões de FIN/CON/CORE08, IN092 original |
| P1-02 | Definir conciliação e auditoria (C07+C08) | Matriz duplicado/atrasado/corrompido/concorrente, classificação de logs e retenção por finalidade | Ainda faltam contratos INT, LGPD, revisão de risco e evidência real |
| GATE-FUTURO | Revisão formal para qualquer GO técnico | Registrar versão, riscos, revisor competente, autorização específica da etapa, critérios de testes e rollback quando pertinente | Não ocorre automaticamente pela aprovação documental R10-A05 |

**A ordem acima é prioridade de análise para a S01**, não fechamento dos CORE, nem liberação de uma branch de desenvolvimento; qualquer fast-track técnico separado precisa de autorização expressa, limites e gates próprios.

## 5. Evidência mínima futura e responsáveis

1. **Versões e precedência:** path/branch/SHA da fonte e documento de decisão vigente; divergências abertas descritas, inclusive a referência IN-092 ausente como arquivo-fonte independente.
2. **Fonte do fato e alçada:** CRM/IAM é fonte de identidade/membership; FIN/CON do CRM de assinatura CRM; Portal de contrato de site; CORE02 controla autorização por operação; INT transporta dados, não determina o direito por ACK.
3. **Disciplina técnica:** separar quem escreve especificação/código de quem revisa SEG/DBA/LGPD/QA quando exigido; um único titular G01..G06 não emite segunda avaliação independente por atuar em dois papéis.
4. **Caso e risco:** mapear a casos originais S01-T01..T22 e CORE01-T01..T10, com expectativa positiva/negativa, e não inventar uma bateria de testes duplicados para parecer evolução.
5. **Só depois de autorização específica:** ambiente, branch/código/SQL, versões, execução de casos, evidência real, incidentes e aceite por alçada competente; sem status 'passed' enquanto não executado.

## 6. Proposta de próxima etapa concreta, sem aprovação automática

Concentrar o próximo trabalho em **C03 — matriz de autorização e isolamento**, pois é o núcleo da proteção entre empresas. A saída será exclusivamente **inventário documental de papéis, pontos de bypass, escopos, transações e cenários adversos**, sem SQL nem alteração de aplicação. Em paralelo, manter a especificação de sessão e tenant C01/C02 e a classificação C04 como dependências que precisam estar completas para a execução real.

**Não se propõem novos votos sobre as mesmas diretrizes nesta etapa.** Uma decisão futura deverá apreciar lacunas específicas, ou autorizar uma tarefa técnica *expressamente delimitada*, se os pré-requisitos exigidos tiverem sido atendidos.

## 7. Preservações

**C01..C08 PENDENTES; 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE ABERTOS.** Nenhuma evidência nova de teste real nem parecer independente foi produzida pela publicação.

Sem mudar `main`, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, código, SQL, schema, `ALLOW`/`GRANT`, CI, testes, VPS, produção, cobrança ou dados de clientes. Apenas arquivo `.md` em branch documental isolada.