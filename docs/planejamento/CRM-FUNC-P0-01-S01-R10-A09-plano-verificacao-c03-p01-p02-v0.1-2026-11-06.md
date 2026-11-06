# CRM Imagine — FUNC-P0-01/S01-R10-A09 — Plano verificável de evidências para C03-P01/P02 (proposta v0.1)

**Data:** 06/11/2026. **Natureza:** planejamento de verificação DOCUMENTAL, sem varredura técnica ou banco reais. **NÃO HOMOLOGADO; NENHUM TESTE EXECUTADO.**

## 1. Deliberação precedente e limites

- [Ratificação A08 — alternativas P01-D01..D04 e P02-D01..D04 A](CRM-DEC-FUNC-P0-01-S01-R10-A08-P01-P02-alternativas-A-2026-11-06.md), aprovada somente como diretriz documental preliminar.
- [R10-A08 — dez grupos OBJ, seis classes ROLE e oito etapas CTX](CRM-FUNC-P0-01-S01-R10-A08-inventario-roles-contexto-conexoes-C03-P01-P02-proposta-v0.1-2026-11-06.md) blob `32925d36ffd81446f938409ba5c08d19189c76f9`.
- [R10-A07 — contrato conceitual C03](CRM-FUNC-P0-01-S01-R10-A07-contrato-conceitual-C03-isolamento-autorizacao-proposta-v0.1-2026-11-06.md) blob `2089149d5a11c68687831a5ba5f6e4e302a829f9`.
- [CORE01](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md), [CORE02](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) e [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md), com pendências de gates ainda abertas.

Este A09 é um **roteiro de documentação e de evidências requeridas**, não afirma acesso, inventário de código/migrations, inspeção de banco/produção, verificação de SQL ou achado de segurança concreto. A etapa aprovada foi apenas elaboração documental.

## 2. Matriz de verificabilidade — 14 checkpoints

| ID | Item que exigirá verificação | Pergunta de inspeção futura | Evidência objetiva esperada | Situação efetiva desta etapa | Área competente | Vinculado a |
|---|---|---|---|---|---|---|
| I01 | Objetos tenant-bound | Tabelas, views e materializações reais por módulo, proprietários, tenancy e chaves | Manifesto código/migrations/modelos versionado (Git path+SHA), sujeito a revisão | Não verificado fisicamente | CORE01/02 e owners dos módulos | C03-P01 |
| I02 | Objetos globais/híbridos | Identidades, sessão, catálogos/planos/operadores e exceções aos tenants | Mapa excepcional de autorização por objeto/campo/ação e owner, justificação de globalidade | Não verificado fisicamente | CORE01/02/SEG | C03-P01 |
| I03 | Views/funções/rotinas SQL | Risco de definer rights, owner, bypass e consultas indiretas | Lista de definições versionadas e análise de contexto/role/fonte do resultado | Não verificado fisicamente | DBA/SEG e CORE01 | C03-P01 |
| I04 | Relações FK/joins | Referências entre dados A/B e objetos globais inadvertidos | Matriz tenant × entidade × FK/relacionamento; critérios CORE01-T09 | Não verificado fisicamente | CORE01/DBA | C03-P01 |
| I05 | Roles PostgreSQL runtime | Proprietário de tabelas, superuser/BYPASSRLS, concessões e separação de execução/migração | Catálogo de role/ownership/privilege efetivos, sem expor credenciais nem alterar grants | Nenhum privilégio real atestado | DBA/SEG | C03-P01 |
| I06 | Políticas RLS efetivas | Cobertura por tabela, exceções, FORCE RLS quando cabível, comportamento sem tenant | Mapa da política física e demonstração futura de bloqueio pelo papel runtime; não presume proteção por schema | Nenhuma política real atestada | SEG/DBA | C03-P01/P02 |
| I07 | Rotas e políticas Laravel | Autorização por principal, vínculo, recurso, ação, campo e escopo; vias indiretas | Inventário de middleware/policies/gates/serialização em código versionado, comparado com CORE02 | Não verificado no código | CORE02/SEG | C03-P01/P02 |
| I08 | Resolução de tenant | Fonte de tenant e membership no request, job e integrações | Matriz fonte confiável × contexto × possibilidade de override; teste futuro IDOR | Não verificado no código | CORE01/04/SEG | C03-P02 |
| I09 | Ciclo transacional/pool | Atribuição de contexto na aquisição; commit/rollback/erro/limpeza; pooler usado | Contrato operacional por driver/pool e prova futura de A→B sem vazamento, não assumir SET LOCAL válido em todas as modalidades | Modelo físico não escolhido | DBA/CORE03/SEG | C03-P02 |
| I10 | Workers e efeitos diferidos | Separação service principal/humano, revalidação de estado e mandato por classe CORE03 | Fluxos reais e momento de autorização para retry/jobs/eventos; CORE03-T01/T02/T13 | Não verificado no código | CORE03/INT/SEG | C03-P02 |
| I11 | Cache e artefatos derivados | Chaves por tenant/principal/permissão e invalidação após revogação; exportações/arquivos | Inventário cache/storage/CDN/report, ACL e prazo por categoria; S01-T17/T21 | Não verificado no código/ambiente | CORE06/SEG/LGPD | C03-P01/P02 |
| I12 | Falha segura e observabilidade | DB/Redis/control plane indisponível, logs sanitizados e revalidação | Cenários preexistentes S01-T17/T19, classificação de dados/tempo de retenção/alerta | Sem testes/parecer executados | CORE06/SEG/QA | C03-P02 |
| I13 | Cruzamento entre tenants | IDOR, mesmo CNPJ em tenants distintos, revoked membership, objetos indiretos | Roteiro S01-T04/T05/T06/T07/T16/T20, CORE01-T01/T05/T09 e CORE02-T02 | Nenhum teste executado | QA/SEG/DBA | C03-P01/P02 |
| I14 | Risco, independência e gate | Parecer de SEG/DBA e revisão independente quando necessária, formalização de risco residual | Identidade/competência de revisor, escopo, evidência, decisão específica de gate; não por acumulação G01..G06 | Nenhum parecer independente comprovado | G04/G06 internos + revisor competente conforme requisito | C03 e gates |

## 3. Fronteira entre plano, exame de repositório e prova real

| Onda | Atividade | Resultado auditável esperado | Estado de autorização |
|---|---|---|---|
| V1 — Cotejo documental GitHub | Revisar contratos CORE01/02/03/04, fichas C03, A07/A08 e suas versões/precedência | Manifesto de referência documental e inconsistências específicas com path, branch e blob | Permitido como trabalho documental sob decisão atual; **não é auditoria física** |
| V2 — Levantamento somente leitura de código | Inspecionar árvore de código, migrations, models, policies, jobs, views, scripts e CI para confirmar o que existe | Relatório inventário real por path/SHA e classificação conhecida/desconhecida | **Depende de autorização de escopo apropriada**; não confundir com autorização de código ou testes |
| V3 — Diagnóstico efetivo do banco/pool | Inspecionar roles, privilégios, RLS ativo, objetos SQL, conexões e configurações em ambiente aprovado | Matriz role/objeto/policy e superfícies de exceção verificadas | **Não autorizado agora**; sujeito a autorização, acesso mínimo, ambiente seguro e critérios SEG/DBA |
| V4 — Evidência dinâmica/QA | Executar cenários originais de isolamento, revogação, falhas e concorrência | Relatório resultados reais, riscos, correções, parecer e decisão formal de gate | **Não autorizado**; requer código, ambiente e decisão formal futura |

**Regra de passagem:** resultados de uma onda não permitem iniciar a onda seguinte automaticamente. Cada saída deve citar arquivo/commit, requisito vigente, evidência e sua limitação. Não considerar documento conceitual como evidência de um banco configurado. A onda V2 é apenas leitura e análise do repositório, sem compilar, testar ou rodar SQL; V3/V4 exigem autorização específica distinta.

## 4. Modelo de registro de lacuna

Para cada achado no futuro: `check_id`, `contract_id`, fonte oficial/owner, documento de regra e SHA, alvo examinado, versão, capacidade real confirmada ou **NÃO VERIFICADO**, evidência não sensível, impacto no tenant A/B, categoria de risco, dependência CORE/SEG/DBA, ação proposta, status (ABERTO/EM REVISÃO/FECHADO POR EVIDÊNCIA) e revisão competente. Nunca publicar credenciais, dados reais, segredos de produção ou logs pessoais.

## 5. Regras para não criar falsos positivos

- Ausência de arquivo em uma branch não prova ausência no sistema implantado; inventário V2 deverá registrar branch/commit preciso e sua abrangência.
- Documento pode afirmar RLS aprovado conceitualmente sem que exista RLS ativa. Anotar **NÃO VERIFICADO**, nunca PASS.
- Um `tenant_id` no contexto da aplicação ou GUC no banco **não é prova de autenticação ou de isolamento**, sobretudo sob role owner/BYPASSRLS.
- Mesma pessoa acumulando G01–G06 responde pela coordenação, mas não equivale a revisão externa ou independente; exigir declaração de conflitos de interesse e parecer independente onde a regra demandar.
- Não confundir negativa S01 com exclusão de dados, impedimento de regularização ou perda do direito à portabilidade, cuja disciplina pertence a contratos próprios.

## 6. Resultado atual e próxima ação justificável

**A09 produzido, mas suas 14 verificações permanecem NÃO EXECUTADAS**, pois são critérios para etapas posteriores. A tarefa seguinte de maior utilidade, caso a direção autorize explicitamente, seria uma **auditoria estática somente leitura do repositório na branch indicada**, levantando caminhos de código, migrations e objetos reais versionados, sem tocar main, PRs, VPS ou banco. Ela **não** autoriza consulta ao DB nem teste funcional.

## 7. Preservações

**R02-C01..C08 PENDENTES; 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.**

Sem SQL, `ALLOW`/`GRANT`, modificações de código, scripts, CI, merges, testes, inspeção da VPS, credenciais, produção, dados reais ou mudanças de cobrança. Apenas arquivo Markdown na branch documental isolada.