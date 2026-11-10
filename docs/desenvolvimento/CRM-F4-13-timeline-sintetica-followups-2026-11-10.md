# CRM F4-13 — Série temporal sintética de follow-ups

## Objetivo
Construir uma projeção local para protótipos de indicadores de acompanhamento, a partir de até 31 snapshots sintéticos. A saída não inclui identificadores: apenas horário normalizado UTC, contagens, variações e métricas de continuidade de cada par consecutivo.

## Contrato
- Entradas estritamente crescentes em instantes ISO-8601 com offset explícito, sem duplicação temporal mesmo com offsets diferentes.
- Até 31 observações; entradas sem formato exato, timestamps inválidos e contagens adulteradas são rejeitados.
- A primeira observação não tem comparação: `delta=null` e `continuity=null`.
- Cada comparação utiliza os contratos F4-08/F4-10/F4-11 existentes.

## Segurança e semântica
Somente dados sintéticos. Nenhum controller, autorização, consulta ao tenant, cron, SQL, API, webhook, publicação de relatório real, dados pessoais ou custo novo. A saída agregada **não é autorização de divulgação** de estatísticas de clientes, e números não comprovam SLA, conversão, produtividade nem atendimento efetivo. Gates CORE, LGPD e parecer independente SEG/DBA #27 permanecem abertos. `main`, VPS e produção intocadas.
