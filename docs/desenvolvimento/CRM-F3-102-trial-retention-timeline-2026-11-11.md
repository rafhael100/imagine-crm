# CRM F3-102 — Linha do tempo ilustrativa de trial e retenção

**Data:** 11/11/2026. **Ambiente:** somente DEV, PHP puro, dados sintéticos, sem banco, sessão ou identidade real.

## Objetivo

A aplicação precisa deixar legível a relação entre o trial de **14 dias** e a janela candidata de revisão de dados de **60 dias após seu término**. O componente `SyntheticTrialRetentionTimeline` recebe o objeto `TrialWindowAdvisory` já existente e calcula somente instantes UTC e contadores para apresentação.

Fases: `before_review_window` (trial ainda não terminou), `within_review_window` (desde o instante de término até um instante antes do limite da janela) e `review_window_elapsed` (no limite ou depois). A contagem utiliza dias de 86.400 segundos, não datas civis, nem SLAs ou calendários locais.

**Importante:** é uma hipótese de apresentação, não a política jurídica vigente nem o momento autorizador de exclusão. A efetiva retenção/exclusão depende da finalidade, base legal, situações contratuais, solicitações, backups, dependências, registro da empresa e parecer SEG/DBA/LGPD. A simulação **não apaga, restaura, cobra, ativa trial, concede acesso ou acessa dados**. Todos esses campos de autorização são `false` em qualquer fase.

## Evidência técnica

Testes abrangem antes do início, início, véspera do fim dos 14 dias, início da janela de 60 dias, último segundo do prazo, expiração, comportamento determinístico e overflow em `PHP_INT_MAX`. O relatório retorna somente nomes fixos de fase, quatro números de contagem/tempo e flags fixas; nenhum CNPJ, contato ou ID sai do Core.

## Gate

Sem endpoint, scheduler, banco, migration, job de limpeza, identidade real, despesa, VPS, `main` ou produção. Os gates CORE/SEG/DBA/LGPD continuam NO-GO. Qualquer futura regra operacional de retenção exige aprovação específica e revisão independente.
