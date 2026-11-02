# IN-138 — Matriz Simplificada de Riscos e Autonomia dos Agentes Técnicos

**Estado:** APROVADA funcionalmente em 01/11/2026 pelo responsável do projeto ("ok").  
**Fase:** planejamento e documentação; **não iniciar programação**.  
**Escopo:** Central de Incidentes Técnicos da Imagine, Agente Supervisor Técnico e agentes especializados.  
**Referências:** IN-129 a IN-137, especialmente IN-131 (níveis de autonomia) e IN-137 (Central de Aprovações Técnicas, revista para remover dupla aprovação).

## Diretriz central

**Resolver automaticamente tudo o que for seguro, reversível e previamente autorizado; solicitar intervenção humana somente quando indispensável.** Priorizar restauração de serviço e proteção de dados, evitando filas e burocracia de aprovações.

## Matriz de risco — APROVADA

| Risco | Regra de execução |
|---|---|
| **Baixo** | Agente pode resolver automaticamente, dentro de suas permissões e dos procedimentos aprovados. |
| **Moderado** | Agente pode resolver automaticamente **somente por procedimento seguro, homologado, reversível quando aplicável e especificamente autorizado** para o serviço/ambiente. |
| **Alto** | Solicitar **uma única aprovação humana**, quando a operação exigir autorização conforme políticas; sem duplo aprovador. |
| **Crítico** | Intervenção crítica exige **uma única aprovação humana autorizada** antes da execução; não há segunda aprovação. Diagnóstico, monitoramento e contenção segura previamente autorizada podem prosseguir. |

A matriz se refere ao **risco da intervenção**, que é distinto da **prioridade do incidente** (baixa/média/alta/crítica na IN-133). Um incidente crítico pode justificar diagnóstico e ações de baixo risco imediatamente, mas não transformar ação crítica em operação automaticamente autorizada.

## Regras funcionais aprovadas

1. **Diagnóstico imediato:** o agente especializado identifica e investiga erros, abre/atualiza caso técnico, consulta evidências autorizadas e verifica o impacto.
2. **Autocorreção segura:** ações de risco baixo e moderado, quando incluídas em procedimento previamente homologado e nas permissões do agente, podem ser executadas sem abertura de aprovação humana individual.
3. **Autorização humana simples:** para alterações críticas ou de alto risco sujeitas a autorização, pedir somente **uma aprovação expressa de administrador habilitado**, contextualizada e específica para a ação/ambiente; rejeição ou ausência de aprovador não autoriza execução.
4. **Sem dupla aprovação:** a regra de duas aprovações foi **expressamente recusada** pelo responsável do projeto e não deverá ser reintroduzida no fluxo padrão.
5. **Agentes não podem reduzir a própria classificação de risco** para burlar a aprovação nem ampliar permissões, redefinir políticas, suspender auditoria ou conceder acesso a si próprios. Classificação requer critérios/políticas versionadas e verificações independentes.
6. **Correção verificada:** após agir, executar teste controlado de saúde do serviço e registrar evidência de resultado; só encerrar o incidente após confirmação da recuperação ou decisão administrativa fundamentada.
7. **Procedimento seguro de exemplo:** reiniciar um processo travado somente se o procedimento estiver previamente autorizado para aquele serviço e houver verificações de estado, limitação de tentativas, controle de impacto e reversibilidade/contingência.
8. **Ações sensíveis:** mudanças em credenciais, segredos, DNS, permissões, servidores de produção, dados de clientes, exclusões irreversíveis e novas despesas continuam sujeitas a aprovação humana quando classificadas como críticas/de alto risco, segundo a IN-131 e a IN-137.
9. **Na ausência de aprovador:** manter diagnósticos, monitoramento, alertas, medidas seguras autorizadas e escalonamento; não executar ações críticas sem autorização, mesmo que o incidente seja urgente.
10. **Auditoria:** manter histórico da detecção, avaliação de risco, escolha do procedimento, concessões de permissão/decisões humanas quando exigidas, ação executada, teste final, resultado e eventual rollback.
11. **Políticas mais restritivas:** administração pode configurar controles por serviço/ambiente, desde que nenhuma regra elimine salvaguardas obrigatórias ou crie aprovações ocultas contraditórias às decisões do projeto.

## Fluxo funcional

Detectar incidente → diagnóstico e classificação do risco da **ação** → verificar procedimento e permissão:
- **Baixo/moderado autorizado:** executar correção segura automaticamente → testar → registrar → resolver ou escalar.
- **Alto/crítico:** pedir **uma aprovação humana** → executar somente ação aprovada → testar → registrar → resolver ou escalar.
- **Sem permissão/sem procedimento/sem aprovação:** não executar mudança; registrar diagnóstico, tentar alternativas seguras autorizadas e encaminhar à equipe responsável.

## Controles para detalhar antes de implementar
- Matriz de classificação por tipo de ação, risco inerente/residual, exposição dos clientes, impacto, reversibilidade e ambiente.
- Catálogo de playbooks homologados, proprietário da autorização, revisão/versão, condições de execução, limites de repetição e testes de rollback.
- Validação independente do risco e permissões fora da decisão livre do próprio modelo de IA; execução por mecanismos determinísticos e auditáveis.
- Controles de concorrência, operações idempotentes, redução de alertas e diagnóstico de falhas de autocorreção.
- Forma de autenticação do aprovador único, validade e escopo da autorização, notificações e contingência de indisponibilidade humana.
- Auditoria, retenção de registros, conformidade LGPD, segurança dos segredos, proteção contra instruções maliciosas em logs ou chamados.
- Integração aos procedimentos de contingência e aos objetivos de atendimento internos das IN-133 a IN-136.

**Aprovado:** matriz simplificada de baixo/moderado/alto/crítico, execução automática segura quando autorizada, **uma aprovação humana para operações críticas**, vedação a dupla aprovação, auditoria e verificação de resultado.  
**Pendente:** catálogo técnico de ações, parâmetros concretos e implantação.  
**Programação:** não iniciada.
