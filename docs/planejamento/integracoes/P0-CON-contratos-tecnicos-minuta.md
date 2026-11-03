# P0-CON — Contratos técnicos compartilhados (MINUTA PARA VALIDAÇÃO)

**Estado:** proposta documental derivada de [INT-R01](INT-R01-governanca-contratos-homologacao.md) e [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md). **Não aprova automaticamente nomes de campos, enums de API, prazos, taxas, tecnologia ou alçadas adicionais.** P0-CON-01 permanece aberto.
**Limite:** somente documentação; nenhum código, implantação, custo ou serviço/motor novo. Proprietários de negócio, Identidade/Segurança, Aprovações, Eventos, Filas, COM, Analytics e Auditoria Geral permanecem soberanos.

## 1. Famílias de contrato propostas — IDs de documentação, não novos módulos
| ID | Família | Responsabilidade e evidência exigida |
|---|---|---|
| CT-01 | Conector/conexão/capacidade | INT-11 homologa versões; INT-01 cadastra conexões; INT-04 descreve operações tipadas; conector homologado ≠ conexão autorizada |
| CT-02 | Autorização contextual | Identidade e Segurança + INT-02 + Aprovações aplicáveis; tenant, ator, finalidade, escopo e vigência, sem duplicar autorizador |
| CT-03 | Comando tipado | INT-04 e módulo proprietário; INT-09 quando coordena; identificar operação, recurso, versão, precondição, idempotência e origem |
| CT-04 | Resultado/confirmacão | Executor técnico e módulo proprietário; separar transmissão, aceite, aplicação técnica e confirmação oficial do efeito |
| CT-05 | Evento/webhook | INT-06 rege fronteira externa; Motor Central de Eventos rege eventos internos; integridade, autoria, versão, duplicidade e ordem |
| CT-06 | Sincronização/checkpoint | INT-07; origem/destino/escopo, cursor, conflitos, efeito confirmado, retomada segura |
| CT-07 | Lote de importação/exportação/migração | INT-08, Arquivos e domínio; arquivo, versão, mapeamento, prévia, processamento e aceite por lote/registro |
| CT-08 | Processo/instância/etapa | INT-09; modelos versionados, instância preservada, dependências, comandos, aprovação e resultado |
| CT-09 | Incidente/recuperação | INT-10 + componentes centrais; saúde observada, hipótese, autorização, recuperação e verificação, sem declarar sucesso de negócio |
| CT-10 | Compensação | INT-09 coordena; proprietário decide, aprova e confirma conforme efeito/reversibilidade; sem rollback universal |
| CT-11 | Evidência/auditoria | Auditoria Geral é fonte oficial; INT-12 correlaciona, investiga e exporta conforme permissão e finalidade |

## 2. CT-03 — Envelope conceitual proposto (NÃO é schema aprovado)
| Grupo | Identificadores / campos candidatos | Controle |
|---|---|---|
| Identidade | tenant, unidade/marca, actor_ref, service_principal_ref | Menor privilégio e segregação |
| Responsabilidade | origin_module, owner_module, operation_type, resource_ref, field_scope | Módulo proprietário decide |
| Técnica | command_id, logical_operation_id, attempt_id, correlation_id, causation_id | Identidade lógica não muda em retentativa |
| Contrato | contract_version, connector_version, schema_ref, expected_state_version | Compatibilidade antes de execução |
| Autorização | purpose_ref, auth_context_ref, approval_ref, connection_ref | Referência não substitui revalidação |
| Consistência | idempotency_ref, expected_state, dependencies, checkpoint_ref | Não duplicar escritas, concorrência controlada |
| Prazo | technical_deadline_ref, policy_ref, attempt_window_ref | Timeout técnico ≠ prazo do negócio |
| Evidência | audit_ref, external_result_ref, official_confirmation_ref | Não armazenar tokens nem payload pessoal em log |

**Status:** nomes e obrigatoriedade condicionada ainda requerem discussão; podem ser alterados em revisão sem alterar as regras aprovadas.

## 3. CT-04 — Estados de interoperabilidade aprovados
| Marco | Demonstra | NÃO comprova |
|---|---|---|
| Preparado | Comando local verificado | Efeito externo |
| Enviado | Evidência de tentativa de transmissão | Recebimento no destino |
| Aceito | Destino reconheceu o pedido | Efeito de negócio concluído |
| Aplicado | Evidência técnica da execução | Confirmação oficial do proprietário |
| Confirmado | Fonte oficial competente validou o fato específico | Outros efeitos não verificados |
| Rejeitado | Rejeição do comando/fato com origem | Ausência de efeitos parciais |
| Pendente | Trabalho ainda aguardado | Sucesso ou fracasso definitivo |
| Inconclusivo | Resposta insuficiente, ambígua ou divergente | Segurança de repetir escrita |

Estados de transporte, execução, negócio, instância, sincronização, compensação e lote são **distintos**, sem máquina de estados universal concorrente.

## 4. Classes candidatas de erro (NÃO são códigos definitivos)
| Classe proposta | Tratamento conforme princípios aprovados |
|---|---|
| AUTH_INVALID / AUTH_REVOKED | Bloquear operações protegidas, revalidar credenciais/escopo e encaminhar à competência |
| CONTRACT_INCOMPATIBLE | Impedir operação com versão/esquema não homologado |
| QUOTA_OR_RATE | Respeitar limites, suspender retentativas excessivas |
| TRANSIENT_FAILURE | Retentativa limitada se realmente segura e autorizada |
| BUSINESS_REJECTION | Preservar decisão proprietária, sem convertê-la em falha técnica |
| UNKNOWN_WRITE_OUTCOME | Reconciliar antes de repetir uma escrita potencialmente duplicável |
| PARTIAL_EFFECT | Preservar efeitos comprovados, analisar compensação elegível |
| SECURITY_EVENT / EVIDENCE_GAP | Segurança/investigação e auditoria, sem inventar resultados |

Nenhuma resposta HTTP 2xx/202, webhook ou workflow n8n concluído é prova universal de persistência de negócio.

## 5. Consistência distribuída, concorrência e versão
- Idempotência é contextual (tenant, pedido lógico, efeito, recurso, versão do contrato) e exige prova do mecanismo do destino quando necessário.
- Uma submissão idêntica nos dados pode ser legitimamente outra submissão; não deduplicar apenas por conteúdo.
- Checkpoints e persistência durável, filas e outbox quando aplicáveis; sem ACID global/exatamente-uma-vez entre serviços.
- Resultado desconhecido exige consulta/reconciliação e controle de concorrência antes de retry de escrita.
- Compensação tem elegibilidade e confirmação específicas; pode ser parcial, irreversível ou inconclusiva.
- Mudança de conector/contrato/modelo não altera silenciosamente uma instância ativa.
- Os 3+15 dias de filas de leads, limpeza em 24h de entregues e 90 dias de auditoria técnica de filas são regras especializadas IN-127/128/147/148/149, não prazos universais das INTs.

## 6. Dois percursos para orientar o detalhamento
**Formulário Imagine → CRM:** API principal ou de contingência confirma recepção somente após persistência durável. O CRM somente é considerado destinatário efetivo após persistir/criar/associar o lead oficialmente; retries preservam ID lógico, e conteúdo idêntico não elimina submissões distintas. O destino anterior não muda silenciosamente com novo vínculo de site.

**Operação de FIN orquestrada:** INT-09 coordena pedido tipado, INT-04 transporta; aceite de provedor é técnico e FIN confirma o fato financeiro. Timeout após escrita pede reconciliação antes de reenvio; estorno, troca de provedor crítico ou compensação não são decisões livres da IA/INT-09.

## 7. Aplicação das famílias de contrato aos requisitos e atos

- [P0-TR53 — 53 associações propostas TR → CT](P0-TR53-vinculo-contratos-aceite.md).
- [P0-OP — 12 casos operacionais de referência](P0-OP-casos-operacionais-contratos-alcadas.md).

Essas referências não aprovam schemas, decisões funcionais ou execução.

## 8. Especialização dos contratos CT-03 e CT-04

- [P0-CON CT-03/CT-04 — Comandos, resultados, transições, idempotência e reconciliação](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) aprofunda as regras já aprovadas, mantendo campos, máquinas de estados, estratégias de retry e esquemas físicos na condição de **proposta para validação**.
- Não presumir que essa minuta autorize desenvolvimento, configure uma segunda máquina de estados ou confirme fatos de negócio fora do módulo proprietário.

## 9. Decisão CT-03/CT-04 e detalhamento por operação

- [Aprovação expressa CT-03/CT-04](P0-DEC-aprovacao-CT03-CT04.md): especificação documental de base aceita, sem aprovar schemas físicos ou parâmetros por provedor.
- [24 fichas de operações de referência](P0-OP24-detalhamento-CT03-CT04-por-operacao.md): aplicação da base aprovada como **proposta operacional de revisão**, com fronteiras de segurança e confirmações.

## 10. Especificações CT-05 a CT-11 (03/11/2026)

- [P0-CON CT-05 a CT-11 — minutas de contratos técnicos](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) apresenta **sete fichas conceituais**, com dados, marcos, falhas, reconciliação, exemplos negativos e critérios de revisão, ligadas a INT-06..12, CT-02/03/04, OP24, INV e HT.
- As diretrizes funcionais existentes permanecem aprovadas, mas **estas sete especificações são propostas documentais para aceite posterior**. Não aprovam schemas, perfis/alçadas, limites, retenções, versões, APIs, serviços, automações ou testes.
- [P0-GATE](P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) continua controlando P0-CON-01 como parcial/aberto; as fontes originais integrais e a homologação não estão concluídas.

## 11. Aprovação de CT-05 a CT-11 e revisão cruzada

- [Decisão P0-DEC CT-05..11](P0-DEC-aprovacao-CT05-CT11-base.md): sete famílias aceitas como **bases documentais**, sem aprovação de schemas físicos, enum, alçada ou parâmetros.
- [P0-CON Revisão cruzada CT-01..11](P0-CON-revisao-cruzada-CT01-CT11.md): 11 famílias comparadas, 105 associações TR→CT nas 53 linhas iniciais, 13 pontos de atenção e fronteiras preservadas; **não representa 105 requisitos distintos nem cotejo literal integral**.
- **CT-01** ainda tem apenas definição conceitual no inventário: precisa de minuta especializada e aprovação própria.

## 12. Especificação especializada CT-01 (03/11/2026)

- [P0-CON CT-01 — Conectores, contas, conexões e capacidades](P0-CON-CT01-conectores-conexoes-capacidades-minuta.md) desenvolve **minuta especializada da última família ainda somente conceitual**, com separação INT-01/02/04/11, elegibilidade contextual, estados distintos, suspensão e proteção de instâncias pendentes.
- **CT-01 segue em proposta para revisão**, não recebeu aceite especializado; CT-02..11 possuem bases documentais anteriormente aceitas. Nenhuma norma de conexão, enum, schema, alçada, provedor ou parâmetro foi aprovada por essa minuta.

## 13. Itens pendentes de aceite P0-CON-01
- CON-A: schemas físicos definitivos e campos condicionais por contrato.
- CON-B: transições de estados por tipo e situações inválidas.
- CON-C: erros/versionamento/garantias por provedor.
- CON-D: TTL, timeouts, limites, taxa, concorrência e frequência de retry por operação.
- CON-E: escopos, aprovações e identidade por recurso/campo.
- CON-F: compatibilidade/migração de versões e instâncias.
- CON-G: vínculo de cada contrato a requisitos TR e cenários HT.

**Conclusão:** minuta documentada para revisão; P0-CON-01, P0-VER-01 e P0-ACC-01 permanecem abertos.
