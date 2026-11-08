# CRM Imagine — FUNC-P0-01 / S01-R02 — Contratos de exibição, estados e evidências (proposta v0.1)

**Data:** 08/11/2026. **Estado:** **PROPOSTA DOCUMENTAL NÃO APROVADA**; nenhuma implementação, alteração de política, execução de homologação ou merge autorizados.
**Precedência:** [R01-D01..D05 ratificadas somente documentalmente](CRM-FUNC-P0-01-S01-R01-DEC-aprovacao-documental-D01-D05-2026-11-08.md), [S01-C01..C06 e S01-T01..T22 (DEV)](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md), [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22).
**Objetivo:** tornar a primeira autoconsulta segura um escopo testável **no papel**, sem inferir identidade, concessão de direitos, estados comerciais ou endpoints.

## 1. Fronteiras do R02

A visão inicial candidata exibe somente:
- **CAM-01:** `organization.display_name` — nome exibível da **organização corrente resolvida server-side**, mediante fonte oficial e política específica futuramente homologadas.
- **CAM-02:** mensagem de apresentação **“Seu vínculo: Ativo”** — estado derivado da **membership do próprio humano**, ativa e atualmente comprovada na organização corrente, nunca do cliente ou cache.
- **CAM-03..CAM-10:** excluídos da apresentação V0: IDs internos, papel/permissões, plano, faturamento, CNPJ, telefone, outros usuários, leads, arquivos, IA e integrações.

**Ainda não aprovados:** origem física e esquema de dados, tamanho e normalização de CAM-01, classificação LGPD concreta, base/finalidade do tratamento, comportamento de cache, wire DTO/JSON, código HTTP, roteamento, sessão, algoritmo de autorização, regras de estados comerciais e quaisquer permissões positivas.

## 2. Modelo documental de estados da apresentação

| Estado proposto | Condição conceitual | Apresentação V0 candidata | Regra de proteção |
| --- | --- | --- | --- |
| **UI-01 — Inicial/carregando** | Consulta ainda sem evidência autorizada | Indicação neutra “Verificando acesso…”; nenhum nome, ID ou dado pré-renderizado | Não reutilizar dados de sessão/tenant anterior. |
| **UI-02 — Resumo validado (futuro)** | Fontes oficiais, sessão, membership, tenant, estado, política por recurso/campo e demais controles **todos** demonstrados | Somente CAM-01 e CAM-02 após futura autorização funcional específica | É **exemplo de comportamento futuro**, não um `ALLOW` atual. |
| **UI-03 — Indisponível/negado** | Sessão, membership, contexto, autorização, estado ou sistema insuficiente, inválido ou desconhecido | Mensagem genérica “Não foi possível apresentar estas informações.”, sem nome/ID/status de terceiros | Fail-closed; não vazar se a organização existe nem qual controle negou. |
| **UI-04 — Revalidação necessária** | Dados exibidos ficaram potencialmente obsoletos após troca de contexto, revogação ou expiração | Retirar imediatamente a apresentação local, antes de nova consulta; mensagem neutra | Não prometer reversão atômica de pixels já vistos; revalidar antes de nova operação. |
| **UI-05 — Demonstração sem backend** | Protótipo **separado**, fictício e rotulado, ainda sujeito a autorização própria | “Empresa Exemplo” + “Seu vínculo: Ativo” com aviso **Dados fictícios** | Não chamar API de produção, não usar dados pessoais reais, não insinuar autenticação/entitlement. |

O comportamento técnico desses estados **não está aprovado** por esta proposta. UI-02 não será alcançável enquanto o autorizador permanecer deny-only. Uma apresentação em protótipo **não representa acesso real**.

## 3. Matriz de fonte, owner e classificação pendentes

| Objeto | Owner funcional a designar | Owner técnico/revisão a designar | Dado oficial / decisão exigida |
| --- | --- | --- | --- |
| CAM-01 | Organizações / Produto | Backend IAM; Privacidade/LGPD | Fonte única, limites de comprimento, escaping, classificação quando nome identificar pessoa física, minimização. |
| CAM-02 | IAM / CORE07 | Segurança / CORE04, DBA/SEG | Estado atual da membership própria, identidade, tenant, revogação e auditoria da decisão. |
| Sessão e MFA | IAM / CORE04 | Segurança competente | Mecanismo de verificação, risco, reautenticação, expiração, step-up e revogação. |
| Organização/assinatura | CORE08 / Produto | FIN/CON/Privacidade | Separar estado técnico vs comercial, direitos mínimos legítimos, retenção e portabilidade; não decidir bloqueios por suposição. |
| PostgreSQL/RLS | CORE01 / Arquitetura | DBA/SEG independente quando exigido | Runtime sem owner/BYPASSRLS, ACL, FKs tenant-bound, transação/pool, limpeza de contexto e política de migration segura. |
| Negativas/logs | CORE06 / QA | Segurança / LGPD | Linguagem neutra, logs mínimos sanitizados, retenção legal e acesso controlado à auditoria. |

**Nenhuma pessoa real foi designada.** Atribuir o nome de quem implementa à função de revisor independente não satisfaz segregação.

## 4. Critérios de segurança e aceitação futura

1. **Sessão e sujeito:** o servidor deve provar identidade humana e sessão vigente; cookies, headers, `tenant_id`, CNPJ e `app.tenant_id` **não são autoridade**. Não aceitar agente, staff, suporte, IA ou n8n como `read_self`.
2. **Isolamento:** o contexto atual deve vincular sujeito–membership ativa–organização **na mesma transação/fonte aprovada**, sem herança de outra empresa e com revalidação entre requests; tratamento consistente de conexões reutilizadas.
3. **Autorização:** `DenyByDefaultAuthorizer` continua único; CORE02-D05 modelo B/P1+P2 e GOV01/M2 são diretrizes, **não política executável**; GOV02 ainda pendente.
4. **Estado comercial:** não inferir que plano pago, trial, cortesia ou suspensão automaticamente confere ou suprime qualquer leitura. EST-05..12 seguem sem resultado final, com fluxo legítimo de direitos separado.
5. **Campos e cache:** somente CAM-01/02 poderão ser candidatos futuros de saída; sanitização de string/XSS e não compartilhamento entre identidades/tenants; avaliar `Cache-Control: no-store` com Segurança antes de estabelecer política HTTP.
6. **Erro/auditoria:** falhas de PostgreSQL, Redis, MFA, auditoria mandatória, RLS ou fonte de estado => negação genérica; sem IDs de terceiros, SQL, segredos, stack trace, payload ou enumeração em tela/log.
7. **Condições de teste:** toda evidência futura precisará apontar para autorização de execução específica, Git SHA, ambiente sintético, owner, resultado esperado/real, negativa positiva correspondente, controles de banco, logs sanitizados e parecer/aceite adequado.
8. **Proteção de infraestrutura:** [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27) continua aberta; o ensaio [PR #30](https://github.com/rafhael100/imagine-crm/pull/30) prova PITR em laboratório descartável, não barreiras de banco persistente, produção ou RPO/RTO.

### Rastreabilidade S01-Cxx ↔ testes S01-Txx, todos futuros e NÃO executados

| Contrato | Evidências propostas |
| --- | --- |
| **S01-C01 — Entrada confiável** | T01..T08, T13..T14, T20..T21 |
| **S01-C02 — Resposta mínima** | T01, T02, T12, T13, T19 |
| **S01-C03 — Legitimidade e política** | T03..T11, T14..T17, T21 |
| **S01-C04 — Estados/revogação** | T06, T07, T15, T17, T20, T22 |
| **S01-C05 — RLS, apresentação e privacidade** | T04, T05, T12, T16, T19..T21 |
| **S01-C06 — Falhas/observabilidade** | T03, T14, T17, T19, T20 |

Os 22 cenários S01-T01..T22 **não são declarados executados/homologados**; inclusive os dois casos positivos (T01/T02) exigem aprovação futura de concessões restritas e de seus gates.

## 5. Protótipo visual: proposta de escopo separado

Uma futura demonstração navegável **sem backend** pode auxiliar produto/UX, mas deve ser expressamente autorizada como pacote à parte e observar:
- Navegação sintética de baixo risco, sem formulário que colete dados reais e sem pedidos de login, senha, CNPJ ou MFA.
- Tela de autoconsulta com “Empresa Exemplo” e “Seu vínculo: Ativo”, sempre rotulada **Protótipo — dados fictícios**.
- Estados UI-01/03/04 exibíveis com mock local, mas **não** simular autorização real, claims de produção nem controle de segurança.
- Fora de `main` e de VPS, sem tocar interfaces de clientes/Portal; nova branch, revisão e PR DRAFT requerem autorização específica antes de criação.

## 6. Próximas deliberações propostas — R02-D01..D05 (NÃO APROVADAS)

| ID | Proposta para futura decisão |
| --- | --- |
| **R02-D01** | Adotar estados UI-01..UI-04 como contrato documental de experiência, sem implementar nem transformar a tela em controle de acesso. |
| **R02-D02** | Exigir owner, fonte oficial, classificação LGPD e política de exibição de CAM-01/02 antes do primeiro código funcional. |
| **R02-D03** | Manter EST-05..12 sem liberação ou bloqueio comercial final até parecer dos owners CORE08/FIN/CON/LGPD. |
| **R02-D04** | Aprovar somente a rastreabilidade **documental** S01-C01..C06 ↔ T01..T22 e as evidências esperadas, sem autorizar execução. |
| **R02-D05** | Preparar em etapa separada uma **proposta** de protótipo offline navegável, com dados fictícios; código e PR dependem de GO posterior específico. |

**NO-GO vigente:** sem `ALLOW`, autenticação/rotas reais, código funcional, novos testes de homologação, migration/roles, merge dos PRs #2/#21/#23..#30, alteração de DEV/`main`, VPS, produção ou dados reais. Os 67 CORE, SEG/AUT/ARQ/INT/FIN/CON/LGPD/DEV-GATE/G01..G06 seguem abertos. Este arquivo é **especificação para revisão**, não decisão da Direção.
