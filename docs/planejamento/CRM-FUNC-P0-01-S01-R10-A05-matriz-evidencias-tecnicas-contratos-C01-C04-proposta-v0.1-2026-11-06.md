# FUNC-P0-01/S01-R10-A05 — Matriz de evidências para futura homologação dos contratos C01–C04 (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** PROPOSTA DOCUMENTAL DE PRONTIDÃO, NÃO HOMOLOGADA, NÃO EXECUTÁVEL.

## 1. Objetivo e referências

Converter o detalhamento aprovado **somente como diretriz preliminar** em um checklist de lacunas reais que antecedem eventual código, testes e homologação. O quadro **não inicia** nenhuma dessas ações e **não** substitui o planejamento C05..C08.

- [R10 — quadro de lacunas C01..C08](CRM-FUNC-P0-01-S01-R10-matriz-fechamento-evidencias-C01-C08-v0.1-2026-11-06.md).
- [R10-A01 — negativas](CRM-FUNC-P0-01-S01-R10-A01-matriz-negativas-C01-C04-proposta-v0.1-2026-11-06.md); [R10-A02 ratificada](CRM-DEC-FUNC-P0-01-S01-R10-A02-D01-D08-alternativas-A-2026-11-06.md); [R10-A03 ratificada](CRM-DEC-FUNC-P0-01-S01-R10-A03-D01-D08-alternativas-A-2026-11-06.md); [R10-A04 ratificada](CRM-DEC-FUNC-P0-01-S01-R10-A04-D01-D06-alternativas-A-2026-11-06.md).
- [Fichas R07 — C01..C08](CRM-FUNC-P0-01-S01-R07-PAINEL-fichas-evidencias-pendencias-v0.1-2026-11-06.md); [R09 — nomeações G01..G06](CRM-DEC-FUNC-P0-01-S01-R09-G04-G06-acumulo-governanca-2026-11-06.md).
- Fonte de casos futuros: [S01-T01..T22 na DEV](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md) (blob `78e1d021c0f1c5f66fc4b9f59092383a4d7b4`). **Zero resultados executados** nesta análise.

## 2. Status por contrato — nenhum considerado fechado

| Contrato | Tema | Fontes documentais já existentes | Bloqueios efetivos | Artefato ainda exigido antes de qualquer GO específico | Dependências/gates ainda abertos | Titular interno/revisão ainda pendente | Estado verificado |
|---|---|---|---|---|---|---|---|
| C01 | Sessão, autenticação e MFA | R01, R10-A02, R10-A03, R10-A04; CORE04 base D01–D03 | Métodos e políticas finais, nível de MFA, expiração por papel, reauth/step-up, revogação, mensagens neutras e recuperação | Documento de contrato de sessão/MFA por papel, estados, fronteiras e responsáveis; roteiro S01-T03/T08/T14/T17, sem resultado | CORE04-D04..D10, SEG01/02, CORE10; política aplicada e evidência futura | G01/G02/G04 coordenam; revisão de segurança competente independente quando exigida | PENDENTE / nenhuma prova funcional declarada |
| C02 | Principal, tenant e própria membership | R01 EST, A02-D03/D04, A03-D01..D03, A04-D01..D03; CORE01 base D01–D03 | Estado próprio, fonte/versionamento de membership, troca de contexto, revogação, conflito entre tenants A/B e vínculos do mesmo CNPJ | Catálogo de autoridades de dados, transições de vínculo, ator/tenant/versão, desenho de revogação e provas futuras S01-T04..T07 | CORE01-D04..D07, CORE07, SEG, eventual efeito de contratos CRM/Portal sem grants implícitos | G02 coordena; G04 revisão interna, independência de validação ainda não comprovada | PENDENTE / modelo físico não validado |
| C03 | Autorização Laravel + PostgreSQL RLS | A02-D05/D06, A03-D05..D07, A04-D03/D04; CORE01/02/03 bases documentais | Policy por recurso/ação/campo/escopo, papéis DB sem bypass, contexto transacional, reuso de conexão, FK tenant-bound, cache e riscos de corrida | Matriz de ameaças/roles e tabelas, matriz de negações S01-T09..T11/T16/T20/T21; especificação de revisão PostgreSQL futura sem SQL nesta fase | CORE01/02/03, SEG01/02, DBA, CORE10; SQL/RLS e provas funcionais futuros não autorizados | G02/G04 mesma pessoa (autoria e revisão internas); revisão independente de isolamento se requerida | PENDENTE / sem SQL, sem ALLOW, sem testes |
| C04 | Projeção CAM01 + CAM02 e não enumeração | R01 CAM, A02-D07/D08, A03-D04/D08, A04-D05; CORE02 base | Fonte e classificação de CAM01/02, texto de MEI/pessoa natural, tratamento de erro, sanitação/XSS, formatos e logs | Contrato de saída mínima e catálogo de atributos; rastreio S01-T01/T02/T12/T13/T19, prova futura de negação e escaping | CORE02-D04/D06, CORE06, LGPD; owner de dado/UX; revisão de privacidade exigível | G01/G02/G05/G06 coordenam; parecer especializado e testes não comprovados | PENDENTE / só contrato semântico |

**Regra de interpretação:** 'fontes documentais já existentes' significa somente texto/decisões acessíveis, **não** implementação nem prova de segurança. Os níveis de conclusão de CORE/P0 da fonte prevalecem; esta planilha textual não altera status de outro gate.

## 3. Checklist de critérios mínimos transversais

| Código | Condição de prontidão | Já identificado documentalmente | Ainda necessário | Estado real |
|---|---|---|---|---|
| E01 | Proveniência de requisito | Referência a R01/A02/A03/A04 e CORE com decisões e versões identificadas | Verificar documentos/SHAs e resolver divergências ao montar contrato | Parcial, não homologado |
| E02 | Proprietário do dado/contrato | Titular da governança interna G01–G06 documentado em R09 | Definir autoridade do dado e aceite específico sobre IAM, empresa e campos; G01–G06 não substituem fontes empresariais nem terceiros | PENDENTE |
| E03 | Matriz de permissão efetiva | Diretrizes deny-by-default e só CAM01/CAM02 | Aprovar recurso/ação/campo/escopo, fonte de grant/deny e resolução de conflito; status de plano sozinho não concede direito | PENDENTE |
| E04 | Revisão de segurança | Threat cases S01-T01..T22 documentados | Parecer tecnicamente competente e independente quando exigido sobre autenticador, RLS e isolamento/concorrência, com escopo identificável | PENDENTE |
| E05 | Revisão de privacidade | Propostas de minimização e exclusão CAM03..CAM10 | Finalidade/base legal, classificação de nomes comerciais pessoais, logs, acesso, retenção, direitos dos titulares e parecer competente | PENDENTE |
| E06 | Evidência técnica executada | Casos de teste futuros e resultados esperados documentados | Ambiente expressamente autorizado, revisão da implementação, execução real, resultados/artefatos íntegros e avaliação QA/SEG | NÃO EXECUTADO |
| E07 | Autorizações de passagem de gate | 67 CORE/SEG/AUT/INT/FIN/CON/LGPD/DEV-GATE abertos | Atos específicos e comprovados de aprovação; não presumir por diretriz documental G01 | NÃO HOMOLOGADO |

## 4. Modelo de ficha de evidência futura (somente esquema documental)

| Campo descritivo | Conteúdo a documentar quando houver autorização específica |
|---|---|
| Contrato e caso original | C01..C04 e S01-Txx original (sem inventar suíte redundante) |
| Fonte e versão | Documento fonte, SHA, decisão vigente e alterações de precedência |
| Requisito e resultado esperado | Operação, ator, tenant, vínculo, campo, risco, negativa ou positiva |
| Execução real | Somente após autorização: ambiente, versão de código, banco, políticas, usuário fictício, data e executor |
| Evidência objetiva | Resultado real, logs sanitizados, rastreamento de request sem segredos/PII e erros/reteste |
| Parecer e competência | Quem revisou o que, risco residual, independência quando exigida, fundamento de aceite |
| Aceite por autoridade e gate | Decisão específica registrada depois das evidências; nunca inferida do aceite A02–A04 |

## 5. Regras de declaração de prontidão

- 'Aprovado documentalmente' em A02/A03/A04 **não** significa que as condições E01..E07 foram satisfeitas.
- 'Pronto para especificar' só poderá ser usado para um contrato quando fonte, lacunas e responsáveis constarem de uma ficha; isso ainda **não** autoriza implementação.
- 'Pronto para teste' e 'Homologado' exigem autorizações e resultados reais separados; atualmente **não** existem na trilha S01.
- G01–G06 estão assumidas internamente pela mesma pessoa; revisão de segurança/DBA, LGPD/jurídica e QA **não** se tornam independentes pelo acúmulo. Se o critério exigir independência, registrar **parecer competente independente pendente**.
- Direitos de exportação, regularização e portabilidade devem seguir seus contratos e não ser eliminados por uma negativa limitada de S01.

## 6. Próxima decisão documental possível — A05-D01..D04 (AINDA NÃO APROVADAS)

| ID | Alternativa A (recomendada) | Alternativa B |
|---|---|---|
| A05-D01 | Adotar esta matriz como **checklist documental de evidência**, mantendo todos os estados de C01–C04 PENDENTES | Considerar a existência da matriz como homologação dos contratos |
| A05-D02 | Manter um **registro de fonte/versionamento/owner por condição**, sem deduzir grants de nomes, assinaturas ou decisões comerciais | Aceitar indícios incompletos como fonte definitiva |
| A05-D03 | Exigir **revisões de segurança, privacidade e QA com competência e independência quando aplicável**, marcando lacunas e sem autodeclaração de independência | Tratar G01–G06 numa pessoa como substituição automática de pareceres necessários |
| A05-D04 | Continuar a especificação documental orientada por lacunas, só encaminhando autorização de programação/testes/SQL mediante **pedido futuro expresso e específico**, não por aceite de minuta | Interpretar aprovação documental como GO de desenvolvimento |

Todas as alternativas A acima são **propostas ainda não aprovadas** e devem ser submetidas separadamente à Direção de Produto.

## 7. Resultado de prontidão hoje

**C01: PENDENTE. C02: PENDENTE. C03: PENDENTE. C04: PENDENTE. C05..C08: PENDENTES.** Nenhum teste, SQL, política de RLS física, parecer independente ou passagem formal de gate foi executado/obtido nesta etapa. Os 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates seguem ABERTOS.

## 8. Escopo da entrega

Somente Markdown na branch documental isolada. Não alterar `main`, PRs #2/#21, branch DEV, SQL, `ALLOW`/`GRANT`, migrações, CI, código, testes, VPS ou produção. Nenhuma permissão operacional ou acesso a dados reais resultante deste arquivo.