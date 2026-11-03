# P0-TR-AUD — Auditoria de proveniência e correspondência dos requisitos INT

**Data:** 2026-11-03.
**Status:** **REVISÃO DOCUMENTAL INICIAL E RELATÓRIO DE LACUNAS**, não conferência literal exaustiva de todas as aprovações de INT-01 a INT-12. Não converter sínteses em textos primários aprovados.
**Autoridade:** as deliberações originais do responsável prevalecem sobre as sínteses e sugestões posteriores; referências usadas aqui são documentos do repositório e textos expressos efetivamente disponíveis.
**Sem execução:** nenhum código, teste, mudança de configuração, publicação, custo ou nova automação.
**Referências:** [matriz TR inicial](INT-P0-rastreabilidade-requisitos.md), [associação TR→CT](P0-TR53-vinculo-contratos-aceite.md), [INT-R01](INT-R01-governanca-contratos-homologacao.md), [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md), [aceites disponíveis](P0-ORIGEM-aprovacoes-dialogo.md) e [revisão geral](P0-REC-conciliacao-transversal.md).

## 1. Escopo auditado e método

1. Comparar **identificador a identificador** o inventário inicial TR-INTxx-### e a segunda matriz que lhes associa famílias CT, invariantes e HT.
2. Verificar a existência de cobertura de **HT-01..18 e INV-01..16** nesse inventário inicial.
3. Verificar para cada família INT **qual fonte efetivamente foi localizada**, distinguindo aceita formalmente, transcrita no GitHub, síntese de decisão e texto integral original.
4. Registrar todas as fontes não recuperadas com status **pendente**, sem inventar citação, ID de mensagem, data individual ou aprovação de detalhes.
5. Classificar a necessidade de complemento, eventual conflito/duplicação e critério de fechamento antes de G01–G06.

**Resultado verificável da comparação entre os documentos:** **53/53 IDs TR iniciais coincidem nas duas matrizes**; todos os **18/18 HT** e os **16/16 INV** são citados por ao menos um TR inicial. **Essas quantidades NÃO demonstram cobertura exaustiva da decisão original**, correção de mapeamentos, especificação completa, revisão por proprietário ou homologação.

## 2. Proveniência por módulo

| Módulo | Quantidade de TR iniciais | Tipo de fonte atualmente sustentada | Documentação complementar | Bloqueio |
|---|---:|---|---|---|
| INT-01 | 4 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-02 | 4 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-03 | 4 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-04 | 4 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-05 | 4 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-06 | 4 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-07 | 4 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-08 | 5 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-09 | 6 | Síntese funcional; aprovação original integral não recuperada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-10 | 5 | Aceite expresso transcrito; proposta completa não cotejada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-11 | 4 | Aceite expresso transcrito; proposta completa não cotejada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |
| INT-12 | 5 | Aceite expresso transcrito; proposta completa não cotejada | Documento INT, INT-R01/R02 e TR53, conforme aplicável | Pendente de cotejo literal e enumeração exaustiva |

Aprovações posteriores de **CT-03/04**, **CT-02**, **P0-SEC**, **P0-VER** e **HT-01..18 como bases** já estão registradas em arquivos P0-DEC próprios. Essas decisões **não substituem** as propostas originais das INTs nem certificam que os 53 requisitos sejam inventário exaustivo.

**Fonte de aceite reproduzida no repositório:** INT-10, INT-11, INT-12, INT-R01 e INT-R02, com ressalva de que seus aceites podem remeter a propostas mais extensas não reproduzidas integralmente. **INT-01..09:** as mensagens integrais originais ainda não foram verificadas nesta auditoria; o fato de uma mensagem ter existido no histórico não significa disponibilidade de transcrição integral.

## 3. Matriz nominal dos 53 requisitos iniciais

| TR | Requisito sintetizado | Fonte secundária acessível | Estado do aceite | CT candidata | INV indicada | HT indicada | Validação literal |
|---|---|---|---|---|---|---|---|
| TR-INT01-001 | Cadastro oficial de provedores, contas e conexões | [INT-01](INT-01.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-01,CT-02 | INV-01,INV-02 | HT-01 | **Parcial — falta fonte primária integral** |
| TR-INT01-002 | Ciclo de vida e referências de capacidade por tenant | [INT-01](INT-01.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-01,CT-11 | INV-01,INV-10 | HT-18 | **Parcial — falta fonte primária integral** |
| TR-INT01-003 | Separar conexão de credenciais externas | [INT-01](INT-01.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-01,CT-02 | INV-02,INV-03 | HT-02 | **Parcial — falta fonte primária integral** |
| TR-INT01-004 | Preservar dependências e efeitos pendentes nas mudanças | [INT-01](INT-01.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-01,CT-03,CT-04 | INV-05,INV-07 | HT-18 | **Parcial — falta fonte primária integral** |
| TR-INT02-001 | Autorizações granulares por escopo e finalidade | [INT-02](INT-02.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02 | INV-03 | HT-01 | **Parcial — falta fonte primária integral** |
| TR-INT02-002 | Credenciais protegidas em infraestrutura central | [INT-02](INT-02.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-11 | INV-12,INV-14 | HT-02 | **Parcial — falta fonte primária integral** |
| TR-INT02-003 | Revogação, expiração, rotação e revalidação contextual | [INT-02](INT-02.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-09 | INV-03 | HT-02 | **Parcial — falta fonte primária integral** |
| TR-INT02-004 | Não assumir aprovação humana nem permissão autônoma de IA | [INT-02](INT-02.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-11 | INV-04,INV-13 | HT-15 | **Parcial — falta fonte primária integral** |
| TR-INT03-001 | Esquemas versionados e correspondência de campos | [INT-03](INT-03.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-03,CT-07 | INV-10 | HT-03 | **Parcial — falta fonte primária integral** |
| TR-INT03-002 | Validações e transformações tipadas com proveniência | [INT-03](INT-03.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-03,CT-11 | INV-14 | HT-03 | **Parcial — falta fonte primária integral** |
| TR-INT03-003 | Prévia sem escrita e sem mesclagem automática de dados | [INT-03](INT-03.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-07,CT-02 | INV-02,INV-11 | HT-07 | **Parcial — falta fonte primária integral** |
| TR-INT03-004 | Compatibilidade de esquema antes de aplicar mudanças | [INT-03](INT-03.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-01,CT-03 | INV-10 | HT-12 | **Parcial — falta fonte primária integral** |
| TR-INT04-001 | Operações técnicas tipadas e capacidades homologadas | [INT-04](INT-04.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-01,CT-03 | INV-02,INV-11 | HT-01 | **Parcial — falta fonte primária integral** |
| TR-INT04-002 | Separar comando enviado, aceito, aplicado e confirmado | [INT-04](INT-04.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-03,CT-04 | INV-05 | HT-08 | **Parcial — falta fonte primária integral** |
| TR-INT04-003 | Idempotência e controle de tentativas e concorrência | [INT-04](INT-04.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-03,CT-04 | INV-06,INV-07 | HT-08 | **Parcial — falta fonte primária integral** |
| TR-INT04-004 | Sem endpoints genéricos nem decisão autônoma de negócio | [INT-04](INT-04.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-03 | INV-02,INV-11 | HT-15 | **Parcial — falta fonte primária integral** |
| TR-INT05-001 | Aplicações autorizadas por tenant, escopo e plano | [INT-05](INT-05.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-03 | INV-01,INV-03 | HT-13 | **Parcial — falta fonte primária integral** |
| TR-INT05-002 | API pública bloqueada no trial, preservando nativas autorizadas | [INT-05](INT-05.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-03 | INV-03,INV-16 | HT-04 | **Parcial — falta fonte primária integral** |
| TR-INT05-003 | Quota, revogação e auditoria de acessos | [INT-05](INT-05.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-11 | INV-03,INV-14 | HT-04 | **Parcial — falta fonte primária integral** |
| TR-INT05-004 | API não contorna o módulo proprietário | [INT-05](INT-05.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-04 | INV-02 | HT-13 | **Parcial — falta fonte primária integral** |
| TR-INT06-001 | Webhooks autenticados, íntegros e protegidos de replay | [INT-06](INT-06.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-05,CT-02 | INV-03,INV-14 | HT-05 | **Parcial — falta fonte primária integral** |
| TR-INT06-002 | Deduplicação e tratamento de eventos fora de ordem | [INT-06](INT-06.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-05,CT-04 | INV-07 | HT-05 | **Parcial — falta fonte primária integral** |
| TR-INT06-003 | Separar entrega/aceite de confirmação de negócio | [INT-06](INT-06.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-04,CT-05 | INV-05 | HT-05 | **Parcial — falta fonte primária integral** |
| TR-INT06-004 | Eventos oficiais internos permanecem centrais | [INT-06](INT-06.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-05,CT-11 | INV-02,INV-14 | HT-16 | **Parcial — falta fonte primária integral** |
| TR-INT07-001 | Sincronização delimitada com autoridade por campo | [INT-07](INT-07.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-06,CT-02 | INV-02,INV-03 | HT-06 | **Parcial — falta fonte primária integral** |
| TR-INT07-002 | Cursores/checkpoints e retomada segura | [INT-07](INT-07.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-06,CT-04 | INV-07 | HT-06 | **Parcial — falta fonte primária integral** |
| TR-INT07-003 | Conflitos, ciclos, exclusões e resultados desconhecidos explícitos | [INT-07](INT-07.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-06,CT-09 | INV-07,INV-08 | HT-08 | **Parcial — falta fonte primária integral** |
| TR-INT07-004 | Sem último-escritor universal ou sobrescrita de fonte oficial | [INT-07](INT-07.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-06,CT-04 | INV-02 | HT-06 | **Parcial — falta fonte primária integral** |
| TR-INT08-001 | Importação/exportação/migração delimitada e autorizada | [INT-08](INT-08.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-07,CT-02 | INV-02,INV-03 | HT-07 | **Parcial — falta fonte primária integral** |
| TR-INT08-002 | Prévia, validação segura e mapeamentos INT-03 | [INT-08](INT-08.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-07,CT-03 | INV-11,INV-15 | HT-07 | **Parcial — falta fonte primária integral** |
| TR-INT08-003 | Lotes, checkpoints, idempotência e aceite fundamentado | [INT-08](INT-08.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-07,CT-04 | INV-07 | HT-07 | **Parcial — falta fonte primária integral** |
| TR-INT08-004 | Exportação com escopo, links temporários e auditoria | [INT-08](INT-08.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-07,CT-11 | INV-01,INV-14 | HT-17 | **Parcial — falta fonte primária integral** |
| TR-INT08-005 | Sem exclusão, mesclagem ou rollback universal por decisão própria | [INT-08](INT-08.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-07,CT-10 | INV-02,INV-09 | HT-07 | **Parcial — falta fonte primária integral** |
| TR-INT09-001 | Modelos e instâncias versionados sem mutação silenciosa | [INT-09](INT-09.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-08,CT-01 | INV-10 | HT-09 | **Parcial — falta fonte primária integral** |
| TR-INT09-002 | Dependências e convergência por resultados oficialmente confirmados | [INT-09](INT-09.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-08,CT-04 | INV-05 | HT-09 | **Parcial — falta fonte primária integral** |
| TR-INT09-003 | Comandos idempotentes com correlação, estados e tentativas | [INT-09](INT-09.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-03,CT-08 | INV-06,INV-07 | HT-08 | **Parcial — falta fonte primária integral** |
| TR-INT09-004 | Reconciliação antes de retentar escrita inconclusiva | [INT-09](INT-09.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-04,CT-09 | INV-08 | HT-08 | **Parcial — falta fonte primária integral** |
| TR-INT09-005 | Compensação somente pela autoridade competente | [INT-09](INT-09.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-08,CT-10 | INV-02,INV-09 | HT-10 | **Parcial — falta fonte primária integral** |
| TR-INT09-006 | IA não comanda operações críticas nem altera produção autonomamente | [INT-09](INT-09.md) | Aceite funcional indicado no projeto; mensagem integral não conferida | CT-02,CT-08 | INV-13 | HT-15 | **Parcial — falta fonte primária integral** |
| TR-INT10-001 | Saúde, diagnóstico e classificação de incidentes técnicos | [INT-10](INT-10.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-09,CT-11 | INV-14 | HT-11 | **Parcial — falta fonte primária integral** |
| TR-INT10-002 | Recuperação homologada e limitada, com revalidação | [INT-10](INT-10.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-09,CT-02 | INV-03,INV-08 | HT-11 | **Parcial — falta fonte primária integral** |
| TR-INT10-003 | Estados inconclusivos reconciliados antes de nova escrita | [INT-10](INT-10.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-09,CT-04 | INV-08 | HT-08 | **Parcial — falta fonte primária integral** |
| TR-INT10-004 | Saúde do provedor não confirma conclusão do negócio | [INT-10](INT-10.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-09,CT-04 | INV-05 | HT-11 | **Parcial — falta fonte primária integral** |
| TR-INT10-005 | Sem serviços paralelos de observabilidade e auditoria | [INT-10](INT-10.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-09,CT-11 | INV-14 | HT-16 | **Parcial — falta fonte primária integral** |
| TR-INT11-001 | Catálogo e homologação de versão de conectores | [INT-11](INT-11.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-01,CT-11 | INV-10 | HT-01 | **Parcial — falta fonte primária integral** |
| TR-INT11-002 | Submissão, testes, aprovação e publicação controlada | [INT-11](INT-11.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-01,CT-02 | INV-03,INV-10 | HT-18 | **Parcial — falta fonte primária integral** |
| TR-INT11-003 | Sem execução livre ou publicação autônoma por terceiros | [INT-11](INT-11.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-01,CT-02 | INV-11 | HT-18 | **Parcial — falta fonte primária integral** |
| TR-INT11-004 | Suspensão/descontinuação com tratamento das conexões afetadas | [INT-11](INT-11.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-01,CT-09 | INV-02,INV-10 | HT-18 | **Parcial — falta fonte primária integral** |
| TR-INT12-001 | Rastreabilidade de comandos, decisões, versões e confirmações | [INT-12](INT-12.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-11,CT-04 | INV-05,INV-14 | HT-14 | **Parcial — falta fonte primária integral** |
| TR-INT12-002 | Correlação e investigação de evidências com proveniência | [INT-12](INT-12.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-11,CT-09 | INV-14 | HT-14 | **Parcial — falta fonte primária integral** |
| TR-INT12-003 | Exportações autorizadas e minimizadas, segregadas por tenant | [INT-12](INT-12.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-11,CT-02 | INV-01,INV-03 | HT-17 | **Parcial — falta fonte primária integral** |
| TR-INT12-004 | Auditoria da própria consulta e exportação | [INT-12](INT-12.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-11 | INV-14 | HT-17 | **Parcial — falta fonte primária integral** |
| TR-INT12-005 | Apenas Auditoria Geral como trilha oficial, IA consultiva | [INT-12](INT-12.md) | Aceite expresso transcrito em P0-ORIGEM; proposta integral não cotejada | CT-11,CT-02 | INV-13,INV-14 | HT-14 | **Parcial — falta fonte primária integral** |

**Atenção:** as linhas acima citam as fontes efetivamente acessíveis e as associações já documentadas, mas ainda **não** oferecem a evidência primária integral necessária a P0-DOC-01/P0-TR-01. Não inserir automaticamente “aprovado integralmente” no estado de cada TR por haver um aceite geral do bloco.

## 4. Cobertura nominal dos cenários HT pelo inventário inicial

| HT | TR que o citam como cenário inicial | Plano atual | Limite |
|---|---:|---|---|
| HT-01 | 4 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-01) | Referência inicial somente; cobertura completa não certificada |
| HT-02 | 3 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-02) | Referência inicial somente; cobertura completa não certificada |
| HT-03 | 2 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-03) | Referência inicial somente; cobertura completa não certificada |
| HT-04 | 2 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-04) | Referência inicial somente; cobertura completa não certificada |
| HT-05 | 3 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-05) | Referência inicial somente; cobertura completa não certificada |
| HT-06 | 3 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-06) | Referência inicial somente; cobertura completa não certificada |
| HT-07 | 5 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-07) | Referência inicial somente; cobertura completa não certificada |
| HT-08 | 6 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-08) | Referência inicial somente; cobertura completa não certificada |
| HT-09 | 2 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-09) | Referência inicial somente; cobertura completa não certificada |
| HT-10 | 1 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-10) | Referência inicial somente; cobertura completa não certificada |
| HT-11 | 3 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-11) | Referência inicial somente; cobertura completa não certificada |
| HT-12 | 1 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-12) | Referência inicial somente; cobertura completa não certificada |
| HT-13 | 2 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-13) | Referência inicial somente; cobertura completa não certificada |
| HT-14 | 3 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-14) | Referência inicial somente; cobertura completa não certificada |
| HT-15 | 3 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-15) | Referência inicial somente; cobertura completa não certificada |
| HT-16 | 2 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-16) | Referência inicial somente; cobertura completa não certificada |
| HT-17 | 3 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-17) | Referência inicial somente; cobertura completa não certificada |
| HT-18 | 5 | [Plano específico](P0-ACC-planos-homologacao-HT01-HT18.md#ht-18) | Referência inicial somente; cobertura completa não certificada |

Os 18 procedimentos foram aceitos como **base documental** em [P0-DEC P0-ACC](P0-DEC-aprovacao-ACC-HT01-HT18-base.md); nenhum teste foi executado. Uma HT citada uma única vez ainda precisa cruzamento com os riscos e comportamentos aprovados não presentes nos 53 requisitos.

## 5. Invariantes referenciados por pelo menos um TR inicial

- INV-01: 5 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-02: 13 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-03: 12 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-04: 1 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-05: 6 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-06: 2 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-07: 7 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-08: 4 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-09: 2 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-10: 7 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-11: 5 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-12: 1 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-13: 3 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-14: 12 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-15: 1 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.
- INV-16: 1 vínculo(s) na matriz TR inicial; cobertura e semântica dependem de revisão das propostas originais.

## 6. Lacunas e prioridades documentais

| Item | Problema concreto | O que falta para a decisão G01/G02/G03 | Prioridade |
|---|---|---|---|
| TR-A01 | Texto integral das aprovações INT-01..09 não disponível nesta revisão | Recuperar proposta e aceite integral, versão/escopo, enumerar todos os requisitos e exceções | P0 |
| TR-A02 | INT-10..12 com texto expresso do aceite, mas proposta detalhada não cotejada por requisito | Comparar cláusulas, estados, exceções e parâmetros reais sem inferência | P0 |
| TR-A03 | INT-R01 e R02 aprovadas, porém as propostas originais completas não foram cotejadas com documentos de síntese | Verificação cláusula a cláusula, referências e correção de divergências | P0 |
| TR-A04 | Quantidade 53 inicial não é contagem certificada do projeto INT | Inventariar requisitos granulares adicionais; garantir IDs estáveis sem reaproveitar | P0 |
| TR-A05 | Associações TR→CT/INV/HT são propostas do levantamento | Verificar com proprietários e os contratos efetivos; validar testes negativos relevantes | P0 |
| TR-A06 | Propriedade por recurso/campo e parâmetro não decididos | Complementar CT-02/03/04 e competência sem conceder poderes por suposição | P0 |
| TR-A07 | Nenhum HT executado e planos ainda precisam revisão completa | Especificar evidências, versões, ambientes, aprovadores e teste futuro autorizado | P0-ACC/documental e homologação futura |
| TR-A08 | Potencial dependência circular entre INTs e serviços centrais sem contratos por fase | Distinguir catálogo/execução/evento/recuperação e seus resultados oficiais | P0-DEP |
| TR-A09 | Regras IN de Portabilidade, Filas, Segredos, Observabilidade e Auditoria não totalmente reconciliadas por TR | Referenciar IN-118/124/148/149/151/159/160/162/167/168 por requisitos específicos | P0-XREF |

## 7. Conclusão e regra de avanço

- **Integridade dos IDs entre dois documentos:** conferida (53 linhas).
- **Existência de referências a todos os HT e INV iniciais:** conferida (18/18 e 16/16).
- **Conferência literal das propostas originais:** **PENDENTE**; a busca disponível não recuperou textos integrais verificáveis de INT-01..09 e propostas completas de INT-10..12.
- **Fidelidade da enumeração e escopo real de cada TR:** **NÃO CERTIFICADA**.
- **G01/G02/G03/G05/G06:** **não encerrados**; G04 parcial, dependente de responsáveis/impactos e fechamento.

**Complementos documentais (03/11/2026):** [P0-DEP — dependências e fronteiras](P0-DEP-dependencias-criticas-e-fronteiras.md), [P0-DOC-TR — 28 verificações candidatas por módulo](P0-DOC-TR-deltas-candidatos-fonte-original.md) e [P0-GATE — bloqueios e critérios](P0-GATE-painel-fechamento-rastreabilidade-dependencias.md). Nenhum constitui cotejo literal da fonte primária nem autoriza implementar.
