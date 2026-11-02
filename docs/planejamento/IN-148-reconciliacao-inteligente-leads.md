# IN-148 — Reconciliação Inteligente de Leads entre APIs, Filas e CRM Imagine

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 (resposta "ok").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, migração, execução operacional ou contratação**.  
**Escopo:** formulários dos Sites Imagine, API principal, API de contingência, armazenamento e filas de integração, entrega e confirmação no CRM Imagine.  
**Referências:** IN-124 (recepção por APIs independentes, persistência e idempotência), IN-126 a IN-129 (monitoramento, filas, retenção e diagnóstico), IN-130/IN-132 (incidentes e agentes), IN-137/IN-138 (autonomia), IN-139/IN-140 (aprendizado e avaliação), IN-143 a IN-146 (recuperação e failover/failback), **IN-147** (exclusão automática após 18 dias).

## Objetivo

Garantir que cada submissão recebida pelas APIs tenha **identidade rastreável, estado confiável e entrega ao CRM verificável**, mesmo com falhas, retentativas, contingência e mensagens atrasadas. Os agentes de IA devem identificar e corrigir inconsistências usando procedimentos autorizados, evitando duplicar leads ou excluir conteúdos ainda em estado incompatível com as regras de descarte.

## Regras funcionais expressamente aprovadas

1. **Identificador único e estável por submissão:** usar identificador idempotente preservado em retentativas de envio, troca da API principal para a de contingência, reprocessamento, reconciliação e confirmação no CRM. Manter referência de empresa/conta, site, formulário e destino autorizado, com isolamento entre empresas.
2. **Persistência durável e confirmação ao visitante:** sucesso do formulário somente após uma das APIs confirmar a persistência durável do envio. **Recebimento pela API não equivale a entrega ao CRM.**
3. **Estados distintos e rastreáveis:** diferenciar recebido/persistido, em processamento, em retentativa, entregue e confirmado pelo CRM, pendente de reconciliação, falha e **expirado/excluído sem entrega**. Os nomes e subestados técnicos serão definidos na arquitetura.
4. **Comprovação de entrega efetiva:** classificar como **entregue** somente após resposta confiável da operação de criação/associação no CRM com identificação do registro persistido ou outro mecanismo equivalente de consulta e comprovação, conforme contrato de integração. Um HTTP 2xx que apenas aceita mensagem para processamento assíncrono **não comprova por si só a persistência final no CRM**.
5. **Reconciliação entre serviços:** comparar identificadores e estados da API principal, API secundária, réplicas/filas e CRM, distinguindo reenvios da mesma submissão de envios legitimamente diferentes com campos semelhantes.
6. **Correção controlada:** os agentes podem recuperar entregas pendentes, reparar estados inconsistentes e reprocessar **somente** dentro das permissões, dos prazos e dos playbooks previamente homologados, com testes e auditoria. Ações críticas seguem exigindo **uma única autorização humana**, quando aplicável.
7. **Não duplicar leads:** usar idempotência aplicada também na integração de destino (CRM), com controle de tentativas concorrentes, confirmações tardias e retomadas após queda. Não afirmar garantia absoluta de 'exactly once' sem mecanismo técnico testado.
8. **Nenhuma exclusão indevida:** antes de eliminar cópia temporária, conferir identificador, prazo, entregas já confirmadas, respostas tardias e possíveis estados divergentes entre ambientes. A existência de uma cópia redundante não autoriza excluir o registro principal do CRM.
9. **Respeitar a política temporal:** 3 dias de tentativas normais, seguidos de 15 dias na fila de falhas (total de **18 dias a contar da primeira persistência durável**); retentativas e failover não reiniciam a contagem. Aviso 72 horas antes da expiração e alerta crítico no vencimento (IN-127/IN-128).
10. **Exclusão automática após 18 dias:** quando completar 18 dias **sem entrega confirmada ao CRM**, interromper tentativas automáticas, consolidar o estado, efetuar verificação de elegibilidade e **permitir a exclusão automática do conteúdo temporário**, sem quarentena adicional de 30 dias, conforme IN-147. Registrar o resultado em auditoria técnica minimizada por 90 dias; o envio continua classificado como não entregue.
11. **Exceções de integridade ou guarda legal:** diante de conflito de estados que impeça afirmar elegibilidade para exclusão, não usar um estado potencialmente desatualizado para apagar dados: sinalizar a exceção para verificação controlada, sem criar uma nova quarentena padronizada nem reiniciar o prazo de 18 dias. Obrigações legais de preservação têm tratamento próprio (IN-147).
12. **Limpeza de entregues:** depois da confirmação de entrega no CRM, limpar o payload temporário após **24 horas**, conforme IN-128, independentemente de ter sido recebido pela API principal ou de contingência.
13. **Observabilidade e aprendizado:** erros recorrentes, divergências de estado, reprocessamentos, entregas confirmadas e descarte de expirados alimentam a Central de Incidentes e a biblioteca de conhecimento, sem armazenar dados pessoais além do necessário.
14. **Segurança:** logs e indicadores não expõem senhas, tokens, conteúdos completos de formulários ou dados sensíveis desnecessários; acesso a exportação, diagnósticos e reprocessamento é restrito e auditado.

## Ciclo funcional de reconciliação aprovado

1. **Recepção:** o formulário gera/transmite o mesmo ID lógico durante retentativas; a API disponível valida o contexto e confirma sucesso apenas após persistência durável.
2. **Enfileiramento:** a mensagem é identificada com estado, horário original, destino autorizado e histórico de tentativas.
3. **Entrega ao CRM:** o processador invoca a operação idempotente de registro/associação no CRM e aguarda a comprovação efetiva da persistência no destino.
4. **Conferência:** comparar IDs, confirmações e estados entre origem, fila, API de contingência e CRM; prevenir entrega duplicada e detectar inconsistências.
5. **Recuperação:** em divergência recuperável, reprocessar dentro do prazo autorizado e executar playbook seguro; em risco crítico ou sem procedimento aprovado, abrir caso e pedir a autorização humana necessária.
6. **Entrega confirmada:** marcar como entregue e limpar cópias temporárias após 24 horas, preservando o registro no CRM e a auditoria técnica minimizada.
7. **Vencimento sem entrega:** aos 18 dias totais, avisar criticamente, encerrar retentativas, verificar estado e elegibilidade e excluir automaticamente payload temporário se elegível, registrando perda de oportunidade comercial e mantendo auditoria minimizada por 90 dias (IN-147).

## Cenários a tratar no detalhamento técnico

- O visitante envia uma única vez, mas o site retenta após timeout e ambas as APIs podem receber a mesma identidade.
- A API de contingência persiste um envio durante a indisponibilidade da principal; na recuperação, as duas filas precisam reconciliar o mesmo ID.
- O CRM grava o lead, mas perde-se a resposta de confirmação: a consulta idempotente deve evitar criar outro registro indevido.
- O CRM responde "aceito para processamento" sem garantir gravação final: manter status intermediário até confirmação de persistência.
- Duas submissões legítimas com dados idênticos chegam em momentos diferentes: não apagar a segunda por similaridade de campos.
- A entrega é confirmada perto do vencimento de 18 dias; uma exclusão concorrente não pode agir sobre estado desatualizado.
- Uma cópia reaparece após restauração de backup ou recuperação de fila; não reprocessar lead já entregue ou já expirado sem validação e políticas específicas.
- O vínculo de um site com uma conta CRM muda: respeitar o destino e a política de vínculo do envio original, sem transferir dados históricos automaticamente para outra conta.

## Papel dos agentes

- **Agente de Integrações:** analisar divergências, correlacionar APIs/filas/CRM, executar reconciliação e reprocessamentos homologados, registrar causas e soluções.
- **Agente de Infraestrutura/Automações:** investigar condições de rede, armazenamento, processos e workflows que provoquem diferenças ou atrasos.
- **Agente Supervisor Técnico:** acompanhar casos recorrentes, integridade do resultado, exclusões por vencimento, tendências de falha e aprendizado entre incidentes.
- **Administrador Imagine:** acompanhar relatórios e alertas, autorizar operações críticas e consultar evidências conforme perfil.

## Métricas previstas, ainda sem valores numéricos aprovados

- Volume de submissões recebidas e efetivamente confirmadas no CRM; pendentes e expiradas.
- Tempo entre persistência na API e entrega confirmada no CRM.
- Divergências detectadas e resolvidas entre APIs, filas e CRM.
- Quantidade de reprocessamentos, tentativas duplicadas bloqueadas e situações ambíguas.
- Taxa e motivos de expiração ao completar 18 dias; destinatários/clientes afetados.
- Falhas de persistência, alertas preventivos e ocorrências de consistência após failover/failback.

## Especificações pendentes antes da implementação

1. Protocolo formal entre APIs e CRM de aceitação, confirmação durável e consulta de estado do registro de destino.
2. Modelo de IDs, chaves idempotentes, namespaces por empresa e identificadores de vínculo com contas CRM.
3. Controle distribuído de concorrência, reconciliação periódica, estados terminais, ordenação e respostas atrasadas.
4. Metodologia de verificação final antes de exclusão após 18 dias, consistência entre réplicas, backups e registros do CRM.
5. Política operacional para estados desconhecidos quando a validação estiver indisponível no vencimento, sem reintroduzir retenção adicional automática indiscriminada.
6. Retenção técnica de logs minimizados por 90 dias, privacidade, segurança, custo e relatórios.
7. Testes com submissões sintéticas, inclusive quedas de rede e race conditions, antes de homologar qualquer playbook.
8. Definição de limiares de alerta, indicadores, processos de auditoria e responsabilidades.

**Aprovado:** conferência efetiva de entrega, ID estável, reconciliação entre APIs/filas/CRM, prevenção de duplicações, correção controlada e verificação antes da exclusão, preservando integralmente a política da **IN-147 (excluir após 18 dias)** e o aprendizado contínuo dos agentes.  
**Pendente:** protocolos e parâmetros técnicos.  
**Programação:** não iniciada.
