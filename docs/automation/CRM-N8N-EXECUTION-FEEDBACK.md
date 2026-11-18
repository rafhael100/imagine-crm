# Acompanhamento do resultado do n8n no CRM

## Finalidade

Este candidato fecha o retorno técnico CRM → Agents/n8n → CRM. O CRM mantém uma
projeção do estado observado e o histórico das mudanças, sem reabrir o envio do
evento e sem confundir recebimento com conclusão da execução.

A implantação e os testes continuam restritos ao ambiente sintético isolado. O
contrato não autoriza mudanças comerciais, contatos, cobrança ou chamadas pagas.
O motor nativo do CRM continua responsável pelas regras comerciais.

## Integração

O reconciliador de execução é separado de `AgentsReceiptReconciler`. O recibo
existente confirma apenas a entrega; a nova consulta usa
`POST /private/events/execution`, operação `event.execution.read`, schema
`AUTO_IA_EXECUTION_QUERY_V1` e resposta `AUTO_IA_EXECUTION_STATUS_V1`.

A consulta exige o perfil de desenvolvimento explícito, grant local de leitura,
evento e lead pertencentes à mesma empresa e outbox já existente. Nunca cria ou
renova o envelope original. O destino exige mTLS, assinatura e grant próprio;
uma credencial de envio não concede acompanhamento automaticamente.

O CRM verifica JSON fechado sem chaves duplicadas, assinatura, schema, emissor,
destinatário, empresa, tenant, evento, lead, hash dos bytes originais,
request/nonce, janela da resposta e coerência dos estados. A chave de resposta
`agents-execution.key` é específica deste contrato.

| Estado persistido | Significado |
| --- | --- |
| `NOT_STAGED` | Evento capturado, ainda sem tarefa autorizada |
| `NOT_STARTED` | Tarefa vinculada, sem intenção de execução registrada |
| `STARTED` | Intenção durável; resultado ainda não comprovado |
| `RESULT` | Resultado preparado, confirmação final pendente |
| `ACKED` | Resultado preparado e confirmação comprovados |
| `UNKNOWN` | Resultado incerto; repetição automática permanece bloqueada |

O código `PREPARED` significa preparo técnico do evento. Não altera estágio do
lead, qualificação, funil, tarefas comerciais ou qualquer permissão. `NONE`
indica que nenhum resultado preparado foi comprovado. O estado da fila é
registrado separadamente e não concede autorização para operá-la.

## Projeção e histórico

`execution_read_grants` define a autorização local de acompanhamento.
`execution_observations` guarda as observações verificadas, e
`execution_projections` aponta para a observação atual do evento. As três tabelas
possuem FORCE RLS e owner restrito. A role `crm_auto_execution_reconciler` recebe
apenas as funções necessárias, sem acesso direto às tabelas de serviço ou leads.

A consulta mantém locks da autorização e do evento até verificar a resposta e
confirmar a transação local. O histórico e a projeção são gravados juntos; uma
falha de gravação ou expiração durante espera deve desfazer ambos. Leituras
concorrentes do mesmo evento são serializadas.

O hash da observação contém somente fatos do processamento. Horário, assinatura,
request e nonce de uma nova consulta não criam outra entrada quando os fatos são
iguais. O CRM recusa uma regressão de execução ou um vínculo de tarefa
incompatível com o que já observou. A confirmação de entrega original e os campos
comerciais permanecem separados dessa projeção.

`read_execution_projection` disponibiliza o estado e histórico sob autorização
do serviço. A extensão não cria uma rota pública ou uma nova tela de cliente;
qualquer apresentação posterior deve usar a sessão e a política de acesso do CRM.

## Autorização histórica

Uma permissão de leitura própria pode continuar válida após a revogação de uma
permissão de envio ou execução. Isso permite acompanhar resultados já existentes
sem reativar efeitos. O grant de acompanhamento, vínculo da empresa e chaves
atuais continuam obrigatórios. Revogar a leitura bloqueia novas consultas e
preserva o histórico já salvo.

## Backup e recuperação

O perfil `synthetic-private-s2s-execution-v1` exige exatamente as três tabelas e
quatro funções da extensão, além do perfil S2S completo. Perfis incompletos ou
objetos desconhecidos continuam recusados. O backup usa o snapshot PostgreSQL
consistente existente, inclui as tabelas novas nas contagens e nas provas de
isolamento e preserva owners, ACLs e FORCE RLS.

A comparação mantém o inventário e o estado de validação de todas as constraints,
as definições exatas de PK/FK/UNIQUE e os CHECKs exatos das três tabelas novas.
Os CHECKs legados de `varchar IN (...)` podem ser decompilados de forma diferente
após `pg_dump`/restore, como observado na CI; a equivalência textual dessas
definições antigas fica fora desta ampliação. Nenhuma constraint é removida ou
modificada para acomodar essa diferença, e não se aplica normalização genérica a SQL.

A restauração cria a role nova sem login ou privilégios elevados. Restaurar
projeção e histórico não chama o n8n nem autoriza uma repetição. O ensaio bilateral
usa um resultado produzido pelo n8n real e uma resposta HTTP verificada pelo CRM;
o ensaio do backup executa criptografia, adulteração recusada e restauração em
outro cluster sem rede.

## Validação e limites

As CIs da HEAD publicada são a fonte de evidência. A suíte de contrato deve
recusar assinatura inválida, request/nonce de outra consulta, escopo trocado,
resultado incoerente e JSON ambíguo. A prova PostgreSQL/HTTPS verifica projeção,
histórico sem duplicação, concorrência, revogação, rollback e recuperação.

A implementação parte da candidata CRM #647, preservando os testes e contratos
anteriores. Os gates de atualização F251/F252 não são ampliados: sua allowlist de
manutenção não aprova o conjunto acumulado de novas funcionalidades. CI técnica
deste recorte não equivale a liberação desses gates, homologação com dados reais,
merge na main ou instalação na VPS.
