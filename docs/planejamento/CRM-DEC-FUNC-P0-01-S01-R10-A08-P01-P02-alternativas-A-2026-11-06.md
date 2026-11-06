# CRM-DEC — FUNC-P0-01/S01-R10-A08 — Ratificação das alternativas A P01-D01..D04 e P02-D01..D04

**Data:** 06/11/2026 (America/Sao_Paulo). **Natureza:** registro de aceitação expressa de **diretrizes documentais preliminares**. Não homologa contrato, teste, política executável, revisão especializada ou gate.

**Pergunta imediatamente anterior:** “você aprova as alternativas A de P01-D01 a P01-D04 e P02-D01 a P02-D04, exclusivamente como diretrizes documentais preliminares?”
**Resposta literal do usuário:** **“aprovado”**.

**Fonte da deliberação:** [R10-A08 — Inventário lógico e ciclo de contexto C03-P01/P02](CRM-FUNC-P0-01-S01-R10-A08-inventario-roles-contexto-conexoes-C03-P01-P02-proposta-v0.1-2026-11-06.md), blob `32925d36ffd81446f938409ba5c08d19189c76f9`. A classificação da minuta como 'NÃO APROVADA' representa o estado anterior à decisão e é superada **somente quanto às alternativas A listadas**.

## 1. Decisões aprovadas exclusivamente como diretrizes

| ID | Orientação ratificada | Limite obrigatório |
|---|---|---|
| **P01-D01 — A** | Inventário inicial de objetos OBJ01..OBJ10 por classificação lógica; mapeamento físico real segue pendente | Documento de planejamento; não é autorização de ação técnica |
| **P01-D02 — A** | Seis classes semânticas de papéis ROLE01..ROLE06, evitando owner/BYPASSRLS no runtime comum, sem criar roles reais | Documento de planejamento; não é autorização de ação técnica |
| **P01-D03 — A** | Abrangência de views/funções/relacionamentos, arquivos, caches, backups e canais indiretos; exceções globais por justificativa | Documento de planejamento; não é autorização de ação técnica |
| **P01-D04 — A** | Autoridades distintas para identidade, empresa, membership, contrato e serviço; CNPJ/pagamento/site não concedem acesso cruzado | Documento de planejamento; não é autorização de ação técnica |
| **P02-D01 — A** | Contexto confiável por operação e fronteira transacional de conexão futura, ainda sem mecanismo físico homologado | Documento de planejamento; não é autorização de ação técnica |
| **P02-D02 — A** | Ciclo de conexão, execução, commit/rollback, exceção e limpeza; falha/mistura de tenants resulta em negativa | Documento de planejamento; não é autorização de ação técnica |
| **P02-D03 — A** | Jobs/filas com autoridade técnica e revalidação conforme classe CORE03; não reutilizar sessão humana nem paralisar rotinas oficiais indiscriminadamente | Documento de planejamento; não é autorização de ação técnica |
| **P02-D04 — A** | Evidências futuras cobrindo RLS, roles, pool, cache, concorrência, sem testes executados e com parecer competente/independente quando exigido | Documento de planejamento; não é autorização de ação técnica |

**As alternativas B não estão aprovadas.** Os 10 grupos OBJ, 6 classes ROLE e 8 fases CTX são **inventário conceitual**. Não constituem prova de que objetos, roles ou mecanismos estejam presentes no código ou VPS.

## 2. O que continua aguardando comprovação

- **C03-P01 físico:** levantamento das tabelas, views, funções, donos, permissões de conexão, índices, FKs, casos globais, storage/cache, integração e pontos de bypass efetivos; não inspecionado nesta decisão.
- **C03-P02 físico:** estratégia exata de contexto de tenant no PostgreSQL, transação/pool, forma de limpeza após rollback/erro, workers, versionamento/revogação e efeitos de concorrência; não selecionada nem testada.
- **CORE01-D04..D07, CORE02-D04..D10, CORE03-D04..D10, SEG-01/02, DBA, CORE10/DEV-GATE, LGPD e demais dependências técnicas continuam abertos.**
- **Todos os contratos R02-C01..C08 ficam PENDENTES, 67 CORE e demais gates ABERTOS.** Um mesmo responsável interno G01..G06 não comprova independência das revisões, quando necessária.
- Não executar S01-T01..T22 nem CORE01/CORE03, não marcar resultados sem execução, não publicar SQL ou ativar `ALLOW`/`GRANT` por inferência.

## 3. Continuidade documental permitida

Elaborar [R10-A09 — Plano de cotejo verificável das lacunas C03-P01/P02](CRM-FUNC-P0-01-S01-R10-A09-plano-verificacao-c03-p01-p02-v0.1-2026-11-06.md) e registrar quais evidências seriam necessárias para autorizar **separadamente** qualquer inspeção de ambiente, programação ou execução de testes. A ratificação não autoriza inspeção de VPS, criação/edição de código, banco, CI, PR ou deploy.

## 4. Fronteiras preservadas

Somente registro Markdown na branch `docs/func-p0-01-s01-r01-r02-2026-11-06`; não alterar `main`, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, SQL/DDL/RLS real, permissões, credenciais, Código Laravel, CI, testes, VPS, produção, dados de clientes ou cobrança.