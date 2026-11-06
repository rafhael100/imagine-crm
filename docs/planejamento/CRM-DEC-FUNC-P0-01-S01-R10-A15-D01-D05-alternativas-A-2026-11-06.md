# CRM-DEC — FUNC-P0-01 / S01-R10-A15 — Ratificação das alternativas A de D01 a D05

**Data:** 06/11/2026 (America/Sao_Paulo). **Status:** APROVADO SOMENTE COMO DIRETRIZES DOCUMENTAIS PRELIMINARES, não como contratos executáveis ou homologação de segurança.

## 1. Decisão expressa

Após apresentação da [proposta R10-A15 — IAM, membership e read_self](CRM-FUNC-P0-01-S01-R10-A15-lacunas-iam-read-self-portao-de-codigo-proposta-2026-11-06.md), a Direção respondeu **“aprovado”**. Pelo contexto imediato, ratificam-se exclusivamente as alternativas **A15-D01-A, D02-A, D03-A, D04-A e D05-A** como bases documentais para o primeiro recorte funcional futuro.

**Fonte versionada:** R10-A15 blob `9bd591066f8e8cef81b944a4d98bed7970087ccc`. As alternativas B não foram aprovadas. O status PROPOSTA da fonte fica superado apenas quanto às alternativas A acima, e não quanto às lacunas técnicas que ela descreve.

## 2. Conteúdo deliberado

| ID | Eixo | Diretriz A aceita para documentação | Limite explícito |
|---|---|---|---|
| A15-D01-A | IAM | Autenticação humana verificável é contrato distinto de S01; não converter perfil experimental em login público; MFA/step-up/origem confiável continuam sujeitos a CORE04/SEG. | Sem endpoint/login ou política de MFA homologados. |
| A15-D02-A | Principal, tenant e membership | Exigir prova server-side encadeada: principal autenticado, membership própria ativa/versionada, tenant corrente e escopo, com revalidação e fail-closed. | IDs, CNPJ e GUC não autenticam nem autorizam. |
| A15-D03-A | Leitura `read_self` | Especificar política contextual por recurso/ação/campo/escopo, com CAM-01/02 e negações, antes de qualquer permissão positiva; `DenyByDefaultAuthorizer` permanece inalterado. | Não autoriza `ALLOW`, grants, SQL nem leitura funcional. |
| A15-D04-A | Estados e privacidade | Não decidir EST-05..12 por suposição; remeter entitlement, trial/cortesia/suspensão/cancelamento e direitos legítimos a CORE08/FIN/CON/LGPD. | Sem regras de cobrança/acesso automático aprovadas. |
| A15-D05-A | Sequenciamento técnico | Preparar evidência e parecer competente antes de habilitar jornada; se autorizado especificamente depois, o primeiro incremento pode limitar-se a contratos/testes negativos inertes, em branch filha/PR DRAFT. | Sem GO de código ou testes funcionais por este ato. |

## 3. Próximo recorte técnico que pode ser autorizado SEPARADAMENTE

**S01-NEG-01 (candidato, ainda não autorizado)** — somente reforço dos *contratos de negação e testes unitários/sintéticos negativos* sobre a cadeia de sessão → identidade → membership → tenant → autorização. Proposta de escopo estrito:

- Branch filha exclusiva da DEV `dev/fast-track-crm-2026-11-04`, PR DRAFT direcionado à DEV, sem merge automático, `main` ou VPS.
- Investigar testes existentes para evitar duplicação. Adicionar apenas cenários negativos: sessão ausente/revogada/MFA não comprovada; membership de outra identidade/tenant, inativa/revogada; tenant arbitrário ou contexto contaminado; request de `read_self` sem política/grant aplicável; tentativa de devolver CAM03..10; exceções/erros sem dados de terceiros.
- **Não** produzir endpoint, tela, policy positiva, `ALLOW`, grants, SQL novo, credenciais, banco persistente nem nova integração. Não alterar `DenyByDefaultAuthorizer` para conceder acesso.
- Testes exclusivamente locais/CI com fixtures sintéticas; informar nomes, SHA, resultados dos workflows e riscos, sem alegar homologação da jornada S01.
- Exigir futura autorização específica para código e testes, com arquivos permitidos e revisão competente quando aplicável. A direção não aprovou S01-NEG-01 nesta mensagem.

## 4. Bloqueios ainda reais para primeiro usuário funcional

1. CORE04/SEG — contrato de autenticação, sessão, MFA/step-up, origem de principal, revogação e logs.
2. CORE01/07/SEG-DBA — membership versionada, tenant correto, RLS non-bypass, transações e limpeza em pool.
3. CORE02-D04..D10 — `read_self` por campo/escopo com fonte de grants/denies; GOV02 ainda não homologada.
4. CORE08/FIN/CON/LGPD — estados EST-05..12 e direitos mínimos legítimos sem deduzir política de assinatura.
5. CORE10/QA/DEV-GATE — evidência S01-T01..T22, parecer técnico competente/independente quando exigido, GO de engenharia e autorização separada para qualquer primeira leitura positiva.

## 5. Preservações

`DenyByDefaultAuthorizer` segue deny-only; PRs #2/#21/#23 continuam DRAFT/sem merge; `main`, DEV original e VPS não alteradas. C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates permanecem ABERTOS. Progresso estimado do código total permanece aproximadamente **10%**, sem acréscimo por esta ratificação documental.

**Próxima decisão útil:** a Direção pode emitir, em ato separado, GO **estritamente para S01-NEG-01** (contratos/testes negativos em laboratório), mantendo autorização funcional e todos os gates abertos. Isso cria trabalho executável sem fingir que o CRM comercial já está pronto.