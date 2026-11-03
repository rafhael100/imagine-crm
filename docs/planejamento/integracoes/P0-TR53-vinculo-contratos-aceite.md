# P0-TR53 — Vínculo operacional dos 53 requisitos iniciais aos contratos CT

**Estado:** avanço de rastreabilidade P0-TR-01 e P0-CON-01, **proposta de associação documental**. As 53 linhas derivam da [matriz inicial](INT-P0-rastreabilidade-requisitos.md), não da conferência literal integral das decisões. Não ampliar escopos, aprovar nomes de API, campos, prazos, limites ou execução.
**Cobertura desta etapa:** **53/53 requisitos inicialmente cadastrados receberam associação proposta a famílias CT e evidência esperada**. **Não** significa 53/53 requisitos completos do projeto, 53/53 origens recuperadas, 53/53 regras documentalmente validadas ou qualquer teste homologado.
**Fase:** planejamento; sem programação.

## Legenda da matriz
- **TR:** identificador inicial do requisito; não reciclar após aceitação.
- **CT:** família documental candidata de contrato (CT-01 a CT-11); propostas em [P0-CON](P0-CON-contratos-tecnicos-minuta.md).
- **INV:** invariante já aprovado na [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- **HT:** cenário já aprovado no planejamento, sem implementação/teste realizado.
- **Evidência esperada:** critério de revisão/homologação a detalhar futuramente, não resultado real.
- **Situação de origem:** vínculo somente à síntese funcional do módulo; exige texto original/versão.

## Matriz das 53 associações propostas
| Requisito | Regra sintetizada | CT candidata | INV | HT | Evidência esperada (proposta) | Proveniência / status |
|---|---|---|---|---|---|---|
| TR-INT01-001 | Cadastro oficial de provedores, contas e conexões | CT-01,CT-02 | INV-01,INV-02 | HT-01 | Cadastro/vínculo e tenant válidos | Referência à síntese; cotejo original pendente |
| TR-INT01-002 | Ciclo de vida e referências de capacidade por tenant | CT-01,CT-11 | INV-01,INV-10 | HT-18 | Estado e versão de conexão auditáveis | Referência à síntese; cotejo original pendente |
| TR-INT01-003 | Separar conexão de credenciais externas | CT-01,CT-02 | INV-02,INV-03 | HT-02 | Nenhum segredo no cadastro da conexão | Referência à síntese; cotejo original pendente |
| TR-INT01-004 | Preservar dependências e efeitos pendentes nas mudanças | CT-01,CT-03,CT-04 | INV-05,INV-07 | HT-18 | Pendências preservam identidade e destino | Referência à síntese; cotejo original pendente |
| TR-INT02-001 | Autorizações granulares por escopo e finalidade | CT-02 | INV-03 | HT-01 | Permissão vigente por escopo e finalidade | Referência à síntese; cotejo original pendente |
| TR-INT02-002 | Credenciais protegidas em infraestrutura central | CT-02,CT-11 | INV-12,INV-14 | HT-02 | Segredo inacessível em registro/log | Referência à síntese; cotejo original pendente |
| TR-INT02-003 | Revogação, expiração, rotação e revalidação contextual | CT-02,CT-09 | INV-03 | HT-02 | Revogação impede nova ação protegida | Referência à síntese; cotejo original pendente |
| TR-INT02-004 | Não assumir aprovação humana nem permissão autônoma de IA | CT-02,CT-11 | INV-04,INV-13 | HT-15 | Aprovação humana não substituída por IA | Referência à síntese; cotejo original pendente |
| TR-INT03-001 | Esquemas versionados e correspondência de campos | CT-03,CT-07 | INV-10 | HT-03 | Mapeamento validado por esquema e versão | Referência à síntese; cotejo original pendente |
| TR-INT03-002 | Validações e transformações tipadas com proveniência | CT-03,CT-11 | INV-14 | HT-03 | Transformação reproduzível e rastreável | Referência à síntese; cotejo original pendente |
| TR-INT03-003 | Prévia sem escrita e sem mesclagem automática de dados | CT-07,CT-02 | INV-02,INV-11 | HT-07 | Prévia não persiste efeitos de negócio | Referência à síntese; cotejo original pendente |
| TR-INT03-004 | Compatibilidade de esquema antes de aplicar mudanças | CT-01,CT-03 | INV-10 | HT-12 | Versões incompatíveis não aplicadas | Referência à síntese; cotejo original pendente |
| TR-INT04-001 | Operações técnicas tipadas e capacidades homologadas | CT-01,CT-03 | INV-02,INV-11 | HT-01 | Capacidade e comando tipados elegíveis | Referência à síntese; cotejo original pendente |
| TR-INT04-002 | Separar comando enviado, aceito, aplicado e confirmado | CT-03,CT-04 | INV-05 | HT-08 | Confirmação proprietária independente do aceite | Referência à síntese; cotejo original pendente |
| TR-INT04-003 | Idempotência e controle de tentativas e concorrência | CT-03,CT-04 | INV-06,INV-07 | HT-08 | Mesma operação lógica não gera escrita dupla | Referência à síntese; cotejo original pendente |
| TR-INT04-004 | Sem endpoints genéricos nem decisão autônoma de negócio | CT-02,CT-03 | INV-02,INV-11 | HT-15 | Operação genérica/não autorizada bloqueada | Referência à síntese; cotejo original pendente |
| TR-INT05-001 | Aplicações autorizadas por tenant, escopo e plano | CT-02,CT-03 | INV-01,INV-03 | HT-13 | Aplicação isolada e autorizada | Referência à síntese; cotejo original pendente |
| TR-INT05-002 | API pública bloqueada no trial, preservando nativas autorizadas | CT-02,CT-03 | INV-03,INV-16 | HT-04 | API pública do trial bloqueada, nativa preservada | Referência à síntese; cotejo original pendente |
| TR-INT05-003 | Quota, revogação e auditoria de acessos | CT-02,CT-11 | INV-03,INV-14 | HT-04 | Quota/revogação e consultas rastreáveis | Referência à síntese; cotejo original pendente |
| TR-INT05-004 | API não contorna o módulo proprietário | CT-02,CT-04 | INV-02 | HT-13 | API não conclui ato reservado ao proprietário | Referência à síntese; cotejo original pendente |
| TR-INT06-001 | Webhooks autenticados, íntegros e protegidos de replay | CT-05,CT-02 | INV-03,INV-14 | HT-05 | Origem e assinatura conferidas | Referência à síntese; cotejo original pendente |
| TR-INT06-002 | Deduplicação e tratamento de eventos fora de ordem | CT-05,CT-04 | INV-07 | HT-05 | Eventos duplicados/tardios sem efeito indevido | Referência à síntese; cotejo original pendente |
| TR-INT06-003 | Separar entrega/aceite de confirmação de negócio | CT-04,CT-05 | INV-05 | HT-05 | Aceite externo não declara fato de negócio | Referência à síntese; cotejo original pendente |
| TR-INT06-004 | Eventos oficiais internos permanecem centrais | CT-05,CT-11 | INV-02,INV-14 | HT-16 | Evento interno publicado via Motor Central | Referência à síntese; cotejo original pendente |
| TR-INT07-001 | Sincronização delimitada com autoridade por campo | CT-06,CT-02 | INV-02,INV-03 | HT-06 | Campo/fonte/destino conforme autorização | Referência à síntese; cotejo original pendente |
| TR-INT07-002 | Cursores/checkpoints e retomada segura | CT-06,CT-04 | INV-07 | HT-06 | Checkpoint resiste a interrupção e retomada | Referência à síntese; cotejo original pendente |
| TR-INT07-003 | Conflitos, ciclos, exclusões e resultados desconhecidos explícitos | CT-06,CT-09 | INV-07,INV-08 | HT-08 | Conflito/inconclusão bloqueia escrita insegura | Referência à síntese; cotejo original pendente |
| TR-INT07-004 | Sem último-escritor universal ou sobrescrita de fonte oficial | CT-06,CT-04 | INV-02 | HT-06 | Fonte oficial não é sobrescrita sem autoridade | Referência à síntese; cotejo original pendente |
| TR-INT08-001 | Importação/exportação/migração delimitada e autorizada | CT-07,CT-02 | INV-02,INV-03 | HT-07 | Importação/migração com autorização e finalidade | Referência à síntese; cotejo original pendente |
| TR-INT08-002 | Prévia, validação segura e mapeamentos INT-03 | CT-07,CT-03 | INV-11,INV-15 | HT-07 | Prévia e arquivo validados sem escrita | Referência à síntese; cotejo original pendente |
| TR-INT08-003 | Lotes, checkpoints, idempotência e aceite fundamentado | CT-07,CT-04 | INV-07 | HT-07 | Lote reconciliado com aceite fundamentado | Referência à síntese; cotejo original pendente |
| TR-INT08-004 | Exportação com escopo, links temporários e auditoria | CT-07,CT-11 | INV-01,INV-14 | HT-17 | Exportação protegida por escopo e auditoria | Referência à síntese; cotejo original pendente |
| TR-INT08-005 | Sem exclusão, mesclagem ou rollback universal por decisão própria | CT-07,CT-10 | INV-02,INV-09 | HT-07 | Sem rollback universal/mesclagem não autorizada | Referência à síntese; cotejo original pendente |
| TR-INT09-001 | Modelos e instâncias versionados sem mutação silenciosa | CT-08,CT-01 | INV-10 | HT-09 | Instância preserva versão ativa | Referência à síntese; cotejo original pendente |
| TR-INT09-002 | Dependências e convergência por resultados oficialmente confirmados | CT-08,CT-04 | INV-05 | HT-09 | Convergência somente com confirmação oficial | Referência à síntese; cotejo original pendente |
| TR-INT09-003 | Comandos idempotentes com correlação, estados e tentativas | CT-03,CT-08 | INV-06,INV-07 | HT-08 | Comando e tentativa causalmente rastreáveis | Referência à síntese; cotejo original pendente |
| TR-INT09-004 | Reconciliação antes de retentar escrita inconclusiva | CT-04,CT-09 | INV-08 | HT-08 | Escrita incerta reconciliada antes de reexecução | Referência à síntese; cotejo original pendente |
| TR-INT09-005 | Compensação somente pela autoridade competente | CT-08,CT-10 | INV-02,INV-09 | HT-10 | Compensação elegível autorizada e verificada | Referência à síntese; cotejo original pendente |
| TR-INT09-006 | IA não comanda operações críticas nem altera produção autonomamente | CT-02,CT-08 | INV-13 | HT-15 | IA não executa ato crítico sem autoridade | Referência à síntese; cotejo original pendente |
| TR-INT10-001 | Saúde, diagnóstico e classificação de incidentes técnicos | CT-09,CT-11 | INV-14 | HT-11 | Saúde observada e incidente sustentado por telemetria | Referência à síntese; cotejo original pendente |
| TR-INT10-002 | Recuperação homologada e limitada, com revalidação | CT-09,CT-02 | INV-03,INV-08 | HT-11 | Recuperação dentro da política e alçada | Referência à síntese; cotejo original pendente |
| TR-INT10-003 | Estados inconclusivos reconciliados antes de nova escrita | CT-09,CT-04 | INV-08 | HT-08 | Resultado inconclusivo não gera nova escrita | Referência à síntese; cotejo original pendente |
| TR-INT10-004 | Saúde do provedor não confirma conclusão do negócio | CT-09,CT-04 | INV-05 | HT-11 | Normalização técnica não equivale à de negócio | Referência à síntese; cotejo original pendente |
| TR-INT10-005 | Sem serviços paralelos de observabilidade e auditoria | CT-09,CT-11 | INV-14 | HT-16 | Observabilidade/Auditoria Geral reutilizadas | Referência à síntese; cotejo original pendente |
| TR-INT11-001 | Catálogo e homologação de versão de conectores | CT-01,CT-11 | INV-10 | HT-01 | Versão publicada possui homologação comprovável | Referência à síntese; cotejo original pendente |
| TR-INT11-002 | Submissão, testes, aprovação e publicação controlada | CT-01,CT-02 | INV-03,INV-10 | HT-18 | Publicação depende de aprovação humana válida | Referência à síntese; cotejo original pendente |
| TR-INT11-003 | Sem execução livre ou publicação autônoma por terceiros | CT-01,CT-02 | INV-11 | HT-18 | Terceiro não publica ou executa código arbitrário | Referência à síntese; cotejo original pendente |
| TR-INT11-004 | Suspensão/descontinuação com tratamento das conexões afetadas | CT-01,CT-09 | INV-02,INV-10 | HT-18 | Suspensão/retirada trata conexões afetadas | Referência à síntese; cotejo original pendente |
| TR-INT12-001 | Rastreabilidade de comandos, decisões, versões e confirmações | CT-11,CT-04 | INV-05,INV-14 | HT-14 | Rastro de comando a confirmação verificável | Referência à síntese; cotejo original pendente |
| TR-INT12-002 | Correlação e investigação de evidências com proveniência | CT-11,CT-09 | INV-14 | HT-14 | Hipóteses separadas de evidências | Referência à síntese; cotejo original pendente |
| TR-INT12-003 | Exportações autorizadas e minimizadas, segregadas por tenant | CT-11,CT-02 | INV-01,INV-03 | HT-17 | Exportação segregada, minimizada e autorizada | Referência à síntese; cotejo original pendente |
| TR-INT12-004 | Auditoria da própria consulta e exportação | CT-11 | INV-14 | HT-17 | Consulta/exportação também é auditável | Referência à síntese; cotejo original pendente |
| TR-INT12-005 | Apenas Auditoria Geral como trilha oficial, IA consultiva | CT-11,CT-02 | INV-13,INV-14 | HT-14 | Auditoria Geral fonte única; IA só interpreta | Referência à síntese; cotejo original pendente |

## Autoridade e precedência
- A associação CT não transfere competência de módulos de negócio. Referência à INT não basta para conceder acesso; detalhes de [P0-SEG](P0-SEG-matriz-autorizacoes-proposta.md) e [matriz de proprietários](INT-P0-autoridades-contratos.md) continuam obrigatórios.
- CT-02 não é novo IAM/autorizador e CT-11 não é banco de auditoria adicional.
- A fonte oficial do resultado e os estados próprios de cada negócio permanecem nos respectivos proprietários, não na INT-09 ou INT-10.
- A proposta de associação CT pode ser revista sem revogar o requisito funcional aprovado. Exigir revisão expressa se mudar efeito, escopo, autonomia ou regra de negócio.

## Auditoria de correspondência e proveniência (03/11/2026)

- [P0-TR-AUD](P0-TR-AUD-auditoria-proveniencia-requisitos.md) conferiu 53/53 IDs entre as matrizes iniciais, com referências a todos os 18 HT e 16 INV.
- Não se concluiu que sejam somente 53 requisitos no módulo, que suas sínteses tenham redação literal ou que cada vínculo CT/HT seja definitivo. Faltam as propostas integrais e a revisão por módulo/proprietário.

## Pendências antes de declarar P0-TR-01 encerrada
1. Recuperar a **lista exaustiva** de requisitos das propostas e aprovações individuais (INT-01 a INT-12) e do planejamento transversal.
2. Inserir referência inequívoca a texto integral, versão, exceções e decisão de origem por requisito (P0-DOC-01).
3. Validar com proprietários a família CT e os campos/operação associados; desdobrar TR quando houver atos distintos.
4. Completar identidade, escopo por recurso/campo, alçada, confirmação oficial e critério de evidência verificável.
5. Revisar cobertura HT e obter aceite documental; não executar testes nesta fase.

**Status P0-TR-01:** PARCIAL. **Status P0-CON-01:** ABERTO.
