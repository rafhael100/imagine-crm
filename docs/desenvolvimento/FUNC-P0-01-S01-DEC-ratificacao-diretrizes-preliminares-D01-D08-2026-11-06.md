# CRM Imagine — FUNC-P0-01 / S01 — Registro de aprovação documental das diretrizes D01..D08

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação expressa do responsável:** “Aprovo S01-D01, D02, D03 e as diretrizes gerais D04 a D08 do FUNC-P0-01 somente como orientações documentais preliminares. Mantenha todos os gates CORE/SEG/AUT e demais pendências abertos, não implemente código, ALLOW, SQL ou testes funcionais, não altere main, PRs #2/#21 nem VPS. Prossiga com o próximo detalhamento documental dos campos, estados e critérios de segurança para minha avaliação.”

**Natureza da decisão:** seleção e aceitação **apenas das orientações documentais preliminares de S01-D01, D02, D03 e do propósito geral de D04..D08**. Não há homologação de contratos operacionais ou de suas opções físicas; detalhes de campos/estados/risco/UX/governança ainda serão decididos. **A especificação S01 original não foi aprovada integralmente.**

**Referências:** [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22); [S01 proposta v0.1](FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md); [F1-10](F1-10-consolidacao-gates-seguranca.md); [PR documental CORE02 #21](https://github.com/rafhael100/imagine-crm/pull/21).

## 1. Diretrizes aprovadas preliminarmente

| Diretriz | Alcance documental aprovado | Decisões que NÃO foram aprovadas |
|---|---|---|
| **S01-D01** | Primeira superfície *dentro do CRM Imagine*, autoconsulta read-only do próprio tenant corrente comprovado e do próprio vínculo; não ter seletor de empresas neste recorte. | Tela/código, rotas, arquitetura de login, calendário de abertura de clientes, SSO, protótipo gráfico definitivo. |
| **S01-D02** | Minimização visual como orientação: nome de apresentação da organização + indicação genérica “Vínculo ativo”; campos além desses fora da apresentação inicial até aprovação própria. | DTO/JSON, fonte oficial de dados, classes finais LGPD/CORE02-D04, inclusão de papel, status financeiro, CNPJ, IDs ou outros usuários. |
| **S01-D03** | Exigência conceitual de sessão humana comprovada, membership ativa no tenant atual verificado server-side, revalidação, RLS não-bypass e deny-by-default. MFA proporcional conforme política futura. | Políticas técnicas executáveis, `ALLOW`, TTL, método de MFA, step-up, grants, SQL, RLS real, API pública ou testes de homologação executados. |
| **S01-D04** | Necessidade de detalhar autorização, precedência normativa CORE02-D05 alternativa B/P1+P2, M2 (governança), negações e seus limites. | Algoritmo concreto, roles, competência física, regras GOV02 A–D (ainda não aprovadas), criação de perfil/grant/deny. |
| **S01-D05** | Necessidade de definir contrato de estados por trial/cortesia/pago/suspenso/cancelado com CORE08, FIN/CON e direitos legítimos. | Matriz real de entitlement, acesso ou bloqueio de qualquer estado, prazo ou cobrança. |
| **S01-D06** | Necessidade de modelar ameaça, privacidade e auditoria proporcional antes de qualquer código. | Schema, retenção, base legal/consentimento presumida, criptografia/segredo, logs e infraestrutura de produção. |
| **S01-D07** | Necessidade de critérios positivos/negativos, owners e evidências rastreáveis, mantendo os 22 casos S01-T01..T22 como **propostas de teste futuro**. | Homologação, aprovação dos casos, execução de testes funcionais ou fechamento de gate. |
| **S01-D08** | Necessidade de autorização de implementação posterior, segregando branch/ambiente, validação, rollback e responsabilidade por eventual primeira política `ALLOW`. | Qualquer autorização efetiva de programação funcional, acesso a dados reais, promoção para `main` ou deploy. |

## 2. Precedência e governança

- **Recorte A** (autoconsulta da própria organização/vínculo) permanece o único recorte selecionado, apenas para documentação; opções B/C não foram selecionadas para programação.
- [PR #21](https://github.com/rafhael100/imagine-crm/pull/21) continua DRAFT para `main`: CORE02-D05 alternativa **B** (P1 união de grants positivos válidos com P2 negações explícitas escopadas) e CORE02-D05-GOV01 **M2** ratificados documentalmente; CORE02-D06..D10 A–D preliminarmente ratificados; **GOV02 A–D é proposta ainda não aprovada**. Nada se converte em `ALLOW` de runtime.
- **Todos os 67 IDs CORE**, CORE01..10, CORE02-D04..D10, SEG-01/02, AUT-P01..14, ARQ/INT/FIN/CON/LGPD, G01..G06 e DEV-GATE permanecem **ABERTOS**. A aprovação documental não substitui aceite de owner, segurança, DBA, privacidade ou produto.

## 3. Encaminhamento documental autorizado

Preparar a [S01-R01 — matriz de campos, estados e segurança](FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md), para **nova avaliação** da direção, com opções, exclusões, incertezas e critérios de homologação propostos. **Não** há autorização para emitir task de programação, código Laravel/SQL, política `ALLOW`, execução de testes funcionais, integração dos PRs #2/#21, alteração da `main` ou implantação na VPS.

**Regra efetiva presente:** `DenyByDefaultAuthorizer` continua único no runtime, negando efeitos de negócio. Apenas documentação poderá ser alterada na branch `dev/fast-track-crm-2026-11-04` nesta decisão.
