# IN-151 — Gestão Inteligente de Credenciais e Chaves de Integração

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 (resposta "ok").  
**Fase:** somente planejamento e documentação; **não iniciar programação, alterações operacionais, provisionamento nem contratações**.  
**Escopo:** Sites Imagine, formulários, API principal e API de contingência, filas, CRM Imagine, n8n, serviços administrativos e agentes técnicos internos.  
**Referências:** IN-124 (APIs independentes), IN-129 (falhas e agentes), IN-130 a IN-132 (incidentes/autonomia/agentes), IN-137/IN-138 (uma aprovação humana para ações críticas), IN-139/IN-140 (aprendizado/avaliação), IN-142 (controle financeiro), IN-143 a IN-146 (continuidade), IN-148/IN-149 (reconciliação e confirmação), IN-150 (segurança dos formulários e APIs).

## Objetivo
Proteger e administrar o ciclo de vida de segredos técnicos e credenciais de integração, permitindo recuperação segura de falhas e rotação controlada, **sem expor chaves privilegiadas em sites públicos nem criar burocracia desnecessária para procedimentos já homologados**.

## Funcionalidades aprovadas

1. **Cofre central de segredos:** armazenar credenciais, tokens, senhas de serviço e chaves privadas em serviço protegido, com controle de acesso, criptografia e auditoria. A escolha do produto/infraestrutura e sua disponibilidade ainda serão dimensionadas.
2. **Permissões por serviço:** cada serviço e agente técnico terá identidade própria e acesso **somente aos segredos estritamente necessários** ao seu papel, ambiente e operação, com isolamento entre contas/empresas/clientes.
3. **Rotação controlada:** permitir renovação e substituição de chaves, conforme validade e políticas; executar automaticamente apenas rotações previamente homologadas, com verificação de dependências e resultado.
4. **Detecção de vazamento ou comprometimento:** identificar sinais de segredo exposto, acesso anômalo, tentativa suspeita e uso não autorizado, acionando Agente de Segurança, Central de Incidentes e responsáveis.
5. **Recuperação automática autorizada:** agentes poderão recuperar conexões e atualizar configurações de integrações com procedimentos seguros, homologados, escopados e auditados, sem revelar segredos à interface administrativa.
6. **Revogação de acessos:** permitir bloquear credenciais comprometidas, substituir segredos e restaurar integrações por procedimento controlado. Revogações potencialmente disruptivas ou críticas exigem uma aprovação humana, exceto se uma medida específica de contenção de baixo/moderado risco já estiver previamente autorizada.
7. **Auditoria sem exposição:** registrar identidade técnica, solicitante/operador, evento, escopo, momento, justificativa, resultado, versão ou referência não sensível do segredo; **nunca gravar o valor secreto em logs, tickets, notificações ou memória de IA**.

## Princípios obrigatórios de implementação futura

- **Nada de segredo privilegiado no navegador:** formulários, HTML, JavaScript público, aplicativos clientes e URLs compartilháveis não armazenarão tokens secretos administrativos ou credenciais da API interna. Identificadores públicos de formulário não substituem validação e segurança de backend.
- **Autenticação privilegiada entre servidores:** preferir credenciais de serviço de curta duração e permissões delimitadas quando o fornecedor suportar. Autenticação de submissão pública é diferente da autenticação servidor-servidor.
- **Segredo não deve ser instrução ou memória da IA:** agentes podem solicitar operações por ferramentas controladas, preferencialmente sem acesso ao valor bruto do segredo; evitar incluí-lo em prompts, transcrições, relatórios e contexto de aprendizado da IN-139.
- **Privilégio mínimo e segregação:** credenciais de produção e teste, de empresas distintas e de serviços distintos não podem ser misturadas; nenhuma rotação concede ao agente acesso adicional.
- **Armazenamento e transporte seguros:** criptografia em trânsito e em repouso, proteção e rotação de chaves de criptografia, limitação de exposição em memória/processos e proteção de backups, conforme arquitetura a detalhar.
- **Inventário e expiração:** conhecer proprietário, ambiente, dependências, validade, última rotação, escopo de uso, estado e plano de revogação de cada segredo, sem armazenar conteúdo secreto nos metadados.
- **Rotação sem interrupção desnecessária:** quando tecnicamente possível, criar credencial substituta, atualizar dependências, validar em testes e depois revogar a anterior; prever rollback/contingência em caso de falha, sem assumir que todo fornecedor permite duas chaves simultâneas.
- **Revogação emergencial:** ao identificar comprometimento confirmado, executar somente a contenção já autorizada ou solicitar **uma aprovação humana** para intervenção crítica, com alertas imediatos e registro. Não postergar a comunicação crítica aguardando tentativas automáticas.
- **Segredos e indisponibilidade do cofre:** planejar contingência que não desabilite todas as APIs simultaneamente; não copiar segredos para o código público como atalho. Novas despesas para armazenamento de segredos exigem autorização humana expressa (IN-142).
- **Auditoria e detecção:** monitorar tentativas de acesso aos segredos, comportamento irregular, falha de rotação e alterações não autorizadas, minimizando dados pessoais e restringindo consulta a autorizados.

## Fluxo funcional de rotação segura

Identificação de vencimento, comprometimento ou necessidade de atualização → classificação da sensibilidade e do risco da **ação** (IN-138) → avaliação de dependências e permissões → escolha do procedimento homologado → **se seguro e previamente autorizado, executar automaticamente; se crítico, solicitar uma única autorização humana** → implantar credencial substituta em ambiente protegido → verificar conectividade, persistência e integridade das integrações → revogar credencial anterior no momento seguro/necessário → registrar auditoria e aprendizado → atualizar inventário.

O agente não deverá rotacionar credenciais sem verificar efeitos sobre **API principal, contingência, filas e CRM**. A atualização não pode afetar indevidamente a entrega de leads nem provocar descarte/duplicação.

## Cenários de referência

1. **Token de serviço prestes a expirar:** agente monitora validade, agenda rotação autorizada, testa conectividade e registra renovação.
2. **Erro de autenticação no fluxo de leads:** Agente de Integrações diagnostica; Agente de Segurança avalia possível revogação/comprometimento; recuperação segura conforme playbook e limites.
3. **Chave de integração exposta:** abrir incidente com gravidade compatível, conter uso indevido, autorizar uma intervenção crítica quando necessária, substituir a chave e verificar acessos recentes.
4. **Rotação afeta uma das APIs:** preservar recepção pela alternativa saudável quando possível, corrigir com procedimento autorizado e reconciliar mensagens pendentes conforme IN-146/IN-148.
5. **Falha do cofre/fornecedor:** alertar, limitar alterações, manter capacidade de serviços já autorizados por mecanismos de contingência projetados e recuperar o cofre de forma segura.

## Agentes e responsabilidades

- **Agente de Segurança:** suspeitas de vazamento, auditoria de uso, anomalias e medidas de proteção autorizadas.
- **Agente de Integrações:** falhas de autenticação entre APIs, CRM e formulários, verificação de recuperação de entregas.
- **Agente de Infraestrutura:** saúde do serviço de segredos e dependências autorizadas.
- **Agente de Automações:** credenciais e dependências do n8n e workflows, com segregação de ambientes.
- **Agente Supervisor Técnico:** correlação de incidentes, verificação de solução, recorrência e aprendizado; não pode ler segredos desnecessários nem expandir permissões.
- **Administração Imagine:** definir políticas, conceder/retirar acessos, homologar playbooks e **aprovar uma única vez ações críticas** quando exigido, sem dupla aprovação.

## Integração com políticas já aprovadas

- **IN-150:** defesa de formulários públicos e APIs.
- **IN-124/IN-149:** a confirmação de recebimento exige persistência durável na API; uma falha de autenticação do CRM não transforma uma mensagem aceita pela API em entrega confirmada.
- **IN-127/IN-128/IN-147:** envio não entregue mantém 3 dias de tentativas normais mais 15 dias de fila de falhas, aviso preventivo 72 horas antes, e **pode ter o payload temporário excluído automaticamente após completar 18 dias**, com auditoria técnica minimizada por 90 dias; rotação de credenciais não reinicia o prazo.
- **IN-137/IN-138:** somente uma aprovação humana quando realmente necessária para ações críticas; agentes não podem autoconceder privilégios.
- **IN-139/IN-140:** aprendizado com incidentes sem guardar senhas, tokens ou chaves na memória do agente.

## Pontos pendentes antes da implementação

1. Tecnologia de cofre/gerenciador de segredos, disponibilidade, redundância, custos e limites.
2. Catálogo detalhado de tipos de credenciais por serviço e ambiente, titulares, durações e mecanismos de autenticação.
3. Matriz de acesso, autorização, segregação e ciclo de vida de identidades técnicas.
4. Processo de renovação/rotação compatível com cada provedor, uso de duas chaves ou fallback quando possível, rollback e testes.
5. Critérios de identificação de vazamentos, bloqueios emergenciais, monitoramento e obrigações de comunicação.
6. Gestão de chaves de criptografia, backup/recuperação do cofre e política de auditoria/retenção.
7. Interface administrativa que mostre metadados operacionais sem revelar o valor de segredos.
8. Políticas e testes de recuperação quando a infraestrutura de segredos estiver indisponível.

**Aprovado:** cofre de segredos, privilégios mínimos, rotação controlada, detecção de comprometimento, recuperação segura automatizada, revogação de acessos e auditoria sem exposição de credenciais.  
**Pendente:** ferramentas, topologia, periodicidade, valores de risco por operação e dimensionamento.  
**Programação:** não iniciada.
