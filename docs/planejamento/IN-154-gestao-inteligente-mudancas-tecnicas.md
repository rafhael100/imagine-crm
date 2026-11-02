# IN-154 — Gestão Inteligente de Mudanças Técnicas

**Status:** APROVADA funcionalmente pelo responsável do projeto em 02/11/2026 (confirmação "ok").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, implantação, atualizações reais ou novas despesas**.  
**Escopo:** administração técnica Imagine, Sites Imagine, CRM Imagine SaaS, Portal Imagine, servidores, APIs e filas de leads, n8n, bancos de dados, domínios, credenciais e integrações.  
**Referências:** IN-101 (backups e recuperação dos sites); IN-124 e IN-127–IN-129 (recepção, filas, retenção e recuperação de leads); IN-130–IN-132 (incidentes, autonomia e agentes); IN-137/IN-138 (autorização humana única e risco); IN-139–IN-142 (aprendizado, avaliação, prevenção e capacidade); IN-143–IN-146 (continuidade e recuperação); IN-147–IN-149 (exclusão após 18 dias, reconciliação e confirmação); IN-150–IN-153 (segurança, credenciais, vulnerabilidades e inventário).

## Objetivo

Gerenciar alterações técnicas na infraestrutura e nos sistemas Imagine **com o máximo de automação segura e o mínimo de intervenções manuais**, reduzindo falhas, regressões, alterações sem rastreabilidade e impactos inesperados em outros serviços. Antes de mudar um serviço, os agentes devem compreender suas dependências e riscos; após a mudança, precisam comprovar o resultado e aprender com ele.

## Funcionalidades aprovadas

1. **Identificação de mudanças:** acompanhar alterações planejadas, autorizadas e imprevistas em servidores, aplicações, configurações, versões, DNS, integrações e demais componentes, correlacionando com o inventário da IN-153.
2. **Análise de impacto e dependências:** identificar quais serviços, clientes, funções críticas, credenciais, bancos, filas, backups e rotas de contingência podem ser afetados antes da execução.
3. **Planejamento automático:** produzir plano de mudança com justificativa, escopo, ambiente, pré-condições, riscos, sequência de passos, controles de segurança, testes e procedimento de recuperação/rollback quando tecnicamente possível.
4. **Backup preventivo e ponto de recuperação:** quando a natureza da mudança justificar, verificar backups existentes ou criar ponto de recuperação seguro e adequado antes da intervenção, respeitando a independência entre backups dos sites e do CRM.
5. **Execução automatizada autorizada:** aplicar mudanças **simples, seguras, de baixo risco ou moderadas com procedimento homologado**, apenas dentro de permissões preexistentes, limites aprovados e condições verificáveis.
6. **Validação posterior:** verificar saúde, integridade, disponibilidade e comportamento dos serviços afetados, inclusive suas dependências, após a mudança; execução concluída não equivale a sucesso.
7. **Reversão e recuperação inteligente:** ao detectar regressão, suspender novas ações conflitantes e utilizar rollback/recuperação já autorizado, seguro e tecnicamente viável; ações críticas ou destrutivas exigem autorização humana.
8. **Aprendizado contínuo:** documentar estados antes/depois, causas, evidências, resultados, falhas, reversões e melhorias sugeridas para a biblioteca versionada de procedimentos homologados (IN-139).
9. **Acompanhamento pelo Supervisor Técnico:** acompanhar dependências, mudanças simultâneas, incidentes associados, procedimentos ineficazes e resultados; não ampliar permissões nem contornar autorizações.

## Diretriz de autonomia e autorização

- O objetivo **não é solicitar aprovação humana para cada pequena mudança**. Diagnósticos, propostas e alterações seguras **já autorizadas por política e playbook homologado** podem ser executadas automaticamente.
- **Mudanças críticas**, por exemplo alterações com risco significativo para produção, credenciais e permissões, DNS sensível, migrações irreversíveis ou sobrescrita de dados, dependem de **uma única aprovação humana expressa e específica**, conforme IN-137/IN-138; **não há dupla aprovação**.
- Um incidente urgente não aumenta automaticamente o nível de acesso dos agentes.
- Nenhum agente pode alterar sua própria classificação de risco, suas permissões ou a política de homologação para dispensar aprovação.
- Contratação de recursos, serviços ou ferramentas pagos e aumento de despesas dependem de autorização humana prévia (IN-142), ainda que a mudança técnica seja classificada como baixo risco.

## Fluxo funcional aprovado

1. **Detectar ou receber proposta de mudança** e vincular ao serviço/ativo do inventário.
2. **Conferir estado atual real**, versão e origem dos dados; distinguir infraestrutura planejada da que está efetivamente implantada (IN-153).
3. **Analisar dependências, impacto e risco** da intervenção, incluindo clientes, serviços críticos, filas, APIs de contingência, credenciais e custos.
4. **Selecionar playbook homologado**; preparar plano, condições, testes, limites, sequência e recuperação.
5. **Preparar salvaguardas**, incluindo backup/ponto de recuperação quando necessário, validação de integridade e prevenção de mudanças concorrentes.
6. **Executar automaticamente** se a ação for segura e já autorizada; **solicitar uma aprovação humana** se for crítica ou fora do escopo permitido. Se não houver autorização, não executar a operação crítica.
7. **Testar resultados** por indicadores reais de serviço e integração, não somente por comando bem-sucedido.
8. **Se houver falha**, interromper a sequência, aplicar correção/rollback permitido ou escalar o caso, com abertura de incidente quando cabível.
9. **Registrar antes/depois, resultados e lições**, e sugerir melhorias que somente se tornam executáveis após homologação.

## Regras essenciais de segurança e continuidade

- **Registro íntegro:** cada mudança terá identificador, alvo, serviços e clientes afetados, responsável/agente, janela quando aplicável, versão anterior/nova, risco, autorização, ações realizadas e evidências de validação.
- **Concorrência controlada:** impedir duas mudanças incompatíveis simultâneas no mesmo componente, serviço dependente ou dado; priorizar restauração de operação em caso de incidente.
- **Menor impacto possível:** preferir implantação gradual, verificações prévias, ambiente de homologação e procedimentos reversíveis quando a arquitetura permitir; não assumir que todo sistema suporta implantação sem parada.
- **Backups por domínio:** cópias e recuperação de sites seguem IN-101, separadamente da política de backup/retensão do CRM; verificar que o ponto de recuperação é íntegro antes de depender dele.
- **APIs de formulários independentes:** mudanças não devem derrubar simultaneamente as duas rotas de recepção nem invalidar a independência de persistência da API principal e de contingência (IN-124).
- **Integridade dos leads:** preservar IDs idempotentes, confirmação de entrega efetiva, reconciliação das filas e comportamento seguro durante failover/failback (IN-146/IN-148/IN-149).
- **Prazos já aprovados:** a mudança não reinicia a contagem de **3 dias em fila normal + 15 dias na fila de falhas**. Após 18 dias, o payload não entregue pode ser excluído conforme IN-147, com avisos e auditoria; a mudança não cria quarentena adicional.
- **Dados e segredos protegidos:** não mostrar tokens, senhas, dados pessoais desnecessários ou credenciais em relatórios, logs, prompts ou memória dos agentes; respeitar IN-150/IN-151 e o isolamento entre empresas.
- **Mudanças inesperadas:** detectar e sinalizar desvios entre inventário aprovado e ambiente observado, sem presumir que todo desvio deva ser revertido automaticamente; avaliar se existe manutenção autorizada em curso.
- **Testes posteriores obrigatórios:** validar endpoints e processos afetados, disponibilidade, filas, integração ao CRM, automações e dependências relevantes; se resultados forem inconclusivos, não marcar a mudança como plenamente bem-sucedida.
- **Falha no rollback:** quando a reversão automática não for segura, completa ou possível, aplicar contenção já autorizada, manter incidente aberto e solicitar uma única aprovação humana para operações críticas.
- **Biblioteca de conhecimento:** soluções e mudanças bem-sucedidas geram lições pesquisáveis; novos playbooks nunca passam automaticamente a ser permitidos só porque uma tentativa funcionou (IN-139).

## Exemplo ilustrativo

O Agente de Segurança identifica uma atualização relevante de uma biblioteca de serviço. O Agente de Infraestrutura e o Agente de Publicação consultam o inventário de dependências, verificam quais sites e APIs são afetados, testam em homologação, asseguram ponto de recuperação quando necessário e aplicam a atualização automaticamente **somente se houver um playbook seguro previamente autorizado**. Depois conferem respostas, processamento de filas, integração com CRM e saúde do serviço. Se houver regressão, usam recuperação homologada ou escalonam. O Supervisor registra resultados e recomenda a melhoria do procedimento.

## Interação com a Central de Incidentes e de Aprovações

- Mudanças relacionadas a incidentes devem ter vínculo com o caso técnico, prioridade e histórico único (IN-130/IN-133).
- Mudanças críticas dependentes de aprovação humana entram na Central de Aprovações Técnicas da IN-137, com **somente um aprovador habilitado**.
- Aprovação da mudança não implica autorização para outro ambiente, outra conta ou uma nova despesa.
- Pedidos não atendidos não impedem monitoramento, diagnóstico e contenção segura já permitida.
- Mudanças que resultem em falha operacional devem gerar ou atualizar incidente, com alertas e escalonamento proporcionais, além de verificação de recuperação.
- O Supervisor Técnico acompanha eficácia e recorrência, mas não aprova mudanças críticas sozinho nem altera os níveis de autonomia.

## Indicadores previstos, sem metas quantitativas aprovadas

- Mudanças executadas automaticamente versus mudanças com intervenção humana.
- Taxa de mudanças bem-sucedidas com validação confirmada.
- Alterações que causaram incidentes, regressões ou necessidade de rollback.
- Tempo de preparação, execução, verificação e recuperação.
- Mudanças imprevistas detectadas e analisadas.
- Incidência de falhas repetidas por serviço, tipo de operação e playbook.
- Impacto sobre disponibilidade e estabilidade dos serviços afetados.

## Pendências antes da implementação

1. Catálogo técnico de mudanças possíveis, ambientes, risco e permissões por agente, com procedimentos homologados.
2. Processo para aprovar/homologar novos playbooks, revisar versões e revogar permissões operacionais.
3. Regras objetivas de análise de dependências, priorização e concorrência de mudanças.
4. Políticas de backups e pontos de recuperação por componente, sem confundir snapshots de infraestrutura com backups de dados verificados.
5. Critérios de validação de mudanças, limiares de regressão, métodos de rollback e recuperação de bancos/filas com efeitos não reversíveis.
6. Fluxo de mudança em produção, janelas de manutenção e notificações a clientes quando necessário.
7. Modelo de dados e trilha de auditoria para estado anterior/atual, ambientes, evidências e identidade do executante.
8. Integrações com inventário, monitoramento, incidentes, central de aprovações, pipelines de publicação e base de conhecimento.
9. Retenção de registros e proteção de dados de mudança, métricas, relatórios e custos.

**Aprovado:** identificação de mudanças, análise de impacto, planejamento, backup preventivo quando aplicável, execução automática homologada, validação posterior, reversão inteligente, registro antes/depois e aprendizado contínuo.  
**Pendente:** catálogo detalhado, limites operacionais e especificações técnicas.  
**Programação:** não iniciada.
