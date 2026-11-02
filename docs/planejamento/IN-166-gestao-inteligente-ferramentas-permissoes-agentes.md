# IN-166 — Gestão Inteligente das Ferramentas e Permissões dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável em **02/11/2026** (confirmação "ok").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, integrações reais, provisionamento/revogação de acessos ou contratações**.  
**Escopo:** os sete agentes técnicos especializados da Imagine, o Agente Supervisor Técnico e seus mecanismos de orquestração; ferramentas para infraestrutura, sites, APIs, CRM, n8n, GitHub, monitoramento, backups, domínios, segurança e demais integrações autorizadas.  
**Referências:** IN-130 a IN-132 (incidentes, autonomia e agentes), IN-137/IN-138 (Central de Aprovações e riscos, sem dupla aprovação), IN-139/IN-140 (conhecimento e desempenho), IN-142 (controle financeiro), IN-150/IN-151 (segurança e credenciais), IN-153 (inventário), IN-154/IN-155 (mudanças e homologação de playbooks), IN-156/IN-157 (recuperação e modelos), IN-159/IN-160 (observabilidade e auditoria), IN-163/IN-164 (ciclo de vida e curadoria), IN-165 (versionamento e homologação das instruções).

## Objetivo e regra central

Definir **quais ferramentas cada agente pode acessar e quais operações pode executar**, com limites por finalidade, ambiente, serviço, conta, criticidade e contexto. A autorização deve ocorrer em **camada técnica independente do modelo de IA**, e não por declaração do agente ou instrução textual.

**Um agente só executa uma ação quando a ferramenta, a operação, o alvo, o ambiente, a identidade e o procedimento estão autorizados naquele contexto.** Ajustar o prompt, mudar o modelo ou trocar de agente nunca amplia privilégios.

## Funcionalidades aprovadas

1. **Catálogo de ferramentas:** cadastrar ferramentas internas e de terceiros, funções/ações disponíveis, proprietários, sistemas de destino, versão, requisitos, risco, permissões e estado operacional.
2. **Permissões específicas por agente e operação:** separar acesso para leitura/diagnóstico, simulação, proposta, alterações seguras, execução crítica e gerenciamento de permissões. Aplicar o **menor privilégio**, escopo mínimo e princípio de negação por padrão.
3. **Controle por ambiente:** segregar desenvolvimento, homologação e produção, inclusive contas e empresas clientes; concessão em teste não vale automaticamente em produção.
4. **Verificação antes de cada operação:** mecanismo externo ao modelo confere identidade técnica, ferramenta e versão autorizadas, operação, recurso, ambiente, políticas de risco, homologação do playbook, condições, limites de repetição e, quando necessária, **uma aprovação humana específica**.
5. **Gestão de versões de ferramentas:** registrar versões, compatibilidade de parâmetros, mudanças em contratos de API, comportamentos e riscos; exigir avaliação antes de disponibilizar uma versão nova que altere capacidade ou efeitos das operações.
6. **Revogação e suspensão de acessos:** suspender credenciais, ferramentas ou operações comprometidas, obsoletas, desnecessárias ou inseguras, preservando auditoria; restabelecimento segue procedimento controlado.
7. **Auditoria por operação:** registrar agente, identidade técnica, ferramenta/versão, ação, alvo, ambiente, autorização, playbook/versão, resultado comprovado e eventuais erros, sem incluir segredos nem dados pessoais desnecessários.

## Regras de autorização, riscos e autonomia

- **Nenhuma instrução concede acesso:** prompts e modelos da IN-165/IN-157 apenas orientam propostas; permissões e execução devem ser verificadas por serviço intermediário/autorizador, com políticas e credenciais fora do controle do texto da IA.
- **Negação por padrão:** se identidade, escopo, risco, integridade da ferramenta, condição do playbook ou aprovação estiverem ausentes/inválidos, a ação de efeito externo não será executada. Permitir diagnóstico sem efeitos colaterais apenas quando já autorizado.
- **Baixo risco:** operações seguras previamente autorizadas e com condições comprovadas podem ser automáticas.
- **Risco moderado:** somente execução automática quando houver playbook seguro, testado, homologado e limites adequados, de acordo com IN-138/IN-155.
- **Alto/crítico ou fora da homologação:** exigir **uma única aprovação humana para a intervenção**, quando aplicável. A autorização precisa delimitar ação, alvo, serviço, ambiente, prazo/validade e riscos; não é aprovação genérica para todo o agente.
- **Sem dupla aprovação:** o fluxo não criará revisões humanas duplicadas para uma mesma autorização. A homologação inicial de procedimento seguro permite reuso automático em seu escopo; não transforma ações críticas em livres.
- **Sem autoconcessão:** agentes e Supervisor Técnico não podem criar papéis, atribuir permissões a si mesmos, elevar privilégios, alterar a matriz de riscos ou reclassificar a execução para dispensar checagens.
- **Custos:** permissão técnica para chamar uma ferramenta não implica autorização para contratar serviços, gerar novas despesas, aumentar orçamento ou mudar plano; aprovação financeira continua específica (IN-142/IN-158).
- **Isolamento entre empresas:** uma ferramenta de empresa/cliente só pode atuar no contexto autenticado e autorizado; dados, arquivos, domínios e credenciais de outro cliente não podem ser acessados por simples manipulação de parâmetros.
- **Operações destrutivas:** exclusão, sobrescrita, migrações, alterações de DNS/credenciais/permissões e procedimentos potencialmente irreversíveis demandam classificação apropriada e controles específicos; não considerar uma chamada de ferramenta segura só porque foi bem-sucedida.
- **Revogação efetiva:** ao revogar autorização, bloquear novas execuções na camada executora e impedir reuso por sessões, filas, modelos alternativos, cópias de credenciais ou agentes substitutos.

## Matriz funcional ilustrativa por especialidade

| Agente | Acessos típicos a avaliar no catálogo | Limite fundamental |
|---|---|---|
| Integrações | Diagnóstico de APIs/formulários, filas, confirmação de entregas e reprocessamentos idempotentes | Não apagar leads do CRM nem alterar destinos de clientes sem autorização específica |
| Infraestrutura | Consultar saúde, recursos, serviços e executar recuperações homologadas | Não ampliar servidores/recursos tarifados nem efetuar mudanças críticas sem autorização |
| Domínios | Consultar DNS, SSL, vencimentos e propor correções | Alterações críticas de DNS ou transferência de domínio seguem aprovação específica |
| Automações | Inspecionar workflows n8n, erros e execuções; recuperar processos homologados | Não publicar workflows de alto impacto nem disparar efeitos duplicados sem autorização |
| Backup | Verificar cópias, integridade e testes de recuperação aprovados | Restaurações e sobrescritas sensíveis seguem controles de risco, separadas por sistema |
| Segurança | Analisar detecções, conter ameaças por playbooks autorizados e acompanhar credenciais | Não elevar acesso nem desativar proteções e registros unilateralmente |
| Publicação | Consultar versões/artefatos de sites e aplicar publicações seguras homologadas | Não publicar alteração crítica sem autorização nem misturar ambientes/clientes |
| Supervisor Técnico | Correlacionar incidentes, supervisionar execuções, sugerir melhorias e solicitar escalonamento | Não possui acesso universal automático nem pode autorizar suas próprias operações críticas |

**Esta matriz descreve funções a especificar, não concede hoje credenciais, ferramentas nem acesso de produção.** A lista exata de operações autorizadas por especialidade será definida antes da implementação.

## Catálogo, configuração e ciclo de vida das ferramentas

Cada ferramenta deverá registrar, conforme aplicável:
- identificador estável, categoria, proprietário, provedor e endpoint técnico ou referência interna;
- versão, compatibilidade, contrato de entrada/saída, operações e efeitos externos potenciais;
- identidade técnica responsável, escopos, segregação de ambientes/tenants, serviços e recursos de destino;
- nível de risco por operação (não somente por ferramenta), pré-condições e exigência de playbook;
- cotas, limites de taxa, concorrência, repetição e custo;
- homologação vigente, testes, data de publicação, estado ativo/suspenso/revogado;
- mecanismos de logs/auditoria, reversão quando possível, retenção e dependências;
- **referência segura a credenciais da IN-151**, sem armazenar valores secretos no catálogo.

**Mudanças de ferramenta/versão:** detectar alteração, verificar impacto e compatibilidade com instruções da IN-165 e playbooks da IN-155, testar, homologar conforme risco e ativar controladamente. Uma versão nova que inclua operações adicionais não herda autorização irrestrita da anterior.

## Fluxo de uso de ferramenta aprovado

1. Agente identifica a necessidade, contexto, tarefa/incidente e efeito pretendido.
2. Catálogo verifica ferramenta ativa, versão e contrato técnico.
3. Autorizador independente confirma identidade do agente, usuário/empresa/serviço/alvo e ambiente.
4. Checar se **a operação específica** é permitida, com classificação de risco, pré-condições e playbook homologado.
5. Se necessário, obter **uma aprovação humana específica**, válida para o escopo daquela intervenção. A ausência de aprovação impede ação crítica.
6. Aplicar limites de tempo, tentativas, taxa, concorrência e custo; priorizar idempotência e confirmação do estado antes de repetir.
7. Executar com identidade técnica de menor privilégio, minimizando dados e mantendo controles de segurança.
8. Validar resultado técnico independente da alegação do agente e registrar trilha de auditoria com justificativa e eventuais exceções.
9. Se houver falha, revogação ou comportamento inesperado, bloquear repetição perigosa e acionar recuperação homologada ou Central de Incidentes.

## Segurança e prevenção de contornos

- **Injeção em texto externo:** conteúdo de logs, formulários, tickets, respostas de API e documentos nunca é instrução válida para habilitar uma ferramenta ou aumentar privilégio.
- **Cofre e segredos:** credenciais protegidas e acessadas conforme IN-151; não expor tokens em prompts, históricos ou relatórios e não embutir segredo administrativo em site público.
- **Agente substituto:** redistribuir tarefas conforme IN-156 somente a agente que **já** tenha permissões adequadas, sem elevar acesso por transferência de tarefa.
- **Contingência de modelos:** alternar modelo por IN-157 não altera identidades, escopos, ferramentas ou autorizações vigentes.
- **Suspensão de ferramenta:** bloquear novas ações potencialmente perigosas após comprometimento, violação de contrato ou incompatibilidade de versão, com proteção a execuções parcialmente concluídas.
- **Auditoria e retenção:** vincular logs de execução à política de categoria apropriada (IN-160/IN-162), preservando obrigações de auditoria e LGPD; não fixar prazo novo que contradiga políticas anteriores.
- **Serviços críticos:** nenhuma ferramenta deve tornar a central administrativa ponto único de falha para recepção de leads, confirmação durável, filas ou alertas independentes.
- **Prazos de leads:** ações dos agentes não podem reiniciar a política de 3+15 dias, nem impedir exclusão controlada após 18 dias de payload não entregue autorizada na IN-147, ou apagar dados já entregues ao CRM.
- **Acesso de auditoria:** consultar trilhas e relatórios é diferente de possuir autorização de execução; restringir exportações, diagnósticos sensíveis e ações por perfil.

## Auditoria e indicadores

- Ferramentas ativas, suspensas, revogadas e com versões pendentes de homologação.
- Chamadas autorizadas, negadas e concluídas por agente, ferramenta, operação, serviço/ambiente e período.
- Tentativas de ação sem autorização, permissões excessivas detectadas e credenciais revogadas.
- Quantidade de operações executadas por playbook homologado versus intervenção crítica aprovada.
- Falhas de compatibilidade, regressões, efeitos adversos, duplicidades evitadas e tempo de recuperação.
- Custo e consumo por ferramenta/operação dentro dos limites aprovados.

## Pendências antes da implementação

1. Inventário concreto de ferramentas, versões, contratos e operações por especialidade.
2. Modelo de identidade técnica, credenciais, perfis e escopos por agente, ambiente e cliente.
3. Matriz detalhada de operações e níveis de risco com verificações independentes do modelo.
4. Arquitetura do autorizador, proteção contra alteração de política pelos agentes, revogação efetiva e auditoria.
5. Procedimentos de homologação e publicação de versões de ferramentas, testes e integração com instruções IN-165 e playbooks IN-155.
6. Controles de idempotência, concorrência, confirmação de resultado e bloqueio de repetição perigosa.
7. Política de acesso excepcional, revogação de credenciais comprometidas e gestão de incidentes de segurança.
8. Retenção de auditorias, custos, interoperabilidade entre provedores e segregação multiempresa.
9. Testes de tentativas de contorno de autorização, prompt injection, credenciais vazadas e indisponibilidade dos serviços de execução.

**Aprovado:** catálogo de ferramentas, permissões por operação e ambiente, verificação de autorização independente do modelo antes de cada execução, gestão de versões, revogação de acessos e auditoria detalhada.  
**Preservado:** execução automática somente de procedimentos seguros homologados; **uma única aprovação humana por intervenção crítica quando cabível**; sem ampliação automática de permissões ou custos.  
**Pendente:** matriz concreta de ferramentas/ações, arquitetura técnica, limiares, segurança e testes.  
**Programação:** não iniciada.
