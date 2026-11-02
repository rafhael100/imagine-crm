# IN-149 — Protocolo Inteligente de Entrega e Confirmação de Leads

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 (resposta "ok").  
**Fase:** somente planejamento/documentação; **não iniciar programação, implantação, alteração de serviços ou contratação**.  
**Escopo:** formulários dos Sites Imagine, API principal, API de contingência, filas de integração e CRM Imagine.  
**Referências:** IN-124 (API principal e contingência, persistência durável), IN-125/IN-126 (formulários e monitoramento), IN-127/IN-128/IN-129 (filas, prazos, auditoria e retentativas), IN-143 a IN-146 (continuidade, contingência e failback), IN-147 (exclusão após 18 dias), IN-148 (reconciliação inteligente).

## Objetivo

Definir um **protocolo funcional de identidade, recebimento, processamento e confirmação de entrega** que permita comprovar a persistência do lead na API receptora e, separadamente, comprovar a criação/associação efetiva do registro no CRM Imagine. O protocolo deverá resistir a falhas temporárias, interrupções de rede, respostas tardias, failover/failback e reprocessamentos, **evitando duplicação indevida e perda silenciosa**.

## Regras aprovadas

1. **Identificador único e estável por submissão lógica:** cada envio terá uma chave de idempotência, mantida em todas as tentativas referentes àquela mesma submissão. Envios genuinamente distintos, mesmo com conteúdo idêntico, terão identificadores distintos. O escopo da chave inclui identidade técnica validada de empresa/site/formulário/destino, evitando colisões ou acesso entre empresas.
2. **Primeira confirmação — API receptora:** a API principal **ou** a API de contingência somente poderá comunicar "recebido com sucesso" ao site/visitante após gravar duravelmente o envio e os metadados essenciais para seu posterior processamento. Não confundir uma conexão aberta ou resposta intermediária com gravação concluída.
3. **Segunda confirmação — CRM:** o estado "entregue ao CRM" somente será marcado após comprovação de que o CRM efetivamente persistiu/criou/associou o registro correto, com identificador de destino ou consulta equivalente confiável. Um status HTTP 2xx/202 de aceitação **não é evidência suficiente, por si só, de persistência final**.
4. **Resistência a duplicações:** retentativas, reprocessamentos manuais autorizados, reenvios por timeout e alternância de APIs devem consultar ou preservar o identificador original para evitar registros duplicados no CRM.
5. **Falha de comunicação ou resposta perdida:** antes de reenviar uma operação cujo resultado seja incerto, o sistema deve verificar, por chave idempotente/consulta de estado quando tecnicamente disponível, se a operação anterior já foi concluída; se não puder comprovar, registrar estado pendente/incerto e processá-la de forma controlada.
6. **Histórico rastreável:** registrar tentativas, carimbos de tempo, origem, destino e identificadores técnicos, processamento, resultado, confirmação da API e do CRM, respostas tardias, erros e ações de reconciliação, com minimização de dados pessoais e sem expor credenciais.
7. **Estados diferenciados:** distinguir pelo menos `recebido_na_api`, `pendente_de_entrega`, `em_processamento`, `confirmacao_em_analise`, `entregue_confirmado_crm`, `falha` e `expirado_excluido_sem_entrega`. A nomenclatura definitiva, transições e estados intermediários serão detalhados tecnicamente.
8. **Destinatário autorizado:** o vínculo de site/formulário ao CRM e o contexto do envio devem ser resolvidos/autenticados de modo confiável; não permitir alterar destino sem política autorizada nem enviar registros entre contas diferentes por simples alteração de parâmetros no cliente.
9. **Reconciliação entre as APIs e CRM:** comparar os estados dos envios na principal, contingência, filas e CRM; resolver divergências por procedimentos seguros homologados, mantendo trilha de auditoria e bloqueando conclusões precipitadas.
10. **Segurança e permissões:** proteger comunicação e origem das requisições, aplicar validação de dados, políticas antiabuso e controle de acesso entre serviços; mecanismos exatos de autenticação, assinatura, limitação de tráfego e gestão de segredos serão definidos futuramente.
11. **Agentes técnicos como supervisores e recuperadores:** o Agente de Integrações e o Supervisor Técnico poderão monitorar falhas, investigar estados divergentes, recomendar e executar reparos seguros previamente autorizados, mas **a confirmação da entrega deve vir de evidência técnica verificável, não da interpretação livre da IA**.
12. **Autonomia:** ações seguras e homologadas podem ser automáticas; intervenções críticas exigem **uma única aprovação humana** (IN-137/IN-138). Não criar dupla aprovação.

## Política temporal consolidada (não alterar)

- **Até 3 dias** desde a primeira persistência durável: fila normal e tentativas automáticas conforme classificação de erros.
- **Dias 3–18:** fila de falhas/exceções, diagnóstico, alertas e recuperações permitidas.
- **72 horas antes do vencimento:** aviso preventivo à administração.
- **Após completar 18 dias, sem confirmação de entrega ao CRM:** interromper tentativas, realizar verificação final de identidade/estado e **permitir exclusão automática do conteúdo temporário** sem quarentena adicional, com alerta crítico e auditoria, nos termos da IN-147.
- **Entrega confirmada ao CRM:** limpar cópia temporária da integração **24 horas após a confirmação**, preservando registro CRM (IN-128).
- **Histórico técnico minimizado:** retenção padrão de **90 dias**, distinta da conservação do payload.
- Falhas, troca de servidor, reprocessamentos ou reabertura de incidente **não reiniciam os 18 dias**. Caso o estado esteja inconsistente no vencimento, aplicar investigação/controlos de integridade da IN-147/IN-148, sem inventar entrega nem apagar dados por hipótese não comprovada.

## Fluxo funcional aprovado

1. Visitante envia formulário: sistema atribui/propaga identificador lógico estável, contexto de site/formulário e validação da submissão.
2. API disponível valida o recebimento e grava payload/metadados duravelmente.
3. Somente após gravar, API confirma recebimento ao site; evento entra na fila de processamento.
4. Worker de integração envia operação idempotente ao CRM, com identificador e destino autorizados.
5. Se o CRM demonstrar persistência efetiva, gravar confirmação/ID de destino e marcar `entregue_confirmado_crm`.
6. Se a resposta for apenas intermediária, perdida ou ambígua, consultar estado, evitar reenvio cego e registrar/retentar conforme procedimento autorizado.
7. Reconciliação verifica concordância entre principal, contingência, filas e CRM.
8. Para entregues, limpar cópias temporárias após 24 horas; para não entregues, respeitar ciclo de 18 dias e descarte aprovado na IN-147.
9. Central de Incidentes e agentes registram problemas recorrentes, tentativas de recuperação, efeitos e lições aprendidas.

## Exemplos de falhas a considerar

- Timeout após a API gravar o envio: um retry com a mesma chave não cria um segundo evento lógico.
- API de contingência recebe durante queda da principal: as duas persistências devem reconciliar o mesmo envio.
- CRM persiste um lead mas a resposta se perde: antes de repetir, consultar chave/registro de destino.
- CRM retorna 202 indicando processamento assíncrono: aguardar comprovação final de armazenamento; não contar como entregue prematuramente.
- Dois leads legítimos têm os mesmos campos: não deduplicar somente por nome, telefone ou conteúdo.
- Reprocessamento manual e worker automático atuam no mesmo envio: serializar ou controlar concorrência, preservando idempotência.
- Resposta de entrega chega ao mesmo tempo em que inicia descarte após 18 dias: verificar atomicamente estados e conferir confirmação válida antes de remover payload.
- Mudança de vínculo do site para outra conta de CRM: respeitar vínculo e destino válidos para a submissão original, sem migrar dados históricos automaticamente.

## Auditoria, métricas e acompanhamento

- Volumes recebidos, confirmados na API, confirmados no CRM, pendentes, incertos, não entregues e expirados/excluídos.
- Tempo entre a primeira persistência e a confirmação final no CRM.
- Taxa de reprocessamento, erros por classe, confirmações perdidas, reconciliações e tentativas duplicadas bloqueadas.
- Integridade da auditoria minimizada, sem armazenar payload integral em logs técnicos.
- Visibilidade administrativa por site/conta e acesso controlado, sem expor dados de outros clientes.

## Pontos pendentes antes de implementação

1. Contrato exato da API (campos, formatos, códigos HTTP e semântica de resposta), chaves de idempotência e versionamento.
2. Identidade/autorização da origem do formulário e do destino CRM, segurança de transporte, proteção de segredos e antiabuso.
3. Mecanismo para comprovar persistência final no CRM, incluindo consulta por ID e operações assíncronas.
4. Transições de estados, restrições de integridade, processos atômicos ou transacionais, concorrência distribuída e recuperação pós-falha.
5. Conciliação entre as infraestruturas independentes principal e contingência, latências e eventuais partições de rede.
6. Comportamento quando a confirmação do CRM é desconhecida perto do vencimento de 18 dias; segurança do descarte e exceções legais.
7. Alertas, observabilidade, testes de carga e falhas, métricas e limites de custos de processamento.
8. Testes de homologação com eventos sintéticos, inclusive timeouts, falhas parciais, entregas assíncronas, concorrência e expiração.

**Aprovado:** ID único/estável, confirmação de recebimento apenas após persistência na API, confirmação efetiva separada no CRM, prevenção de duplicidades, consulta antes de retentar resultados incertos e histórico integral de estados/etapas, **respeitando a IN-147 e as políticas de retenção**.  
**Pendente:** contrato técnico, parâmetros, testes e implementação.  
**Programação:** não iniciada.
