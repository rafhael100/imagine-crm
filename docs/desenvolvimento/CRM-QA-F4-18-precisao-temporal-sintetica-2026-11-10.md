# QA F4-18 — Precisão temporal sintética

## Ajuste

Os contratos offline `FollowUpAgingProjection` e `FollowUpTimeline` passam a admitir frações de segundo com **1 a 6 dígitos decimais** em ISO-8601 com offset explícito, alinhando a validação à precisão em microssegundos usada em PHP.

Antes, a expressão regular aceitava quantidades arbitrárias de dígitos e podia permitir que o parser descartasse silenciosamente frações além de seis casas. Agora entradas com sete ou mais casas são rejeitadas, sem criar um falso empate no relógio ou na ordem cronológica.

## Testes

Regressões adicionais provam rejeição de sete casas no instante de referência, em `due_at` e no instante de observação da série. Casos válidos de seis casas continuam cobertos pela suíte anterior.

## Limites

Somente regras e testes sintéticos, sem migrações, endpoint, cron, envio, dados pessoais ou autorização. Não concede `ALLOW`, não homologa CORE, SEG/DBA #27, LGPD ou produção. `main` e VPS intocadas. Merge restrito à DEV com CI F0 e PostgreSQL RLS verdes na mesma SHA.
