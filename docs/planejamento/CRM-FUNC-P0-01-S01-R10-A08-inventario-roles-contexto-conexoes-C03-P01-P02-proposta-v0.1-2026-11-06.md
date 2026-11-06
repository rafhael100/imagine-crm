# CRM Imagine — FUNC-P0-01/S01-R10-A08 — Inventário lógico e ciclo de contexto C03-P01/P02 (proposta v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Natureza:** DOCUMENTAÇÃO DE PLANEJAMENTO TÉCNICO; **NÃO APROVADA NESTA VERSÃO**; não implementa código, banco, roles, SQL, `ALLOW`, GRANT, teste, CI ou deploy.

**Mandato desta etapa:** usuário respondeu 'ok vamos para a proxima atv' à proposta expressa de elaborar o **inventário C03-P01/P02**, sem autorização de desenvolvimento ou homologação. A aceitação autoriza **produzir a minuta**, não a escolha final de tecnologia ou concessão de acesso.

## 1. Fontes com precedência e escopo

- [R10-A07 — análise C03](CRM-FUNC-P0-01-S01-R10-A07-contrato-conceitual-C03-isolamento-autorizacao-proposta-v0.1-2026-11-06.md) blob `2089149d5a11c68687831a5ba5f6e4e302a829f9`; C03 segue PENDENTE.
- [CORE01 — identidade/membership/RLS](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) blob `ec7a03e38fedc3514eaa2c91c6def80d0ac6dba1` (I01..I10, D01..D03 base; D04..D07 abertos).
- [CORE02 — ação/recurso/campo](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) blob `6222b2676f5ae59282c36ee607a3f9e62dd2ead6` (D01..D03 base, D04+ abertos).
- [CORE03 — jobs/serviços/autoridade](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md) blob `3266eedea35f9d58f0b1fc85f4c991bc8400994a` (D01..D03 base, D04..D10 abertos).
- [R10-A06 — ordem documental dos oito contratos](CRM-FUNC-P0-01-S01-R10-A06-sequenciamento-contratos-e-gates-proposta-v0.1-2026-11-06.md); [ficha C03 R07](CRM-FUNC-P0-01-S01-R07-FICHA-C03-evidencias-v0.1-2026-11-06.md).
- **R10-A02/A03/A04/A05** aprovadas exclusivamente como diretrizes documentais preliminares. Este A08 detalha riscos e escolhas novas, sem reabrir nem estender tais aprovações.

## 2. P01 — Inventário inicial de objetos lógicos e fronteiras

Os grupos OBJ01..OBJ10 são **classes candidatas** a mapear; nenhum item prova existência de tabela, coluna, view, índice ou bucket no repositório/servidor.

| ID | Objeto/família candidato | Fronteira proposta | Owner por competência | Risco/controle de isolamento | Contratos | Bloqueio verificável |
|---|---|---|---|---|---|---|
| OBJ01 | Identidade global, autenticadores e sessões | Global/control plane | IAM/CORE04 | Acesso global controlado por finalidade; não é leitura irrestrita dos tenants | C01/C02 | Catálogo físico/atributos sensíveis pendente |
| OBJ02 | Organizações CRM e memberships | Organização e relações identidade×tenant | CORE01/CORE07 | Tenant estável, membership ativa por pessoa; um CNPJ pode ter tenants separados | C02/C03 | Mapeamento de tabelas, estados e FKs pendente |
| OBJ03 | Papéis/atribuições/permissões | Tenant-bound com regras globais excepcionais | CORE02/SEG | Ação+recurso+campo+escopo e negações aplicáveis; não inferir de papel nominal | C03 | Matriz física/precedência CORE02-D05 pendentes |
| OBJ04 | Leads, contatos, funis, atividades, atendimentos e oportunidades | Tenant-bound | Donos DAT/VEN/ATD/CRM | Sem leitura/mesclagem/acesso cruzado por ID; escopo de dono/equipe quando houver | C03 | Inventário por módulo/tabela e classificação LGPD pendentes |
| OBJ05 | Arquivos/anexos, relatórios, exports e backups | Tenant-bound com artefatos derivados | CORE02/INT-08/IN118/CORE06 | Metadados e objetos de armazenamento devem preservar tenant e autorização de ação específica | C03/C08 | ACL de object store, URLs, storage externo e lifecycle pendentes |
| OBJ06 | Assinaturas, planos, contratos, trial, cortesia e financeiro | Dados de organização + catálogos globais | CORE08/FIN/CON; Portal para contrato do site | Autoridade comercial separada do IAM; ACK não quita fatura nem concede membership | C05/C06 | Identificar fatos oficiais e exceções de objetos compartilhados |
| OBJ07 | Integrações, conectores, webhooks, jobs, automações e agentes | Tenant-bound e service principals segregados | CORE03/INT/CORE09/AUT | Principal técnico não simula pessoa S01; escopo por mandato/versão/tenant | C03/C07 | Catálogo de canais/papéis operacionais e delegação por tarefa pendentes |
| OBJ08 | Log, auditoria, incidentes e evidências | Por tenant/categoria com exceções globais protegidas | CORE06/SEG/LGPD | Sem dados excessivos, token, segredo, IDs terceiros; controle por finalidade e retenção | C08 | Mapa dados/categorias/retencões e requisitos de auditoria pendentes |
| OBJ09 | Catálogos/provedores/configurações gerais Imagine | Global excepcional | Admin Geral/CORE05/INT | Acesso global estritamente separado; não dá acesso genérico a dados dos clientes | C03 | Taxonomia global e responsáveis por exceção pendentes |
| OBJ10 | Views, funções, materializações, buscas, caches e relacionamentos | Dependente da origem; superfície indireta | CORE01/03/SEG/DBA | Cobrir caminhos que possam contornar RLS, executar com privilégios ampliados ou juntar tenants | C03 | Objetos e privilégios efetivos devem ser inventariados antes de SQL |

### Checklist físico futuro por objeto (A PREENCHER, não efetuado)

Para cada objeto real: caminho/commit ou esquema autorizado; módulo e owner; classificação global/tenant-bound/híbrido; forma de resolução do tenant; chaves/FKs e riscos de mistura; views/funções/policies e modo de privilégio; roles com acesso; operação/ação/campo e escopo; armazenamento/cache/backup; tratamento de revogação; evidências de revisão e testes. **Nenhum inventário físico foi realizado nesta entrega**.

## 3. P01 — Classes candidatas de papéis e autoridade técnica

| ID | Classe funcional de role (NÃO nome SQL aprovado) | Âmbito | Proteção proposta | Contratos pendentes |
|---|---|---|---|---|
| ROLE01 | Aplicação CRM — leituras/escritas normais | Tenant-bound runtime | Menor privilégio e sem BYPASSRLS/owner para acesso cotidiano; identidade e ação verificadas no Laravel | Nome de role, escopo de GRANT, hierarquia e provisionamento pendentes |
| ROLE02 | Executor de jobs e filas CRM | Serviço técnico | Não usar sessão humana como credencial permanente; contexto validado por tarefa e rechecagem antes de efeitos | Separação física de ROLE01, revisão de mandato e fila pendentes |
| ROLE03 | Ingressos externos e integrações | Serviço técnico de fronteira | Validar origem, contrato e destino; autorização no CRM; sem autoescolha por payload | Role/secret e separação API ↔ CRM não especificadas |
| ROLE04 | Migrador/DDL e manutenção excepcional | Privilegiado, fora do runtime normal | Role privilegiada controlada e auditada; não usado por aplicação comum; sem presunção de bypass seguro | Privilégios, janela, revisão e segregação SEG/DBA pendentes |
| ROLE05 | Suporte excepcional Imagine | Mandato humano/técnico limitado | Por caso, finalidade, escopo, duração e auditoria; não role SQL geral de leitura | CORE05, credenciamento e revisão competente pendentes |
| ROLE06 | Auditoria e recuperação autorizadas | Serviço ou pessoa com competência especial | Consulta de evidência/backup não vira permissão comercial; política de retenção/restauração e reconciliação por tenant | CORE06/LGPD/SEG, acesso e trilha de auditoria pendentes |

**Risco específico a examinar:** PostgreSQL distingue privilégios de superuser, BYPASSRLS e proprietário de tabela; a revisão deverá analisar permissões, propriedade e, se relevante, FORCE ROW LEVEL SECURITY. Isso não equivale a aprovar comandos, criar role ou declarar que o runtime atual já está protegido. Views, funções com privilégios elevados e outras rotas indiretas exigem exame próprio.

## 4. P02 — Ciclo de contexto de operação e conexão (fluxo conceitual)

| Fase | Momento | Fonte/controle esperado no desenho | Negativa ou ameaça a cobrir | Rastreamento a cenário futuro existente |
|---|---|---|---|---|
| CTX01 | Requisição humana | IAM confirma identidade/sessão/MFA; IAM+CORE01 confirma tenant/membership, CORE02 confirma recurso/ação/campo/escopo | Tenant vindo de query, header ou cookie sem comprovação; sessão expirada | S01-T03..T11 |
| CTX02 | Antes da consulta de dado protegido | Contexto lógico imutável da operação, com tenant autorizado, ator, versão de vínculo/política e motivo | Sem autorização positiva específica ou contexto inconclusivo | S01-T04/T09/T11 |
| CTX03 | Aquisição da conexão/pool | Mecanismo futuro amarra contexto validado à transação ou fronteira equivalente e impede herança de outro request | Conexão de A reaproveitada em B, role com BYPASSRLS, contexto residual | S01-T16/T20; CORE03-T02 |
| CTX04 | Execução SQL protegida | Laravel autoriza operação e DB aplica isolamento tenant-bound e integridade de relações; objetos globais com exceções explícitas | Filtro ORM sozinho, view privilegiada, função sem contrato, relação cross-tenant | CORE01-T03/T09 |
| CTX05 | Commit, rollback ou exceção | Fronteira de contexto encerrada e comprovadamente descartada independentemente do desfecho; nenhuma nova transação assume autoridade anterior | Rollback que deixa contexto em conexão persistente ou dado antigo no cache | S01-T16/T17/T20 |
| CTX06 | Worker assíncrono e retries | Reconstituir principal técnico, tenant, operação e mandato a partir de evento oficial e revalidar direitos ao efeito conforme classe CORE03 | Identidade humana reaproveitada como token de serviço; evento assinado sem vínculo/autoridade | CORE03-T01/T02/T07/T13/T14 |
| CTX07 | Cache, exportações, artefatos derivados | Escopar por tenant+principal+autorização+versão conforme necessidade; revalidar antes de expor arquivo ou resultado | Resposta de tenant A servida para B, export com mais campos que tela | S01-T12/T13/T17/T21; CORE02-T03/T10 |
| CTX08 | Operação de manutenção/suporte | Fluxo de privilégio separado da operação comum, autorização de caso e auditoria; sem operar S01 como humano cliente | Admin Imagine ou ferramenta técnica usando role owner para ler CRM do cliente | CORE01-T07; CORE02-T01/T12 |

**Propriedades conceituais exigidas para estudo futuro:** (i) valor de tenant em GUC/variável de sessão **não autentica** pessoa ou serviço; (ii) contexto do SQL precisa estar preso à operação confiável e não vazar entre requisições; (iii) rollback, falha, retry e workers long-lived precisam fronteira clara de descarte; (iv) role/política pode invalidar toda a segurança mesmo com contexto correto; (v) o desenho físico final precisa considerar pooler e modalidade real de conexão, sem presumir compatibilidade; (vi) não prometer revogação global instantânea sem desenho de consistência.

## 5. Cenários adversos de decisão — não testes executados

| Incidente candidato | Fronteira exigida | Evidência futura necessária |
|---|---|---|
| Operador entra em A e tenta ID da empresa B | Autorização Laravel por operação + RLS do tenant | S01-T04/T05; CORE01-T01/T02 |
| App executa sob owner ou papel com BYPASSRLS | Role runtime e inspeção de política real | S01-T16; revisão SEG/DBA |
| Pool reutiliza contexto A em B, inclusive após erro | Atribuição/limpeza de contexto por fronteira de transação | S01-T16/T20; CORE03-T02 |
| Relação FK entre registros de tenants distintos | Integridade tenant-aware também em relações | CORE01-T09 |
| Usuário perde membership A com job em fila | Revalidação por classe de execução e efeito | S01-T07; CORE03-T01/T13 |
| Admin central/IA/n8n tenta simular usuário cliente | Credencial/mandato técnico distinto, sem personificação | S01-T14; CORE01-T07/T08 |
| Cache, relatório ou export retorna dados de B para A | Escopo por usuário/tenant/versão e autorização de canal | S01-T12/T13/T17/T21; CORE02-T03/T10 |
| PostgreSQL/Redis/autorizador indisponível | Falha segura da operação S01, não do CRM todo | S01-T17/T19; CORE03-T08 |

Os IDs acima apenas reutilizam **cenários futuros** já documentados; nenhum resultado foi executado, nenhum teste passou ou falhou nesta tarefa.

## 6. Oito decisões novas P01/P02 para avaliação futura — NÃO APROVADAS

| Código | Ponto | Alternativa A recomendada — somente diretriz documental | Alternativa B — não recomendada |
|---|---|---|---|
| **P01-D01** | Objetos e classificação | A: adotar OBJ01..OBJ10 como inventário inicial CONCEITUAL para análise de tenancy e exceções, exigindo levantamento físico por módulo antes de qualquer SQL. | B: considerar esse catálogo textual como inventário real completo ou liberar exceções automaticamente. |
| **P01-D02** | Papéis de execução | A: separar semanticamente ROLE01..ROLE06 por responsabilidade e risco, vetando no desenho uso rotineiro de owner/BYPASSRLS por serviço tenant-bound comum; privilégios reais somente após projeto SEG/DBA. | B: uma credencial SQL privilegiada e comum para aplicação, jobs e suporte. |
| **P01-D03** | Cobertura de superfícies indiretas | A: exigir análise de views, funções, FKs, arquivos, caches, buscas, relatórios e backups com isolamento e auditoria aplicáveis; exceções globais justificadas uma a uma. | B: limitar defesa a tabelas CRUD expostas na UI e confiar em filtro Eloquent. |
| **P01-D04** | Separação identidade/contrato/dado | A: reconhecer identidades globais, tenants distintos, membership própria, contrato/site, plano e service principal como autoridades diferentes, sem fundir organizações por CNPJ. | B: tratar mesmo CNPJ, pagamento ou vínculo de site como direito de leitura cruzada. |
| **P02-D01** | Contexto por operação | A: definir obrigatoriedade de contexto confiável por operação e uma fronteira transacional/conexão para execução SQL, com método físico ainda sujeito à revisão SEG/DBA. | B: usar variável de sessão/client header livre como autenticação e deixar conexão persistentemente contextualizada. |
| **P02-D02** | Ciclo de vida da conexão | A: especificar aquisição, atribuição, verificação, execução, commit/rollback e limpeza por request/worker; falha ou mistura de tenant bloqueia a operação. | B: presumir que o pool limpa tudo sem teste ou ignorar exceções/rollback. |
| **P02-D03** | Assíncronos e efeitos posteriores | A: cada job/evento reconstitui contexto técnico por mandato válido e aplica autorização do módulo ao efeito conforme classe CORE03, distinguindo atos humanos adiados de processos obrigatórios. | B: reutilizar sessão humana ou encerrar indistintamente todas as rotinas oficiais quando o colaborador é desligado. |
| **P02-D04** | Revisão e evidências futuras | A: vincular CTX01..CTX08 às suítes S01/CORE01/CORE03 originais, exigindo exame de BYPASSRLS, pool, concorrência, cache e integridade em ambiente autorizado; parecer independente quando exigido. | B: tomar documentação ou compilação como prova de isolamento e homologar o gate. |

**Aprovar eventualmente essas alternativas A não fecha C03, não cria tabelas/roles nem escolhe o mecanismo físico de execução SQL.** Cabe a uma autorização futura, posterior ao parecer competente, definir testes, código e infraestrutura em ambiente aprovado.

## 7. Lacunas e responsáveis efetivamente pendentes

- **P01-F1:** localizar e confirmar lista física por módulo, views/funções/materializações, tabelas globais e tenant-bound, relações, owners e exposição de buckets/arquivos; não inferir do modelo lógico.
- **P01-F2:** especificar papel real do runtime de aplicação, jobs, manutenção e suporte, privilégio de BYPASSRLS, propriedade das tabelas, políticas SQL e separação de credenciais; sem criar ou editar roles agora.
- **P02-F1:** avaliar ciclo exato de conexão/transação, pooling, savepoints/rollback, workers e reset; método SET LOCAL/GUC é **candidato**, não escolha executável.
- **P02-F2:** formalizar matriz recurso/ação/campo/escopo e fonte de permissão no CORE02; status de CNPJ, site, pagamento ou membership não concede `read_self` implicitamente.
- **P02-F3:** definir mecanismo de revogação e consistência/concorrência, efeitos assíncronos por classe CORE03 e proteção de cache/derivados.
- **P02-F4:** preparar revisão técnica de SEG/DBA, LGPD conforme campo e QA. **G01..G06 são assumidas pelo mesmo responsável internamente, sem parecer independente comprovado quando exigido.**

## 8. Resultado documental e próximos passos

**Resultado desta entrega:** inventário conceitual de 10 grupos de objetos, seis classes candidatas de papéis, oito fases de contexto/conexão e oito propostas de decisão. Nada disso afirma ter vistoriado o DB real ou o código. A próxima deliberação útil é sobre a lista específica P01/P02 **como diretriz documental**, sem repetir os mesmos princípios genéricos. Após isso, uma avaliação separada deverá determinar se há base para autorizar o **levantamento técnico somente de leitura** do esquema real; tal autorização não está dada neste documento.

## 9. Barreiras e preservações

**R02-C01..C08 permanecem PENDENTES; os 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates estão ABERTOS.** Funções R08-G01..G06 não são gates técnicos INT-G01..G06.

**NÃO** editar main, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, código, SQL, schema, RLS físico, credenciais, permissões/`ALLOW`/`GRANT`, CI, testes, VPS ou produção. Exclusivamente arquivo Markdown nesta branch de documentação.