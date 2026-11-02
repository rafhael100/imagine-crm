# IN-156 — Autodiagnóstico e Recuperação dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável do projeto em 02/11/2026 (resposta "ok").  
**Fase:** planejamento e documentação; **não iniciar programação, implantação, intervenções operacionais ou despesas**.  
**Escopo:** os sete agentes técnicos especializados, o Agente Supervisor Técnico, serviços de orquestração, filas internas de tarefas e Central de Incidentes Técnicos da Imagine.  
**Referências:** IN-130 (Central de Incidentes); IN-131/IN-132 (autonomia e catálogo dos agentes); IN-133 a IN-136 (priorização, comunicação, SLA e plantão); IN-137/IN-138 (aprovação humana única e matriz de risco); IN-139/IN-140 (biblioteca e avaliação contínua); IN-141/IN-142 (prevenção/capacidade); IN-143 a IN-146 (continuidade e recuperação); IN-151 (credenciais); IN-153 (inventário); IN-154/IN-155 (mudanças e homologação de procedimentos).

## Objetivo
Monitorar o funcionamento dos próprios agentes técnicos, identificar indisponibilidade, travamentos, respostas inadequadas, falhas repetitivas e comportamentos anormais; aplicar recuperação automatizada **somente quando segura e previamente homologada**, suspender operações potencialmente perigosas, permitir redistribuição de trabalho autorizada e escalar falhas que não puderem ser solucionadas automaticamente.

A recuperação dos agentes **não pode enfraquecer controles de segurança, ampliar permissões ou transformar monitoramento 24/7 em promessa de atendimento humano 24/7**.

## Funcionalidades aprovadas

1. **Monitoramento de disponibilidade e saúde dos agentes:** verificar execução, status, comunicação com ferramentas, respostas a tarefas, filas pendentes, duração, limites de recursos e capacidade de cumprir atividades previstas.
2. **Identificação de falhas:** detectar travamentos, erros em ferramentas, dependências indisponíveis, tarefas presas, respostas malformadas, timeout, falhas de autenticação e resultados inconsistentes.
3. **Recuperação automática segura:** executar reinício ou recuperação de componentes de agente **apenas** mediante playbook previamente homologado, baixo/moderado risco, permissão explícita, limite de tentativas e checagem de resultados.
4. **Detecção de comportamento anormal:** identificar loops de tentativas, consumo excessivo, ações fora do padrão, repetição sem progresso, propostas fora do escopo e uso incompatível com as políticas definidas.
5. **Suspensão preventiva:** interromper/impedir novas operações potencialmente perigosas, desativar playbooks problemáticos e sinalizar responsável, preservando a rastreabilidade do incidente; suspender deve ser um controle independente da vontade do agente afetado.
6. **Substituição operacional:** quando viável, atribuir tarefas pendentes a outro agente **que já detenha as permissões, escopo, ferramentas e condições adequadas**; não usar substituição para contornar uma negativa de aprovação ou expandir autoridade.
7. **Aprendizado contínuo:** registrar falhas, causas verificadas, tentativas, recuperações, efeitos adversos e lições para a biblioteca de procedimentos da IN-139 e os indicadores da IN-140.
8. **Supervisão central:** o Agente Supervisor Técnico coordena a detecção e o escalonamento das falhas dos especialistas, correlaciona recorrências, sugere melhorias e verifica se a operação foi restabelecida, mas **não recebe acesso irrestrito**.

## Autodiagnóstico e monitoramento independente
- O **agente pode reportar seu próprio estado**, mas seu autorrelato **não é prova suficiente** de funcionamento saudável.
- Serviços externos de observabilidade e verificações determinísticas devem validar disponibilidade, saúde e progresso real das tarefas, com status confiável mesmo quando o agente parar de responder.
- O **próprio Agente Supervisor Técnico deve ser monitorado** por mecanismo independente; sua indisponibilidade não poderá impedir totalmente detecção, registro e alertas críticos.
- A indisponibilidade de agentes não deve, por si, impedir funções essenciais de recepção e persistência de leads, redundância das APIs ou outras rotas que não dependam dos agentes (IN-124).
- O monitoramento deve distinguir falha do modelo de IA, falha da ferramenta, falha de rede, falta de autorização, esgotamento de cota e indisponibilidade da infraestrutura.

## Fluxo funcional aprovado

1. Monitor/observabilidade ou agente detecta comportamento anormal.
2. Verificar evidências, classificar gravidade e abrir ou atualizar incidente na Central, com histórico e avisos proporcionais.
3. Identificar se a falha está no agente, em ferramenta, modelo, autenticação, rede, fila de tarefas ou dependência externa.
4. **Impedir ações perigosas e ciclos de repetição**, registrando o motivo.
5. Se existir playbook seguro, ativo e homologado, aplicar recuperação limitada, observando as condições e permissões aprovadas.
6. Validar retorno à saúde por verificações independentes e pela conclusão efetiva de uma tarefa de teste não destrutiva.
7. Se recuperação falhar, suspender o procedimento ou agente afetado e transferir tarefas recuperáveis a outro agente autorizado quando possível.
8. Caso não haja alternativa segura, manter histórico e alertas, sinalizar falta de automação disponível e escalar a responsável humano conforme IN-133 a IN-136.
9. Registrar causa, resultado, recursos usados e lição aprendida; só homologar novos procedimentos conforme IN-155.

## Regras de segurança, limites e autorização

- **Sem autoampliação de privilégios:** agentes não podem mudar suas próprias permissões, conceder tokens, editar políticas de segurança, reclassificar arbitrariamente o risco ou desativar logs/auditoria.
- **Recuperação ≠ autorização irrestrita:** reiniciar um processo só será permitido quando não houver impacto inaceitável em tarefas em andamento, dados, clientes ou disponibilidade e o procedimento estiver validado.
- **Uma aprovação humana para ações críticas:** mudanças produtivas sensíveis, operações destrutivas, alterações de credenciais/permissões e outros riscos altos/críticos continuam sujeitos a **uma única aprovação humana** quando exigida, sem dupla aprovação, conforme IN-137/IN-138.
- **Limitação de loops:** definir limites de tentativas, tempo, concorrência, recursos e reiterações; a interrupção segura não pode depender de comando voluntário do próprio agente que está em loop.
- **Bloqueio de repetição perigosa:** após falhas consecutivas, comportamento suspeito ou risco, impedir novas execuções do playbook afetado até revisão e eventual re-homologação.
- **Transição de tarefas protegida:** substituição deve preservar identificadores, histórico, contexto mínimo necessário, estado e decisões anteriores, evitando execução duplicada de efeitos externos e repetição de ações já concluídas.
- **Segurança dos segredos:** não transferir tokens, senhas ou credenciais em prompts ou histórico entre agentes; acesso a segredos via controles e identidades técnicas da IN-151.
- **Sem custos adicionais automáticos:** não contratar modelo, servidor, provedor ou aumentar quotas pagas sem autorização humana específica, conforme IN-142.
- **Priorizar a operação principal:** processos de monitoramento/reparo dos agentes não podem consumir recursos a ponto de indisponibilizar sites, APIs, CRM, filas ou sistema de alertas.
- **Auditabilidade:** registrar agente e ferramenta, evento, duração, causa, playbook/versão, limites, tentativas, permissão, suspensão, redistribuição, validação, escalonamento e desfecho.

## Substituição e continuidade de tarefas

- Transferir somente tarefas que possam ser retomadas com segurança e que sejam tecnicamente suportadas pelo agente substituto.
- Antes da transferência, verificar se a operação já foi executada parcial ou totalmente, especialmente em ações com efeitos externos.
- Preservar vínculo com o incidente original e impedir perda de evidências ou reabertura silenciosa.
- Se nenhum agente elegível existir, registrar ausência de cobertura automatizada e notificar o responsável humano; **não criar um agente "superadministrador" emergencial**.
- Falha do Supervisor Técnico exige monitoramento e escalonamento por mecanismo independente; eventual substituição do Supervisor também respeitará permissões existentes.

## Exemplos ilustrativos

### Loop no Agente de Integrações
O agente repete uma tentativa sem progresso. O monitor detecta excesso, interrompe novas tentativas, registra o incidente, verifica se alguma etapa de entrega ao CRM ocorreu e aplica somente recuperação segura homologada. Se não resolver, atribui diagnóstico a agente autorizado ou notifica responsável humano; **não duplica envio de leads nem reinicia prazo de retenção**.

### Agente de Infraestrutura indisponível
Uma verificação externa constata ausência de resposta e tarefas pendentes. Se houver reinício controlado seguro e autorizado, executa e valida. Se falhar ou houver impacto crítico potencial, suspende novas ações e escala.

### Supervisor Técnico indisponível
Monitores independentes mantêm registro e envio de alertas essenciais, evitando que a ausência do Supervisor produza falsa aparência de operação normal. A intervenção de recuperação obedecerá às mesmas permissões e critérios de risco.

## Indicadores de qualidade a detalhar
- Disponibilidade e sucesso operacional por agente, especialidade e período.
- Tempo médio de detecção, contenção e recuperação de agentes.
- Quantidade de loops bloqueados, tarefas interrompidas, recuperadas e redistribuídas.
- Taxa de sucesso das recuperações homologadas e recorrência de falhas.
- Ações suspensas por risco e casos que exigiram escalonamento humano.
- Duplicações evitadas, efeitos adversos, consumo de recursos e custos por incidente.

## Pendências antes de qualquer implementação
1. Arquitetura de monitoração independente do Supervisor e dos agentes; componentes sem ponto único de falha nos alertas essenciais.
2. Métricas objetivas de saúde, progresso de tarefas, anomalia, timeout, limites de tentativas e uso de recursos.
3. Catálogo de ações recuperáveis e playbooks homologados, teste de reversibilidade, segurança e condições de suspensão.
4. Definição de tarefas passíveis de redistribuição, formato de estado/contexto, controle de concorrência e idempotência.
5. Política de contingência quando modelos, provedores, redes, cofres de segredos ou orquestrador estiverem indisponíveis.
6. Alertas, escalonamento, autorizações e comportamento sem plantonista humano.
7. Auditoria, retenção, segregação de dados e acesso a logs e informações operacionais.
8. Testes periódicos de falha dos agentes, do Supervisor e das ferramentas, com evidências de recuperação.
9. Dimensionamento de infraestrutura, custo e limites de IA/serviços para monitoramento e recuperação.

**Aprovado:** monitoramento da saúde dos agentes, detecção de falhas e comportamentos anormais, recuperação segura, suspensão preventiva, redistribuição autorizada, aprendizado e supervisão central, com monitoramento independente do Supervisor.  
**Pendente:** parâmetros operacionais, arquitetura detalhada, testes e implementação.  
**Programação:** não iniciada.
