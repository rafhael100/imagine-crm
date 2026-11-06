# FUNC-P0-01/S01-R07 — Painel de evidências documentais e pendências (v0.1)

**Data:** 06/11/2026. **Natureza:** índice documental de fontes verificadas e lacunas de homologação; não representa liberação técnica.
**Escopo:** autoconsulta S01 de apenas dois campos conceituais da própria organização e do próprio vínculo ativo, condicionados a autenticação/autorização futura.

## 1. Entregas de evidência nesta etapa

- [EVID01 — Cotejo da pauta literal R01 e de fontes comerciais](./CRM-FUNC-P0-01-S01-R07-EVID01-cotejo-fontes-r01-comercial-v0.1-2026-11-06.md): R01-D01..D05 alternativas A localizadas e transições documentais comparadas; não homologadas.
- [EVID02 — Pesquisa rastreável da IN-092](./CRM-FUNC-P0-01-S01-R07-EVID02-rastreamento-IN092-2026-11-06.md): não localizada como arquivo independente nas árvores das 15 branches atuais CRM e main do Portal; menção histórica em REV-02 existente, fonte original ainda pendente.
- Oito fichas separadas com documentação efetivamente lida, link/branch/SHA da fonte, owner candidato, risco, prova futura e lacunas abertas.

## 2. Fichas individuais

| Contrato | Tema | Documento | Estado |
|---|---|---|---|
| C01 | Autenticação/MFA | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C01-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |
| C02 | Principal/tenant/membership | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C02-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |
| C03 | Aplicação/RLS/revogação | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C03-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |
| C04 | Campos mínimos e não enumeração | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C04-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |
| C05 | Proveniência comercial | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C05-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |
| C06 | Direitos e entitlements | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C06-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |
| C07 | Conciliação/concorrência | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C07-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |
| C08 | Auditoria/LGPD | [Abrir ficha](./CRM-FUNC-P0-01-S01-R07-FICHA-C08-evidencias-v0.1-2026-11-06.md) | **PENDENTE** |

## 3. Situação das lacunas

| Lacuna | Resultado verificável | Pendente |
|---|---|---|
| GAP-01 | R01 original encontrado na DEV, incluindo R01-D01..D05-A e matriz S01-T01..T22 | Integração final de precedência, autorização e homologação |
| GAP-05 | REV-02, IN-120 e COM-09 distinguem formalmente cancelamento CRM e cancelamento de site | IN-092 histórica não localizada; contratos físicos e revisão jurídica/FIN/CON |
| GAP-06 | IN-119/122 e COM-08/09 especificam regras de múltiplos sites e duas transições | Eventos oficiais, eficácia, idempotência e reconciliação homologadas |
| GAP-02..04 | Fonte R01, REV-01 e CORE01/02/04 dão bases documentais de IAM/RLS/campos | Políticas específicas, owners e provas de segurança |
| GAP-07 | CORE03 e INT-GATE trazem contratos a conciliar | Versão, autoridade, eventos fora de ordem e decisões de correção |
| GAP-08 | CORE06 e IN-162 distinguem auditoria e logs por categoria | Classificação LGPD/acesso/prazos/descarte específicos |
| GAP-09 | Responsabilidades por função candidatas identificadas | Nomeação e aceite formal por contrato |
| GAP-10 | 22 cenários de S01 localizados em documento | **Nenhuma homologação ou teste funcional executado** |

## 4. Próximas resoluções concretas

1. Localizar o texto original IN-092 no histórico de commits ou obter arquivo-fonte do responsável; **não criar versão presumida**.
2. Identificar nominalmente owner/autoridade de cada contrato C01..C08 e obter aceite específico, sem delegação automática.
3. Consolidar quadro de eventos de produto/contrato/tenant entre FIN/CON CRM, Portal e INT, com fato oficial e relógio independente; só documentação.
4. Completar fichas individuais com comprovação documentada de requisitos, ameaças e aceites competentes, sem executar testes.

## 5. Limites mandatórios

**R02-C01..C08 permanecem PENDENTES; 67 IDs técnicos CORE e os gates SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD e DEV-GATE seguem ABERTOS.** As ratificações R01..R07 são somente diretrizes documentais preliminares.
Não alterar `main`, PR #2, PR #21, branch DEV, código, ALLOW, SQL, migrations, testes funcionais, CI, VPS ou produção. Este painel e suas fichas não autorizam implementação.
