# CRM-DEC — FUNC-P0-01 / S01-R01 — Ratificação preliminar R01-D01 a R01-D05 (alternativa A)

**Data da decisão:** 06/11/2026.  
**Tipo:** ratificação documental preliminar — **não** aprovação técnica, homologação, contrato executável, autorização de implementação ou fechamento de gate.  
**Origem:** manifestação expressa do responsável no ChatGPT, de 06/11/2026.  
**Registro:** branch documental isolada `docs/func-p0-01-s01-r01-r02-2026-11-06`, originada do commit `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012` da `main`, sem atualização da própria `main` ou das branches dos PRs #2 e #21.

## 1. Decisão expressa

> Aprovo as alternativas A de R01-D01 a R01-D05 do FUNC-P0-01/S01-R01 exclusivamente como diretrizes documentais preliminares. Registre a ratificação no GitHub, preserve os 67 CORE, SEG, AUT e demais gates abertos, mantenha main, PRs #2/#21 e VPS inalterados, não implemente código, ALLOW, SQL nem testes funcionais. Prossiga para o próximo detalhamento documental dos estados comerciais e contratos de segurança pendentes para minha avaliação.

| Decisão | Alternativa ratificada | Natureza e limite |
|---|---|---|
| FUNC-P0-01/S01-R01-D01 | **A** | Diretriz **documental preliminar**; detalhes físicos, políticas e homologação abertos. |
| FUNC-P0-01/S01-R01-D02 | **A** | Diretriz **documental preliminar**; detalhes físicos, políticas e homologação abertos. |
| FUNC-P0-01/S01-R01-D03 | **A** | Diretriz **documental preliminar**; detalhes físicos, políticas e homologação abertos. |
| FUNC-P0-01/S01-R01-D04 | **A** | Diretriz **documental preliminar**; detalhes físicos, políticas e homologação abertos. |
| FUNC-P0-01/S01-R01-D05 | **A** | Diretriz **documental preliminar**; detalhes físicos, políticas e homologação abertos. |

**Limite de literalidade:** o teor integral da pauta original de alternativas de R01-D01..D05 não foi localizado em arquivo canônico do repositório na presente consulta. Este documento registra a **escolha explícita A para cada ID**, sem inventar título, conteúdo ou efeito de cada alternativa. Antes da consolidação canônica, cotejar a pauta original e anexar o texto literal versionado; nenhuma descrição inferida substitui tal conferência.

## 2. Contexto funcional preservado, sem atribuí-lo indevidamente aos cinco IDs

O recorte A de FUNC-P0-01/S01 trata de **autoconsulta humana somente leitura** da própria organização e do próprio vínculo, no CRM, sem seletor que revele outros tenants e sem acesso a dados de terceiros. Orientações documentais já expressas para S01: sessão humana válida, vínculo ativo, contexto de organização confirmado pelo servidor, isolamento no backend/PostgreSQL RLS e revalidação; MFA conforme a política especializada futura. A apresentação mínima em discussão contempla **nome de exibição da própria organização** e **estado do próprio vínculo**.

Essas referências de S01 permanecem sujeitas a CORE, SEG, AUT, contratos de campos/estado, legislação, consentimento quando aplicável e revisão de precedência. **Não** se presume que os campos acima constituam o texto integral das alternativas R01-D01..D05, nem que esta ratificação autorize estados comerciais, cobrança ou permissão adicional.

## 3. Gates e superfícies expressamente preservados

- **67 IDs técnicos CORE continuam abertos**, inclusive critérios de implementação/homologação; não recalcular nem reduzir esse quantitativo por esta ratificação.
- **SEG-01, SEG-02, AUT**, CORE01/CORE02 e demais famílias CORE, ARQ-01/02/03, AGT-01, contratos INT e gates G01–G06, FIN/CON, LGPD e DEV-GATE global permanecem abertos conforme suas matrizes; não alterar status ou precedências.
- **Sem código, testes funcionais, SQL, migration, GRANT/ALLOW, novas permissões, feature flag funcional, sessões reais, cobrança, integração real, CI ou deploy.**
- `main`, branches e metadados dos **PRs #2 e #21**, branch `dev/fast-track-crm-2026-11-04` e **VPS/produção** fora do escopo e intocados por este registro.
- A ratificação não resolve conflitos entre documento antigo e novo; prevalece cotejo específico com os responsáveis de domínio antes de homologação.

## 4. Encaminhamento: próximo detalhamento **não aprovado**

Ver proposta independente [FUNC-P0-01/S01-R02 — estados comerciais e contratos de segurança](CRM-FUNC-P0-01-S01-R02-estados-comerciais-contratos-seguranca-proposta-v0.1.md).

Esse material é **minuta para avaliação**, não decorrência automaticamente ratificada por R01-D01..D05. As decisões R02-D01..D08 e os contratos conceituais propostos permanecem **PENDENTES**, sem novos direitos ou implementação.

## 5. Evidência do alcance do registro

Alteração restrita à **documentação na branch isolada**, sem PR/merge. Não declarar testes executados, inspeção da VPS ou homologação de segurança. A verificação externa de ausência de alteração de PRs e `main` será feita por leitura de metadados antes/depois.
