# CRM-DEC — FUNC-P0-01/S01-R10-A13 — Ratificação das alternativas A de D01 a D05

**Data:** 06/11/2026 (America/Sao_Paulo). **Natureza:** RATIFICAÇÃO EXCLUSIVAMENTE DOCUMENTAL PRELIMINAR. Não é GO técnico, revisão independente ou homologação de gate.

**Pergunta imediatamente submetida:** “Você aprova as alternativas A de A13-D01 a A13-D05 como diretrizes documentais preliminares?”
**Resposta expressa do responsável G01:** **“aprovado”**. Na mesma mensagem solicitou estimativa percentual do desenvolvimento do Imagine CRM; essa estimativa gerencial não constitui decisão técnica, autorização ou alteração de gates.

**Fonte:** [R10-A13 — matriz de riscos residuais e comandos destrutivos do PR #23](CRM-FUNC-P0-01-S01-R10-A13-matriz-riscos-residuais-operacoes-destrutivas-PR23-proposta-v0.1-2026-11-06.md), blob `d6a93a125f8417b77f06d52c2c9e46c6837b948e`.

## 1. Alternativas A aprovadas, apenas documentalmente

| ID | Diretriz aprovada | Limite |
|---|---|---|
| **A13-D01 — A** | Proibir comandos destrutivos fora de banco efêmero autorizado; exigir controles de credenciais/release separados do guard da migration. | Sem execução ou fechamento de gate |
| **A13-D02 — A** | Classificar host loopback e `current_database()` como mitigação parcial; exigir verificação de instância, credenciais e pipeline antes de DDL persistente. | Sem execução ou fechamento de gate |
| **A13-D03 — A** | Adotar como backlog apenas documental futuro incremento de guardrails CI e testes sintéticos em schema descartável; execução/código depende de GO específico distinto. | Sem execução ou fechamento de gate |
| **A13-D04 — A** | Tratar teste do ledger como evidência parcial e exigir reconciliação de drift/histórico, sem recorrer a `fresh`/`wipe` em bancos persistentes. | Sem execução ou fechamento de gate |
| **A13-D05 — A** | Manter PR #23 DRAFT e sem merge até revisão competente/independente quando exigida, risco residual, CI/HEAD atualizados e autorização expressa DEV. | Sem execução ou fechamento de gate |

Todas as alternativas B do documento de origem **não foram aprovadas**. A expressão `AINDA NÃO RATIFICADAS` na minuta descrevia o estado histórico anterior e agora é superada **somente para as alternativas A indicadas**.

## 2. Pendências preservadas

- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) aberto e DRAFT, head `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`, base exclusivamente DEV. Nenhum merge aprovado.
- O risco de `migrate:fresh`/`db:wipe` permanece aberto para bancos não efêmeros. Guarda de `up/down`, flag e CI verde não comprovam proteção global do banco ou das credenciais.
- A revisão SEG/DBA competente, com independência se requerida, **não foi entregue**. G01..G06 internos acumulados pela mesma pessoa não são revisão externa.
- **C01..C08 PENDENTES; 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.**
- Nenhum GO de código, SQL, `ALLOW`/`GRANT`, CI novo, testes adicionais, merge PR #23, VPS, produção, dados de clientes ou cobrança. `main`, PRs #2/#21 e branch DEV original permanecem preservados.

## 3. Encaminhamento após esta ratificação

Pode-se **preparar documentalmente** uma tarefa de guardrails CI/execução destrutiva por escopo, mas somente um **ato expresso futuro** autorizará código e testes sintéticos; revisão dos riscos e autorização própria serão necessárias antes de qualquer merge DEV. Não equiparar progresso documental ao desenvolvimento funcional do produto.