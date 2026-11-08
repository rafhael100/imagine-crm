# CRM Imagine — FUNC-P0-01 / S01-R02 — Ratificação documental R02-D01..D05

**Data da decisão:** 08/11/2026 (America/Sao_Paulo).  
**Manifestação da Direção:** “todas aprovadas”, imediatamente após a apresentação das cinco propostas **R02-D01 a R02-D05**.  
**Estado:** **CINCO DIRETRIZES DOCUMENTAIS APROVADAS**, sem homologação técnica e sem ordem executiva.  
**Fonte de escopo:** [S01-R02 proposta](CRM-FUNC-P0-01-S01-R02-contratos-exibicao-estados-e-evidencias-proposta-2026-11-08.md); [R01-D01..D05 ratificadas documentalmente](CRM-FUNC-P0-01-S01-R01-DEC-aprovacao-documental-D01-D05-2026-11-08.md); [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22).

## 1. Decisões ratificadas

| ID | Diretriz aprovada | O que não fica autorizado ou homologado |
| --- | --- | --- |
| **R02-D01** | Adotar **UI-01 a UI-04** como estados **documentais** de apresentação: carregando, resumo validado futuro, indisponível/negado e revalidação. | Não criar tela funcional, consultar sessão/tenant real ou interpretar UI-02 como `ALLOW`. UI-05 continua exemplo de demonstração sintética, não estado operacional. |
| **R02-D02** | Antes do primeiro código funcional, exigir **owner responsável, fonte oficial, classificação de privacidade e política por campo** para CAM-01 (nome de apresentação) e CAM-02 (vínculo próprio ativo). | Não declarar owners designados, campos homologados, consentimentos/base legal definida, DTO, API, SQL ou exibição real. |
| **R02-D03** | **EST-05..EST-12** continuam sem regra final de liberação ou bloqueio comercial, até parecer e decisão de CORE08/FIN/CON/Privacidade. | Não conceder ou negar direitos legais ou comerciais por suposição em trial, cortesia, adimplência, suspensão, cancelamento e retenção. |
| **R02-D04** | Aceitar somente a **rastreabilidade documental** S01-C01..C06 ↔ S01-T01..T22 e o formato de evidências futuras. | Não autorizar nem considerar executados os 22 testes de homologação. |
| **R02-D05** | Autorizar o **preparo de uma proposta de escopo** para protótipo **offline navegável, apenas fictício**, em etapa separada. | **Não autoriza implementar protótipo**, criar branch/PR de código, modificar Laravel, instalar dependências, simular credenciais reais, hospedar UI ou testar módulos funcionais. |

## 2. Limites preservados

1. A primeira fatia proposta (**FUNC-P0-01 / recorte A**) é autoconsulta do próprio tenant corrente e do próprio vínculo humano, somente leitura. O resultado visível futuro é limitado a **CAM-01/CAM-02**; CAM-03..10 ficam ocultos.
2. O `DenyByDefaultAuthorizer` segue exclusivo e **deny-only**. Não conceder `ALLOW`, ler/escrever dados de usuários reais, construir API/login/SSO, operar SQL ou modificar papéis, migrations, sistema de permissões, cobrança, leads ou integrações.
3. CORE02-D05 B/P1+P2 e GOV01 M2 continuam **documentais**; GOV02 ainda não foi aprovada; o protótipo não pode fingir cumprimento de CORE/SEG/LGPD.
4. Todos os **67 CORE** e respectivos critérios técnicos, SEG-01/02, [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), AUT-P01..14, ARQ/INT/FIN/CON/LGPD, DEV-GATE e G01..G06 permanecem **abertos**. Falta owner técnico/privacidade e parecer SEG/DBA independente quando exigido.
5. Preservar `main`, `dev/fast-track-crm-2026-11-04`, PRs #2/#21/#23..#30 (sem merge), VPS Leaseweb, ambientes comerciais e clientes; não provisionar infra nem gerar despesas.
6. Uma demonstração futura com textos “Empresa Exemplo”, “Seu vínculo: Ativo” e marcação “Protótipo — dados fictícios” não comprovará sessão, autorização, RLS, plano nem habilitação de conta real.

## 3. Encaminhamento imediato autorizado

**Somente preparação documental:** elaborar especificação de protótipo (objetivo, páginas, estados simulados, navegação, componentes, critérios UX, acessibilidade, dados fictícios, limites, trilha de revisão, opção de branch de código futura) para **nova deliberação expressa**. Não criar código, branch executiva ou PR de implementação com base nesta decisão.

**Regra de precedência:** esta decisão substitui somente “R02-D01..D05 NÃO APROVADAS” da versão histórica da proposta S01-R02. Os limites técnicos, a ausência de homologação e as proibições dessa mesma proposta continuam vigentes.
