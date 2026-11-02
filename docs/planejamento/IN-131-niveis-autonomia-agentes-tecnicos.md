# IN-131 — Níveis de Autonomia dos Agentes Técnicos da Imagine

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Etapa:** exclusivamente planejamento e documentação; **não autoriza implementação**.  
**Escopo:** Central de Incidentes Técnicos, agentes de IA de TI da Imagine e integrações operacionais do Portal Imagine.  
**Referências:** IN-124 (APIs redundantes para formulários), IN-126 a IN-129 (filas, erros e recuperação de leads), IN-130 (Central de Incidentes Técnicos).

## Objetivo
Estabelecer três níveis distintos de autonomia para agentes técnicos de IA da Imagine, com privilégios mínimos por serviço, supervisão administrativa, trilha de auditoria e aprovação humana obrigatória para ações críticas.

## Níveis aprovados

### Nível 1 — Monitoramento
- Identificar falhas, consultar registros e sinais técnicos aos quais o agente tenha acesso autorizado, classificar eventos, abrir ou atualizar casos e sugerir soluções.
- **Sem autorização para modificar configurações de produção ou executar correções**.

### Nível 2 — Autocorreção controlada
- Engloba monitoramento e pode **executar apenas procedimentos seguros, reversíveis, de baixo risco e previamente autorizados** para aquele serviço.
- Pode testar integrações e reprocessar operações pendentes quando as políticas e permissões específicas permitirem, com controle de idempotência, limites e auditoria.
- Não pode elevar suas permissões nem executar ações críticas por considerar uma correção necessária.

### Nível 3 — Operações críticas supervisionadas
- Pode analisar, diagnosticar, preparar plano de correção e solicitar aprovação de responsável humano para intervenções críticas.
- **A execução de operações críticas continua dependendo de aprovação humana explícita e autorizada, caso a caso**, mesmo no nível 3. O nível 3 não equivale a autonomia irrestrita.
- Uma vez aprovada a ação, aplicar escopo da aprovação, validações prévias, plano de reversão quando viável e verificação posterior.

## Regras adicionais aprovadas
1. **Permissões por serviço:** cada agente recebe autorizações específicas de leitura, diagnóstico, testes e ações permitidas, com escopo por infraestrutura, integração, cliente e projeto.
2. **Controle administrativo:** a administração Imagine poderá habilitar ou desabilitar a autocorreção de cada agente, sem necessidade de desativar seu monitoramento.
3. **Aprovação humana obrigatória** para alterações críticas envolvendo servidores/infraestrutura produtiva, credenciais ou segredos, DNS, permissões/acessos, dados de clientes e ações irreversíveis. Novas despesas ou compromissos financeiros não podem ser autorizados unilateralmente pelo agente.
4. **Auditoria integral:** registrar detecção, abertura do caso, diagnósticos, recomendações, ferramentas usadas, solicitações, aprovações/negações, execução, resultados e responsável; proteger dados sensíveis e segredos.
5. **Escalonamento:** quando não houver solução segura dentro das permissões do agente, encaminhar o caso a responsável humano ou especialista apropriado, preservando o contexto técnico e a prioridade.
6. **Reutilização de soluções:** procedimentos bem-sucedidos podem entrar em biblioteca versionada **somente depois de validados**, respeitando revisão, controles de risco e permissões; sucesso anterior não autoriza execução automática em qualquer ambiente.
7. **Respeito aos prazos e integrações:** no caso de leads de formulários, não reiniciar os prazos aprovados (3 dias na fila normal + 15 dias na fila de falhas), aplicar as regras de alerta e não eliminar silenciosamente envios não entregues.

## Diretrizes de segurança e governança para detalhamento
- Catálogo de ações permitidas por serviço, identidade técnica distinta por agente, permissões mínimas, segregação entre clientes e ambientes, rotação e proteção de credenciais.
- Matriz de risco da ação e critérios objetivos de aprovação, rollback, limites de execução, testes em ambiente controlado e bloqueio preventivo.
- Registro de aprovações com ação exata, aprovador, data/hora, validade e evidência; aprovação expirada ou em escopo diferente não concede autorização.
- Tratamento de entradas não confiáveis (logs, tickets, e-mails e conteúdo recebido de sites) para prevenir execução de instruções maliciosas por agentes.
- Monitoramento de comportamento anormal e mecanismos de desativação rápida.
- Detalhes de retenção de auditoria específica dos agentes e incidentes ainda devem ser compatibilizados com as demais políticas.

## Status final
**Aprovado:** três níveis de autonomia; limites por serviço; autocorreção administrativa opcional; aprovação humana para operações críticas; auditoria; escalonamento; reutilização de soluções validadas.  
**Pendente:** catálogo dos agentes e procedimentos, matriz formal de riscos, políticas detalhadas de autorização, prazos e infraestrutura técnica.  
**Programação:** não iniciada.
