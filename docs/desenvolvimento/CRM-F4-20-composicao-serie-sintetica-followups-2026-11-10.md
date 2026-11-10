# F4-20 — Série de acompanhamentos a partir de observações sintéticas

## Implementação

`FollowUpObservationSeries::project` recebe uma lista explícita de até **31 observações** com `at` (timestamp ISO-8601 com offset) e `items` (até **100 registros sintéticos** por observação). Cada observação é convertida por `FollowUpSnapshot::build` e a série inteira passa por `FollowUpTimeline::build`.

A saída contém apenas horário UTC normalizado, contagens por faixa, diferenças e métricas de movimentação entre observações. Nenhum identificador de registro consta da saída. Identificadores são usados apenas em memória para reconhecer transições entre dois snapshots consecutivos.

## Validação fail-closed

- Só aceita listas e as chaves exatas `at` e `items`, recusando campos imprevistos.
- Rejeita listas acima dos limites, IDs repetidos em um único instante, datas inválidas ou sem offset e instantes fora da ordem.
- Reutiliza F4-06/07/08/09/10/11/13 em vez de duplicar as regras de cálculo.
- Testes sintéticos cobrem transições reais dentro da fixture, normalização de fuso, totalizadores, ausência de IDs, limites e falhas.

## Restrições

É somente um **adaptador puro de código para fixtures**, não um serviço conectado ao banco ou ao CRM real. Nenhuma consulta a clientes, autorização, tenant, HTTP, SQL, filas, importação, notificação, geração de oportunidades, cron, decisão de SLA ou `ALLOW` está implementada. Sem acesso ao relógio implícito do sistema. Não altera `main`, VPS ou produção.

Uma utilização operacional requer contrato de leitura multiempresa auditável, autorização e revisão independente SEG/DBA (#27), CORE e privacidade. CI verde apenas autoriza a integração em DEV, nunca uso com clientes.
