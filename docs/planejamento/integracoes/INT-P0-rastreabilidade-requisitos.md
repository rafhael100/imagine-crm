# INT-P0 — Primeira matriz de requisitos rastreáveis

**Status:** rascunho de trabalho documental P0; gerado a partir das sínteses funcionais aprovadas. **Não constitui conferência literal exaustiva das deliberações originais.**
**Escopo:** 53 requisitos iniciais identificados para classificação e rastreamento. A contagem não representa total definitivo; novas linhas serão acrescentadas após conferência das aprovações integrais.

## Matriz inicial
| ID estável proposto | Origem funcional | Requisito sintetizado | Invariantes associados | Teste candidato | Verificação |
|---|---|---|---|---|---|
| TR-INT01-001 | [INT-01](INT-01.md) | Cadastro oficial de provedores, contas e conexões | INV-01,INV-02 | HT-01 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT01-002 | [INT-01](INT-01.md) | Ciclo de vida e referências de capacidade por tenant | INV-01,INV-10 | HT-18 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT01-003 | [INT-01](INT-01.md) | Separar conexão de credenciais externas | INV-02,INV-03 | HT-02 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT01-004 | [INT-01](INT-01.md) | Preservar dependências e efeitos pendentes nas mudanças | INV-05,INV-07 | HT-18 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT02-001 | [INT-02](INT-02.md) | Autorizações granulares por escopo e finalidade | INV-03 | HT-01 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT02-002 | [INT-02](INT-02.md) | Credenciais protegidas em infraestrutura central | INV-12,INV-14 | HT-02 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT02-003 | [INT-02](INT-02.md) | Revogação, expiração, rotação e revalidação contextual | INV-03 | HT-02 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT02-004 | [INT-02](INT-02.md) | Não assumir aprovação humana nem permissão autônoma de IA | INV-04,INV-13 | HT-15 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT03-001 | [INT-03](INT-03.md) | Esquemas versionados e correspondência de campos | INV-10 | HT-03 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT03-002 | [INT-03](INT-03.md) | Validações e transformações tipadas com proveniência | INV-14 | HT-03 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT03-003 | [INT-03](INT-03.md) | Prévia sem escrita e sem mesclagem automática de dados | INV-02,INV-11 | HT-07 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT03-004 | [INT-03](INT-03.md) | Compatibilidade de esquema antes de aplicar mudanças | INV-10 | HT-12 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT04-001 | [INT-04](INT-04.md) | Operações técnicas tipadas e capacidades homologadas | INV-02,INV-11 | HT-01 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT04-002 | [INT-04](INT-04.md) | Separar comando enviado, aceito, aplicado e confirmado | INV-05 | HT-08 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT04-003 | [INT-04](INT-04.md) | Idempotência e controle de tentativas e concorrência | INV-06,INV-07 | HT-08 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT04-004 | [INT-04](INT-04.md) | Sem endpoints genéricos nem decisão autônoma de negócio | INV-02,INV-11 | HT-15 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT05-001 | [INT-05](INT-05.md) | Aplicações autorizadas por tenant, escopo e plano | INV-01,INV-03 | HT-13 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT05-002 | [INT-05](INT-05.md) | API pública bloqueada no trial, preservando nativas autorizadas | INV-03,INV-16 | HT-04 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT05-003 | [INT-05](INT-05.md) | Quota, revogação e auditoria de acessos | INV-03,INV-14 | HT-04 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT05-004 | [INT-05](INT-05.md) | API não contorna o módulo proprietário | INV-02 | HT-13 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT06-001 | [INT-06](INT-06.md) | Webhooks autenticados, íntegros e protegidos de replay | INV-03,INV-14 | HT-05 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT06-002 | [INT-06](INT-06.md) | Deduplicação e tratamento de eventos fora de ordem | INV-07 | HT-05 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT06-003 | [INT-06](INT-06.md) | Separar entrega/aceite de confirmação de negócio | INV-05 | HT-05 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT06-004 | [INT-06](INT-06.md) | Eventos oficiais internos permanecem centrais | INV-02,INV-14 | HT-16 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT07-001 | [INT-07](INT-07.md) | Sincronização delimitada com autoridade por campo | INV-02,INV-03 | HT-06 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT07-002 | [INT-07](INT-07.md) | Cursores/checkpoints e retomada segura | INV-07 | HT-06 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT07-003 | [INT-07](INT-07.md) | Conflitos, ciclos, exclusões e resultados desconhecidos explícitos | INV-07,INV-08 | HT-08 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT07-004 | [INT-07](INT-07.md) | Sem último-escritor universal ou sobrescrita de fonte oficial | INV-02 | HT-06 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT08-001 | [INT-08](INT-08.md) | Importação/exportação/migração delimitada e autorizada | INV-02,INV-03 | HT-07 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT08-002 | [INT-08](INT-08.md) | Prévia, validação segura e mapeamentos INT-03 | INV-11,INV-15 | HT-07 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT08-003 | [INT-08](INT-08.md) | Lotes, checkpoints, idempotência e aceite fundamentado | INV-07 | HT-07 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT08-004 | [INT-08](INT-08.md) | Exportação com escopo, links temporários e auditoria | INV-01,INV-14 | HT-17 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT08-005 | [INT-08](INT-08.md) | Sem exclusão, mesclagem ou rollback universal por decisão própria | INV-02,INV-09 | HT-07 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT09-001 | [INT-09](INT-09.md) | Modelos e instâncias versionados sem mutação silenciosa | INV-10 | HT-09 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT09-002 | [INT-09](INT-09.md) | Dependências e convergência por resultados oficialmente confirmados | INV-05 | HT-09 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT09-003 | [INT-09](INT-09.md) | Comandos idempotentes com correlação, estados e tentativas | INV-06,INV-07 | HT-08 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT09-004 | [INT-09](INT-09.md) | Reconciliação antes de retentar escrita inconclusiva | INV-08 | HT-08 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT09-005 | [INT-09](INT-09.md) | Compensação somente pela autoridade competente | INV-02,INV-09 | HT-10 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT09-006 | [INT-09](INT-09.md) | IA não comanda operações críticas nem altera produção autonomamente | INV-13 | HT-15 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT10-001 | [INT-10](INT-10.md) | Saúde, diagnóstico e classificação de incidentes técnicos | INV-14 | HT-11 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT10-002 | [INT-10](INT-10.md) | Recuperação homologada e limitada, com revalidação | INV-03,INV-08 | HT-11 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT10-003 | [INT-10](INT-10.md) | Estados inconclusivos reconciliados antes de nova escrita | INV-08 | HT-08 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT10-004 | [INT-10](INT-10.md) | Saúde do provedor não confirma conclusão do negócio | INV-05 | HT-11 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT10-005 | [INT-10](INT-10.md) | Sem serviços paralelos de observabilidade e auditoria | INV-14 | HT-16 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT11-001 | [INT-11](INT-11.md) | Catálogo e homologação de versão de conectores | INV-10 | HT-01 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT11-002 | [INT-11](INT-11.md) | Submissão, testes, aprovação e publicação controlada | INV-03,INV-10 | HT-18 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT11-003 | [INT-11](INT-11.md) | Sem execução livre ou publicação autônoma por terceiros | INV-11 | HT-18 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT11-004 | [INT-11](INT-11.md) | Suspensão/descontinuação com tratamento das conexões afetadas | INV-02,INV-10 | HT-18 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT12-001 | [INT-12](INT-12.md) | Rastreabilidade de comandos, decisões, versões e confirmações | INV-05,INV-14 | HT-14 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT12-002 | [INT-12](INT-12.md) | Correlação e investigação de evidências com proveniência | INV-14 | HT-14 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT12-003 | [INT-12](INT-12.md) | Exportações autorizadas e minimizadas, segregadas por tenant | INV-01,INV-03 | HT-17 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT12-004 | [INT-12](INT-12.md) | Auditoria da própria consulta e exportação | INV-14 | HT-17 | Aprovado funcionalmente; conferência literal P0 pendente |
| TR-INT12-005 | [INT-12](INT-12.md) | Apenas Auditoria Geral como trilha oficial, IA consultiva | INV-13,INV-14 | HT-14 | Aprovado funcionalmente; conferência literal P0 pendente |

## Próximos campos para fechamento integral
Para cada linha acrescentar: referência inequívoca à decisão original e sua versão, texto literal relevante, módulo proprietário de negócio, operação tipada, escopos por campo/recurso, versão do contrato, evidência documental, revisão cruzada, responsável e estado. IDs não devem ser reciclados ou reatribuídos após aceitação.

**Não declarar P0-TR-01 concluído:** esta matriz é uma primeira indexação, ainda sem todos os requisitos granulares, sem referências diretas às mensagens originais e sem critérios HT detalhados por caso.

## Referências
- [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md)
- [INT-R01](INT-R01-governanca-contratos-homologacao.md)
- [Matriz de pendências](INT-P0-fechamento-documental.md)
