# FUNC-P0-01/S01-R10-A02 — Contratos conceituais e oito decisões C01–C04 (PROPOSTA v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Estado:** PROPOSTA DOCUMENTAL NÃO APROVADA, NÃO EXECUTÁVEL. **Sem ALLOW, SQL, endpoint, código ou testes.**

## 1. Objetivo e precedência

Registrar detalhes ainda não homologados sobre sessão/MFA (C01), identidade e membership (C02), autorização Laravel e RLS PostgreSQL (C03), dois campos de saída (C04) para S01 — **autoconsulta da própria organização e do próprio vínculo ativo**.

Fontes documentais verificadas:

- [S01-R01 original na DEV](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md), blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3` — diretrizes preliminares A de R01-D01..D05 ratificadas posteriormente em ato separado, sem política executável.
- [S01-T01..T22 originais](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md), blob `78e1d021c0f1c5f66fc4b9f59092383a4d7b4`, **não executados**.
- [CORE-P0-04 MFA/sessões](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md), blob `adf09f4e790aefd8679735e62111489f3a74db68` — D01..D03 já base documental, D04..D10 abertas.
- [CORE-P0-01 identidade/tenant](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md), blob `ec7a03e38fedc3514eaa2c91c6def80d0ac6dba1`; [CORE-P0-02 autorização](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md), blob `6222b2676f5ae59282c36ee607a3f9e62dd2ead6` — bases conceituais, pendências técnicas mantidas.
- [R10 — matriz de evidências](CRM-FUNC-P0-01-S01-R10-matriz-fechamento-evidencias-C01-C08-v0.1-2026-11-06.md) e [R10-A01 — negativas](CRM-FUNC-P0-01-S01-R10-A01-matriz-negativas-C01-C04-proposta-v0.1-2026-11-06.md), com 15 agrupamentos documentais mapeados a testes S01 já existentes.
- [R09 — seis funções internas acumuladas pelo mesmo titular](CRM-DEC-FUNC-P0-01-S01-R09-G04-G06-acumulo-governanca-2026-11-06.md) — **não** prova revisão independente G04/G05/G06.

**Regra de precedência:** A02 esclarece pontos para discussão, não muda CORE04-D01/D02/D03, R01-D01..D05, aprovações anteriores de CORE01/02, os direitos de portabilidade/regularização ou condições comerciais. Em conflito ou lacuna, marcar PENDENTE e negar concessão implícita.

## 2. Visão simples para validação do escopo

Exemplo **somente ilustrativo, não tela ou API implementada**:

> Organização: Empresa Exemplo
> Seu vínculo: Ativo

Não mostra CNPJ, papel, status comercial, plano, valor, outros usuários ou tenants. Uma assinatura paga, trial válido, um site elegível ou nome cadastrado não substituem autenticação nem autorização.

## 3. Oito decisões para avaliação da direção — A02-D01..D08

Todas as opções abaixo são **PROPOSTAS**, e aprovação eventual de A significará somente **diretriz documental preliminar**, sem completar CORE/SEG/AUT nem habilitar leitura.

| Código | Contrato | Tema | **Alternativa A recomendada** | Alternativa B |
|---|---|---|---|---|
| **A02-D01** | C01 | Validação de sessão na leitura | Exigir sessão de pessoa humana vigente comprovada pelo backend na operação S01; revalidar contexto tenant/membership sem confiar em identidade da UI ou token arbitrário. Falha, timeout ou prova inconclusiva nega somente S01. | Admitir identidade ou tenant apenas pelo navegador/estado de tela |
| **A02-D02** | C01 | MFA e step-up na S01 | Não exigir novo desafio MFA em toda leitura S01 se a sessão já cumpre a política de segurança aplicável ao principal, organização e papel; exigir reautenticação/step-up quando a política futura indicar insuficiência ou risco. Admins com MFA obrigatório, funcionários segundo política do tenant. | Dispensar MFA de administradores ou impor um desafio novo em toda consulta independentemente da política |
| **A02-D03** | C02 | Fonte autorizada do vínculo | Fonte oficial de organização e membership no domínio CRM/IAM, consultada com principal humano autenticado e tenant corrente validado server-side; nunca derivar elegibilidade de CNPJ, pagamento de site, webhook, agente ou texto do cliente. | Aceitar tenant de parâmetro ou CNPJ comum como posse/autorização |
| **A02-D04** | C02 | Efeito de revogação e múltiplos tenants | Vínculo diferente de ativo ou próprio nega S01; revogação em A retira a operação em A sem eliminar automaticamente outros vínculos legítimos em B. Revalidar antes de nova leitura, sem dar continuidade pelo cache. | Permitir consulta por membership pendente/revogada ou vincular sessão a tenant anterior por fallback |
| **A02-D05** | C03 | Autorização como regra por operação | Tratar `read_self` apenas como nome semântico candidato para uma operação com todos os fatores: pessoa, tenant, membership, recurso, ação, campos e escopo; negar por padrão sem autorização positiva específica. Uma tela não concede o direito. | Autorizar qualquer usuário com sessão/membership ou plano pago sem política por ação |
| **A02-D06** | C03 | Isolamento na aplicação e no PostgreSQL | Manter defesa em profundidade: checagem de direitos Laravel mais RLS não contornável para papéis de aplicação tenant-bound; avaliar owner/BYPASSRLS, contexto transacional, pool de conexões, FK e filas como pontos explícitos de homologação futura. | Assumir que filtro em interface/ORM ou política genérica de RLS garante isolamento |
| **A02-D07** | C04 | Saída mínima e origem dos campos | Exibir apenas CAM-01 (nome de apresentação da própria organização corrente) e CAM-02 (indicador do próprio vínculo ativo), sem CAM03..CAM10; renderização segura de texto não confiável e sem enriquecimento por fontes paralelas. | Incluir papel/CNPJ/plano, lista de organizações ou status comercial nessa primeira tela |
| **A02-D08** | C04/C03 | Falha, erro e caminhos alternativos | Não enumerar organização/terceiros em resposta de negativa, logs ou mensagens; se dependência obrigatória está indisponível negar S01 sem cache inseguro; exportação/portabilidade e regularização de conta são fluxos próprios com outro contrato. | Expor motivo técnico sensível ou criar fallback de leitura por API/export para contornar S01 |

## 4. Fundamentação e reservas de cada decisão

### A02-D01 — Validação de sessão na leitura

**Alternativa A recomendada, apenas para futura ratificação documental:** Exigir sessão de pessoa humana vigente comprovada pelo backend na operação S01; revalidar contexto tenant/membership sem confiar em identidade da UI ou token arbitrário. Falha, timeout ou prova inconclusiva nega somente S01.

**Alternativa B, não recomendada:** Admitir identidade ou tenant apenas pelo navegador/estado de tela

**Rastreabilidade:** R01 EST02/14; S01-T03,T14,T17; CORE04-D05/D10.

**Mesmo após A, permanecerá aberto:** Forma exata de credencial, sessão web/API, renovação e tempo absoluto/inatividade (exceto 30 min do admin central já registrado).

### A02-D02 — MFA e step-up na S01

**Alternativa A recomendada, apenas para futura ratificação documental:** Não exigir novo desafio MFA em toda leitura S01 se a sessão já cumpre a política de segurança aplicável ao principal, organização e papel; exigir reautenticação/step-up quando a política futura indicar insuficiência ou risco. Admins com MFA obrigatório, funcionários segundo política do tenant.

**Alternativa B, não recomendada:** Dispensar MFA de administradores ou impor um desafio novo em toda consulta independentemente da política

**Rastreabilidade:** CORE04-D01..D03 base; S01-T08.

**Mesmo após A, permanecerá aberto:** Fator e nível de garantia, expiração do contexto MFA e ações que exigem step-up permanecem CORE04-D04/D08/SEG.

### A02-D03 — Fonte autorizada do vínculo

**Alternativa A recomendada, apenas para futura ratificação documental:** Fonte oficial de organização e membership no domínio CRM/IAM, consultada com principal humano autenticado e tenant corrente validado server-side; nunca derivar elegibilidade de CNPJ, pagamento de site, webhook, agente ou texto do cliente.

**Alternativa B, não recomendada:** Aceitar tenant de parâmetro ou CNPJ comum como posse/autorização

**Rastreabilidade:** CORE01-I01/I02/I07; R01 EST03/04; S01-T04,T05,T06.

**Mesmo após A, permanecerá aberto:** Catálogo físico de membership, órgão de cadastro/atualização, estados/versionamento e momento exato da revalidação CORE01/07.

### A02-D04 — Efeito de revogação e múltiplos tenants

**Alternativa A recomendada, apenas para futura ratificação documental:** Vínculo diferente de ativo ou próprio nega S01; revogação em A retira a operação em A sem eliminar automaticamente outros vínculos legítimos em B. Revalidar antes de nova leitura, sem dar continuidade pelo cache.

**Alternativa B, não recomendada:** Permitir consulta por membership pendente/revogada ou vincular sessão a tenant anterior por fallback

**Rastreabilidade:** R01 EST03/04; S01-T05,T06,T07,T20.

**Mesmo após A, permanecerá aberto:** Prazo de propagação e concorrência, troca de organização fora deste recorte e tratamento de sessão já renderizada.

### A02-D05 — Autorização como regra por operação

**Alternativa A recomendada, apenas para futura ratificação documental:** Tratar `read_self` apenas como nome semântico candidato para uma operação com todos os fatores: pessoa, tenant, membership, recurso, ação, campos e escopo; negar por padrão sem autorização positiva específica. Uma tela não concede o direito.

**Alternativa B, não recomendada:** Autorizar qualquer usuário com sessão/membership ou plano pago sem política por ação

**Rastreabilidade:** CORE02-D01/D02 e D05 preliminar; S01-T09,T10,T11,T18,T21.

**Mesmo após A, permanecerá aberto:** Recurso/ação nominal definitivo, matriz P1/P2, conflitos de grants/denies, canal e eventual permissão executável permanecem abertos.

### A02-D06 — Isolamento na aplicação e no PostgreSQL

**Alternativa A recomendada, apenas para futura ratificação documental:** Manter defesa em profundidade: checagem de direitos Laravel mais RLS não contornável para papéis de aplicação tenant-bound; avaliar owner/BYPASSRLS, contexto transacional, pool de conexões, FK e filas como pontos explícitos de homologação futura.

**Alternativa B, não recomendada:** Assumir que filtro em interface/ORM ou política genérica de RLS garante isolamento

**Rastreabilidade:** CORE01-I03/I04/I05; S01-T04,T16,T20.

**Mesmo após A, permanecerá aberto:** Papéis efetivos, tabelas, RLS SQL, mecanismo do pool, PostgreSQL 16, revisão SEG/DBA e testes positivos/negativos posteriores.

### A02-D07 — Saída mínima e origem dos campos

**Alternativa A recomendada, apenas para futura ratificação documental:** Exibir apenas CAM-01 (nome de apresentação da própria organização corrente) e CAM-02 (indicador do próprio vínculo ativo), sem CAM03..CAM10; renderização segura de texto não confiável e sem enriquecimento por fontes paralelas.

**Alternativa B, não recomendada:** Incluir papel/CNPJ/plano, lista de organizações ou status comercial nessa primeira tela

**Rastreabilidade:** R01-D01-A; S01-T01,T02,T12,T13; CORE02/LGPD.

**Mesmo após A, permanecerá aberto:** Owner formal do atributo, catálogo/normalização do nome e classificação quando houver nome pessoal/MEI, apresentação exata e evidência XSS.

### A02-D08 — Falha, erro e caminhos alternativos

**Alternativa A recomendada, apenas para futura ratificação documental:** Não enumerar organização/terceiros em resposta de negativa, logs ou mensagens; se dependência obrigatória está indisponível negar S01 sem cache inseguro; exportação/portabilidade e regularização de conta são fluxos próprios com outro contrato.

**Alternativa B, não recomendada:** Expor motivo técnico sensível ou criar fallback de leitura por API/export para contornar S01

**Rastreabilidade:** R01 EST05..12 (não decididos), S01-T15,T17,T19,T21,T22; CORE06/08.

**Mesmo após A, permanecerá aberto:** Forma de resposta/status e texto UX não homologados; observabilidade, acesso LGPD, retenção, cache headers e direitos comerciais por estado ainda abertos.

## 5. Matriz sequencial de verificação conceitual — NÃO algoritmo em produção

| Etapa lógica candidata | Fonte que deverá ser competente | Se inválida/inconclusiva | Prova futura requerida (não executada) |
|---|---|---|---|
| Sessão e identidade humana | IAM/CORE04 | Negar S01 sem identificar empresa | S01-T03,T14,T17; ameaça de impersonação |
| MFA exigível | CORE04 e política de organização/papel | Negar S01 quando nível insuficiente | S01-T08; matriz step-up por risco |
| Tenant corrente e vínculo próprio | CORE01/CORE07 | Negar S01, sem escopo alternativo | S01-T04..T07; revogação concorrente |
| Autorização por ação/atributo | CORE02/SEG | Deny-by-default; não inferir de plano | S01-T09..T11,T18,T21 |
| Estado operacional/comercial compatível | CORE08/FIN/CON e dono de produto | Não inferir ALLOW; outros fluxos legítimos distintos | S01-T15,T22; EST05..12 pendentes |
| Isolamento Laravel/PostgreSQL | CORE01/02/SEG/DBA | Não retornar dados por cache/fallback | S01-T16,T20; roles/pool/RLS futuros |
| Projeção e exibição mínimas | Organizações/IAM, CORE02 e LGPD | Omitir CAM03..CAM10; negativa sem enumeração | S01-T01,T02,T12,T13,T19 |

**Semântica importante:** uma negativa de S01 não é decisão de eliminar informações nem de bloquear direitos legais de exportação, backup, contratação ou regularização por fluxo próprio. Também não é definição do status HTTP ou formato JSON.

## 6. Quadro de lacunas de implementação ainda bloqueantes

| Eixo | Precisão ainda não disponível, logo NÃO homologada | Gate futuro |
|---|---|---|
| Sessão/MFA | cookies/tokens, rotação, tempos de sessão por perfil, fator, recuperação, step-up, revogação, antipirataria | CORE04-D04..D10; SEG01/02 |
| Identidade/tenant | entidade e estados oficiais, versão de membership, troca e revalidação, legitimidade de principal, vínculo com tenant | CORE01-D04..D07; CORE07 |
| Autorização/RLS | papel de aplicação e bypass, política de tabela, integridade, pools, transações, cache, recursos/ações/campos, policy versionada | CORE01/02/03; SEG e CORE10 |
| Dados/privacidade | fonte CAM01 e CAM02, classificação por pessoa jurídica/MEI, finalidade, escaping, limites de logs, erros e retenção | CORE02-D04/D06; CORE06; LGPD |
| Comércio | EST05..12, contrato pago/cortesia/trial e direitos de reativação/portabilidade | CORE08; FIN/CON; Portal; LGPD |
| Evidência | provas negativas e positivas reais, PostgreSQL/RLS em ambiente aprovado, teste simultâneo, parecer SEG/DBA, revisão LGPD e qualidade | CORE10/DEV-GATE; C01..C08 |

## 7. Recomendações de decisão (não homologação)

Submeter à direção as alternativas **A de A02-D01..D08 para aceitação apenas como diretrizes documentais preliminares**. Não solicitar novo GO de código, testes, `ALLOW`, SQL ou homologação. Depois, aprofundar o **catálogo de fontes e estados C02/C04** e a **matriz de papéis/isolamento C03**, priorizando as indefinições que impedem implementação segura.

## 8. Preservações integrais

**R02-C01..C08 = PENDENTES. 67 IDs técnicos CORE, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates permanecem ABERTOS.**

**R08-G01..G06 = funções de governança interna**, todas acumuladas documentalmente; **INT-G01..G06 = gates técnicos distintos**, todos abertos. Atribuição interna não resolve independência de revisão de segurança/privacidade/QA quando necessária.

Alteração restrita a Markdown na branch `docs/func-p0-01-s01-r01-r02-2026-11-06`. **Não alterar `main`, PR #2/#21, branch `dev/fast-track-crm-2026-11-04`, VPS/produção, CI, código, SQL, migration, `ALLOW`/`GRANT`, testes funcionais, dados reais ou cobrança**.