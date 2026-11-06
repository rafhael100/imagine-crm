# FUNC-P0-01/S01-R10-A01 — Negativas, estados e evidências do recorte C01–C04 (PROPOSTA v0.1)

Data: 06/11/2026. **Minuta documental — NÃO HOMOLOGADA, sem testes executados e sem ALLOW.**
Objetivo: especificar as condições de segurança e riscos de uma futura leitura humana limitada à própria organização e ao próprio vínculo ativo. Não cria endpoint, schema, API, configuração, role, SQL, migrations ou política de execução.

## 1. Proveniência

- [R01 — catálogo de campos, estados e segurança](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) blob 36a353caf695aa03aea3b0d6e0e13331a1fda5b3; diretrizes A ratificadas posteriormente apenas documentalmente.
- [S01 — casos S01-T01..T22 originais](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md) blob 78e1d021c0f1c5f66fc4b9f59092383a4d7b4; **casos futuros não executados**.
- [R10 — Matriz consolidada](CRM-FUNC-P0-01-S01-R10-matriz-fechamento-evidencias-C01-C08-v0.1-2026-11-06.md); [R09 — G01..G06 sob mesmo titular](CRM-DEC-FUNC-P0-01-S01-R09-G04-G06-acumulo-governanca-2026-11-06.md).
- Documentos CORE01, CORE02, CORE04, CORE06, CORE07/08, SEG e LGPD permanecem exigíveis; não importar status de branch DEV como prova de homologação.

## 2. Fronteira de dados C04

| Campo lógico | Exposição no recorte S01 | Fonte e condição exigidas |
|---|---|---|
| CAM01 — nome de apresentação da organização corrente | Candidato único a nome visível | Organização corrente identificada pelo servidor, fonte confiável, sanitação/escaping e finalidade validada |
| CAM02 — rótulo de próprio vínculo ATIVO | Candidato único a atributo de vínculo visível | Membership do próprio principal, no tenant atual, estado ativo e verificado por autoridade oficial |
| CAM03/CAM04 — identificadores técnicos | Somente internos se imprescindíveis e justificados | Não funcionam como prova de direito; não exibir na resposta V0 |
| CAM05..CAM10 — papel, estado comercial, CNPJ, usuários, pagamentos, leads, integrações, IA etc. | **Excluídos da saída V0** | Exigem outros contratos e direitos; não abrir por inferência |

## 3. Condição conceitual de leitura futura (não regra executável)

Uma leitura hipoteticamente elegível depende de: sessão humana válida, fatores MFA apropriados ao contexto aprovado, tenant corrente determinado pelo servidor, própria membership ativa verificada, direito específico recurso+ação+campos+escopo homologado, estado da organização compatível com contrato aprovado, saída CAM01+CAM02 mínima, isolamento Laravel e PostgreSQL RLS e falha segura. **Ainda não existe decisão que conceda essa leitura no runtime**.

## 4. Casos negativos e rastreamento a matriz futura já existente

| ID de análise (novo, documental) | Contratos | Ameaça / estado | Resposta futura esperada para estudo | Referência de casos existentes |
|---|---|---|---|---|
| A01-01 | C01 | Sessão ausente/adulterada/expirada ou principal não humano | Negar leitura, não divulgar tenant/organização; não trocar principal pela interface | S01-T03,T14; CORE04 |
| A01-02 | C01 | MFA insuficiente para papel e risco da operação | Negar até política de MFA aprovada e sessão revalidada; não fixar TTL/step-up sem CORE04 | S01-T08; CORE04-D08 |
| A01-03 | C02 | Tenant arbitrário em query/header/cookie/body | Não usar identificador do navegador como prova de autoridade; negar cross-tenant | S01-T04,T05; CORE01 |
| A01-04 | C02 | Mesmo usuário vinculado a A e B enquanto contexto é A | A leitura elegível não pode trazer B; S01 não cria seletor de organização | S01-T05; CORE01/07 |
| A01-05 | C02 | Membership convidada, pendente, suspensa, revogada, expirou ou incerta | Negar S01 sem enumerar organização nem terceiros; fluxo convite/recuperação separado | S01-T06,T07; CORE07 |
| A01-06 | C02/C03 | Membership revogada após renderização da tela, antes de nova consulta | Revalidar nova leitura; não confiar em cache/estado exibido anteriormente | S01-T07,T20; CORE01/04 |
| A01-07 | C03 | Grant inexistente, sem deny cadastrado | Deny-by-default específico à operação; não inventar deny administrativo global | S01-T11; CORE02-D05 |
| A01-08 | C03 | Grant de escopo A, sem direito B; deny específico no mesmo campo/ação | Negar B e interseção negada em A; não aplicar veto universal que não pertença ao mesmo escopo | S01-T09,T10; CORE02-D05 |
| A01-09 | C03 | Pool/conexão PostgreSQL com contexto de outro tenant, owner ou BYPASSRLS | Nunca produzir leitura por role/contexto inadequado; exigir avaliação da política física futura | S01-T16,T20; CORE01/SEG/RLS |
| A01-10 | C03 | Banco/Redis/observabilidade mandatória indisponível, contexto inconclusivo | Fail-closed para S01; não retornar conteúdo sensível do cache | S01-T17; CORE04/06/SEG |
| A01-11 | C03/C04 | Tentativa em API, exportação, edição, convites ou canal não coberto | S01 permanece leitura restrita; negar efeito de negócio e acesso por canal alternativo | S01-T18,T21; CORE02 |
| A01-12 | C04 | Atributo extra de papel, CNPJ, plano, colaborador, tenant ou outro vínculo | Omitir/negar atributos CAM03..CAM10; sem lista, dados de terceiros ou enriquecimento implícito | S01-T12,T13; CORE02/LGPD |
| A01-13 | C04 | Nome de organização malicioso (HTML/XSS) | Exibir CAM01 apenas após validação/escaping apropriados; não executar script e não logar valor bruto indevidamente | R01 CAM01; S01-T01 |
| A01-14 | C04/C08 | Erro de consulta e auditoria retornam SQL, ID de terceiro, token ou stack | Mensagem sanitizada e auditoria categorizada/minimizada; sem vazamento na UI | S01-T19; CORE06/LGPD |
| A01-15 | C03/C04/C06 | Estado comercial desconhecido, suspenso, trial expirou ou mudança da organização | Manter S01 sem regra positiva de acesso até owner decidir EST05..EST12; portabilidade e regularização usam fluxo próprio autorizado | S01-T15,T22; CORE08/FIN/CON |

Os novos códigos A01-xx **não são testes novos**: apenas agrupamentos de análise para os cenários originais S01-T01..T22. Não marcar passed, failed, executado ou revisão independente. O resultado esperado deve passar por owner competente e aprovação de versão antes de uso em execução.

## 5. Estados e decisões não tomadas

| Dimensão | Condição conhecida na fonte R01 | Situação do recorte | Pendência de aceite |
|---|---|---|---|
| Sessão | inválida/ausente/inconclusiva | negar sem enumeração | política de cookie/tokens, MFA, expiração, revogação, CORE04-D04..D10 |
| Membership | pendente, inativa, revogada ou de outro tenant | negar S01 | versão e autoridade do estado, convite/desligamento CORE01/07 |
| Organização técnica | ativa/inativa/suspensa/encerrada/desconhecida | não inferir acesso positivo | owner decidir estados EST05/EST12 e efeitos |
| Comercial | trial, cortesia, paga, inadimplente, cancelada, retenção | não confundir com identidade/tenant nem declarar ALLOW | FIN/CON/CORE08/LGPD decidir EST06..EST12 |
| Dados/Campos | CAM01/CAM02 candidatos, demais excluídos | sem projeção executável | classificação, finalidade, fonte, revisão de ameaças e contratos de autorização |

## 6. Fichas de evidência futuras por caso — NÃO PREENCHIDAS

Para cada S01-Txx futuro: owner do requisito, versão documental e decisão de aprovação, ambiente formalmente autorizado, commit/teste/código, configuração de tenant e roles, PostgreSQL efetivo, pré-condições, resultado esperado e real, logs sanitizados, falhas/reteste, risk owner, revisão SEG/DBA/LGPD/QA competente, parecer independente quando exigido. **Não existe prova executada nesta entrega**.

## 7. Prontidão e próximos atos de definição

- C01: fechar semântica de sessão/MFA por perfil; não presumir 30 min de admin geral para outros usuários.
- C02: fechar estados de vínculo, tenant atual e revogação, sem listar outros membros.
- C03: fechar contrato de autorização por recurso/ação/campo e RLS non-bypass, sem produzir SQL/ALLOW neste recorte.
- C04: confirmar fonte e classe de CAM01/CAM02, sanitação e resposta mínima, sem campos extras.
- R10-A01 pode embasar **revisão documental subsequente**, mas não permite implementação, testes ou fechamento de gates.

## 8. Barreiras preservadas

**C01..C08 = PENDENTES; todos os 67 CORE técnicos, SEG01/02, AUT, ARQ/AGT, INT G01..G06, FIN/CON, LGPD e DEV-GATE ABERTOS.** A nomeação de G01..G06 internamente a uma única pessoa não gera certificação de segurança ou revisão independente.
**Não** mexer na main, PRs #2/#21, branch DEV, SQL, código, ALLOW/GRANT, CI, testes, VPS ou produção; somente Markdown nesta branch isolada.