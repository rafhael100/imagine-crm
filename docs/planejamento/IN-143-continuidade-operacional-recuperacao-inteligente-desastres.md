# IN-143 — Continuidade Operacional e Recuperação Inteligente de Desastres

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Fase:** somente planejamento e documentação; **não iniciar programação, provisionamento, migrações ou contratações**.  
**Escopo:** serviços Imagine (sites, hospedagem, recepção e entrega de leads, integrações, automações e CRM), Central de Incidentes Técnicos, agentes especializados e Agente Supervisor Técnico.  
**Referências:** IN-101 (backups dos sites), IN-124 (APIs principal/contingência), IN-126 a IN-129 (filas, retenção, tratamento e diagnóstico de falhas), IN-130 a IN-136 (incidentes, agentes, alertas e plantão), IN-137/IN-138 (aprovações e risco), IN-139/IN-141 (aprendizado e prevenção), IN-142 (capacidade e custos).

## Objetivo
Manter continuidade de operação na ocorrência de falhas graves, com detecção rápida, contingências já projetadas, recuperação controlada, preservação de integridade dos dados, verificações de consistência e aprendizado a cada incidente. **Não prometer indisponibilidade zero, recuperação instantânea ou perda absoluta zero de dados sem arquitetura e testes que fundamentem tais compromissos.**

## Capacidades funcionais aprovadas

1. **Detecção de falhas graves:** monitorar indisponibilidade de servidores, falha de rede, paralisação de processos, indisponibilidade de APIs, filas, armazenamento e serviços críticos; classificar impacto e registrar na Central de Incidentes.
2. **Acionamento de contingência:** utilizar serviços alternativos **pré-configurados e testados**, inclusive a API de contingência de formulários, conforme as condições de segurança e autorização aprovadas.
3. **Recuperação inteligente:** agentes diagnosticam, correlacionam logs/telemetria, identificam dependências e propõem ou executam somente procedimentos de recuperação previamente homologados e permitidos.
4. **Proteção e consistência dos dados:** preservar confirmações de persistência durável, integridade de registros, trilhas de auditoria, idempotência, controle de duplicidades e reconciliação de dados após a retomada.
5. **Recuperação por backup:** permitir restabelecimento a partir de cópias verificadas, com testes de integridade, compatibilidade, isolamento e análise de risco antes de restaurar; ações destrutivas/sobrescritas exigem autorização humana conforme IN-137/IN-138.
6. **Simulações e testes periódicos:** planejar exercícios seguros de failover, falhas parciais, restauração em ambiente isolado e retorno à operação, com verificação, registro, revisão e prevenção de impacto inesperado sobre clientes.
7. **Aprendizado contínuo:** registrar diagnósticos, pontos de falha, procedimentos eficazes/ineficazes, testes, resultados e lições para a biblioteca de autocorreções e a avaliação de agentes (IN-139/IN-140).

## Separação entre serviços e planos de recuperação
- **Sites Imagine:** preservar código, artefatos, configurações e fluxos de publicação; utilizar backups e versionamento conforme IN-101.
- **Recepção de leads:** API principal e **API de contingência em infraestrutura separada, preferencialmente em provedores distintos**, com persistência durável independente, conforme IN-124. O failover deverá reduzir indisponibilidades sem criar duplicidade de processamento nem confirmar recebimento antes de persistir o envio.
- **Filas de leads:** recuperar processadores e retomar entregas idempotentes, preservando envios já recebidos; respeitar as políticas aprovadas de **3 dias em fila normal + 15 dias em fila de falhas**, sem reiniciar prazo por tentativa. A destinação final de mensagens não entregues após o prazo total permanece **pendente** — não permitir descarte silencioso.
- **CRM Imagine SaaS:** terá política de backup, retenção e recuperação **independente** daquela dos sites; não equiparar restauração do CRM ao restabelecimento de um site.
- **Automações/n8n e serviços complementares:** verificar dependências, credenciais, versionamento de workflows e consistência antes de ativar processamento ou repeti-lo.
- **Portal Imagine administrativo:** indisponibilidade do painel não deve impedir, por si só, recepção durável nas APIs independentes nem inviabilizar todos os alertas técnicos críticos.

## Cenários a serem cobertos
1. Queda isolada de API principal com contingência íntegra: recepção migra ao serviço alternativo configurado; investigar e recuperar principal, validar e reconciliar registros.
2. Queda simultânea das duas APIs: incidente **crítico**, acionamento imediato de agente e humano; não apresentar sucesso ao visitante sem confirmação de persistência durável. A interface pode manter campos temporariamente para retry enquanto possível, sem alegar recebimento garantido.
3. Falha na entrega ao CRM com lead já recebido: preservar fila, aplicar retentativas idempotentes, alertas e escalonamento; não descartar mensagens para aliviar capacidade.
4. Servidor/hospedagem indisponível: avaliar serviços afetados, dependências, contingência viável e procedimentos homologados de recuperação.
5. Falha/corrupção de dados: isolar o problema, identificar ponto íntegro e risco de perda, evitar sobrescrever dados válidos e solicitar autorização humana para restauração destrutiva.
6. Falha de conectividade, de provedores externos ou de canais de notificação: detectar e utilizar alternativas configuradas quando disponíveis, deixando explícita a ausência de garantia de entrega das notificações.
7. Recuperação que provoca regressão: suspender ação problemática, verificar rollback quando viável e escalar com evidências.

## Metas de recuperação: conceitos aprovados, valores pendentes

Para cada serviço, definir e dimensionar antes de qualquer compromisso contratual:
- **RTO (Recovery Time Objective):** meta de quanto tempo o serviço pode permanecer indisponível até sua recuperação.
- **RPO (Recovery Point Objective):** meta da quantidade máxima de dados recentes que o projeto admite perder em um desastre, medida em tempo.

A seleção dos valores precisa considerar criticidade, contratos, custo de redundância, mecanismos de persistência, snapshots/backups, testes de restauração, dependências e disponibilidade humana. O RTO/RPO de API receptora, filas, CRM e sites **não precisa ser idêntico**. Não pressupor RPO zero nem recuperação instantânea.

## Segurança, autorizações e custos
- **Autonomia proporcional ao risco:** diagnóstico, monitoramento, failover e correções seguras previamente homologados podem ser automáticos dentro de permissões; operações críticas, como sobrescrita de bases, troca sensível de credenciais, alterações irreversíveis ou mudanças de infraestrutura de alto risco, exigem **uma autorização humana**. Não há dupla aprovação, conforme decisão expressa da IN-137/IN-138.
- **Sem autorização financeira implícita:** agentes não podem contratar redundância, servidores, armazenamento ou serviços pagos nem aumentar custos fora dos limites aprovados (IN-142).
- **Privilégio mínimo e segregação:** manter isolamento entre empresas, restrição de dados pessoais e proteção de credenciais; logs e entradas de usuários são dados não confiáveis.
- **Auditoria e confirmação de recuperação:** ações, responsáveis, autorizações, testes, impactos, retorno ao funcionamento e incidentes remanescentes devem ser registrados. Um comando concluído não prova que o serviço esteja recuperado.
- **Contingência degradada:** se alternativas também estiverem indisponíveis, registrar indisponibilidade e continuar alertas/escalonamento sem prometer funcionamento completo.

## Fluxo funcional aprovado
Detecção → classificação de gravidade → abertura de incidente e alertas → avaliação de serviços e dados afetados → ativação de contingência segura já autorizada → diagnóstico e recuperação por procedimento homologado (ou uma solicitação humana se houver ação crítica) → validação de integridade e entrega → reconciliação e retorno controlado → encerramento verificado → registro de aprendizado e melhoria.

## Pendências antes da implementação
1. **Matriz de criticidade, dependências, RTO e RPO por serviço**, com valores concretos aprovados e custos para alcançá-los.
2. Planos de continuidade e recuperação passo a passo para sites, APIs redundantes, filas, CRM, n8n, banco de dados, domínios, backups e fornecedores externos.
3. Arquitetura de contingência, persistência e recuperação independente para os componentes prioritários; definição de como lidar com falha compartilhada entre provedores.
4. Política de retorno da contingência à operação primária (failback), idempotência, reconciliação e integridade dos dados.
5. Testes de recuperação/restauração em ambiente isolado, periodicidade, evidências e responsáveis.
6. Retenção, segurança e isolamento de backups por serviço, sem misturar dados ou políticas de CRM e sites.
7. Regras de acionamento de responsáveis/plantão, comunicação com clientes e status público quando pertinente.
8. Orçamento, contratos, limites financeiros e métricas observáveis de sucesso.

**Aprovado:** detecção de falhas graves, contingência pré-configurada, recuperação por agentes com autonomia controlada, proteção e validação de dados, recuperação por backups, testes periódicos, aprendizado e definição futura de RTO/RPO.  
**Pendente:** metas numéricas, infraestrutura detalhada, custos, procedimentos e homologação.  
**Programação:** não iniciada.
