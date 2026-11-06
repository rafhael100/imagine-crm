# CRM-DEC — FUNC-P0-01/S01-R09-N04 a N06 — Assunção documental de G04, G05 e G06 pelo titular de G01–G03

**Data da manifestação:** 06/11/2026 (America/Sao_Paulo).  
**Natureza:** registro de **responsabilidade interna de governança e coordenação**, a pedido expresso da direção da Imagine. **Não é homologação, certificação, assinatura técnica/jurídica, nomeação legal de encarregado LGPD, verificação independente nem comprovação de habilitação profissional.**

## 1. Manifestação e decisão

**Pergunta anterior:** “Você prefere indicar um profissional para G04 ou manter essa função vaga até contratarmos ou identificarmos um revisor especializado?”  
**Resposta literal:** **“todas sera eu”**.

Dado o contexto direto de designações progressivas **G01–G06** e as nomeações já expressas de [G01](CRM-DEC-FUNC-P0-01-S01-R09-G01-designacao-direcao-produto-2026-11-06.md), [G02](CRM-DEC-FUNC-P0-01-S01-R09-G02-designacao-responsavel-tecnico-2026-11-06.md) e [G03](CRM-DEC-FUNC-P0-01-S01-R09-G03-designacao-portal-fincon-integracoes-2026-11-06.md), registrar que o mesmo declarante **assume também G04, G05 e G06 como papéis internos de governança**. A resposta não significa que terceiro emitiu parecer, ou que a própria atuação seja independente.

**Titular:** o mesmo responsável empresarial que declarou os aceites G01–G03, sem inferência de identidade civil a partir de conta GitHub/commit. Nome institucional completo e habilitações formais, se exigidos, podem ser conferidos em registro independente.

**Alcance do aceite:** o declarante assume responsabilidade por **coordenar, preparar e supervisionar** as disciplinas indicadas nas seis funções; revisão técnica, proteção de dados e qualidade devem ser tratadas como disciplinas **distintas mesmo que acumuladas numa só pessoa**.

## 2. Quadro consolidado após o aceite

| Função R08 | Titular interno documental | Escopo assumido | Limite ou prova externa ainda inexistente |
|---|---|---|---|
| **G01 — Direção de Produto Imagine** | **Mesmo declarante** — previamente aceito | estratégia, prioridades, escopo, regras empresariais | decisão comercial não é homologação técnica |
| **G02 — Responsável Técnico CRM/IAM/Arquitetura** | **Mesmo declarante** — previamente aceito | coordenar modelo Laravel/PostgreSQL, IAM, arquitetura, CORE | autoria técnica não equivale a revisão independente |
| **G03 — Portal/FIN/CON/Integrações** | **Mesmo declarante** — previamente aceito | coordenar contratos, pagamentos e eventos por autoridades distintas | fato de negócio exige fonte oficial competente, não ACK de integração |
| **G04 — Segurança e Banco de Dados** | **Mesmo declarante** — **aceite documental atual** | coordenar modelagem de riscos, RLS, DBA, MFA, incidentes e demanda de evidências | **NÃO há revisor de segurança independente comprovado**; não confundir supervisão G04 com parecer externo ou segunda opinião |
| **G05 — Privacidade/LGPD e Jurídico** | **Mesmo declarante** — **aceite documental atual** | coordenar documentação de privacidade, minimização, bases e consultas jurídicas | **NÃO há parecer jurídico ou avaliação especializada validada por terceiro**; não presumir nomeação de encarregado/DPO legalmente formalizada ou qualificação profissional |
| **G06 — Qualidade, Conformidade e Homologação** | **Mesmo declarante** — **aceite documental atual** | organizar matriz de critérios e futura coleta de evidências, supervisionar QA | **NÃO há testes funcionais S01 executados, revisão independente ou homologação**; validar a própria autoria não constitui independência |

**Resultado:** G01–G06 têm **titular interno documental confirmado — o mesmo declarante**. Ficam **PENDENTES** as revisões, pareceres, avaliações por competências especializadas e provas independentes necessárias conforme risco, lei e contrato. Os controles de conflito de interesses e segregação devem ser aplicados antes de qualquer conclusão técnica.

## 3. Registro de conflito e ressalvas de independência

- A separação **entre papéis** é organizacional; o acúmulo não gera **separação entre pessoas**. Uma avaliação feita por quem produziu o artefato é **autorrevisão**, e deve ser identificada dessa forma.
- Um documento de G04 pode ser uma avaliação interna de segurança, **não** parecer independente de segurança. Uma minuta de G05 pode ser parecer de planejamento interno, **não** aconselhamento jurídico profissional confirmado. Uma checklist de G06 não é certificação de QA ou teste executado.
- Se o gate requer análise independente/segregação obrigatória, registrar explicitamente **PARECER INDEPENDENTE PENDENTE**, mesmo com G04–G06 ocupadas; solicitar revisor especializado **antes do fechamento do gate**, não confundir cargo preenchido com evidência suficiente.
- ChatGPT, Codex ou agentes de IA podem contribuir com estudos e execução futura controlada, mas não substituem atestação humana profissional exigida nem conferem independência de autoria.
- Esta decisão **não autoriza** acesso a dados de clientes, concessão de papéis, alteração de credenciais, contratação, cobrança, recuperação automática, implantação, `ALLOW`/`GRANT`, SQL, RLS físico, novos testes ou CI.

## 4. Estado dos contratos C01–C08 e gates: SEM ALTERAÇÃO

- **R02-C01..C08 continuam PENDENTES**, aguardando documentação final, avaliação de ameaças, critérios, aceites especializados, homologação e provas futuras.
- **67 CORE técnicos, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates seguem ABERTOS**, sem exceções.
- `R08-G01..G06` são **funções de governança**, não os gates técnicos `INT-G01..G06`; nomear toda a equipe interna não reduz a exigência dos seis gates INT.
- S01 segue limitada à autoconsulta da própria organização e do próprio vínculo humano ativo com **apenas dois campos mínimos**; sem implementação funcional.
- `main`, PRs #2/#21, `dev/fast-track-crm-2026-11-04`, VPS e produção sem intervenção neste registro. A criação desta documentação ocorre **somente** na branch `docs/func-p0-01-s01-r01-r02-2026-11-06`.

## 5. Próximo trabalho documental autorizado pelo contexto (não GO técnico)

Consolidar um **quadro de lacunas de parecer e evidência por contrato C01..C08**, distinguindo quem coordena (G01–G06, mesmo titular), quem possui a autoridade do fato (CRM/Portal/IAM), qual competência técnica/privacidade precisa atestar e quais artefatos devem ser reunidos antes da avaliação formal. Não abrir nova rodada de nomeações e não considerar que a falta de revisor independente foi superada.

**Status final:** seis funções internas documentalmente assumidas pelo declarante, porém **zero homologações técnicas adicionais, zero novos testes, zero gates fechados**.