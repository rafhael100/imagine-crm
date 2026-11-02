# IN-137 — Central de Aprovações Técnicas da Imagine

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026; **revisada na mesma data para retirar integralmente a exigência/possibilidade de dupla aprovação humana**.  
**Fase:** planejamento e documentação; **não iniciar programação, implantação ou contratação**.  
**Escopo:** área administrativa do Portal Imagine, Central de Incidentes Técnicos e os sete agentes técnicos especializados + Agente Supervisor Técnico (IN-132).  
**Referências:** IN-129 (agente de TI e casos técnicos), IN-130 (incidentes), IN-131 (níveis de autonomia), IN-132 (catálogo de agentes), IN-133 (prioridades), IN-134 (alertas), IN-135 (metas internas de resposta), IN-136 (plantão).

## Objetivo

Centralizar e tornar auditável a **autorização humana prévia de intervenções técnicas críticas** solicitadas por agentes de IA, sem dar autonomia irrestrita aos agentes ou transformar urgência em autorização implícita.

## Funcionalidades aprovadas

1. **Solicitação automática:** quando um agente identificar uma intervenção crítica ou fora de sua autorização, preparar solicitação para a Central, vinculada ao incidente, serviço, cliente/ambiente envolvidos e ação específica.
2. **Análise de risco:** mostrar justificativa, impacto esperado, serviços/recursos atingidos, consequências potenciais, alternativas e plano de reversão quando aplicável.
3. **Decisão humana:** somente usuários administrativos **expressamente autorizados** podem aprovar ou rejeitar a solicitação. A rejeição não concede autorização substituta.
4. **Prazo de aprovação:** permitir validade configurável conforme risco e criticidade, com avisos e escalonamento; valores numéricos a definir em etapa posterior.
5. **Execução delimitada:** a autorização permite **apenas a ação, o ambiente, os recursos e as condições aprovadas**. O executor deve revalidar autorização, identidade, validade e condições antes de efetuar a mudança.
6. **Rollback:** preparar e oferecer procedimento de reversão quando tecnicamente possível, incluindo verificações de eficácia e limitações. Não presumir reversão perfeita ou possível para toda ação.
7. **Auditoria:** registrar solicitante/agente, aprovador único autorizado, data/hora, contexto, risco, decisão, validade, execução, resultados, falhas, tentativas e evidências de verificação.

## Regras de segurança aprovadas

- **Sem autoaprovação:** nenhum agente pode aprovar suas próprias solicitações ou atribuir-se competências adicionais.
- **Autorização específica:** aprovação vale somente para a intervenção e o contexto expressamente descritos; não equivale a autorização ampla ou permanente para produção.
- **Expiração segura:** solicitações vencidas não são aprovadas automaticamente e exigem nova decisão humana para execução.
- **Suspensão imediata:** administradores autorizados podem suspender a execução de um agente e bloquear novas ações enquanto se analisa o incidente; operações já concluídas exigem recuperação própria.
- **Uma aprovação humana suficiente:** quando a ação exigir autorização humana por ser crítica ou de alto risco, **uma única aprovação de administrador autorizado** basta para a ação específica. **Não exigir dupla aprovação nem criar cadeias de aprovação**. A validação técnica, a análise de risco e a verificação posterior continuam obrigatórias conforme o procedimento.
- **Monitoramento durante espera:** incidentes críticos permanecem monitorados, com alertas e ações não críticas permitidas, mas **sem executar a intervenção crítica enquanto a aprovação necessária não existir**.
- **Privilégio mínimo:** proteger segredos, dados pessoais, integrações e fronteiras entre empresas. Ler logs, chamados e arquivos não pode ampliar permissões de agentes nem ser tratado como comando confiável.
- **Separação entre decisões:** confirmação de assunção do incidente (IN-135/IN-136) não significa aprovação de mudança técnica crítica.
- **Registro de consequências:** quando houver negação, expiração, falha de execução ou ausência de aprovador humano, manter o incidente/caso técnico aberto conforme critérios da IN-130, com motivo, notificações e possibilidade de escalonamento.

## Fluxo funcional

Detecção de incidente → diagnóstico especializado → agente determina que há ação crítica → proposta específica e análise de risco → solicitação de aprovação → análise de um único aprovador humano autorizado → decisão:
- **Aprovada e vigente:** revalidar condições e executar somente o autorizado, registrar resultado e verificar/efetuar rollback quando necessário;
- **Rejeitada, expirada ou sem aprovador:** não executar ação crítica; registrar, alertar/escalonar, propor alternativas seguras;
→ validar recuperação do serviço e registrar fechamento do incidente apenas com evidências ou justificativa autorizada.

## Exemplos de intervenções críticas
Mudanças em DNS/SSL de produção, permissões e credenciais, infraestrutura produtiva, vínculos de CRM e transferência de dados entre empresas, exclusão de mensagens/arquivos, restauração com sobrescrita e novas despesas. A classificação exata deverá considerar risco, escopo e reversibilidade; o catálogo de ações será versionado.

## Pontos pendentes antes de qualquer implementação

- Matriz de risco e quais ações podem ser executadas autonomamente por playbook homologado e quais exigem **uma aprovação humana**.
- Prazos de validade, lembretes, escalonamento e comportamento fora do expediente.
- Identidade e autenticação reforçada dos aprovadores, segregação de funções e políticas de conflito de interesse.
- Execução idempotente de comandos aprovados, proteção contra mudanças no escopo entre aprovação e execução, limites de tempo, plano de rollback e testes.
- Trilhas de auditoria imutáveis/protegidas, retenção e acesso autorizado aos registros.
- Resposta a indisponibilidade da própria Central de Aprovações e garantias de que falha do serviço não permita bypass.
- Forma de solicitação, notificações, comunicação de resultado e interface administrativa.
- Integração com incidentes, agentes, infraestrutura, domínio, backups, publicação e demais operações sujeitas a aprovação.

**Aprovado:** central de solicitações, análise de risco, aprovação/rejeição por humano, validade configurável, execução limitada à autorização, rollback quando possível, auditoria, vedação a autoaprovação, suspensão administrativa e **uma única aprovação humana quando exigida**, sem dupla aprovação.  
**Pendente:** detalhamento operacional e técnico.  
**Programação:** não iniciada.


## Alteração expressa — simplificação das autorizações (01/11/2026)

O responsável determinou: **“não precisa dessa dupla aprovação; o objetivo é já resolver.”** Portanto, a previsão de exigir duas aprovações em operações de alto risco foi **revogada**. Aplicar os seguintes princípios:

- Agentes resolvem automaticamente problemas seguros, de baixo risco e previamente autorizados, sem abrir pedidos de autorização desnecessários.
- Para ação crítica ou de alto risco que ainda dependa de aprovação humana (conforme IN-131), **basta uma autorização de responsável habilitado**, que pode ser solicitada e concedida em fluxo simples e rápido.
- A autorização humana se refere à ação de risco e não ao diagnóstico, à abertura do incidente, ao teste não destrutivo ou à medida de contenção de baixo risco já autorizada.
- Sem humano disponível, preservar monitoramento, diagnóstico, alertas, intervenções seguras e escalonamento; **não presumir autorização para operação crítica**.
- Risco, alcance das permissões, aprovação e resultado permanecem rastreáveis. Não permitir que o próprio agente reduza artificialmente o risco para dispensar autorização.

**Controle documental:** esta revisão prevalece sobre todas as menções anteriores de dupla aprovação da IN-137. A IN-138 deverá utilizar a mesma política simplificada. A alteração continua restrita ao planejamento; não iniciar programação.
