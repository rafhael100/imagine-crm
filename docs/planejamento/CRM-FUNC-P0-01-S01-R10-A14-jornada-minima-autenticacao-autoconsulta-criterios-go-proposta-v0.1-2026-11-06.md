# CRM Imagine — FUNC-P0-01/S01-R10-A14 — Primeira jornada funcional: requisitos de autenticação, autoconsulta e condições para GO (PROPOSTA v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Status:** PROPOSTA DOCUMENTAL PRODUZIDA COM AUTORIZAÇÃO DA DIREÇÃO PARA CONSOLIDAR A PRÓXIMA ETAPA; **NENHUMA NOVA POLÍTICA, TESTE, ALLOW OU IMPLEMENTAÇÃO FUNCIONAL FOI APROVADA**.

## 1. Motivo e precedência

Após a aprovação de [DEC-MVP-P0-01](CRM-DEC-MVP-P0-01-prioridades-crm-funcional-e-progresso-2026-11-06.md) e a sugestão de **consolidar requisitos técnicos e critérios de liberação específicos da primeira jornada**, a direção respondeu **'ok aprovado'**. Isso aprova prosseguir **com o documento**, não o código ou a aprovação tácita de campos/estados/contratos físicos.

**Fontes:** [S01 — contratos e 22 cenários](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md) blob `78e1d021c0f1c5f66fc4b9f59092383a4d7b4`; [S01-R01 — 10 campos e 14 estados, ainda PROPOSTA](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`; [ratificação S01-D01..D08](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-DEC-ratificacao-diretrizes-preliminares-D01-D08-2026-11-06.md) blob `9cd1ae39c3060e9b2762f25f14226decb3f0e2ff`; [baseline de lacunas C03 A10](CRM-FUNC-P0-01-S01-R10-A10-matriz-lacunas-estaticas-por-arquivo-C03-2026-11-06.md); [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) (segurança migrations) ainda DRAFT.

**Precedência obrigatória:** S01-D01..D03 e propósitos D04..D08 possuem aceite **somente documental preliminar**; a proposta S01-R01, incluindo R01-D01..D05, **NÃO foi ratificada em si**. A14 não converte a proposta em verdade aprovada. Mecanismos positivos de permissão ainda NO-GO.

## 2. Resultado mínimo de negócio — uma única jornada

**Objetivo futuro:** após acesso humano legitimamente autenticado e autorizado, visualizar no **CRM** unicamente (a) o nome de apresentação da sua organização corrente comprovada (CAM-01) e (b) a informação de que **seu próprio vínculo está ativo** (CAM-02).

**Fora do recorte:** login novo, endpoint público e SSO Portal já habilitados; seletor de empresas; gestão de colaboradores/convites; permissões editáveis; papéis visíveis; IDs/CNPJ/e-mails; status de plano e cobrança; leads, contatos, funis, tarefas, arquivos, exportação, IA, n8n, webhooks e qualquer escrita. Um fluxo de autenticação humano real é **dependência anterior** desta leitura, não produto instalado.

**Falha segura:** ausência/expiração da sessão, vínculo ausente/revogado, tenant inconclusivo, policy sem grant efetivo, estado comercial sem regra definida, falha DB/Redis ou contexto contaminado => não mostrar os campos; eventual atendimento a direitos legais ocorre em fluxo apropriado a decidir, não por concessão especulativa de S01.

## 3. Requisitos técnicos e rastreabilidade a cenários (ainda não implementados)

| ID | Eixo | Requisito proposto | Dependências normativas | Lacuna para GO | Casos futuros |
|---|---|---|---|---|---|
| J01 | Identidade humana/Sessão | Conta humana autenticada no backend e sessão vigente; não aceitar principal criado por payload/query/token de serviço. | CORE04, CORE01, CORE07, SEG | Sem política de autenticação/MFA de produção homologada | S01-T03/T08/T14/T17 |
| J02 | Vínculo atual de organização | Somente membership ativa da própria identidade na organização corrente comprovada; nenhuma troca de tenant por query/header. | CORE01/07, S01 C01-C03 | Método final de seleção e prova de membership pendentes | S01-T02/T04/T05/T06/T07/T13 |
| J03 | Autorização restrita `read_self` | Modelo recurso/ação/campo/escopo específico, grant válido e precedência de deny delimitada; sem ALLOW genérico. | CORE02-D04..D10, SEG | Algoritmo e grants físicos pendentes; GOV02 não aprovado | S01-T09/T10/T11/T12/T18/T21 |
| J04 | Fonte dos dois campos | CAM-01: nome de exibição da organização corrente; CAM-02: rótulo derivado do próprio vínculo ativo. IDs internos não expostos. | Owner Organizações/CORE07/LGPD | Fonte física de display_name e classificação LGPD não homologadas | S01-T01/T02/T12 |
| J05 | Estados de organização e contrato | Sessão/membership/organização/contrato são autoridades distintas; estados comerciais EST-05..12 ficam em aberto, sem conceder nem bloquear direitos por suposição. | CORE08, FIN/CON, Produto, LGPD | Matriz de acesso por trial/cortesia/pago/suspenso/cancelado pendente | S01-T15/T22 |
| J06 | PostgreSQL RLS e integridade | A política Laravel é condição necessária; RLS com papel sem bypass, tenant transacional, conexões limpando contexto e FKs coerentes. | CORE01/03, SEG/DBA | Só baseline experimental de membership em laboratório; PR #23 DRAFT | S01-T04/T16/T20 |
| J07 | Resposta/UX mínima | Exibir nome de organização + `Seu vínculo: Ativo`; erro/negação sem enumeração ou dados de terceiro; escapar apresentação e não expor campos CAM-03..CAM-10. | CORE02, CORE06/LGPD, Produto | DTO/canal/proteção de cache e UX ainda não definidos/ratificados | S01-T12/T13/T19/T21 |
| J08 | Revalidação e falhas | Revalidar sessão/vínculo/tenant/policy em cada leitura protegida; revogação/timeout/indisponibilidade => fail-closed, sem cache de outro tenant. | CORE04/06/07, SEG | TTL, invalidadores/cache e limites consistência não homologados | S01-T07/T16/T17/T20 |
| J09 | Auditoria/privacidade | Negativas e acessos com evidência mínima sanitizada, finalidade, retenção e acesso restrito; sem PII/SQL/tokens nos logs. | CORE06, LGPD/SEG | Contrato de eventos, retenção e base/finalidade ainda pendentes | S01-T19 |
| J10 | Recorte do canal | Somente futuro fluxo interno CRM; sem login público novo ou API de negócio até GO técnico, sem Site/Portal SSO presumido. | Produto, CORE04, INT/ARQ | Rotas e contratos de autenticação ainda não aprovados para funcionalidade | S01-T14/T18/T21 |

## 4. Portões de saída da fase documental — nenhum fechado por este documento

| Gate de preparo específico | Área | O que deve existir e ser aprovado com evidência antes de programar a jornada funcional | Estado |
|---|---|---|---|
| G-S01-01 | Escopo e campos | Confirmar em decisão própria R01-D01..D05 (A ou ajuste) e owners dos atributos CAM01/02, excluindo CAM03..10. | Em proposta; não homologado |
| G-S01-02 | Sessão e MFA | Contrato IAM/CORE04 de principal humano, sessão, MFA por risco, reautenticação, expiração e revogação. | Pendente |
| G-S01-03 | Permissões | Recurso/ação/campo/escopo, grants/denies por tenant, precedência CORE02-D05 B/M2 em implementação permitida e GOV02 resolvido quando aplicável. | Pendente; sem ALLOW |
| G-S01-04 | Entitlement/organização | Matriz EST-05..12 de acesso ou fluxo mínimo legítimo, owner CORE08/FIN/CON e conflitos com suspensão/portabilidade. | Pendente; não inferir status |
| G-S01-05 | Persistência/RLS | Modelo físico tenant-bound, role não-owner/não-BYPASSRLS, conexão/pool, cache, revogação e integridade, parecer SEG/DBA competente. | Pendente; C03 aberto |
| G-S01-06 | Privacidade/Auditoria | Classificação CAM01/02, finalidade legítima, logs, retenção, minimização, segurança de canal e erros. | Pendente |
| G-S01-07 | QA e alçadas | Matriz de 22 cenários com responsável, SHA, resultado real e revisor; distinguir CI sintético prévio de testes funcionais S01 ainda não executados. | Pendente |
| G-S01-08 | GO de engenharia | Decisão específica: branch filha de DEV, arquivos permitidos, testes só sintéticos, PR DRAFT, proibição de merge e VPS; autorização separada para eventual leitura positiva. | Não autorizado por este A14 |

**NÃO confundir estes identificadores G-S01 com os gates globais CORE/INT/SEG.** São checklist local para preparar futura decisão; não substituem nem reclassificam os 67 CORE e demais bloqueios.

## 5. Sequência de execução PROPOSTA, ainda sem GO

1. Reconciliação documental **R01-D01..D05** e campos físicos CAM01/02 (Organizações/CORE07, CORE02/LGPD). Escolher estados técnicos e comerciais ainda ausentes por autoridade competente, não por hipótese.
2. Consolidar contratos seguros de sessão/identidade/membership e escopo `read_self` **sem implementar ALLOW**; referenciar CORE01/02/04/07 e revisão SEG/DBA de RLS.
3. Preparar um recorte futuro de **engenharia somente mediante GO expresso**, separando as possíveis etapas: (E1) contrato de IAM/sessão e testes negativos, sem rotas públicas ou permissões comerciais; (E2) habilitação estritamente escopada e homologada de consulta própria, se autorizada após dependências; nunca implementação implícita por esta especificação.
4. Implementar apenas após decisão o fluxo mínimo CRM; usar branch filha da DEV, PR DRAFT, fixtures sintéticas, revisão, logs e evidências sem tocar `main`, VPS, PRs #2/#21/#23, nem o deploy. O status do PR #23 sobre migrations é independente: CI verde não autoriza merge ou `ALLOW` S01.
5. Após verdadeira jornada operacional validada e aceite formal, avançar no [MVP](CRM-DEC-MVP-P0-01-prioridades-crm-funcional-e-progresso-2026-11-06.md) para leads/contatos → oportunidades/funil → atividades → ferramentas de dados e QA; não chamar o CRM de pronto antes do primeiro fluxo de negócio.

## 6. Alternativas para deliberação posterior — NÃO APROVADAS

| Código | Tema | Alternativa A, recomendada documentalmente | Alternativa B, não recomendada |
|---|---|---|---|
| A14-D01 | Primeira tela | Alternativa A (recomendada): limitar a tela interna aos campos CAM01/02 do próprio tenant/vínculo. Sem seleção de empresa, outros membros, dados comerciais ou edição. | B: ampliar campos e operações antes de autorização por campo |
| A14-D02 | Segurança antes da leitura positiva | A: prever um `read_self` estrito, negando até homologação de sessão, membership, escopo de campo, RLS e regra de estado. Nenhum `ALLOW` no presente estágio. | B: abrir `ALLOW` genérico para demonstrar a tela |
| A14-D03 | Estados contratuais | A: manter EST-05..12 e acesso mínimo legítimo pendentes de CORE08/FIN/CON/LGPD; não presumir que pagamento equivale à autenticação. | B: permitir/impedir globalmente por status simplificado |
| A14-D04 | Evidências e rollout | A: vincular todos os 22 casos S01-T01..T22 aos oito checkpoints e exigir aceite formal antes de código funcional e antes de eventual produção. | B: considerar CI experimental como homologação |
| A14-D05 | Faseamento funcional | A: somente após GO para jornada de autoconsulta, planejar leads/contatos, funil e atividades em pacotes subsequentes; percentuais recalculados por jornadas comprovadas. | B: iniciar todo CRM, cobrança/IA/integrações em paralelo sem gates |

**Aprovar A14-D01..D05 futuramente só como diretrizes de planejamento não basta para iniciar código.** Solicitação de GO deverá indicar SHA, branch, arquivos/módulos autorizados, testes, escopo `ALLOW` se aplicável, pareceres competentes e proibições explícitas.

## 7. Linha de base de progresso e evidências

Referência gerencial: **aproximadamente 10%** (faixa indicativa **5–15%**) de código total do CRM estimados anteriormente. Este A14 é **somente documentação**; não aumenta o progresso do código. Dev F1-02..10 possui fundação Laravel/PostgreSQL/identidade/tenancy/CI sintéticos, ainda deny-only. Funcionalidades comerciais e autoconsulta real não têm evidências de entrega. Não contar número de arquivos/testes/doc como percentual real de produto sem backlog e critério fechado de conclusão.

## 8. Proteções e status GitHub

`main` e branch `dev/fast-track-crm-2026-11-04` intactas; PRs #2/#21/#23 abertos e em DRAFT, sem merge. Nenhuma alteração SQL, `ALLOW`/`GRANT`, testes, PHP, CI, VPS, rota, conta real ou produção. C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates continuam ABERTOS. G01..G06 internos acumulados não são parecer SEG/DBA independente.

**Próxima aprovação útil:** aceitar ou ajustar especificamente as alternativas A14-D01..D05 **somente documentais**; em seguida, concentrar o trabalho nos bloqueios reais de IAM, política CORE02, RLS e estados comerciais antes da primeira autorização limitada de código funcional.