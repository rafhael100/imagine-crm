# IN-142 — Gestão Inteligente de Capacidade e Escalabilidade da Imagine

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Etapa:** planejamento e documentação; **não autoriza iniciar programação, provisionar infraestrutura ou contratar serviços**.  
**Escopo:** infraestrutura Imagine Sites, APIs principais e de contingência para formulários, filas de entrega de leads, integrações com CRM Imagine, serviços hospedados e automações.  
**Referências:** IN-100 (domínios/DNS/SSL), IN-101 (backups), IN-124 a IN-129 (APIs redundantes, filas e recuperação de leads), IN-130 a IN-132 (incidentes e agentes), IN-137/IN-138 (aprovações e risco), IN-139/IN-140 (aprendizado e desempenho), IN-141 (prevenção de incidentes).

## Objetivo

Monitorar consumo e desempenho da infraestrutura Imagine, prever pressões de capacidade antes de causarem indisponibilidade, executar **otimizações seguras e previamente autorizadas** e apresentar recomendações fundamentadas para aumento de recursos, com **aprovação humana prévia para novas despesas**.

## Funcionalidades aprovadas

1. **Monitoramento de servidores e serviços:** coletar indicadores relevantes de CPU, memória, disco, espaço disponível, saúde dos processos, conexões, disponibilidade e desempenho, com distinção por servidor/ambiente/serviço.
2. **Monitoramento de APIs:** verificar disponibilidade, latência, taxas de erro, qualidade de entrega e tempos de resposta das APIs de recepção principal e contingência, sem depender exclusivamente do Portal administrativo.
3. **Capacidade das filas:** acompanhar crescimento das filas de envios de leads, atrasos, vazão de processamento, erros e retenção, respeitando os prazos e alertas aprovados nas IN-127/IN-128.
4. **Previsão de crescimento:** analisar tendências de consumo, volume de sites, submissões e operações para estimar quando a capacidade se tornará insuficiente, expondo premissas, incertezas e evidências.
5. **Otimização automática controlada:** executar somente ajustes seguros, reversíveis quando aplicável, com playbooks homologados, autorização de escopo, limites operacionais e verificação de resultados.
6. **Alertas preventivos:** notificar quando indicadores se aproximarem de condições críticas ou quando o aumento de capacidade for recomendável, sem gerar alertas repetitivos desnecessários.
7. **Planejamento e controle de custos:** exibir recomendação, justificativa, impacto técnico, alternativas e estimativa de custo antes de contratar novos recursos.

## Regra financeira expressa e obrigatória

**Os agentes de IA NÃO podem contratar servidores, realizar upgrades pagos, habilitar serviços tarifados, ampliar limites contratados com custo adicional nem assumir novas despesas sem uma autorização humana expressa e específica.**

A aprovação humana de despesa é **única**, sem dupla aprovação, conforme IN-137/IN-138, e deve apresentar valor estimado, recorrência prevista, fornecedor, serviço e consequências financeiras. Nem todo recurso de computação tem custo fixo: aumentos de consumo medido, autoscaling e alterações de franquia devem ser tratados como possíveis despesas e bloqueados fora do orçamento/limites explicitamente autorizados.

Mesmo dentro de infraestrutura já contratada, ajustes que possam prejudicar disponibilidade, segurança ou dados de clientes não se tornam automaticamente seguros apenas por não gerarem custo.

## Condições para otimização automática

- Executar apenas ações de **baixo ou moderado risco** previamente homologadas e permitidas para aquele agente, serviço e ambiente.
- Verificar pré-condições, estado atual, capacidade disponível, concorrência, limites técnicos e possível impacto em outras empresas/serviços.
- Respeitar cotas e limites de custo já autorizados, sem ampliar unilateralmente limites financeiros.
- Aplicar limites de tentativas, verificação de saúde após a mudança, recuperação/rollback quando possível e auditoria completa.
- Se não existir procedimento confiável ou se o ajuste for crítico, abrir solicitação com análise de impacto e pedir autorização humana única, mantendo o monitoramento preventivo.
- Ações de otimização precisam preservar a confiabilidade e a persistência durável dos envios de leads; não descartar filas ou mensagens para "liberar capacidade".

## Observações de arquitetura e isolamento

- A recepção de formulários utiliza **API principal e API de contingência em servidores separados, preferencialmente provedores distintos**, com persistência independente, conforme IN-124. A otimização de um componente não pode eliminar essa redundância.
- Considerar separadamente capacidades de sites hospedados, CRM SaaS, n8n, banco de dados, APIs de integração e armazenamento/filas; recursos compartilhados devem ter alertas de concorrência e esgotamento.
- O painel do Portal Imagine deve consolidar dados, mas **não constituir ponto único de falha** de monitoramento e recepção de leads.
- Aumentos de carga podem ser previstos, mas a previsão deve ser tratada como estimativa, nunca garantia de que uma falha acontecerá ou será evitada.

## Fluxo funcional proposto e aprovado

Monitoramento contínuo → análise de tendências/limiares → identificação de risco de capacidade → consulta ao histórico técnico → escolha entre:
- **Otimização permitida e segura:** aplicar playbook já homologado → testar saúde, vazão, custo e efeitos → registrar resultado;
- **Necessidade de expansão/novo gasto/ação crítica:** formular recomendação com alternativas e custo estimado → solicitar **uma autorização humana** → executar somente se aprovada e dentro do escopo → verificar resultados;
- **Sem autorização ou sem procedimento seguro:** manter serviço, alertar responsável e registrar recomendação e plano de contingência.

## Exemplos de indicadores a detalhar

CPU/memória/disco; I/O e conexões; tempo de resposta e erros por API; backlog e idade dos itens nas filas; taxa de entrega ao CRM; projeções de saturação; consumo e custo mensal estimado por servidor/serviço; incidência de falhas após ajustes de capacidade.

## Especificações pendentes para aprovação antes de implementar

- Definição concreta da infraestrutura por serviço, provedores, limites contratados e custos, separando servidores de sites, CRM SaaS, n8n e APIs.
- Métricas e limiares quantitativos de alerta, capacidade reservada, margens de segurança, períodos e horizonte de previsão.
- Mecanismo de telemetria, saúde e alertas independente da interface do Portal.
- Catálogo de otimizações autorizadas e limites máximos de alterações sem intervenção humana.
- Preços, orçamento, cálculo de custo total e estimativas de upgrade; eventual política de orçamento pré-autorizado **somente mediante aprovação expressa posterior**.
- Tratamento de sobrecargas, falhas parciais e isolamento entre clientes/empresas.
- Regras de retenção dos dados de telemetria e auditoria, acesso e segurança.
- Testes de desempenho/capacidade, regressões e procedimentos de rollback.
- Integração a planos de continuidade e recuperação de falhas.

**Decisão aprovada:** monitoramento de infraestrutura, APIs e filas; previsão de crescimento; otimizações automáticas seguras e homologadas; alertas preventivos; recomendações com custos e **aprovação humana para novas despesas**.  
**Pendente:** capacidade concreta, métricas, orçamento e arquitetura executável.  
**Programação:** não iniciada.
