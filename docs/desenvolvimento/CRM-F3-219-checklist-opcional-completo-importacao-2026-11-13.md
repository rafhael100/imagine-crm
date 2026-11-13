# F3-219 — Checklist CSV agregado com revisão de campos obrigatórios opt-in

Esta entrega conecta os recortes F3-215, F3-216/217 e F3-218 no `PortableCsvImportReadinessSummary`: além de formato, cabeçalhos, fórmulas e repetições, aceita **opcionalmente** um quinto argumento com lista explícita de campos candidatos alegadamente obrigatórios. Somente quando o quinto argumento é fornecido, acrescenta contadores e códigos fixos de células vazias e requisitos não mapeados ao relatório.

**Compatibilidade:** os quatro argumentos anteriores (ou o quinto como `null`) produzem o mesmo relatório que a F3-217; nenhuma nova política é inferida automaticamente. Erros de requisito inválido são sanitizados. A lista de campos e valores não são ecoados.

**Gates:** nenhuma permissão, deduplicação, importação, exportação, mesclagem, persistência, schema real ou consentimento LGPD passa a ser autorizado. Desenvolvido só em memória sobre dados fictícios; sem I/O, rota, SQL, tenant, VPS ou cliente. Revisão humana dos contratos, #27 SEG/DBA, #259 CORE/LGPD, #416 UX e decisões IN-118 permanecem necessárias.

Testes: `PortableCsvCompleteChecklistTest.php` cobre agregação, opt-in, compatibilidade legada e argumentos inválidos; exigir F0, PostgreSQL/RLS, paridade Sites e Agentes na SHA exata após revisão.
