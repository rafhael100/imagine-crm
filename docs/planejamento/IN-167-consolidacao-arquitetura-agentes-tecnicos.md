# IN-167 — Consolidação da Arquitetura dos Agentes Técnicos Imagine

**Status:** APROVADA a consolidação documental em 02/11/2026 ("ok").  
**Fase:** somente planejamento e documentação. **Não iniciar programação, implantação, acesso a produção ou despesas.**  
**Escopo:** os sete agentes técnicos especializados, o Supervisor Técnico e os sistemas que administram: Sites Imagine, CRM Imagine, Portal Imagine, APIs de formulários, filas, n8n e infraestrutura.

## 1. Propósito
Reunir decisões aprovadas de IN-130 a IN-166, suas relações, princípios de segurança e pontos pendentes. Esta IN **não aprova novos fornecedores, ferramentas, permissões, custos, métricas numéricas ou cronograma de programação**. Em caso de referências históricas divergentes, prevalece a decisão posterior expressamente aprovada.

## 2. Catálogo de agentes (IN-132)
| Agente | Responsabilidade central |
|---|---|
| Integrações | Formulários, APIs, filas, CRM, confirmações, reconciliação, recuperação idempotente. |
| Infraestrutura | Servidores, capacidade, falhas, disponibilidade, recuperação. |
| Domínios | DNS, SSL, certificados e falhas relacionadas. |
| Automações | n8n, workflows, integrações, recuperação homologada. |
| Backup | Integridade das cópias, testes e restauração autorizada. |
| Segurança | Vulnerabilidades, anomalias, credenciais e contenção autorizada. |
| Publicação | Versões, publicação de sites e reversão controlada. |
| Supervisor Técnico | Coordenação dos especialistas, avaliação, incidentes, aprendizado e escalonamento. |

São **oito papéis lógicos**, não oito contratos ou servidores. Supervisor **sem superprivilégios** e monitorado por verificações independentes (IN-156).

## 3. Arquitetura lógica-alvo e fronteiras
**Operação de produção** (sites, APIs independentes, CRM, filas, n8n, infraestrutura) → **monitoramento independente** (IN-159/160/161) → **Central de Incidentes** (IN-130/133/134/135/136) → **orquestração e especialistas** (IN-132/156/157) → **consulta ao inventário e conhecimento** (IN-153/139/163/164) → **verificação de riscos e autorização independente da IA** (IN-137/138/155/166) → **execução limitada de ferramentas** → **verificação real do resultado, auditoria, aprendizado e revisão** (IN-139/140/154).

Camadas auxiliares: **cofre de credenciais** (IN-151), **modelos homologados/fallback** (IN-157), **controle de custo** (IN-142/158), **versionamento de instruções** (IN-165), **playbooks versionados/homologados** (IN-155).

A Central de Observabilidade e o Portal Imagine são interfaces/serviços de gestão; **não podem ser o único caminho de recebimento de leads ou de alertas críticos**. Inventário de arquitetura planejada **não equivale a infraestrutura instalada**, conforme IN-153. A topologia física e os contratos de componentes ainda são pendentes.

## 4. Autonomia e aprovação (IN-131/137/138/155/166)
| Nível | Regra consolidada |
|---|---|
| 1 — monitoramento | Diagnosticar, sugerir, registrar e notificar, dentro de permissões de leitura. |
| 2 — autocorreção | Aplicar automaticamente ações **seguras de baixo risco ou moderado previamente homologado**, verificando escopo e condições; **sem nova aprovação por execução**. |
| 3 — intervenção crítica | Ação de alto risco/crítica exige **uma única aprovação humana específica**, quando aplicável. **Nunca dupla aprovação.** |

A prioridade do **incidente** não equivale ao risco da **ação**. Homologar playbook não autoriza uso de ferramenta fora de suas permissões. Alterar prompt (IN-165), mudar modelo (IN-157) ou transferir tarefa (IN-156) **não concede privilégios**. Autorização de ferramenta/ação/alvo/ambiente/cliente deve ser **determinística e independente do texto produzido pela IA** (IN-166). Segredos não são incluídos em prompts/logs. Nenhum agente contrata serviços nem aumenta gastos sem autorização financeira específica (IN-142/158).

## 5. Incidentes, alertas e continuidade
Fluxo: **detecção comprovada → registro/correlação → prioridade → aviso/escalonamento → diagnóstico → playbook autorizado ou aprovação humana única para ação crítica → execução → validação independente → aprendizado**.

**Metas internas de assunção (IN-135):** crítica **5 minutos**, alta **30 minutos**, média **4 horas úteis**, baixa **1 dia útil**. Não são metas de resolução nem SLA contratual. Monitoramento automatizado **24/7 não significa equipe humana 24/7** (IN-136). Em crítico, notificar especialista e responsável humano **imediatamente em paralelo**. IN-134 prevê crítico painel/e-mail/WhatsApp quando habilitado; alto painel/e-mail; médio painel/notificações; baixo painel/relatório.

Continuidade IN-143/144/145/146: failover/failback seguro e homologado, conservação de evidências, backup, reconciliação e testes de recuperação controlados. **RTO/RPO numéricos ainda pendentes.** Os backups de sites e CRM são independentes. Não presumir perda zero nem retorno automático sem verificação.

**Limite explícito:** a proposta posterior de módulo **adicional** de testes sintéticos periódicos dos serviços/formulários foi recusada pelo responsável. Isso não revoga os testes de continuidade/recuperação da IN-145. A identificação IN-162 passou a designar a política de retenção de logs, já aprovada.

## 6. Integridade de leads (IN-124/127/128/147/148/149)
- Sites Imagine **sem banco de leads local**; API principal e contingência com persistência durável independente. Confirmar ao visitante **somente após persistir na API**.
- **Recebimento na API não prova entrega ao CRM**. Confirmar entrega efetiva com identidade idempotente e evidência do CRM, inclusive em failover/reprocessamento.
- Lead entregue: limpar **payload temporário 24 horas após confirmação**, sem apagar lead do CRM.
- Lead não entregue: **3 dias de fila normal + 15 dias de falhas = 18 dias totais**, contados desde primeira persistência; tentativas não reiniciam o relógio.
- **Aviso 72 horas antes do vencimento**. Ao completar 18 dias: alerta crítico, parar reenvios, validar estado e **permitir exclusão automática do payload não entregue**, auditada e sujeita a exceções legais/de integridade. **Sem quarentena adicional de 30 dias** (IN-147).
- Auditoria de filas: **90 dias**, minimizada e sem conteúdo completo de formulários. Expirado não conta como entregue. Falha simultânea das duas APIs não permite garantir perda zero.

## 7. Observabilidade, modelos e custos
Uma **única Central de Observabilidade** IN-159 reúne logs/métricas/traces IN-160, qualidade e ausência de dados IN-161, saúde dos agentes IN-156, incidentes e custos IN-158. **Telemetria antiga/ausente = estado desconhecido**, não saudável. Monitor e alertas críticos independentes do painel e da disponibilidade de modelos de IA.

Modelos alternativos somente se **homologados**, compatíveis com a tarefa, segurança, permissões e orçamento (IN-157). Troca de modelo nunca autoriza duplicar efeitos externos. Painel, limite mensal configurável, alertas e bloqueios financeiros aprovados na IN-158, mas **valores monetários ainda indefinidos**. Despesas internas dos agentes técnicos **separadas** dos créditos/franquias de IA de clientes do CRM.

## 8. Conhecimento, versões e retenção
| Artefato | Ciclo aprovado |
|---|---|
| Conhecimento/solução | Registrar fatos, hipóteses, resultados comprovados, revisar/curar IN-139/164. |
| Playbook | Propor, testar, homologar **versão e escopo**, reutilizar automaticamente quando seguro, suspender/revisar IN-155. |
| Instrução/prompt | Versionar, testar, homologar mudanças relevantes, publicar e reverter IN-165. |
| Ferramenta/permissão | Catalogar por operação e versão; autorizar independentemente, auditar e revogar IN-166. |

**Retenções aprovadas:** debug **7 dias**; logs operacionais **30 dias**; rastros **30 dias**; métricas **90 dias**; auditoria de filas de leads **90 dias**; incidentes críticos/eventos de segurança **180 dias** (IN-162). Conhecimento e playbooks **ativos enquanto necessários**, versões **arquivadas 24 meses após arquivamento** (IN-163). Rascunhos **sem movimentação relevante por 90 dias entram em revisão**, não descarte automático (IN-164). **Prazo de retenção das versões de instruções/prompts ainda não aprovado** (IN-165). Nunca conservar senhas, tokens e dados pessoais desnecessários como suposto aprendizado.

## 9. Conciliação de documentos e ambiguidades
1. IN-127/128 mencionavam descarte final pendente: **IN-147 aprovou exclusão automática dos leads não entregues após 18 dias**, com auditoria, verificação e avisos.
2. IN-160 mencionava retenção geral pendente: **IN-162 definiu prazos por categoria**; detalhes técnicos ainda pendentes.
3. IN-139/155 deixavam retenção de biblioteca em aberto: **IN-163 definiu 24 meses após arquivamento**; IN-164 definiu **revisão**, não exclusão, aos 90 dias de inatividade de rascunhos.
4. As versões de **prompts** IN-165, os **playbooks** IN-155 e as **permissões de ferramentas** IN-166 não são a mesma coisa nem herdam autorização automaticamente.
5. IN-135 define **assunção**, não solução; IN-136 não garante plantão humano 24/7.
6. IN-144 ainda depende da escolha de RTO/RPO e da validação de custos/capacidade.
7. IN-145 permite testes controlados de recuperação, mas a proposta de novo módulo de monitoramento por testes sintéticos foi dispensada.

## 10. Pendências concretas antes de iniciar Codex
**Arquitetura e infra:** confirmar ativos realmente instalados, topologia física, independência de serviços críticos, contratos de APIs/eventos, componentes de orquestração, persistência, filas e autorizador.

**Governança:** matriz exata de ferramentas/permissões por agente/alvo/ambiente, políticas de risco, segredos, trilha auditável, acesso multiempresa e recuperação de credenciais.

**Agentes:** catálogo inicial de playbooks, testes e homologação, modelos/fallback elegíveis, versões de prompts, critérios de suspensão, limites de tentativas e verificação independente do sucesso.

**Continuidade:** valores RTO/RPO, políticas de escalonamento e plantão humano real, provedor/custos do WhatsApp, procedimentos de recuperação e testes já previstos.

**Dados:** retenção de versões de instruções, destino dos rascunhos revisados que não foram arquivados, exceções legais, backup, idempotência e descarte seguro dos payloads de leads.

**Financeiro:** orçamentos de IA, cotas reais, custos de servidores e observabilidade, sem despesas autônomas.

**Encerramento:** revisar a documentação, resolver pendências técnicas relevantes e obter **aprovação final expressa** antes de qualquer programação. **Não iniciar Codex agora.**

## 11. Principais documentos de referência
- Incidentes e agentes: [IN-130](IN-130-central-incidentes-tecnicos-agentes-ia.md), [IN-131](IN-131-niveis-autonomia-agentes-tecnicos.md), [IN-132](IN-132-catalogo-agentes-tecnicos-especializados.md), [IN-133](IN-133-priorizacao-escalonamento-inteligente-incidentes.md), [IN-134](IN-134-comunicacao-alertas-inteligentes-incidentes.md), [IN-135](IN-135-sla-resposta-escalonamento-incidentes.md), [IN-136](IN-136-plantao-tecnico-substituicao-automatica.md).
- Governança e aprendizagem: [IN-137](IN-137-central-aprovacoes-tecnicas.md), [IN-138](IN-138-matriz-riscos-aprovacoes-agentes.md), [IN-139](IN-139-biblioteca-inteligente-autocorrecoes-aprendizado-continuo.md), [IN-140](IN-140-avaliacao-continua-desempenho-agentes.md), [IN-141](IN-141-prevencao-inteligente-incidentes.md), [IN-142](IN-142-gestao-inteligente-capacidade-escalabilidade.md).
- Continuidade/leads: [IN-143](IN-143-continuidade-operacional-recuperacao-inteligente-desastres.md), [IN-144](IN-144-matriz-criticidade-metas-recuperacao.md), [IN-145](IN-145-testes-inteligentes-continuidade-recuperacao.md), [IN-146](IN-146-orquestracao-inteligente-contingencia-retorno-operacao.md), [IN-147](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md), [IN-148](IN-148-reconciliacao-inteligente-leads.md), [IN-149](IN-149-protocolo-entrega-confirmacao-leads.md).
- Operação e segurança: [IN-150](IN-150-seguranca-inteligente-formularios-apis.md), [IN-151](IN-151-gestao-inteligente-credenciais-chaves-integracao.md), [IN-152](IN-152-gestao-inteligente-vulnerabilidades-atualizacoes-seguranca.md), [IN-153](IN-153-inventario-inteligente-infraestrutura-dependencias.md), [IN-154](IN-154-gestao-inteligente-mudancas-tecnicas.md), [IN-155](IN-155-homologacao-inteligente-procedimentos-agentes.md), [IN-156](IN-156-autodiagnostico-recuperacao-agentes-tecnicos.md), [IN-157](IN-157-gestao-inteligente-modelos-ia-contingencia.md), [IN-158](IN-158-monitoramento-inteligente-custos-consumo-agentes.md), [IN-159](IN-159-central-inteligente-observabilidade-saude-operacional.md), [IN-160](IN-160-gestao-inteligente-logs-metricas-rastreamento-distribuido.md), [IN-161](IN-161-controle-inteligente-qualidade-dados-monitoramento.md), [IN-162](IN-162-politica-inteligente-retencao-armazenamento-logs.md), [IN-163](IN-163-ciclo-vida-conhecimento-agentes.md), [IN-164](IN-164-curadoria-inteligente-conhecimento-agentes.md), [IN-165](IN-165-versionamento-homologacao-instrucoes-agentes.md), [IN-166](IN-166-gestao-inteligente-ferramentas-permissoes-agentes.md).

**Aprovado na IN-167:** consolidar o planejamento, organizar relações e pendências, sem novas decisões operacionais.  
**Programação:** não iniciada.


## Atualização posterior — Portal e CRM em servidores separados (02/11/2026)

O responsável **aprovou servidores distintos para o Portal Imagine e o CRM Imagine SaaS**. A integração ocorrerá por APIs autenticadas, mantendo isolamento de dados e continuidade operacional independente. Essa decisão é arquitetural, sem aprovar máquinas, fornecedores, custos ou implantação. A disposição completa de referência e pendências constam do [comparativo ARQ-01](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md) e da [matriz IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md). A **hospedagem dos agentes técnicos e do orquestrador em VPS exclusiva, separada do n8n, foi aprovada expressamente em 02/11/2026 (Opção B)**; dimensionamento e contratação continuam pendentes. Programação não iniciada.


## Correção de infraestrutura de sites — IN-096 (02/11/2026)

A hospedagem dos sites dos clientes **Onepage/Landing Pages** já havia sido aprovada na **IN-096 de 01/11/2026**: **Cloudflare Workers Static Assets** com **GitHub** para código/versões e publicação, manutenção e monitoramento por APIs/n8n/agentes autorizados; **WHM/cPanel foi descartado para esse modelo de sites**. **Workers for Platforms** permanece uma possibilidade futura de escala, dependente de dimensionamento e aprovação. O Portal Imagine e o CRM Imagine SaaS terão **servidores independentes**; os **agentes e orquestrador terão VPS exclusiva distinta do n8n conforme Opção B do ARQ-01, aprovada em 02/11/2026**; dimensionamento e contratação continuam pendentes. As duas APIs de recepção de leads continuam em infraestruturas e persistências independentes dos sites, Portal e agentes. O mapa de referência atualizado está no [ARQ-01](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md); nenhuma contratação/programação está autorizada.


## Atualização de infraestrutura — Opção B de ARQ-01 (02/11/2026)

**Aprovada no planejamento:** VPS/servidor exclusivo para os **sete agentes técnicos, Supervisor Técnico e orquestrador**, **fisicamente/virtualmente separado do servidor do n8n**, sem compartilhamento do mesmo host VPS. Mantidas as decisões: **Portal Imagine e CRM Imagine SaaS em servidores separados**, **sites Onepage/Landing Pages via Cloudflare Workers Static Assets conforme IN-096**, e **API principal e de contingência de leads com persistência durável independente**. A fronteira de infraestrutura dos agentes está escolhida; configurações, provedor, recursos, custos, mecanismos de comunicação e continuidade permanecem em estudo. Ver [ARQ-01](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md) e [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md). **Não contratar, provisionar nem iniciar programação**.
