# CRM-DEC — FUNC-P0-01/S01-R06 — Ratificação preliminar R06-D01 a R06-D08 (alternativa A)

**Data da manifestação:** 06/11/2026.  
**Evidência de decisão:** resposta expressa **“aprovado”** à solicitação imediatamente anterior de aprovação das alternativas A de **R06-D01 a R06-D08**, **somente** como diretrizes documentais preliminares.  
**Pauta:** [R06 — Responsabilidades, precedência e pendências de homologação, proposta v0.1](CRM-FUNC-P0-01-S01-R06-responsaveis-precedencia-pendencias-homologacao-proposta-v0.1.md), seção 7.  
**Registro:** branch isolada `docs/func-p0-01-s01-r01-r02-2026-11-06`; não há atualização de `main`, alteração dos PRs #2/#21 ou implantação.

## 1. Alternativas A ratificadas, individualmente

| ID | Alternativa | Alcance aprovado somente no plano documental |
|---|---|---|
| **R06-D01** | **A** | Exigir confirmação de owners e competência documental por R02-C01..C08 antes de qualquer aceite de domínio. Não nomeia owners. |
| **R06-D02** | **A** | Preservar autoridade IAM/membership distinta de FIN/CON e Portal, e autorização por CORE/SEG independente de estado comercial. |
| **R06-D03** | **A** | Mapear fonte oficial, versão, eficácia e precedência de eventos CRM/Portal/INT. Não aprova eventos físicos. |
| **R06-D04** | **A** | Restringir S01 aos dois campos mínimos quando autorizados, sem enumeração de organizações ou vínculos alheios. |
| **R06-D05** | **A** | Conciliar conflitos por autoridade competente, sem autoconcessão nem suspensão arbitrária de direito previamente comprovado. |
| **R06-D06** | **A** | Manter fichas R05 versionadas; cenários negativos permanecem critérios documentais até autorização técnica distinta. |
| **R06-D07** | **A** | Revisar auditoria, acesso, retenção e descarte por categoria/finalidade com LGPD/SEG. |
| **R06-D08** | **A** | Manter C01..C08, 67 CORE, SEG, AUT e demais gates abertos; homologação posterior independente, com evidências. |

**Alternativas B não aprovadas.** A pauta R06 original permanece minuta histórica; esta decisão formaliza apenas as escolhas A. A presente ratificação não aprova integralmente a redação de cada contrato, owners, testes ou implementação.

## 2. Controles e impedimentos

- **R02-C01..C08 continuam todos PENDENTES**; owners candidatos não se tornam nomeações ou sign-offs.
- **67 IDs técnicos CORE continuam ABERTOS**, assim como SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE global e demais gates. Não diminuir o inventário nem baixar pendências automaticamente.
- S01 segue **autoconsulta humana de somente leitura**, exclusivamente da própria organização corrente e do próprio vínculo, com dois campos conceituais mínimos. Nada de dados financeiros, listas de tenants, terceiros ou privilégios globais.
- Sem `ALLOW`/`GRANT`, SQL, schema, migrations, código, API, testes funcionais, CI, pagamentos, dados reais, credenciais ou deploy.
- `main`, PR #2, PR #21, branch `dev/fast-track-crm-2026-11-04`, VPS e produção permanecem **fora do escopo** e não recebem ações desta decisão.
- Pauta literal original R01-D01..D05 ainda carece de cotejo com fonte canônica; diferenças de precedência FIN/CON, Portal, INT e LGPD permanecem não resolvidas.

## 3. Próxima etapa **não aprovada**

Preparar [FUNC-P0-01/S01-R07 — inventário de divergências e critérios documentais de prontidão](CRM-FUNC-P0-01-S01-R07-divergencias-prontidao-homologacao-proposta-v0.1.md), sem declarar contrato de segurança concluído. Suas alternativas R07-D01..D08 serão propostas a avaliar, não uma aprovação automática.

**Conclusão:** ratificação somente preliminar, sem homologação e sem liberação de desenvolvimento.