# Leitura de leads — cenários de revisão v0.1

**Proposta documental, 14/11/2026. Nenhum caso executado.**
Referência: [contrato](CRM-LEAD-READ-V0.1-PROPOSTA.md). Todos os futuros ensaios
usam dados sintéticos e ambiente descartável explicitamente autorizado.

| ID | Preparação/ação futura | Resultado exigido pela proposta | Decisão |
|---|---|---|---|
| LR-T01 | Principal legítimo de A com política positiva revisada lista A | Somente projeção e linhas autorizadas de A | D01/D02/D03 |
| LR-T02 | Mesmo principal participa de A e B, organização ativa A | Não unir dados/cursor de B; mudar contexto limpa a tela | D02/D03/D06 |
| LR-T03 | Cliente envia tenant_id de B | Não resolver autoridade por parâmetro; sem consulta de dados de B | D02/D03 |
| LR-T04 | Sessão expirada ou ausente | UNAUTHENTICATED, sem itens nem cursor, tela limpa | D02/D06 |
| LR-T05 | Membership revogada antes de ler | ACCESS_DENIED, sem consulta de leads | D02 |
| LR-T06 | Membership revogada entre consulta e serialização | Descartar resultado; nenhum item é enviado | D02/D03 |
| LR-T07 | Administrador Imagine sem mandato para A | ACCESS_DENIED; selecionar perfil não concede direito | D02 |
| LR-T08 | Grant válido e negação aplicável no mesmo escopo | Aplicar política revisada; negação impede a leitura atingida | D02 |
| LR-T09 | Campo essencial sem direito | Negar operação sem buscar ou retornar projeção parcial | D01/D02 |
| LR-T10 | source_label sem direito; com direito e valor ausente | Omitir no primeiro caso; null no segundo; sem origem inferida | D01/D05 |
| LR-T11 | Nome contém marcação HTML e caracteres Unicode | Texto simples escapado, sem execução; regra de tamanho revisada | D01/D06 |
| LR-T12 | Fixtures incluem contato, valor, observação e tenant | Não serializar campos fora da projeção; sem valor de oportunidade | D01/D05 |
| LR-T13 | Valores limit 0, 51, true, "20"; parâmetro desconhecido | INVALID_REQUEST após autorização básica; sem consulta | D04 |
| LR-T14 | Datas fora de RFC3339 ou intervalo invertido | INVALID_REQUEST sem eco da entrada | D04/D05 |
| LR-T15 | Empate em created_at, várias páginas | Desempate estável por lead_ref e keyset coerente | D04 |
| LR-T16 | Cursor adulterado/expirado ou de outra empresa/identidade | Recusar sem revelar conteúdo, tenant ou presença de registro | D02/D04 |
| LR-T17 | Cursor com filtros, limite ou revisão de política diferentes | Recusar; usuário reinicia listagem | D02/D04 |
| LR-T18 | Novo lead inserido e outro removido entre páginas | Respeitar semântica sem snapshot; não expor removido/revogado | D03/D04 |
| LR-T19 | Lista de A contém registros não autorizados ao ator | Filtrar antes de ordenar/paginar; sem total ou agregado global | D02/D03 |
| LR-T20 | Consulta autorizada sem registros | OK_EMPTY distinto de acesso negado/erro | D06 |
| LR-T21 | Pool reutiliza conexão de A para B | Contexto transacional isolado; nenhum resíduo de A | D03 |
| LR-T22 | Role runtime com SUPERUSER/BYPASSRLS ou owner inadequado | Barrar validação operacional; não aceitar resultado do laboratório como prova | D03 |
| LR-T23 | Resposta de A chega após UI mudar para B | Descartar resposta de A, sem flash nem cache persistente | D02/D06 |
| LR-T24 | Banco/política/contexto/auditoria indisponível | Falhar fechado segundo política aprovada; sem fallback para fixtures | D02/D03/D05 |
| LR-T25 | Inspecionar logs, erros, cache e URLs | Sem PII, cursor, payload, segredo ou filtro bruto; no-store proposto | D04/D05 |
| LR-T26 | Repetir listagens em volume e tentar exportar | Política antiabuso revisada; leitura não concede exportação | D02/D04/D05 |

D01..D06 referem-se a LR-D01..06 do contrato. Cada futuro relatório deve separar
resultado observado, esperado, evidência de execução, SHA e gate ainda aberto.
Não declarar estes 26 cenários aprovados por terem sido redigidos ou por CI geral.
