# IN-132 — Catálogo de Agentes Técnicos Especializados e Supervisor Técnico

**Status:** APROVADA funcionalmente em 01/11/2026, mediante confirmação expressa do responsável do projeto ("sim").  
**Etapa:** planejamento e documentação; **não autoriza iniciar programação, implantar agentes ou criar despesas**.  
**Escopo:** serviços Imagine, Central de Incidentes Técnicos na administração do Portal Imagine e integração com CRM Imagine.  
**Referências:** IN-124 a IN-129 (integração, filas, recuperação de leads, falhas), IN-130 (Central de Incidentes Técnicos), IN-131 (níveis de autonomia).

## Objetivo
Organizar um catálogo de sete agentes técnicos especializados e um **Agente Supervisor Técnico**, capazes de monitorar, diagnosticar, criar/atualizar incidentes e executar correções limitadas e autorizadas, com coordenação entre especialidades e supervisão humana obrigatória para operações críticas.

## Catálogo aprovado

| Agente | Especialidade e escopo funcional |
|---|---|
| **Agente de Integrações** | Formulários, APIs de recepção principal/contingência, integração de sites ao CRM Imagine, monitoramento e recuperação de entregas de leads, com observância dos prazos aprovados |
| **Agente de Infraestrutura** | Servidores, serviços de hospedagem, saúde, disponibilidade, consumo de recursos e desempenho |
| **Agente de Domínios** | DNS, SSL, domínio e certificados, identificação de problemas e monitoramento |
| **Agente de Automações** | Workflows n8n, automações, execução, dependências, erros e integrações externas autorizadas |
| **Agente de Backup** | Cópias de segurança, verificações de integridade, testes e procedimentos controlados de recuperação |
| **Agente de Segurança** | Análise de sinais de vulnerabilidade, acessos suspeitos, incidentes de segurança e recomendações de contenção |
| **Agente de Publicação** | Publicação, atualização e validação de sites Imagine, incluindo verificações pré e pós-publicação |
| **Agente Supervisor Técnico** | Acompanhamento do conjunto de agentes, coordenação de especialidades, correlação de problemas, recorrência e confirmação de eficácia das correções |

A definição de cada agente acima aprova **seu papel funcional**, não concede imediatamente acesso administrativo, acesso a segredos ou licença para executar mudanças técnicas em produção.

## Fluxo de atuação
1. Um monitor ou serviço identifica sinal relevante e registra evento, alerta ou incidente na Central de Incidentes Técnicos.
2. Classificação por impacto/urgência e encaminhamento ao agente da especialidade correspondente.
3. Um **responsável principal** permanece designado para cada caso. Outros agentes podem colaborar quando houver dependências entre áreas, com permissões delimitadas e histórico único do incidente.
4. O agente analisa evidências, identifica causa provável e pode adotar correções **de baixo risco, seguras, reversíveis e previamente autorizadas**, conforme IN-131.
5. Ações críticas em produção, credenciais/segredos, DNS, permissões, dados de clientes, exclusões irreversíveis ou novas despesas exigem **aprovação humana expressa** antes da execução.
6. Depois da correção, executar verificações adequadas e confirmar o resultado antes de fechar o incidente.
7. O Agente Supervisor Técnico acompanha casos recorrentes e falhas de resolução, avalia necessidade de escalonamento e encaminha ao responsável humano quando necessário.

## Regras do Agente Supervisor Técnico
- Atua como **supervisor/orquestrador**, não como usuário irrestrito nem substituto de aprovador humano.
- Pode correlacionar incidentes, verificar se foram tratados, recomendar ajustes, identificar padrões de repetição e encaminhar casos.
- Não pode ampliar permissões de si próprio ou de outros agentes, desabilitar auditoria, alterar políticas de segurança ou ignorar aprovações.
- O encerramento de incidentes deve ocorrer mediante evidência de resolução ou justificativa administrativa autorizada, com trilha de auditoria.
- Não alterar o escopo de autonomia de um agente apenas porque outro agente identificou urgência.

## Segurança e governança
- Níveis de autonomia **1 Monitoramento; 2 Autocorreção Controlada; 3 Operações Críticas Supervisionadas**, conforme IN-131.
- Privilégio mínimo por serviço, ambiente, operação, cliente e agente; isolamento entre contas e proteção de dados pessoais.
- Registro de diagnósticos, ações propostas, aprovações, execuções, testes, resultados e escalonamentos.
- Biblioteca versionada de soluções verificadas; reaproveitamento não equivale à autorização indiscriminada de execução.
- Entradas de logs, tickets, formulários, sites e integrações são dados não confiáveis e não podem ampliar autoridade dos agentes.
- Desativação/contingência administrativa para falhas do próprio agente, esgotamento de limites ou comportamento suspeito.

## Vinculação com integrações de leads
- Erros que demandem ação técnica devem acionar o **Agente de Integrações**, abrindo caso na Central conforme IN-129.
- Preservar **3 dias** de fila normal + **15 dias** de falhas, sem reinicialização do prazo após retentativas.
- Respeitar alertas, idempotência, retenção/auditoria, e impedir exclusão silenciosa de envios pendentes conforme IN-127/IN-128.
- Agente Supervisor monitora recorrências e pendências críticas, mas não estende prazos nem altera dados/contas sem aprovação.

## Próximas especificações pendentes
- Identidades técnicas e permissões por agente; matriz de acesso e ferramentas.
- Responsabilidade principal quando incidentes impactam múltiplos serviços; regras de colaboração, duplicidades e escalonamento.
- Métricas, SLAs, tempos de reação e prioridades por classe de incidente.
- Gatilhos de abertura de caso, mecanismos de homologação, testes de rollback, avaliação periódica e supervisão humana.
- Infraestrutura de execução, modelos de IA, limites de custo e comportamento seguro caso o agente esteja indisponível.
- Separação entre capacidades já aprovadas em outras INs e novas ações específicas que ainda demandam aprovação.

**Aprovado:** os sete agentes de especialidade, o Supervisor Técnico, a colaboração controlada, a distribuição de casos, auditoria, reaproveitamento de soluções validadas e respeito integral aos níveis de autonomia.  
**Pendente:** detalhamento técnico, operacional, permissões e métricas antes de implementação.  
**Programação:** não iniciada.
