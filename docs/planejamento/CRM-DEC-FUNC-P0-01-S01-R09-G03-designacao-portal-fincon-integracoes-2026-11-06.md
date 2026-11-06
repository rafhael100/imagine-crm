# CRM-DEC — FUNC-P0-01/S01-R09-N03 — Aceite da função G03, acumulada com G01 e G02

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Natureza:** ato **documental de designação de função de governança**, confirmado pelo próprio declarante; não comprova homologação técnica, jurídica, fiscal ou financeira nem emissão de parecer independente.
**Referências:** [R09 — quadro de designações](CRM-FUNC-P0-01-S01-R09-quadro-designacoes-nominais-proposta-v0.1-2026-11-06.md), [R09-N01 — aceite G01](CRM-DEC-FUNC-P0-01-S01-R09-G01-designacao-direcao-produto-2026-11-06.md), [R09-N02 — aceite G02](CRM-DEC-FUNC-P0-01-S01-R09-G02-designacao-responsavel-tecnico-2026-11-06.md), [EVID03 — matriz de eventos](CRM-FUNC-P0-01-S01-R07-EVID03-matriz-eventos-comerciais-fonte-autoritativa-2026-11-06.md).

## 1. Evidência expressa da manifestação

**Pergunta imediatamente anterior do assistente:** “Você também deseja assumir G03, acumulando com G01 e G02, ou prefere indicar outro responsável?”  
**Resposta expressa do responsável empresarial:** **“sim”**.

**Resultado R09-N03:** o mesmo declarante que já havia aceitado **R08-G01 — Direção de Produto Imagine** e **R08-G02 — Responsável Técnico CRM/IAM/Arquitetura** aceita assumir também **R08-G03 — Responsável Portal/FIN/CON e Integrações**, com atribuições e limites documentais próprios. Não se extrai dessa resposta aceitação de G04, G05 ou G06.

**Titular:** a pessoa que se identificou na conversa como responsável empresarial da Imagine e declarou os aceites anteriores, **sem validação adicional de identidade civil, atribuição por login GitHub ou registro profissional**. Se necessário, completar posteriormente identificador institucional e aceite formal de terceiros envolvidos; o commit não é certificação de identidade.

## 2. Competências empresariais abrangidas

- Coordenar, na função de governança, o levantamento das regras comerciais e fatos contratuais do **CRM Imagine** (assinaturas, trial, FIN/CON) e do **Portal Imagine** (sites, campanhas, cortesia), **sem fundir seus contratos ou registros oficiais**.
- Coordenar a especificação documental das integrações e reconciliações entre Portal e CRM, a partir de fontes autoritativas por produto e dos quinze eventos candidatos EV01..EV15 registrados em EVID03.
- Revisar preliminarmente a coerência funcional de mudanças comerciais, elegibilidade de cortesia, inadimplência, cancelamentos e reativações, respeitando aprovações funcionais anteriores e pontos ainda pendentes.
- Encaminhar contratos e políticas às revisões necessárias em segurança/DBA, privacidade/jurídico e qualidade, **sem assinar automaticamente os pareceres dessas áreas**.

**Alçadas separadas:** FIN/CON CRM é a fonte de fatos da assinatura e cobrança CRM; Portal é fonte dos contratos de sites; INT transporta eventos, mas HTTP 2xx, webhook e cache não constituem prova de pagamento, contratação, vínculo humano ou autorização de recursos. A G03 pode coordenar essas fontes, **não declarar fatos concretos confirmados sem a respectiva evidência oficial**.

## 3. Limitações e controle de acúmulo de funções

| Atribuição | Estado após a decisão |
|---|---|
| **G01 — Direção de Produto** | **Aceite documental anterior preservado** |
| **G02 — Responsável Técnico CRM/IAM/Arquitetura** | **Aceite documental anterior preservado** |
| **G03 — Portal/FIN/CON/Integrações** | **Aceite documental expressamente confirmado, acumulado com G01 e G02** |
| **G04 — Revisor de Segurança e Banco (SEG/DBA)** | **A DESIGNAR; sem parecer independente** |
| **G05 — Privacidade/LGPD e Jurídico** | **A DESIGNAR; sem parecer jurídico/LGPD** |
| **G06 — Qualidade/Conformidade e Homologação** | **A DESIGNAR; sem homologação de testes** |

**O acúmulo G01+G02+G03 não cria independência entre avaliação empresarial, autoria técnica e autoridade de negócio.** Será necessário registrar pareceres distinguíveis de G04/G05/G06 quando cabíveis; não tratar autoaceite como revisão independente nem atestado profissional.

Esta atribuição **não autoriza**: cobranças, concessões, alterações reais de planos, disparos de eventos/webhooks, criação/alteração de tenant/benefício, acesso a credenciais ou dados de clientes, programação, SQL, `ALLOW`/`GRANT`, RLS executável, CI, testes funcionais, implantação, merge ou execução de ferramentas por mera designação.

## 4. Estado dos contratos/gates e próximos passos

- **R02-C01..C08 continuam PENDENTES.**
- **67 IDs técnicos CORE, SEG-01/02, AUT, ARQ/AGT, FIN/CON, LGPD, INT P0/G01..G06, CORE10/DEV-GATE e demais gates permanecem ABERTOS**. `R08-G03` é função de governança, diferente de qualquer gate `INT-G03`.
- Permanecem intocadas `main`, PRs #2/#21, `dev/fast-track-crm-2026-11-04`, VPS e produção. Somente registro Markdown na branch isolada.
- **Próxima decisão separada:** selecionar quem assumirá **G04 — Revisor de Segurança e Banco (SEG/DBA)**, com competência técnica e independência de parecer adequadas ao risco. A manifestação sobre G03 não nomeia G04 nem dispensa revisão especializada.

**Conclusão:** aceite específico de G03 registrado documentalmente com acúmulo de G01 e G02; nenhum gate ou contrato é homologado.
