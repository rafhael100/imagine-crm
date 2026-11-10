# QA F4-12 — Regressão de reconciliação sintética

Esta suite cobre integralmente as nove transições possíveis entre as três faixas dos snapshots F4-09/F4-10, incluindo entradas e saídas, reconciliando matriz e resumo F4-11. Também prova que reordenar entradas sintéticas não altera os agregados ou a permanência de IDs.

**Exclusivamente testes offline:** nenhum dado pessoal, consulta de tenant, permissão positiva, `ALLOW`, exportação, API, rede, migrations, VPS, produção ou `main`. Mesmo com CI verde, homologação funcional, CORE, LGPD e revisão independente SEG/DBA #27 permanecem pendentes.
