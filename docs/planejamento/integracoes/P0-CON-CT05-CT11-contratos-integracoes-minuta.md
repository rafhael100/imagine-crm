# P0-CON — Especificação documental de CT-05 a CT-11 (MINUTA)

**Data:** 2026-11-03. **Estado:** **PROPOSTA PARA REVISÃO E FUTURA APROVAÇÃO DOCUMENTAL** das sete famílias técnicas já listadas em [P0-CON](P0-CON-contratos-tecnicos-minuta.md).
**Alcance da continuidade autorizada:** especificar contratos de integração existentes, não criar uma INT-13, automação, motor de estados, autorizador, fila, central de observabilidade, auditoria ou serviço adicional.
**Origem funcional:** [INT-R01](INT-R01-governanca-contratos-homologacao.md) (12 seções aprovadas), [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md) (INV-01..16, HT-01..18), sínteses [INT-06..12](README.md), [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md) e [CT-03/04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) aceitas como bases documentais.
**Limite probatório:** sínteses INT e relações TR→CT→HT são úteis, mas a recuperação literal integral das propostas/aceites originais continua P0-DOC-01/P0-TR-01 pendente. **Não afirmar que a minuta CT-05..11 está aprovada só porque a família já existe.**
**Fase:** planejamento/documentação exclusivamente. Nenhuma programação, teste, migração, API, webhook, workflow n8n, configuração de produção, contratação, aprovação humana operacional ou nova despesa.

## 1. Princípios transversais preservados

1. **Autoridade por ato:** somente o proprietário de negócio confirma seu efeito. INT-04 transporta, INT-09 coordena, INT-10 diagnostica e INT-12 investiga; nenhuma substitui FIN/CON/AGD/VEN/ATD/CAT/COM e demais domínios.
2. **Segurança:** toda ação protegida exige identidade/tenant/finalidade/recurso/campo/operação/contexto elegível segundo CT-02, Identidade e Segurança, INT-02 e Central de Aprovações quando cabível, inclusive em retomada e compensação.
3. **Consistência:** CT-03 e CT-04 distinguem comando lógico, tentativa técnica, transporte, aplicação técnica, resultado oficial e inconclusão. Sem exatamente-uma-vez global, sem reescrita arriscada antes de reconciliação.
4. **Versões:** contratos, esquemas, capacidade, conector e instância mantêm referências; atualização incompatível requer elegibilidade/homologação/migração autorizada, sem alteração silenciosa de instância ativa.
5. **Serviços existentes:** Eventos, Filas, Tarefas, Datas e Prazos, Aprovações, Arquivos, COM, Observabilidade IN-159/160 e Auditoria Geral permanecem únicos e responsáveis pelos seus objetos.
6. **IA:** recomendação assistiva e playbooks técnicos N2 somente homologados/autorizados; ação crítica N3 depende da autoridade humana quando exigida, sem aprovação duplicada da mesma decisão.
7. **Prazos e franquias:** regras especiais de fila de leads 3+15 dias/24h/90d, logs IN-162 e arquivos 1/5/20/50 GB não são aplicáveis indiscriminadamente a todas as CT; limites gerais e custos estão por dimensionar.

## 2. Índice de cobertura e rastreabilidade inicial

| CT | Objeto de intercâmbio | Fichas OP24 relacionadas | Requisitos TR de referência | Cenários HT inicialmente relacionados | Estado |
|---|---|---|---|---|---|
| CT-05 | Eventos e webhooks externos | OP-06-IN, OP-06-OUT, OP-04-RSL | TR-INT06-001..004 | HT-05/09/13/16 | Proposta de detalhamento; não homologada |
| CT-06 | Sincronização, cursores e checkpoints | OP-07-SYNC, OP-07-RET | TR-INT07-001..004 | HT-03/06/08/12/16 | Proposta de detalhamento; não homologada |
| CT-07 | Lotes, arquivos e movimentações delimitadas de dados | OP-08-IMP, OP-08-EXP, OP-03-VAL | TR-INT08-001..005 | HT-03/07/13/17 | Proposta de detalhamento; não homologada |
| CT-08 | Processo, instância, etapas e dependências multissistema | OP-09-INI, OP-09-COM | TR-INT09-001..006 | HT-08/09/10/11/12/15/16 | Proposta de detalhamento; não homologada |
| CT-09 | Incidente, diagnóstico, recuperação e saúde observada | OP-10-DIA, OP-10-REC, OP-07-RET | TR-INT10-001..005 | HT-02/08/11/15/16/18 | Proposta de detalhamento; não homologada |
| CT-10 | Compensação tipada e confirmação de efeito | OP-09-COM, OP-09-INI | TR-INT09-004/005/006 | HT-08/09/10/15 | Proposta de detalhamento; não homologada |
| CT-11 | Evidências, proveniência, auditoria e exportação controlada | OP-12-INV, OP-12-AUD, OP-04-RSL | TR-INT12-001..005 | HT-13/14/17 | Proposta de detalhamento; não homologada |

**Atenção:** vínculos CT/INV/HT são de revisão, não exaustividade certificada, nem homologação. Os casos OP24 são fichas de referência e podem não cobrir todos os atos originalmente aprovados.

## 3. Contratos detalhados

## CT-05 — Eventos e webhooks externos

**Autoridade e propriedade:** INT-06 governa assinaturas e fronteiras externas de recepção/entrega; Motor Central de Eventos é dono dos eventos internos oficiais; INT-04 e módulo proprietário avaliam comandos e fatos de negócio.

**Dependências e fronteiras:** INT-06; INT-04; INT-09; Motor Central de Eventos; Filas centrais; Identidade e Segurança; INT-02; COM para comunicação dirigida.  
**Operações de referência (catálogo OP24 não exaustivo):** OP-06-IN, OP-06-OUT, OP-04-RSL.  
**TR inicialmente relacionados:** TR-INT06-001..004. **INV a conferir:** INV-01/03/05/06/07/10/14. **HT já previstos:** HT-05/09/13/16 (0 testes executados).

### Finalidade contratual

Receber ou entregar uma notificação externa autenticamente vinculada à assinatura/conexão, correlacioná-la à operação e preservar distinção entre evento técnico externo, evento oficial interno e confirmação do módulo proprietário.

### Conteúdo do envelope (classes conceituais, não campos físicos)

| Grupo de dados | Identificadores e validações candidatas |
|---|---|
| Identidade e origem | tenant, empresa/conta, conexão/assinatura, provedor, origem/destino externo, tipo lógico de evento e versão |
| Envelope e integridade | identificador de evento na fonte, algoritmo/meio de autenticidade homologado, instante de origem quando comprovável, referência de payload/manifesto seguro, metadados necessários para anti-replay |
| Relações | operation_ref, command_ref/causation_ref quando existentes, correlation_ref e versão de esquema/conector |
| Entrega técnica | assinante registrado, capacidade homologada, tentativa, receipt/ack, instante e classificação de erro, referência a fila durável se aplicável |
| Governança | finalidade, campos autorizados, política de assinatura, deduplicação/ordenação, classificação da evidência e ligação à Auditoria Geral |

### Marcos e transições de referência (NÃO são enums oficiais)

| Marco / transição proposta | Condição de segurança e evidência |
|---|---|
| Assinatura proposta → ativa | Somente após validação de tenant, destino/serviço, finalidade, autorização e versão; não presumir contrato/publicação automática |
| Evento recebido → autenticidade validada | Não tratar recibo como confiança automática; sem verificação suficiente permanecer restrito/quarentenado conforme política a decidir |
| Recebimento validado → encaminhamento interno | Publicar pelo Motor Central de Eventos mediante contrato; não criar motor paralelo |
| Entrega técnica enviada → aceita/pendente/inconclusiva | Ack do destino e aplicação pelo destino são provas distintas; timeout não significa rejeição |
| Evento duplicado/tardio/fora de ordem | Preservar relação causal, lidar sem repetição indevida; evento legitimamente distinto não pode ser descartado só por conteúdo semelhante |
| Assinatura revogada/suspensa | Bloquear novas entregas não autorizadas; investigar tentativas já despachadas |

### Invariantes e verificações obrigatórias da revisão

1. Autenticidade e integridade devem seguir método homologado por provedor/assinatura; não fixar assinatura HMAC universal nem formato único de token.
2. Tenant e assinante referenciados no evento não concedem autorização de negócio; aplicação do efeito depende do proprietário e de CT-02.
3. Deduplicação pelo identificador e escopo lógico definidos no contrato; dados idênticos não são prova de evento idêntico.
4. URLs de destino são registradas e validadas pelo cadastro autorizado; impedir SSRF/URLs livres, segredos em query e entrega para tenant alheio.
5. Reentrega somente dentro de política segura de fila/retentativa e retenção do domínio; 3+15 dias dos leads não são prazo universal da INT-06.

### Falhas, reconciliação e aceite documental futuro

**Cenários negativos a preparar:** Webhook com assinatura inválida, evento de outro tenant, versão desconhecida, réplica atrasada, falha de fila após recebimento e HTTP 2xx que não prova conclusão de negócio.

**Estratégia de reconciliação a validar:** Verificar recibo de origem e entrega pela fonte apropriada, correlacionar com comando/assinatura; revalidar permissões antes de ações protegidas; solicitar confirmação de negócio ao proprietário sem reprocessar efeitos duvidosos.

**Evidências de aceite ainda por definir e coletar futuramente:** identidade e versão da operação/contrato, contexto de autorização, origem da evidência técnica, referência à confirmação do proprietário, resposta a caso negativo, histórico de tentativas e correlação, divergência e retificação quando cabível. Os critérios serão revisados por proprietário/Segurança/Auditoria conforme risco, sem testes agora.

**Parâmetros em aberto:** Protocolo por provedor, autenticação, aceites de assinaturas, limites anti-replay, ordenação, versões de esquema, rotas permitidas, TTL, retry/quotas, estados físicos e política de retenção conforme categoria.

**Situação de CT-05:** diretrizes transversais têm aprovação funcional pela INT-R01/R02 e módulo INT competente, mas **a ficha acima é proposta documental para revisão**. Não estabelece schema, enum, endpoint, nova ação, permissão ou alçada executável.


---

## CT-06 — Sincronização, cursores e checkpoints

**Autoridade e propriedade:** INT-07 governa sincronização e checkpoints; INT-03 governa mapeamento/esquema; INT-04 transporta comandos; proprietários mantêm autoridade por entidade/campo e confirmação; INT-10 auxilia recuperação técnica.

**Dependências e fronteiras:** INT-07; INT-03/04/06/10; módulos proprietários e política de dados.  
**Operações de referência (catálogo OP24 não exaustivo):** OP-07-SYNC, OP-07-RET.  
**TR inicialmente relacionados:** TR-INT07-001..004. **INV a conferir:** INV-01/02/03/05/06/07/08/10/14. **HT já previstos:** HT-03/06/08/12/16 (0 testes executados).

### Finalidade contratual

Descrever uma sincronização autorizada entre fontes/destinos e a prova de progresso consistente por mudança, checkpoint, versão e efeito efetivamente confirmado.

### Conteúdo do envelope (classes conceituais, não campos físicos)

| Grupo de dados | Identificadores e validações candidatas |
|---|---|
| Identidade da sincronização | tenant, fonte, destino, conta/conexão, entidade, escopo de campos, direção uni/bidirecional autorizada e finalidade |
| Contrato de dados | versão de esquema/mapeamento, fonte oficial por campo/entidade, regras de normalização e classificação de sensibilidade |
| Checkpoint | versão e identificador lógico do cursor, último intervalo/lote confirmado, sequência causal quando oferecida pela fonte, referência de consistência |
| Alteração | identidade da mudança lógica, recurso e versão esperada, operação permitida, command_ref/attempt_ref e evidência de aplicação |
| Controle | conflitos, exclusão explícita vs ausência de campo, tentativas, política de concorrência, autorização atual e trilhas |

### Marcos e transições de referência (NÃO são enums oficiais)

| Marco / transição proposta | Condição de segurança e evidência |
|---|---|
| Configurada/autorizada → apta | Verificar sentido da sincronização, origem de verdade, esquema e escopos, sem assumir bidirecionalidade universal |
| Apta → processando alteração | Criar pedido tipado com precondição e identidade estável; checkpoint não antecipa confirmação |
| Alteração aceita tecnicamente → aplicada/confirmada | Fonte proprietária comprova dado persistido; recebido/aceito não basta para avançar cursor |
| Falha ou timeout após escrita → inconclusiva | Checkpoint não é promovido por ausência de erro; investigar antes de reexecução |
| Conflito de versão/campo/exclusão → pendente | Não adotar último escritor ou exclusão por ausência como regra universal |
| Pausa/retomada ou mudança de versão | Preservar checkpoint e contrato; revalidar autorização e compatibilidade antes de continuar |

### Invariantes e verificações obrigatórias da revisão

1. Manter autoridade por campo/entidade e direção; múltiplas fontes não tornam edição bidirecional automaticamente permitida.
2. Registrar checkpoints duráveis de progresso comprovado; mudança de cursor/esquema exige migração autorizada e reconciliação.
3. Não deduplicar alterações legítimas diferentes pelo conteúdo; distinguir identidade de alteração de tentativa técnica.
4. Aplicar concorrência, idempotência e semântica de tombstone/exclusão somente conforme propriedade e contratos aprovados.
5. A operação delimitada em lote pertence à INT-08; sync contínua não absorve exportação/backup da Central IN-118.

### Falhas, reconciliação e aceite documental futuro

**Cenários negativos a preparar:** Perda de ack com escrita aplicada, replay de mudança, conflito entre duas fontes, cursor avançado sem confirmação, token revogado durante pausa e mudança incompatível de mapeamento.

**Estratégia de reconciliação a validar:** Comparar checkpoints, registros efetivos e versões nas fontes oficiais autorizadas; reprocessar apenas alterações que não duplicarão efeitos; conflitos de negócio retornam ao proprietário.

**Evidências de aceite ainda por definir e coletar futuramente:** identidade e versão da operação/contrato, contexto de autorização, origem da evidência técnica, referência à confirmação do proprietário, resposta a caso negativo, histórico de tentativas e correlação, divergência e retificação quando cabível. Os critérios serão revisados por proprietário/Segurança/Auditoria conforme risco, sem testes agora.

**Parâmetros em aberto:** Modelos de cursor por provedor, ordenação/gaps, batch size, limiares de atraso, tipos de exclusão, estratégia específica de conflito, retentativas, janelas de execução e convivência de versões.

**Situação de CT-06:** diretrizes transversais têm aprovação funcional pela INT-R01/R02 e módulo INT competente, mas **a ficha acima é proposta documental para revisão**. Não estabelece schema, enum, endpoint, nova ação, permissão ou alçada executável.


---

## CT-07 — Lotes, arquivos e movimentações delimitadas de dados

**Autoridade e propriedade:** INT-08 controla operações de importação/exportação/migração delimitadas; IN-118 é a Central de Backup/Portabilidade; Arquivos detém armazenamento; INT-03 mapeia; módulos proprietários confirmam gravações.

**Dependências e fronteiras:** INT-08; IN-118, IN-122/123; INT-02/03/04; Arquivos; Aprovações; módulos proprietários.  
**Operações de referência (catálogo OP24 não exaustivo):** OP-08-IMP, OP-08-EXP, OP-03-VAL.  
**TR inicialmente relacionados:** TR-INT08-001..005. **INV a conferir:** INV-01/02/03/07/08/10/11/14/15/16. **HT já previstos:** HT-03/07/13/17 (0 testes executados).

### Finalidade contratual

Representar movimentação finita e autorizada, com arquivo/manifesto, versão, validação, prévia sem escrita, lotes e confirmações por registro, sem substituir backup integral nem transferir dados automaticamente entre contas.

### Conteúdo do envelope (classes conceituais, não campos físicos)

| Grupo de dados | Identificadores e validações candidatas |
|---|---|
| Operação | tenant, conta de origem/destino, tipo importação/exportação/migração/restauração quando competente, propósito, titularidade, solicitante e aprovações válidas |
| Arquivo/manifesto | referência segura, versão e tipo de formato, origem/escopo, integridade e classificação, estados de validação do serviço de Arquivos |
| Mapeamento | esquema/mapeamento INT-03, entidades/campos, modo de resolução de conflitos e versão de prévia |
| Lotes/itens | IDs de operação/lote/registro, ordem/dependências quando aplicável, referência ao comando, resultado técnico e confirmação proprietária por item |
| Entrega/exportação | artefato, destino autorizado, expiração de acesso conforme política, trilha da exportação e histórico do autoatendimento |

### Marcos e transições de referência (NÃO são enums oficiais)

| Marco / transição proposta | Condição de segurança e evidência |
|---|---|
| Planejamento/configuração → prévia validada | Arquivo, autorização, compatibilidade e dados inspecionados sem aplicar escrita |
| Prévia → revisão/aprovação quando exigida | A aprovação pertence à política e ao titular competente; não presumir que todo item exige uma aprovação humana adicional |
| Operação apta → processamento em lotes | Cada lote e item deve reter identidade, versão, tentativa e efeito real, sem marcar tudo concluído por ack parcial |
| Lote parcial/rejeitado/inconclusivo | Preservar contagem e motivos por registro; rollback universal proibido |
| Suspensão/retomada/reprocessamento | Retomar com autorização vigente, idempotência e reconciliação por registro e lote |
| Aceite/encerramento fundamentado | Somente confirmar o conjunto e suas ressalvas após evidência suficiente das fontes proprietárias |

### Invariantes e verificações obrigatórias da revisão

1. A Central IN-118 garante exportação/importação CSV/XLSX, backup completo, restauração compatível e migração autorizada essenciais em todos os planos, inclusive Básico; não cobrar taxa adicional por operação essencial.
2. JSON pode ser utilizado quando previsto em contrato e capacidade homologada; não convertê-lo em formato universal garantido de portabilidade.
3. Antes de gravar, detectar duplicidades/conflitos; proibir mesclagem automática destrutiva, migração indiscriminada de segredos e transferência sem direitos nas duas contas.
4. Com 100% da franquia de anexos, bloquear apenas novos uploads de anexos segundo política aprovada; exportação/download de dados existentes não pode ser bloqueada pela mesma regra. Upload temporário de arquivo de importação segue controle próprio ainda por especificar.
5. Troca de site para outra conta CRM via chamado é mudança técnica gratuita da Imagine; dados históricos são portados pelo cliente, sem transferência implícita na INT-08.
6. Trial admite importação CSV autorizada, mas não libera API pública do CRM.

### Falhas, reconciliação e aceite documental futuro

**Cenários negativos a preparar:** Arquivo incompatível ou contaminado, campo divergente, lote parcialmente aplicado, reprocessamento após timeout, restauração em conta não vazia, cross-tenant, 100% de uso de anexos bloqueando exportação, troca de vínculo com leads em fila.

**Estratégia de reconciliação a validar:** Verificar para cada lote/registro a gravação do proprietário; manter rejeitados/pendentes e explicar divergências ao usuário autorizado; preservar manifesto e auditoria sem expor segredos; backup integral permanece IN-118.

**Evidências de aceite ainda por definir e coletar futuramente:** identidade e versão da operação/contrato, contexto de autorização, origem da evidência técnica, referência à confirmação do proprietário, resposta a caso negativo, histórico de tentativas e correlação, divergência e retificação quando cabível. Os critérios serão revisados por proprietário/Segurança/Auditoria conforme risco, sem testes agora.

**Parâmetros em aberto:** Tipos de manifesto e formatos por destino, política de validação/antimalware no fluxo, limites de volume/lote/frequência, escopos de upload temporário, critérios de prévia/mesclagem, retenção de artefatos, alçadas e aprovadores.

**Situação de CT-07:** diretrizes transversais têm aprovação funcional pela INT-R01/R02 e módulo INT competente, mas **a ficha acima é proposta documental para revisão**. Não estabelece schema, enum, endpoint, nova ação, permissão ou alçada executável.


---

## CT-08 — Processo, instância, etapas e dependências multissistema

**Autoridade e propriedade:** INT-09 governa modelos/instâncias/etapas e coordenação; Eventos, Filas, Tarefas, Datas/Prazos e Aprovações são serviços centrais; módulos proprietários decidem e confirmam os efeitos.

**Dependências e fronteiras:** INT-09; INT-01..08; INT-10/11/12; serviços centrais; módulos FIN/CON/CAT/VEN/AGD/ATD/COM/PVD/RET/MKT/CMS.  
**Operações de referência (catálogo OP24 não exaustivo):** OP-09-INI, OP-09-COM.  
**TR inicialmente relacionados:** TR-INT09-001..006. **INV a conferir:** INV-01/02/03/04/05/06/07/08/09/10/13/14. **HT já previstos:** HT-08/09/10/11/12/15/16 (0 testes executados).

### Finalidade contratual

Relacionar um modelo homologado e versionado a instância estável e etapas dependentes, com comandos e resultados por proprietário, sem que a coordenação seja a fonte oficial de fatos de negócio.

### Conteúdo do envelope (classes conceituais, não campos físicos)

| Grupo de dados | Identificadores e validações candidatas |
|---|---|
| Modelo | identidade e versão homologada, tenant/escopo permitido, definição de etapas, dependências e elegibilidade |
| Instância | identidade estável, versão fixada, contexto autorizado, responsável/solicitante, correlação, finalidade, prazos e estado de coordenação |
| Etapa | tipo (comando/evento/tarefa/condição), precondição, dependências, versão do contrato, identidade de etapa, autoridade/serviço central competente |
| Comando/resultado | CT-03/04 por ato, tentativa, confirmação oficial, efeitos parciais, divergências e referências a evento/arquivo |
| Controle de fluxos | ciclos permitidos/limites, convergência, suspensão, compensação candidata, condições de parada, encerramento com ressalvas |

### Marcos e transições de referência (NÃO são enums oficiais)

| Marco / transição proposta | Condição de segurança e evidência |
|---|---|
| Modelo cadastrado → revisado/homologado | Mudança de modelo não reescreve instância já criada |
| Solicitação → autorização/programação elegível | Prazos de negócio são do serviço/autoridade competente; não reduzir a timeout técnico |
| Instância iniciada → etapas elegíveis/processando | Avançar apenas após dependências e autorizações válidas, com comandos tipados |
| Etapa recebida/aceita → confirmada/rejeitada/inconclusiva | Status técnico do executor não confirma resultado proprietário |
| Convergência de ramos | Concluir apenas após provas de todas dependências obrigatórias; sucesso de ramo independente não as dispensa |
| Instância parcialmente concluída/suspensa/bloqueada/em compensação | Persistir efeitos já confirmados, pendências e cadeia causal; compensação por CT-10 e proprietário |
| Reconciliada/concluída/encerrada com ressalvas | Cada conclusão precisa de referência oficial aos efeitos; não cancelar negócio por cancelamento técnico |

### Invariantes e verificações obrigatórias da revisão

1. Instância mantém modelo, contrato, conector e versão de etapa compatíveis; eventual migração requer plano/aceite específico.
2. Eventos e tarefas são motores centrais existentes, INT-09 somente os coordena; evitar loops e dependências não resolvidas.
3. Controle de concorrência e idempotência por instância/etapa/efeito; retomada revalida CT-02 e precondições.
4. Compensações só elegíveis via CT-10, com reversibilidade/alçada proprietária; não há rollback transacional universal.
5. Contingência com provedor alternativo somente homologado, compatível e autorizado; escrita anterior inconclusiva precisa de reconciliação antes de nova tentativa.
6. IA é consultiva e não homologa mudanças de fluxo, altera criticidade ou executa ação de negócio crítica autonomamente.

### Falhas, reconciliação e aceite documental futuro

**Cenários negativos a preparar:** Instância v1 com modelo v2, ramo paralelo com confirmação só de HTTP, reabertura após revogação, sub-processo em loop sem limite, falha de fila, timeout de comando financeiro com tentativa em conector alternativo.

**Estratégia de reconciliação a validar:** Reconstituir instância/etapas e sua causalidade, consultar confirmação de cada proprietário, separar pendente de já confirmado e retomar somente ramo autorizado; incidentes via INT-10 e evidências via Auditoria Geral.

**Evidências de aceite ainda por definir e coletar futuramente:** identidade e versão da operação/contrato, contexto de autorização, origem da evidência técnica, referência à confirmação do proprietário, resposta a caso negativo, histórico de tentativas e correlação, divergência e retificação quando cabível. Os critérios serão revisados por proprietário/Segurança/Auditoria conforme risco, sem testes agora.

**Parâmetros em aberto:** Semântica física de estados por modelo/instância/etapa, critérios de aprovação por ato, grafo e limites de ciclos, persistência/checkpoints, orquestração de falhas e migração de instância, timeout e quotas por processo.

**Situação de CT-08:** diretrizes transversais têm aprovação funcional pela INT-R01/R02 e módulo INT competente, mas **a ficha acima é proposta documental para revisão**. Não estabelece schema, enum, endpoint, nova ação, permissão ou alçada executável.


---

## CT-09 — Incidente, diagnóstico, recuperação e saúde observada

**Autoridade e propriedade:** INT-10 faz diagnóstico/recuperação especializada de integrações; IN-159/160 são Observabilidade central; gestão de Incidentes e Aprovações são serviços existentes; proprietários confirmam negócio.

**Dependências e fronteiras:** INT-10; IN-159/160/162/167/168; INT-01..09; Identidade e Segurança; INT-12.  
**Operações de referência (catálogo OP24 não exaustivo):** OP-10-DIA, OP-10-REC, OP-07-RET.  
**TR inicialmente relacionados:** TR-INT10-001..005. **INV a conferir:** INV-02/03/04/05/08/12/13/14. **HT já previstos:** HT-02/08/11/15/16/18 (0 testes executados).

### Finalidade contratual

Correlacionar sinais e incidentes com hipóteses/evidências, indicar ações de recuperação já homologadas e permitir verificação técnica sem usurpar confirmação de negócio.

### Conteúdo do envelope (classes conceituais, não campos físicos)

| Grupo de dados | Identificadores e validações candidatas |
|---|---|
| Fonte de sinal | tipo de telemetria, identidade do componente, tenant/ambiente, instante observado, frescor/confiabilidade e referência central |
| Incidente | identidade/correlação, alvo, impacto observado, severidade conforme política existente, status de investigação, evidências e divergências |
| Diagnóstico | hipótese claramente separada de fatos, causa comprovada quando houver, conjunto de operações/instâncias afetadas |
| Plano de recuperação | playbook homologado/versionado, identidade executora, escopo e autorização aplicáveis, condições de segurança, tentativa e limite |
| Verificação | evidências de saúde técnica e situação de cada comando afetado, referência a reconciliação/proprietário/Auditoria Geral |

### Marcos e transições de referência (NÃO são enums oficiais)

| Marco / transição proposta | Condição de segurança e evidência |
|---|---|
| Saúde observada ou desconhecida | Sem telemetria fresca suficiente não declarar saudável; degradado/indisponível não significa que todo comando falhou |
| Incidente detectado → sob investigação | Registrar hipótese, causa e impacto sem confundir IA com fonte de prova |
| Contenção autorizada → recuperação em curso | Ações técnicas seguras, previamente homologadas, limitadas e autenticadas; risco crítico exige aprovação específica quando aplicável |
| Recuperação → verificação técnica | Comprovar sinais de normalização, sem presumir conclusão de negócio |
| Operação inconclusiva durante incidente | Consultar/reconciliar antes de retry de escrita, com retorno à competência proprietária |
| Encerramento de incidente com ressalvas | Registrar riscos e efeitos pendentes quando saúde normaliza |

### Invariantes e verificações obrigatórias da revisão

1. INT-10 utiliza IN-159/160 e Central de Incidentes existentes; não cria monitoração, logs ou incident manager paralelos.
2. Telemetria ausente, antiga ou inconsistente é evidência insuficiente: saúde desconhecida/investigação, não saudável.
3. Agente N1 monitora, N2 só playbook técnico seguro e previamente homologado nos limites autorizados, N3 tem aprovação humana específica quando exigida. Classificação de risco de ação independe da sugestão da IA.
4. Recuperação/retry respeita quotas, redução de efeito cascata, autorização e compatibilidade atual; falha após escrita não permite repetir ato de negócio.
5. Normas de retenção IN-162 para logs técnicos não substituem evidências oficiais com outras políticas de guarda.

### Falhas, reconciliação e aceite documental futuro

**Cenários negativos a preparar:** Perda de conectividade e de telemetria, falso retorno à saúde, permissão de agente revogada, recuperação parcial, filas pendentes após reinício, tentativa duplicada de pagamento por timeout.

**Estratégia de reconciliação a validar:** Confrontar sinais centrais e estados por operação, verificar restauração técnica, devolver confirmação de negócio ao proprietário; classificar cada pendência e registrar evidências via Auditoria Geral/INT-12.

**Evidências de aceite ainda por definir e coletar futuramente:** identidade e versão da operação/contrato, contexto de autorização, origem da evidência técnica, referência à confirmação do proprietário, resposta a caso negativo, histórico de tentativas e correlação, divergência e retificação quando cabível. Os critérios serão revisados por proprietário/Segurança/Auditoria conforme risco, sem testes agora.

**Parâmetros em aberto:** Limiar de severidade e frescor, tempos de monitoramento e resposta, parâmetros de playbook e retry, delimitação de risco/alçada por tipo, evidências mínimas para normalização e classificação de incidentes.

**Situação de CT-09:** diretrizes transversais têm aprovação funcional pela INT-R01/R02 e módulo INT competente, mas **a ficha acima é proposta documental para revisão**. Não estabelece schema, enum, endpoint, nova ação, permissão ou alçada executável.


---

## CT-10 — Compensação tipada e confirmação de efeito

**Autoridade e propriedade:** INT-09 coordena pedido de compensação; o proprietário do efeito original decide, autoriza, executa e confirma reversão ou outra ação elegível; Central de Aprovações registra decisão humana aplicável.

**Dependências e fronteiras:** INT-09; INT-04; INT-10; FIN/CON/AGD/outros proprietários; Identidade e Segurança; Central de Aprovações.  
**Operações de referência (catálogo OP24 não exaustivo):** OP-09-COM, OP-09-INI.  
**TR inicialmente relacionados:** TR-INT09-004/005/006. **INV a conferir:** INV-02/03/04/05/07/08/09/14. **HT já previstos:** HT-08/09/10/15 (0 testes executados).

### Finalidade contratual

Descrever reversões ou ações compensatórias específicas e verificáveis para efeito prévio comprovado, sem prometer desfazimento universal nem atribuir a INT/IA poderes de cancelamento financeiro ou contratual.

### Conteúdo do envelope (classes conceituais, não campos físicos)

| Grupo de dados | Identificadores e validações candidatas |
|---|---|
| Efeito original | owner_module, efeito confirmado ou inconclusivo, recurso, versão, origem, instante e confirmação oficial |
| Elegibilidade | tipo de compensação aprovado pelo proprietário, política de reversibilidade/irreversibilidade, pré-condições e estado atual do domínio |
| Autoridade | tenant, solicitante, operação, justificativa/finalidade, aprovação específica, alçada e identidade executora |
| Comando e tentativas | CT-03 da compensação, pedido lógico próprio, idempotency_ref distinto do efeito original, command_ref/attempt_ref |
| Resultado | CT-04 com aceitação/transporte e confirmação oficial da compensação, efeitos remanescentes, parcialidade e referência à Auditoria Geral |

### Marcos e transições de referência (NÃO são enums oficiais)

| Marco / transição proposta | Condição de segurança e evidência |
|---|---|
| Proposta de compensação → elegível/inadmissível | Avaliação pertence ao proprietário; não presumir que todo erro aceita rollback |
| Elegível → aprovação quando exigida | Decisão aplicável à compensação específica e vigente, separada de aprovação do efeito original |
| Solicitada → enviada/aceita/aplicada tecnicamente | Transporte não confirma estorno, cancelamento ou retorno patrimonial/contratual |
| Confirmada pelo proprietário | Comprovar reversão/ato exato, manter referência ao efeito original e eventual saldo/efeitos remanescentes |
| Parcial/rejeitada/inconclusiva | Preservar resultado de cada efeito; não declarar processo global compensado |
| Nova tentativa ou mudança de provedor | Reconciliar primeira escrita potencialmente aplicada antes de nova compensação, sem ampliar poderes da INT |

### Invariantes e verificações obrigatórias da revisão

1. Um cancelamento técnico de instância ou timeout não desfaz automaticamente pagamento, contrato, agendamento, documento ou lead confirmado.
2. Não permitir compensação que exija decisão de FIN/CON/AGD etc. sem essa autoridade; agentes IA podem sugerir e não executar livremente.
3. Idempotência contextual do pedido de compensação separada do pedido original, com correlação entre ambos.
4. Compensações parcialmente aplicadas exigem evidências por ato e plano de contingência autorizado; evitar troca silenciosa de tipo/versão.
5. Não executar compensação genérica a partir de URL arbitrária, SQL, workflow livre ou privilégio herdado do orquestrador.

### Falhas, reconciliação e aceite documental futuro

**Cenários negativos a preparar:** Compensação de efeito irreversível, pedido sem alçada do proprietário, timeout após estorno externo, retorno parcial, aprovação revogada, duas tentativas concorrentes do mesmo efeito.

**Estratégia de reconciliação a validar:** Consultar fontes oficiais do ato original e de compensação; recompor efeitos remanescentes/pendentes, manter instância INT-09 com ressalvas até resolução autorizada.

**Evidências de aceite ainda por definir e coletar futuramente:** identidade e versão da operação/contrato, contexto de autorização, origem da evidência técnica, referência à confirmação do proprietário, resposta a caso negativo, histórico de tentativas e correlação, divergência e retificação quando cabível. Os critérios serão revisados por proprietário/Segurança/Auditoria conforme risco, sem testes agora.

**Parâmetros em aberto:** Matriz de compensabilidade por domínio/efeito, estados de elegibilidade, alçadas, semântica financeira/contratual, reversibilidade e limites de reexecução, comprovação por provedor e tratamento de exceções.

**Situação de CT-10:** diretrizes transversais têm aprovação funcional pela INT-R01/R02 e módulo INT competente, mas **a ficha acima é proposta documental para revisão**. Não estabelece schema, enum, endpoint, nova ação, permissão ou alçada executável.


---

## CT-11 — Evidências, proveniência, auditoria e exportação controlada

**Autoridade e propriedade:** Auditoria Geral detém trilha oficial, integridade, controle de acesso e retenção; INT-12 oferece consulta, correlação, investigação e exportação autorizadas; módulos proprietários atestam fatos e IN-162 rege logs técnicos por categoria.

**Dependências e fronteiras:** INT-12; Auditoria Geral; INT-01..11; Identidade e Segurança; IN-159/160/162; Analytics.  
**Operações de referência (catálogo OP24 não exaustivo):** OP-12-INV, OP-12-AUD, OP-04-RSL.  
**TR inicialmente relacionados:** TR-INT12-001..005. **INV a conferir:** INV-01/02/03/05/07/11/12/14/15. **HT já previstos:** HT-13/14/17 (0 testes executados).

### Finalidade contratual

Permitir reconstrução fiel e autorizada de atos de integração, mantendo fonte e classificação das evidências, separando observações, hipóteses e fatos oficiais.

### Conteúdo do envelope (classes conceituais, não campos físicos)

| Grupo de dados | Identificadores e validações candidatas |
|---|---|
| Identidade/proveniência | tenant/empresa, operação/comando/tentativa, autor/serviço, origem oficial, referência e versão do evento/contrato |
| Sequência causal | correlation_ref, causation_ref, carimbos de tempo com fontes, incidente/reconciliação/compensação, indicadores de lacunas |
| Fato/hipótese | tipo de prova, conteúdo minimizado, referência verificável ao resultado oficial ou hipótese com nível de incerteza |
| Segurança | classificação e finalidade, permissão de leitura/exportação, mascaramento, aprovação quando exigida, trilha de quem consultou |
| Retenção e integridade | categoria de retenção aplicável, prova de proveniência/integridade, referência do artefato exportado e seu ciclo de acesso |

### Marcos e transições de referência (NÃO são enums oficiais)

| Marco / transição proposta | Condição de segurança e evidência |
|---|---|
| Evidência recebida → classificada/referenciada | Validação de autoria, integridade, fonte e competência sem inventar novo registro oficial em INT-12 |
| Linha do tempo → correlação fundamentada | Ordem causal distinta de simples timestamp; preservar fatos contraditórios e lacunas |
| Consulta solicitada → autorizada/negada | Controlar tenant, recurso, campo, finalidade e identidade, inclusive para investigadores internos |
| Exportação solicitada → disponibilizada/negada | Permissão de exportar distinta de apenas visualizar quando aplicável; registrar a própria exportação |
| Retificação de informação | Somente pela autoridade/fonte oficial competente, de forma versionada e auditável; nunca editar hipótese como fato sem prova |
| Expiração/retention | Seguir a política de cada classe; IN-162 não determina indiscriminadamente a retenção dos fatos oficiais de negócio |

### Invariantes e verificações obrigatórias da revisão

1. Auditoria Geral é a fonte única oficial: INT-12 não cria banco, log paralelo ou autoridade de retificação do efeito financeiro/contratual.
2. INT-12 e IA podem investigar/correlacionar e produzir hipóteses assinaladas, nunca inventar ou elevar uma observação ao estatuto de confirmação oficial.
3. Consulta/exportação rastreáveis, com minimização, mascaramento, segregação multiempresa, autorização específica e auditoria da atividade de auditoria.
4. Links/artefatos de evidência permanecem protegidos; retenção de debug/traço/métrica segue IN-162 quando aplicável, não substituindo guarda oficial.
5. Analytics continua responsável por painéis/indicadores oficiais, INT-12 não toma essa competência.

### Falhas, reconciliação e aceite documental futuro

**Cenários negativos a preparar:** Investigador de A consulta tenant B, leitura permitida mas exportação não, log técnico expirado e trilha oficial ainda obrigatória, dados com origem não verificável, webhook contraditório que chega atrasado.

**Estratégia de reconciliação a validar:** Comparar evidências e versões sem escrever no negócio; sinalizar lacunas, consultar módulos proprietários e levar incidentes à INT-10 quando necessário, mantendo auditoria de consulta e exportação.

**Evidências de aceite ainda por definir e coletar futuramente:** identidade e versão da operação/contrato, contexto de autorização, origem da evidência técnica, referência à confirmação do proprietário, resposta a caso negativo, histórico de tentativas e correlação, divergência e retificação quando cabível. Os critérios serão revisados por proprietário/Segurança/Auditoria conforme risco, sem testes agora.

**Parâmetros em aberto:** Esquema/prova de integridade, classificações de evento, retenção por categoria conforme fonte, escopo de consulta/exportação por ator/campo, modelos de evidência de conflito e mecanismos de disponibilização segura.

**Situação de CT-11:** diretrizes transversais têm aprovação funcional pela INT-R01/R02 e módulo INT competente, mas **a ficha acima é proposta documental para revisão**. Não estabelece schema, enum, endpoint, nova ação, permissão ou alçada executável.


## 4. Interações entre famílias — sequência de evidências sem autoridade duplicada

| Percurso | Famílias envolvidas | Fonte de verdade | Proteção principal |
|---|---|---|---|
| Webhook externo → processo | CT-05 → CT-08 → CT-03/04 → CT-11 | Evento interno no Motor Central; negócio no proprietário | Não converter origem assinada em autorização de efeito |
| Fonte externa → sync | CT-06 + CT-03/04 + CT-09 | Checkpoint na INT-07, dado no proprietário | Não avançar cursor por mero ack ou saúde técnica |
| Importação em lote → CRM | CT-07 + CT-03/04 + CT-11 | IN-118 para portabilidade, INT-08 para lote, proprietário para registros | Não mesclar/excluir sem direito nem marcar total quando parcial |
| Instância multissistema → recuperação | CT-08 + CT-09 + CT-04/11 | INT-09 coordenação, INT-10 saúde, proprietário negócio | Resultado inconclusivo não autoriza reexecução cega |
| Efeito aplicado → compensação | CT-08 + CT-10 + CT-03/04/11 | Proprietário do efeito e da reversão | Compensação tipada e autorizada, não rollback universal |
| Investigação e exportação | CT-11 + CT-02 | Auditoria Geral, consultada por INT-12 | Integridade/proveniência, finalidade e auditoria da própria consulta |

## 5. Casos negativos compartilhados — critérios de revisão, não testes executados

| Caso documental proposto | Falha/abuso a simular somente na fase futura | Resultado esperado | HT já planejada |
|---|---|---|---|
| CT5-11-V01 | Webhook duplicado/tardio assinadamente válido | Não gerar efeito duplicado nem retroceder estado oficial indevidamente | HT-05/16 |
| CT5-11-V02 | Checkpoint avança após timeout de escrita | Negar avanço sem prova de persistência/reconciliação | HT-06/08 |
| CT5-11-V03 | Migração parcialmente aplicada após arquivo compatível | Refletir confirmação por registro e pendências sem mesclagem destrutiva | HT-07/13 |
| CT5-11-V04 | Instância v1 recebe modelo v2 incompatível | Preservar versão da instância e suspender mudança não homologada | HT-09/12 |
| CT5-11-V05 | Provedor normaliza com comandos anteriores inconclusivos | Manter efeitos pendentes até confirmação do proprietário | HT-08/11 |
| CT5-11-V06 | Compensação proposta para efeito irreversível/sem alçada | Bloquear ato, manter histórico e encaminhar ao proprietário | HT-10/15 |
| CT5-11-V07 | Investigador exporta dados de tenant alheio | Negar e auditar sem expor evidência sensível | HT-13/17 |
| CT5-11-V08 | Log de debug expira e prova oficial ainda é necessária | Conservar evidência conforme regime próprio, sem criar retenção universal | HT-14/17 |
| CT5-11-V09 | Conector suspenso com operação enfileirada e versão antiga | Negar novo despacho incompatível; preservar tentativa anterior inconclusiva | HT-12/18 |

**Estado:** roteiros propostos; **0 execuções, 0 resultados PASS/FAIL, 0 homologações**. Não criar IDs HT-19 etc.; os exemplos acima são subdivisões candidatas dos cenários já aprovados.

## 6. Verificações para especificação física posterior (ainda sem aprovação)

| Gate técnico proposto | Pergunta obrigatória | Quem precisa ser envolvido | Situação |
|---|---|---|---|
| CON-05 | Autenticidade, deduplicação, ordering e contrato de evento | INT-06, Segurança e Motor Central de Eventos | Pendente |
| CON-06 | Autoridade por campo, checkpoint durável e conflitos | INT-07, INT-03 e proprietários | Pendente |
| CON-07 | Manifesto, formato, lote, backup completo e permissões de portabilidade | INT-08, IN-118, Arquivos e proprietários | Pendente |
| CON-08 | Máquina de estados de modelo/instância/etapa, dependências e migração de versão | INT-09, Eventos/Tarefas/Aprovações e proprietários | Pendente |
| CON-09 | Sinais frescos, saúde incerta, playbook e alçadas de recuperação | INT-10, Observabilidade, Segurança e Incidentes | Pendente |
| CON-10 | Matriz por efeito de compensabilidade, irreversibilidade e alçada | INT-09, FIN/CON/AGD e demais donos | Pendente |
| CON-11 | Proveniência, integridade, consulta/exportação e retenção por classe | Auditoria Geral, INT-12 e Segurança | Pendente |
| CON-X | Identidade/versão/campos físicos, erros, parâmetros e teste de regressão por operação | INT-04, INT-02 e todos os donos pertinentes | Pendente |

## 7. Conclusão e precedência

- **CT-05 a CT-11:** sete fichas técnicas com conteúdo conceitual, marcos/transições propostos, limites de autoridade, falhas, reconciliação, HT relacionados e pendências concretas; **não são aprovações definitivas**.
- **P0-CON-01 permanece ABERTO/PARCIAL:** sem schema executável por operação, políticas de erros/retentativas por provedor, valores numéricos, autorização efetiva por perfil/campo, validação de versões e aceite documental final.
- **P0-DOC-01/P0-TR-01 continuam ABERTOS/PARCIAIS:** fonte original integral de aprovações INT ainda não recuperada; vínculos permanecem preliminares.
- **P0-ACC-01 continua ABERTO/PARCIAL:** 18 planos HT aceitos como bases, nenhum teste executado.
- **G01–G06 não concluídos.** Este documento não altera o planejamento aprovado, não cria serviços e não autoriza iniciar programação.
