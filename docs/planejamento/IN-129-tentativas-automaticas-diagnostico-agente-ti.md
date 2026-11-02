# IN-129 — Retentativas Automáticas, Diagnóstico e Acionamento do Agente de TI

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026, com extensão expressamente aprovada para acionamento do agente de TI.  
**Fase:** planejamento e documentação; não iniciar programação.  
**Escopo:** APIs redundantes e filas de formulários de sites Imagine para entrega ao CRM Imagine.  
**Dependências:** IN-123, IN-124, IN-125, IN-126, IN-127 e IN-128.

## Decisões aprovadas

### 1. Cadência de reenvio automático
| Evento | Próxima tentativa |
|---|---|
| Primeira falha de entrega recuperável | Após 1 minuto |
| Segunda falha recuperável | Após 5 minutos |
| Terceira falha recuperável | Após 15 minutos |
| Quarta falha recuperável | Após 1 hora |
| Falhas recuperáveis seguintes nos três primeiros dias | A cada 3 horas, até o limite da etapa de 3 dias |
| Na fila de falhas (15 dias adicionais) | A cada 12 horas, apenas quando o tipo de erro permitir recuperação automática |
| Ao completar 18 dias desde a primeira persistência durável | Interromper as tentativas automáticas e emitir alerta crítico, conforme IN-127 e IN-128 |

Os intervalos referem-se à programação de tentativas após respostas negativas. Deve-se usar o identificador único do envio e implementar idempotência em toda a cadeia, inclusive quando a confirmação de entrega for incerta ou houver failover entre as duas APIs.

### 2. Diagnóstico de falha e resposta
- Detectar e classificar falhas **transitórias** (p. ex., timeout, erro temporário de rede, indisponibilidade, limites temporários) versus falhas **que demandam correção** (p. ex., credenciais inválidas, autorização, configuração, rota, mapeamento de campos, permissões e incompatibilidade de integração).
- Disponibilizar na área administrativa do Portal Imagine a categoria do erro, mensagem legível, código técnico quando aplicável, serviço afetado, site e formulário envolvidos, data/hora, quantidade de tentativas, estado, próxima ação e histórico.
- **Não repetir agressivamente** uma tentativa cujo erro é permanente ou exige alteração de configuração: marcar o impedimento, preservar o lead e acionar recuperação adequada, respeitando os prazos de retenção IN-127.
- Retomar o processamento automaticamente, quando seguro, depois da confirmação de que o serviço ou integração voltou a funcionar, com verificação de idempotência e sem criar leads duplicados.
- Não exibir segredos, tokens, dados pessoais ou cargas completas em alertas e logs técnicos.

### 3. Acionamento automático do Agente de TI especializado — aprovado
- Assim que for identificado **erro de autenticação, configuração, autorização, integração ou outro problema que exija intervenção técnica**, acionar automaticamente o **agente de IA de TI especializado em integrações/formulários da Imagine**.
- O agente **abre um caso técnico/incidente** no ambiente administrativo da Imagine, vinculado ao erro detectado, serviço, site, formulário, conta CRM autorizada, envios afetados e histórico de tentativas; evitar múltiplos casos idênticos para o mesmo incidente.
- O agente investiga a causa com ferramentas e registros aos quais tem autorização, propõe o reparo e, dentro de políticas explícitas e permissões limitadas, **executa de forma automática apenas ações corretivas previamente classificadas como seguras, reversíveis e de baixo risco**.
- **Operações sensíveis ou de alto risco** (alterar credenciais/segredos, permissões, contratos/vínculos CRM, dados de clientes, infraestrutura de produção, excluir mensagens, mudanças irreversíveis e novas despesas) dependem de aprovação humana autorizada, seguindo políticas de segurança, auditoria e segregação.
- Quando não houver correção automática segura, o agente mantém o chamado aberto, inclui diagnóstico e ações recomendadas e **escalona à equipe técnica humana**, com prioridade conforme impacto e proximidade da expiração.
- Após correção, executar verificação controlada da integração e reprocessar os leads pendentes quando permitido; **encerrar o caso só quando a recuperação for confirmada** ou quando resolução administrativa fundamentada for registrada.
- Manter trilha de auditoria de abertura, diagnóstico, ações automáticas/humanas, aprovações, testes, reenvios, resultados e encerramento.

### 4. Articulação com as filas e sua retenção
- Os envios não entregues continuam **até 3 dias em fila normal** e **mais 15 dias na fila de falhas**, totalizando **18 dias**, sem reinício do relógio por tentativas, alternância de APIs ou abertura do caso.
- A abertura do caso técnico **não prorroga** a retenção aprovada, nem autoriza descarte automático de conteúdo não entregue.
- Para casos não resolvidos perto do prazo, aplicar o alerta administrativo de **72 horas antes do vencimento** e o alerta crítico/interrupção de tentativas automáticas no dia 18, conforme IN-128.
- Os casos e registros técnicos observam a retenção de **90 dias de auditoria minimizada** aprovada na IN-128, sem confundir metadados de auditoria com retenção dos conteúdos integrais dos leads.
- Depois da entrega confirmada ao CRM, remover a cópia temporária do lead da fila após **24 horas**, ressalvadas salvaguardas e políticas já aprovadas.

## Exemplo de caso (apenas ilustrativo)
- **Origem:** Site A / formulário Contato
- **Erro:** 401 — credenciais da integração rejeitadas pelo CRM
- **Resposta:** registrar erro e evitar reenvios inúteis; acionar Agente de TI; criar caso com diagnóstico
- **Agente:** analisar logs sem expor segredo, verificar autorização e configuração, propor reparo; solicitar aprovação para qualquer modificação de credencial/permissão
- **Resolução:** após intervenção autorizada, testar integração, reprocessar pendências, confirmar entrega, atualizar e encerrar o caso

## Detalhes técnicos pendentes de planejamento
- Infraestrutura e modelo de execução do agente de TI, suas ferramentas e permissões mínimas, limites financeiros, aprovações, rollback, controle de prompts e registros de auditoria.
- Catálogo determinístico de classes de erros e ações automáticas seguras, incluindo o tratamento de HTTP 4xx/5xx, expiração de credenciais e problemas de payload.
- Modelo e SLA do caso técnico, regras de prioridade, responsável, escalonamento humano, notificações e tratamento de alertas repetidos.
- Detalhes de concorrência, jitter e limitação de taxa em retentativas, tentativas simultâneas nas APIs e prevenção de duplicação.
- Política final para o descarte dos leads não entregues após 18 dias (ainda **não aprovada**).

**Status:** decisões funcionais aprovadas, inclusive acionamento automático do agente de TI e abertura do caso; especificações operacionais/segurança a concluir antes do desenvolvimento.
