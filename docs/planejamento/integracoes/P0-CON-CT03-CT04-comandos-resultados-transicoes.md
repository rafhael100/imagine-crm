# P0-CON — CT-03 e CT-04: contratos de comando, resultado, estados e reconciliação

**Estado:** especificação **proposta para validação documental**, desenvolvida a partir das diretrizes **já aprovadas** em INT-R01/R02 e INT-01 a INT-12. **Não se presume aprovação automática das estruturas, exemplos, nomes de campos, políticas numéricas ou transições específicas desta minuta.**
**Fase:** somente planejamento e versionamento. Sem implementação em Laravel/PostgreSQL/n8n, alteração de filas, configuração real, testes em produção, contratação ou despesas.
**Competências:** INT-04 define execução técnica tipada; INT-09 coordena processos; INT-10 monitoramento/recuperação; INT-12 consulta/audita pela Auditoria Geral. **A autorização** vem de Identidade e Segurança, INT-02 e Central de Aprovações; **o resultado de negócio** é confirmado exclusivamente pelo módulo competente (FIN, CON, VEN, AGD, ATD, CAT, COM etc.).
**Referências:** [INT-R01](INT-R01-governanca-contratos-homologacao.md), [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md), [P0-CON](P0-CON-contratos-tecnicos-minuta.md), [P0-SEG](P0-SEG-matriz-autorizacoes-proposta.md), [P0-TR53](P0-TR53-vinculo-contratos-aceite.md), [P0-OP](P0-OP-casos-operacionais-contratos-alcadas.md).

## 1. Objetivo e fronteiras

- **CT-03** representa uma **solicitação de comando tipado** com identidade lógica, autorizações contextuais, versão, pré-condições, eventual chave de idempotência, controle de concorrência e evidências de transmissão.
- **CT-04** representa um **relato tipado de resultados e evidências** das etapas técnicas e da eventual confirmação por quem detém a competência de negócio.
- **CT-03 não executa por si só.** A execução só ocorre através dos serviços/operadores habilitados do componente proprietário e da infraestrutura central existente.
- **CT-04 não é um novo motor de estados ou uma Auditoria Geral paralela.** Cada módulo persiste seu estado oficial; CT-04 é o contrato de interoperabilidade e referência à fonte. Auditoria Geral detém evidências oficiais e políticas de integridade, proveniência, acesso e retenção.
- **Idempotência não elimina necessidade de reconciliação** quando o provedor não garante a deduplicação daquele efeito na janela e contexto aplicáveis.
- **Não oferecer comando de código livre, SQL, URL irrestrita, mutação direta em outro tenant ou alteração sem contrato homologado.**

## 2. Modelo de identidades (proposta; nomes de campos não aprovados)

| Identificador conceitual | Estabilidade requerida | Finalidade |
|---|---|---|
| tenant_ref / company_ref | Uma empresa/contexto autorizado; estável durante operação | Isolamento e atribuição |
| operation_ref | Mesma operação lógica entre componentes e tentativas | Relacionar intenção e efeitos |
| command_ref | Mesma intenção de comando tipado; não trocar só por retentativa | Idempotência/correlação de comando |
| attempt_ref | **Diferente** a cada tentativa técnica real | Observabilidade das tentativas |
| correlation_ref | Mesmo fluxo causal integrado | Unir etapas, eventos e incidentes |
| causation_ref | Evento/comando imediatamente motivador quando conhecido | Explicar causalidade, sem presumir ordem global |
| resource_ref / owner_module_ref | Identidade do alvo e dono de negócio | Competência e validação |
| contract_ref / schema_version_ref | Versão tipada aplicada à operação | Compatibilidade e reprodutibilidade |
| connection_ref / connector_version_ref | Conexão autorizada e versão homologada | Verificação de provedor e credenciais |
| requestor_ref / service_principal_ref | Identidade humana/técnica e delegação conforme escopo | Segurança e auditoria |
| approval_ref / purpose_ref | Referências válidas quando aplicáveis | Autoridade, sem expor dados sensíveis |
| idempotency_ref | Contextual por tenant + efeito + pedido lógico, segundo contrato | Impedir efeito repetido indevido |
| business_confirmation_ref | Referência **emitida pelo módulo proprietário** somente após comprovação | Evitar sucesso técnico como fato de negócio |

**Proposta:** não tratar os campos acima como obrigatórios em toda operação indiscriminadamente. O contrato tipado especificará obrigatoriedade condicional e política para referências ausentes; identificador fornecido pelo cliente não prova identidade/autorização.

## 3. CT-03 — Conteúdo conceitual e validações de entrada

| Grupo | Dados/validação propostos | Fonte de competência |
|---|---|---|
| Intenção | operation_type, efeito solicitado, recurso, versionamento do contrato | INT-04 + proprietário |
| Contexto | tenant, unidade/marca, finalidade, origem, ator e principal técnico | Identidade e Segurança |
| Permissões | escopos de recurso/campo, conexão, autorização externa e aprovação se exigida | INT-02 + Central de Aprovações + proprietário |
| Precondições | versão/estado esperado, dependência, janela e regra de negócio | Proprietário e motores centrais |
| Consistência | chave lógica, idempotência, concorrência e controle de tentativas | Contrato tipado + executor autorizado |
| Destino | provedor/conector/capacidade/versão homologados e elegíveis | INT-01/04/11 |
| Correlação | IDs de operação, comando, tentativa e causalidade | Contratos e Auditoria Geral |
| Tratamento | categoria de falha, timeout técnico, política de retry/reconciliação | INT-04/10 e proprietário |

**Antes de qualquer efeito:** validar elegibilidade do conector, vínculo da conexão, autorização *atual*, versão, pré-condição do proprietário, limite técnico e evidência de aprovação; falha deve produzir recusa/estado apropriado, **não** tentativa com privilégio improvisado.

## 4. CT-04 — Registros de resultado por dimensão

**Regra aprovada:** preparado, enviado, aceito, aplicado, confirmado, rejeitado, pendente e inconclusivo possuem significados diferentes. **Proposta de representação:** quatro dimensões independentes, evitando uma máquina única concorrente:

| Dimensão conceitual | Informações preservadas | Quem pode atestar |
|---|---|---|
| Transporte | Preparação e transmissão comprovada; erro/timeout, origem de resposta | INT-04/06 ou infraestrutura responsável |
| Processamento externo | Aceite, aplicação técnica, rejeição/pendência evidenciada pelo sistema externo | Adaptador + fonte externa identificada; não confere competência financeira/contratual |
| Fato de negócio | Aplicação/recusa/estado oficial, versão e referência do ato | **Somente módulo proprietário** |
| Reconciliação/recuperação | Estado incerto, consultas, divergências, correções autorizadas, compensação e evidências | INT-10/09 coordenam dentro da competência; proprietário decide efeitos |

As dimensões **não** são enums físicos aprovados. A representação final deve permitir que um transporte tenha falhado enquanto um efeito já tenha sido aplicado e, inversamente, que o transporte tenha sido aceito enquanto o negócio permaneça inconclusivo.

### 4.1. O que cada marco pode significar

| Marco | Comprovação necessária | Não significa |
|---|---|---|
| Preparado | Verificação local documentada, sem transmissão ainda comprovada | Efeito em outro sistema |
| Enviado | Tentativa de transmissão identificável | Recebimento ou aplicação |
| Aceito | Confirmação técnica da recepção/aceite conforme contrato | Conclusão da operação de negócio |
| Aplicado | Fonte externa ou executor competente comprova efeito técnico definido | Confirmação oficial de todo o processo |
| Confirmado | **Fonte oficial do proprietário**, com referência ao efeito exato | Outros resultados/etapas não certificados |
| Rejeitado | Fonte, motivo e escopo da rejeição conhecidos | Que nenhum efeito parcial ocorreu |
| Pendente | Fato está aguardando resultado sob política e prazo | Sucesso ou falha definitiva |
| Inconclusivo | Evidências faltantes, ambíguas, atrasadas ou conflitantes | Permissão de reenviar uma escrita |

**Semântica obrigatória:** “confirmado” deve sempre responder “**o quê** foi confirmado e **por qual autoridade**”. Confirmação de recebimento de webhook não pode ser usada como confirmação de pagamento, contrato, reserva, lead ou entrega final.

## 5. Matriz de transições CT-03/04 — **proposta para análise**, não enum definitivo

| Situação anterior / observação | Próxima conclusão permitida conforme comprovação | Condição e salvaguarda |
|---|---|---|
| Preparado, sem tentativa de transmissão | Cancelado localmente / suspenso / enviado | Cancelamento local só se ausência de transmissão comprovada; momento e precondições rastreados |
| Enviado, recebeu aceite técnico | Aceito na **dimensão de transporte** | Não promover automaticamente negócio para confirmado |
| Enviado, timeout ou perda da resposta | Inconclusivo / pendente de reconciliação | **Não** marcar rejeitado nem repetir escrita arriscada |
| Aceito, processamento assíncrono | Pendente / aplicado / rejeitado conforme evidência específica | Confirmar em fonte aplicável; eventos duplicados ou tardios exigem correlação |
| Aplicado externamente, sem confirmação proprietária | Aplicado tecnicamente + negócio pendente/inconclusivo | Falha de negócio pode persistir apesar do sucesso externo |
| Proprietário emitiu confirmação verificável | Confirmado o **efeito oficial específico** | Identidade, versão, recurso e causalidade conferidos |
| Rejeição informada pelo provedor após aceitação | Rejeitado tecnicamente **apenas para o efeito atestado** | Investigar efeitos já aplicados e divergências antes de concluir ausência de efeito |
| Evento tardio contradiz estado conhecido | Divergência / reconciliação necessária, sem sobrescrita silenciosa | Preservar ambas as evidências e ordem causal |
| Resultado parcial | Efeitos confirmados preservados, outras partes pendentes | Não substituir por rejeição total nem rollback presumido |
| Revogação durante execução assíncrona | Restringir próximas ações protegidas; avaliar efeitos já enviados | Não presumir reversão, verificar autorização ao retomar |
| Cancelamento/timeout da orquestração INT-09 | Estado técnico cancelado/suspenso com pendências explícitas | Não cancelar financeiramente/reservar/excluir sem competência |
| Compensação autorizada e executada | Registrada por efeito, com confirmação própria | Compensação parcial/inconclusiva continua visível |

**Proposta de integridade:** registrar revisões/observações como novos fatos com referência causal; visões consolidadas não podem apagar fatos contraditórios. Não impor monotonicidade de um único campo quando o domínio exige retificação oficial; correções seguem autoridade e trilha oficial.

## 6. Idempotência contextual, tentativas e concorrência

1. **Identidade lógica:** o mesmo pedido/efeito conserva operation_ref e command_ref durante tentativas. Cada tentativa recebe attempt_ref único e o motivo verificável.
2. **Contexto:** idempotency_ref será delimitada por tenant, operação, recurso/efeito e identidade do pedido conforme contrato; colisão entre tenants ou efeitos distintos não equivale a duplicata.
3. **Retentativa não é novo negócio:** uma tentativa repetida não gera segunda autorização de pagamento, novo lead ou nova reserva. Ainda assim, a segurança depende das garantias reais do provedor e da reconciliação.
4. **Concorrência:** evitar escritas conflitantes no mesmo efeito/recurso com precondições versionadas e coordenação apropriada; não bloquear recursos independentes sem justificativa.
5. **Deduplicação de evento:** webhook repetido se relaciona ao comando original, mas o recebimento da réplica não deve repetir o efeito confirmado.
6. **Sem global exactly-once/ACID:** filas, outbox e checkpoints podem reduzir perdas/repetições, sem assegurar atomicidade entre serviços externos. Persistir evidências e reconciliar.
7. **Expiração técnica não reinterpreta o fato:** vencer um timeout não significa que um pagamento/atendimento/contrato falhou ou foi revertido.
8. **Mudança de versão:** a retentativa preserva o contrato/versão adequados ao comando, salvo migração expressamente autorizada e compatível.

## 7. Política de retentativas proposta por categoria (sem valores numéricos)

| Categoria | Repetição automática | Regra de proteção |
|---|---|---|
| Consulta sem escrita | Potencialmente permitida, limitada e autorizada | Respeitar quota, risco, credencial e limite do contrato |
| Escrita rejeitada antes de qualquer transmissão, com prova suficiente | Reavaliação possível | Verificar versão/precondições; ato novo pode exigir nova autorização |
| Escrita com idempotência externa comprovada e resultado ainda não conhecido | Condicionada à prova da semântica e estado, não presumida | Preferir consulta/reconciliação; preservar chave/contexto |
| Escrita transmitida com timeout/resultado desconhecido | **Bloqueada até reconciliação ou decisão formal segura** | Não repetir cegamente, principalmente FIN/CON/AGD |
| Quota/rate limit | Suspensão e retomada conforme limite real | Não agendar tempestade de retries |
| Credencial revogada/escopo expirado | Sem retry com privilégio alternativo | Identidade e Segurança/INT-02 governam regularização |
| Erro de contrato/esquema | Bloquear versão incompatível | Homologação/versionamento INT-03/04/11 |
| Falha permanente ou segurança | Escalonar conforme política | Não usar IA para contornar autorização |
| Compensação de negócio | Nunca genérica | Elegibilidade, reversibilidade e aprovação do proprietário |

**Parâmetros ainda não decididos:** intervalos, multiplicadores, jitter, máximo de tentativas, prazos, custos, quotas, concorrência, retenções e respostas específicas de cada provedor.

## 8. Procedimento conceitual de reconciliação (proposta)

1. Localizar operação lógica e todas as tentativas/correlações, sem alterar o estado de negócio por hipótese.
2. Identificar módulo proprietário, conexão/versão, autorizações atuais, recurso e efeito solicitado.
3. Consultar fonte oficial externa ou interna por mecanismo **homologado e autorizado**, preferencialmente apenas leitura.
4. Comparar evidências de envio, aceite, aplicação, rejeição, eventos fora de ordem, checkpoints e registros oficiais; registrar lacunas.
5. Se comprovado, encaminhar resultado factual ao proprietário para registro/confirmação da competência; se divergente, manter inconclusivo/pendente e escalar.
6. Reexecutar comando somente se operação, autorização, contexto e idempotência permitirem e se efeito duplicado estiver controlado.
7. Para efeito parcial, propor compensação **tipada**, com elegibilidade/risco/alçada, autorização competente, execução e verificação próprias.
8. Correlacionar incidentes/consulta no INT-10 e evidências no INT-12/Auditoria Geral, sem trilhas duplicadas; conservar resultados desconhecidos quando ainda não resolvidos.
9. Encerrar apenas a etapa tecnicamente comprovada, mantendo riscos, pendências, impacto e necessidade de intervenção explícitos.

## 9. Casos de aceite documental — **planejados, não testados**

| ID de caso documental (proposto) | Cenário | Resultado esperado | HT relacionada |
|---|---|---|---|
| CT34-AC01 | Envio de lead recebido duravelmente em API, ainda não persistido no CRM | Transporte aceito; **entrega ao CRM não confirmada** | HT-05, HT-08, HT-14 |
| CT34-AC02 | Retentativa da mesma submissão lógica, resposta anterior perdida | Mesma operação e identidade idempotente; reconciliação sem lead duplicado | HT-05, HT-08 |
| CT34-AC03 | Duas submissões legitimamente distintas com mesmo nome/e-mail | Não deduplicar por semelhança de dados | HT-05, HT-07 |
| CT34-AC04 | Pagamento enviado externamente, timeout antes de resposta | Negócio inconclusivo; **sem nova cobrança automática** | HT-08, HT-10 |
| CT34-AC05 | Conector responde HTTP 200 mas resultado de reserva ainda pendente | Aceite técnico distinto da confirmação do AGD | HT-08, HT-09 |
| CT34-AC06 | Permissão revogada entre aceite e etapa assíncrona protegida | Nova escrita não autorizada impedida; efeito anterior investigado | HT-02, HT-09 |
| CT34-AC07 | Webhook duplicado e atrasado chega após confirmação oficial | Evidência preservada sem duplicar o efeito nem reverter arbitrariamente | HT-05, HT-14 |
| CT34-AC08 | Instância INT-09 suspensa após uma etapa aplicada | Efeitos confirmados permanecem; etapas pendentes são explícitas | HT-09, HT-10 |
| CT34-AC09 | Compensação autorizada executa apenas parcialmente | Estado parcial verificável, sem chamar rollback total | HT-10, HT-14 |
| CT34-AC10 | Versão de conector suspensa antes da retomada | Bloquear nova ação incompatível; não migrar instância silenciosamente | HT-12, HT-18 |
| CT34-AC11 | Tentativa com tenant diferente do contexto assinado/autorizado | Rejeitar, isolar e auditar, sem consultar dados alheios | HT-13, HT-17 |
| CT34-AC12 | Log técnico expira e auditoria de negócio ainda requer retenção | Preservar evidências oficiais sob política correta, sem retenção ilimitada genérica | HT-14, HT-17 |

Casos CT34-AC01..12 detalham **propostas de procedimentos** dentro dos cenários HT aprovados. Não substituem nem acrescentam nova automação INT ou HT obrigatória sem decisão. Retenções de lead IN-127/128/147/148/149 e logs IN-162 **não** viram prazos globais desta especificação.

## 10. Matriz de propriedade e decisão

| Fato ou decisão | Provedor/executor técnico | INT-04 | INT-09 | INT-10 | INT-12 | Proprietário do negócio |
|---|---|---|---|---|---|---|
| Preparação/transmissão e recibo | Relata/atestável conforme contrato | Registra evidência de transporte | Correlaciona etapa | Diagnostica falha | Consulta evidência | Valida seus próprios pré-requisitos |
| Aceite/aplicação técnica | Fonte técnica documentada | Traduz status técnico sem promoção a negócio | Aguarda dependência | Reconcilia se necessário | Investiga | Avalia o impacto/efeito |
| Ato oficial de negócio | Não é autoridade por padrão | Não confirma | Não confirma | Não confirma | Não confirma | **Atesta conforme regras do seu domínio** |
| Compensação | Executa só comando autorizado | Transporta | Coordena | Auxilia diagnóstico | Audita | **Autoriza/efetua/confirma o ato de sua competência** |
| Auditoria/retenção | Sinais técnicos minimizados | Correlaciona IDs | Referencia | Usa observabilidade | Visão especializada | Auditoria Geral é trilha oficial; módulos são donos dos fatos |

## 11. Pendências para apreciação e encerramento

| Item | Detalhe a validar | Estado |
|---|---|---|
| CT34-P01 | Campo/semântica de IDs, chaves, obrigatoriedade por operação, schema JSON/API | **Proposto** |
| CT34-P02 | Estados específicos de transporte/processamento/negócio e transições permitidas | **Proposto** |
| CT34-P03 | Protocolo oficial de confirmação por módulo, referenciação de evidências e retificações | **Proposto** |
| CT34-P04 | Erros e provas de idempotência/efeitos externos por capacidade/provedor | **Pendente** |
| CT34-P05 | Concurrency/versionamento, política de retries e timeouts por tipo | **Pendente de parâmetros** |
| CT34-P06 | Regime de falhas tardias, compensações e reconciliação por proprietário | **Proposto** |
| CT34-P07 | Cobertura de casos a todos os TR-INTxx-### e HT-01..18 | **Parcial** |
| CT34-P08 | Fonte literal das aprovações INT e revisão dos termos com responsáveis | **Pendente** |
| CT34-P09 | Plano de homologação e documentação de evidências, sem execução | **Proposto** |

**Status:** minuta especializada CT-03/CT-04 registrada; **P0-CON-01, P0-TR-01, P0-OWN-01, P0-SEC-01, P0-VER-01 e P0-ACC-01 permanecem em revisão/abertos**. Esta minuta não encerra o conjunto INT nem autoriza programação.
