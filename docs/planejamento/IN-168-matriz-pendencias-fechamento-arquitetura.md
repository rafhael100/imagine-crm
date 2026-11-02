# IN-168 — Matriz de Pendências e Fechamento da Arquitetura dos Agentes Técnicos

**Status:** APROVADO o trabalho de organização e fechamento documental em 02/11/2026 ("sim").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, implantação, alterações de produção, contratação ou despesas**.  
**Origem:** [IN-167 — Consolidação da Arquitetura dos Agentes Técnicos](IN-167-consolidacao-arquitetura-agentes-tecnicos.md), com conferência das IN-144, IN-153, IN-158, IN-163 e IN-165.  
**Escopo:** sete agentes técnicos e Supervisor, Sites Imagine, CRM Imagine, Portal Imagine, APIs/formulários, filas, n8n, observabilidade, segurança e recuperação. **Não substitui o planejamento comercial completo do CRM e dos Sites Imagine.**

## 1. Objetivo, limites e regra de aprovação

Esta matriz transforma as lacunas da IN-167 em **itens rastreáveis com prioridade, origem, dependências, entrega esperada e decisão pendente**. Ela **não aprova as respostas aos itens**, não altera valores ou regras existentes e não autoriza desenvolvimento. Uma pendência só será marcada como **decidida** após decisão expressa do responsável; especificação técnica posterior e testes ainda podem permanecer pendentes.

**Estados a usar:** `aprovado` (regra já decidida); `pendente de decisão` (requer escolha do responsável); `pendente de especificação` (precisa projeto técnico sem alterar política); `dependente de orçamento`; `validar em homologação`; `dispensado` (proposta rejeitada).  
**Prioridade proposta:** **P0** = dependência estrutural para desenho seguro; **P1** = operação, resiliência e dimensionamento; **P2** = acabamento, governança e preparação para revisão final. Prioridade não equivale a autorização para executar.

## 2. Decisões já aprovadas — não reabrir sem solicitação

- **8 papéis**: 7 agentes técnicos especializados e 1 Supervisor (IN-132).
- **Autonomia**: N1 monitoramento; N2 execução de playbooks seguros homologados e escopados; N3 intervenção crítica com **uma única aprovação humana específica quando exigida**; **sem dupla aprovação** (IN-131/137/138/155/166).
- **Permissões técnicas** independentes do modelo e do prompt, menor privilégio por operação, ambiente, agente e cliente; sem autoconcessão ou contratação de serviços (IN-151/165/166).
- **Segurança de leads**: sites sem banco de leads; API principal e contingência com persistência durável independente; confirmação de entrega **após comprovação no CRM** e idempotência (IN-124/148/149).
- **Retenção de leads**: fila normal 3 dias + falhas 15 dias; cópia de lead entregue limpa após 24h; não entregue pode ser excluído **após 18 dias** com verificação, avisos e auditoria; **sem quarentena adicional de 30 dias**; auditoria técnica minimizada 90 dias (IN-127/128/147).
- **Observabilidade**: uma Central de Observabilidade e monitoramento/alertas independentes da UI e dos modelos; não inferir saúde com telemetria ausente (IN-156/159/160/161).
- **Retenção de logs**: debug 7d; operacionais 30d; traces 30d; métricas 90d; auditoria técnica de filas 90d; incidentes críticos e eventos de segurança 180d (IN-162).
- **Conhecimento**: manter ativo enquanto necessário; arquivado por 24 meses após arquivamento; rascunho sem movimentação 90 dias exige **revisão, não exclusão** (IN-163/164).
- **Monitoramento automatizado 24/7 não implica plantão humano permanente**; metas internas de **assunção** (não resolução) são 5 min crítico, 30 min alto, 4h úteis médio e 1 dia útil baixo (IN-135/136).
- **Testes de continuidade e recuperação controlados** aprovados (IN-145); **não criar módulo adicional de testes sintéticos periódicos de serviços/formulários**, que foi dispensado.

## 3. Matriz priorizada de pendências

| ID | Prioridade | Pendência / decisão ou especificação necessária | Dependências e referência | Estado |
|---|---|---|---|---|
| **ARQ-01** | **P0** | **Aprovados:** Portal e CRM em servidores separados; agentes técnicos + orquestrador em VPS exclusiva, separada do n8n (Opção B); sites Onepage/Landing Pages em Cloudflare Workers Static Assets (IN-096); duas APIs de leads independentes. **Falta:** topologia executável detalhada, inventário real, capacidade, provedor, custos e contingência. | IN-096/124/143/146/153/167, comparativo ARQ-01 | **Diretrizes de separação aprovadas; especificação e orçamento pendentes** |
| **ARQ-02** | **P0** | Definir contratos e fluxos entre API principal/contingência, filas, CRM e agentes: identidade idempotente, estados, confirmação durável, concorrência, reconciliação e descarte seguro após 18 dias. | IN-124, IN-127/128, IN-147/149 | **Pendente de especificação** |
| **SEG-01** | **P0** | Matriz concreta de ferramentas por agente, ação, serviço, cliente, ambiente e risco; projeto do autorizador independente, revogação e auditoria. | IN-137/138/151/155/166 | **Pendente de decisão e especificação** |
| **SEG-02** | **P0** | Selecionar arquitetura de cofre de segredos, identidades de serviço e rotação/recuperação, preservando segregação de produção e homologação. | IN-150/151/153, SEG-01 | **Pendente de escolha, custos e especificação** |
| **ARQ-03** | **P0** | Determinar componentes responsáveis por monitoramento/alertas independentes da IA, da Central e do próprio Supervisor; prever falhas comuns. | IN-130/134/143/156/159/161, ARQ-01 | **Pendente de especificação** |
| **AGT-01** | **P0** | Definir catálogo mínimo inicial de playbooks homologáveis, critérios objetivos de risco, testes, limites de execução e política de suspensão/reversão. | IN-131/138/139/154/155/166, SEG-01 | **Pendente de decisão e especificação** |
| **CON-01** | **P1** | Dimensionar e **aprovar valores numéricos de RTO/RPO por serviço** em função de arquitetura real, backups, custos e prioridades; não prometer zero perda. | IN-143/144/145/146, ARQ-01/02 | **Pendente de decisão e orçamento** |
| **OPS-01** | **P1** | Definir cobertura humana real, responsáveis e substitutos, janelas, canais, provedor/custo de WhatsApp e contingência de alertas; preservar metas já aprovadas de assunção. | IN-133/136, ARQ-03 | **Pendente de decisão e custos** |
| **FIN-01** | **P1** | Fixar orçamento mensal interno de IA, cotas, alertas, limites por agente/modelo, reserva se aprovada, critérios de bloqueio e custos de provedores; **sem misturar créditos de clientes**. | IN-142/157/158, ARQ-01 | **Pendente de decisão financeira** |
| **IA-01** | **P1** | **Aprovada:** classificação de modelos em três classes (econômica / intermediária / avançada) para roteamento por tarefa. **Pendente:** escolha e homologação de modelos/fornecedores elegíveis, ordem de fallback por tarefa, custos, métricas de qualidade, privacidade e compatibilidade de ferramentas. | IN-156/157/158/165/166, FIN-01, [IA-01 aprovada](IA-01-matriz-classes-modelos-por-tarefa-agentes-tecnicos.md) | **Classes aprovadas; modelos, provedores, limites e testes pendentes** |
| **OBS-01** | **P1** | Definir tecnologias e contratos de logs/métricas/traces, estados de saúde, limites de alerta e custos de coleta/armazenamento, sem duplicar a Central de Observabilidade. | IN-159/160/161/162, ARQ-03 | **Pendente de especificação e custo** |
| **DAD-01** | **P1** | Especificar mecanismo de retenção/expurgo de logs, leads e réplicas/backups, exceções legais, evidências mínimas e segregação multiempresa. **Prazos já definidos não estão em votação nesta IN.** | IN-127/128/147/150/160/162, ARQ-02 | **Pendente de especificação e conformidade** |
| **AGT-02** | **P1** | Definir manifestos/versionamento de instruções e políticas de publicação/rollback; **aprovar prazo próprio de retenção das versões arquivadas de prompts**, que permanece pendente. | IN-155/163/165/166, AGT-01 | **Pendente de decisão e especificação** |
| **DAD-02** | **P2** | Definir tratamento final de **rascunhos revisados e não arquivados**, sem transformar a revisão aos 90 dias em eliminação automática. | IN-139/163/164 | **Pendente de decisão** |
| **OPS-02** | **P2** | Detalhar relatórios, indicadores e testes de desempenho e recuperação já aprovados; homologação e revisão periódica, sem novo módulo de teste sintético. | IN-140/145/159/161, CON-01, OBS-01 | **Pendente de especificação** |
| **DOC-01** | **P2** | Auditar documentação cruzada e atualização dos trechos históricos contraditórios; consolidar decisões comerciais e operacionais de Sites/CRM em suas áreas próprias. | IN-167 e INs predecessoras | **Pendente de revisão documental final** |
| **DOC-02** | **P2** | Preparar pacote de arquitetura, matriz de decisões, custos, riscos, critérios de aceite e obter **aprovação final expressa do planejamento** antes de programar. | Todos os itens relevantes | **Pendente; bloqueia início de Codex** |

**Observações:** a ordem e os responsáveis concretos ainda podem ser ajustados. Os códigos ARQ/SEG/AGT/CON/OPS/FIN/IA/OBS/DAD/DOC são **identificadores internos de pendências**, não novas INs aprovadas nem implementação de módulos adicionais.

## 4. Ordem recomendada de fechamento

**Bloco A — Fundamentos críticos (P0):** fechar **ARQ-01**, depois ARQ-02, SEG-01, SEG-02, ARQ-03 e AGT-01. Isso permite dimensionar risco, dados, pontos únicos de falha e capacidade sem escolher serviços pagos às cegas.

**Bloco B — Operação e investimento (P1):** com a topologia clara, definir CON-01 (RTO/RPO), OPS-01 (cobertura/alertas), FIN-01 e IA-01 (orçamento/modelos), OBS-01 (telemetria), DAD-01 (retenção executável) e AGT-02 (instruções).

**Bloco C — Acabamento e validação (P2):** DAD-02, OPS-02, DOC-01 e DOC-02. Não iniciar desenvolvimento antes de o responsável concluir e aprovar o pacote final.

**Agrupamento sugerido:** discutir pendências interdependentes em blocos claros, mantendo **uma decisão explícita por tema quando a escolha alterar custo, risco, política ou arquitetura**. Não reabrir automaticamente funções já aprovadas nem multiplicar etapas sem valor.

## 5. Critérios de encerramento por pendência

Uma pendência só poderá ficar **"concluída"** quando tiver:
1. **Decisão aprovada** (quando houver alternativa ou valor a escolher), com data e documento de referência;
2. **Especificação técnica suficiente** para evitar ambiguidades importantes, mantendo o estado como planejado até programação;
3. **Impactos de segurança, dados, contingência e custos** descritos e compatíveis com decisões anteriores;
4. **Dependências e testes de homologação** identificados sem afirmar que já foram executados;
5. **Rastreabilidade no GitHub**, sem contradizer outros documentos; decisão revogada identificada como tal.

Status **"aprovado funcionalmente"** não significa desenvolvido, testado ou instalado. Status **"pendente de validação futura"** pode ser inevitável para testes de implantação, mas isso não autoriza começar programação antes de finalizar o planejamento aprovado.

## 6. Primeira pauta de decisão sugerida: ARQ-01

Antes de selecionar tecnologia adicional, validar o **mapa de ambientes reais e planejados**. Conforme a **IN-096 aprovada**, os sites Onepage/Landing Pages Imagine serão hospedados em **Cloudflare Workers Static Assets**, com GitHub e publicação automatizada por APIs/n8n/agentes sob controles; **não WHM/cPanel**. O Portal e o CRM SaaS terão servidores independentes; n8n tem VPS prevista; as duas APIs principal e contingência de leads têm persistência durável independente; a hospedagem separada dos agentes continua recomendada e pendente de aprovação final. **O planejamento não comprova o que já está instalado.**

Na sequência de planejamento, elaborar opções para **localização e isolamento lógico dos agentes técnicos, orquestrador, serviços de monitoramento/autorização e seus dados**, com requisitos de independência das APIs e avaliação de despesas. **Nenhuma opção concreta foi aprovada nesta IN.**

## 7. Limites explícitos e próximo marco

- **Não iniciar programação**, infraestrutura, testes reais, cadastros de credenciais, compra de servidores ou deploys.
- **Não reintroduzir** a quarentena adicional de 30 dias de leads, a dupla aprovação humana ou o módulo adicional recusado de testes sintéticos periódicos.
- **Não inventar** orçamentos, RTO/RPO, modelos escolhidos, custos de WhatsApp ou retenção de versões de prompts.
- Esta IN aprova **o método e a matriz de organização das pendências**, **não** suas futuras respostas.
- **Próximo assunto recomendado:** iniciar o **Bloco A / ARQ-01**, confrontando arquitetura planejada e recursos efetivamente existentes, seguido de proposta de alternativas para revisão do responsável.

**Status final:** IN-168 — matriz de pendências e fluxo de fechamento **aprovados/documentados**. Decisões técnicas e financeiras listadas **continuam pendentes**; programação **não iniciada**.


## Complemento de análise — ARQ-01 (02/11/2026)

Foi preparada a [comparação de infraestrutura dos agentes técnicos — ARQ-01](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md), com as alternativas **A (compartilhar a VPS n8n/PostgreSQL com isolamento lógico)** e **B (infraestrutura própria para agentes/orquestrador)**. A análise técnica **recomenda B** por isolamento, independência de manutenção e menor correlação de falhas, porém **o responsável ainda não escolheu a arquitetura**, nem aprovou nova VPS, orçamento, provedor, contratação ou implantação. **ARQ-01 continua P0 e PENDENTE DE DECISÃO** até aprovação expressa, confirmação da infraestrutura realmente existente e dimensionamento/custos. Permanecem preservadas duas APIs de leads com persistência independente e a proibição de iniciar programação antes do fechamento do planejamento.


## Decisão parcial de ARQ-01 — Portal Imagine separado do CRM (02/11/2026)

**Aprovado:** Portal Imagine e CRM Imagine SaaS terão **servidores próprios e separados** entre si. É diretriz de arquitetura, não autorização de contratação. Integração por APIs seguras e bancos isolados; falha do Portal não pode impedir funcionamento do CRM nem recepção de leads pelas APIs independentes.

**ARQ-01 continua parcialmente pendente:** infraestrutura dos agentes (Opção B recomendada, ainda não escolhida expressamente), provedores, capacidade, orçamento e confirmação dos recursos instalados. O comparativo e a topologia de sete ambientes de referência estão documentados em [ARQ-01](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md). **Sem programação.**


## Correção da hospedagem de sites — IN-096 (02/11/2026)

O usuário reafirmou a decisão previamente aprovada em **01/11/2026 (IN-096)**: **sites dos clientes Onepage/Landing Pages em Cloudflare Workers Static Assets**, código/versões via GitHub e operação/publicação por APIs, n8n e agentes autorizados. **WHM/cPanel não integra a hospedagem padrão desses sites**. Workers for Platforms permanece **possibilidade futura**, sem implantação autorizada. Portal e CRM em servidores distintos permanecem aprovados. A infraestrutura independente dos agentes (**Opção B**) foi **aprovada expressamente em decisão posterior de 02/11/2026**, com dimensionamento e contratação ainda pendentes. O quadro de sete blocos descreve **serviços**, não sete máquinas novas. Atualizar a estimativa de custos e a comparação ARQ-01 à luz desse modelo; **nenhuma programação ou contratação**.


## Aprovação da hospedagem dos agentes — Opção B (02/11/2026)

**Decisão aprovada:** os **sete agentes técnicos especializados, o Supervisor Técnico e o orquestrador** ficarão em **VPS/servidor exclusivo para esse subsistema**, **separado da VPS do n8n**. Isso complementa a decisão já aprovada de **Portal Imagine e CRM Imagine SaaS em servidores próprios e distintos**. Os sites Onepage/Landing Pages continuam em **Cloudflare Workers Static Assets** conforme IN-096 e as duas APIs de ingestão de leads continuam independentes conforme IN-124.

A **escolha da separação de hospedagem em ARQ-01 está concluída**. O item **ARQ-01 permanece aberto somente para dimensionamento, topologia executável, custos, fornecedor, validação dos ativos existentes, segurança e decisões decorrentes**, que não estão implicitamente aprovados. A comparação histórica e a decisão atual constam de [ARQ-01](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md). **Não iniciar programação nem aquisição**.


## Estudo preliminar de tamanho e custo da VPS de agentes (02/11/2026)

O responsável autorizou a **comparação de capacidade e custos**, não a compra ou a escolha definitiva do porte. Foi concluído o [estudo de dimensionamento e custos de VPS exclusiva dos agentes](ARQ-01-dimensionamento-vps-agentes-tecnicos.md), com três referências: **2 vCPU/4 GB/80 GB (econômica), 4 vCPU/8 GB/160 GB (recomendada como hipótese-base) e 8 vCPU/16 GB/320 GB (expansão)**. Foram levantados preços de tabela de **DigitalOcean** e **Hetzner Europa**; cotação **Leaseweb ainda pendente**, assim como região, capacidade real, backups e custos das APIs de IA.

**ARQ-01:** isolamento dos agentes na VPS própria **já aprovado**, mas **capacidade definitiva, provedor, preço contratado, custo total e implantação continuam pendentes**. Valores e configurações do estudo **não equivalem a orçamento aprovado ou autorização de programação**.


## Decisão parcial IA-01 — três classes de modelos (02/11/2026)

O responsável **aprovou expressamente** a classificação funcional em **econômica, intermediária e avançada**, conforme [matriz IA-01](IA-01-matriz-classes-modelos-por-tarefa-agentes-tecnicos.md). A seleção inteligente multimodelo e a contingência já estavam previstas e aprovadas na IN-157. **Ainda não foram escolhidos modelos, versões, provedores, ordem de preferência/fallback, quotas, limites financeiros, localização de processamento ou contratos.** A IA-01 permanece P1 para fechar esses pontos antes da programação. Sem contratação ou início de implementação.
