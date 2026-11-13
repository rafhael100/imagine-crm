# F3-217 — Colisões no relatório de revisão CSV IN-118

Esta etapa conecta a prova de repetições de F3-216 ao relatório de leitura de F3-215. A classe `PortableCsvImportReadinessSummary` agrega os contadores de linhas idênticas na origem, linhas idênticas no conjunto mapeado e colisões adicionais após omissão de colunas. Verifica que os três analisadores independentes concordam em linhas, colunas, campos mapeados e indicação explícita de cabeçalho, lançando erro sanitizado em caso de inconsistência.

Nenhum título, valor, linha, documento, hash ou identidade sai da execução; a comparação de valores acontece somente na memória limitada pelo codec F3-210. Todos os resultados são informativos e os sinalizadores de autorização, importação, deduplicação, mesclagem, exportação e persistência permanecem **false**. Contagens não são provas de duplicidade de pessoas.

`PortableCsvImportCollisionSummaryTest.php` protege três cenários: duplicata + colisão no fluxo agregado; linhas únicas; falha de validação sem exposição de entrada. Exigir revisão integral de diff e workflows F0, PostgreSQL/RLS, paridade Sites e Agentes na HEAD exata antes de merge isolado DEV. Gates SEG/DBA #27, CORE/LGPD #259 e UX #416 continuam abertos.

**Limitação adicional:** igualdade exata é medida apenas sobre valores de um exemplo local; registros diferentes podem representar a mesma pessoa, e registros idênticos podem não autorizar mesclagem. A decisão permanece sempre NO-GO.
