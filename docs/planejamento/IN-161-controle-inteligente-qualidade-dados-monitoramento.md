# IN-161 — Controle Inteligente da Qualidade dos Dados de Monitoramento

**Status:** APROVADA funcionalmente em 02/11/2026 pelo responsável do projeto (confirmação "ok").  
**Fase:** planejamento e documentação; **não iniciar programação, instalação de coletores, alteração em produção, contratação ou despesas**.  
**Escopo:** infraestrutura e serviços Imagine, Sites Imagine, CRM Imagine SaaS, Portal Imagine, APIs principal e de contingência, filas e integração de leads, n8n, agentes técnicos, Central de Observabilidade e Central de Incidentes.  
**Referências:** IN-124/IN-127/IN-128/IN-147–IN-149 (recepção, retenção, auditoria e confirmação de leads); IN-130 a IN-136 (incidentes, agentes e alertas); IN-137/IN-138 (uma autorização humana para intervenção crítica); IN-139/IN-140 (aprendizado e avaliação); IN-143 a IN-146 (continuidade); IN-153 (inventário); IN-155 (homologação de procedimentos); IN-156 (saúde dos agentes); IN-157 (continuidade sem IA); IN-159 (Central de Observabilidade); IN-160 (logs, métricas e rastreamento).

## Objetivo

Assegurar **confiabilidade, completude, atualidade e integridade dos dados de monitoramento**, para que o painel administrativo e os agentes não utilizem medições inexistentes, desatualizadas, duplicadas ou contraditórias como fundamento para diagnósticos ou intervenções. Os mecanismos de validação serão **determinísticos e independentes dos modelos de IA**; agentes podem auxiliar no diagnóstico, recuperação e aprendizado, sem inventar valores ou ocultar lacunas.

## Funcionalidades aprovadas

1. **Validação das métricas:** verificar formato, tipo, unidade, origem, intervalo, coerência temporal, referências de serviço e consistência com contratos de telemetria aprovados. Distinguir valor legítimo atípico de dado incorreto.
2. **Detecção de ausência:** reconhecer quando um serviço, coletor, agente ou fluxo deixar de enviar informações dentro da janela esperada, diferenciando ausência de medição, ausência de eventos legítimos e indisponibilidade comprovada.
3. **Detecção de dados atrasados:** comparar horário do evento, recebimento, processamento e última confirmação; indicar telemetria obsoleta, atrasos de ingestão e relógios divergentes.
4. **Verificação de integridade:** detectar eventos duplicados, incompletos, fora de ordem, divergentes entre fontes e alterações não autorizadas em indicadores, sem excluir automaticamente eventos legítimos.
5. **Correção automática segura:** recuperar coletores e integrações por **procedimentos previamente homologados** de baixo/moderado risco, com limites de tentativas, validação de resultado, rastreabilidade e escalonamento quando necessário.
6. **Alertas de confiabilidade:** avisar quando determinado indicador, painel, relatório ou diagnóstico não puder ser considerado confiável; não transformar automaticamente falta de dados em estado operacional saudável.
7. **Auditoria da qualidade:** registrar anomalias de coleta, classificações, ações executadas, correções, diferenças de estado e validação dos resultados, com minimização de dados sensíveis.
8. **Aprendizado contínuo:** identificar causas recorrentes das falhas de observabilidade e propor melhorias, submetendo novos procedimentos à homologação da IN-155.

## Estados de confiabilidade previstos

| Estado | Significado funcional |
|---|---|
| **Válido/atual** | Evidência recente, íntegra e compatível com contrato e fonte de medição |
| **Atrasado** | O valor existiu, mas seu instante de coleta ou atualização excede o limite vigente |
| **Ausente** | Não chegou dado esperado; a saúde do serviço monitorado é indeterminada até nova prova |
| **Incompleto** | Faltam dimensões ou informações essenciais para o uso pretendido |
| **Inconsistente** | Fontes, unidades, estados ou eventos conflitam; requer verificação e conciliação |
| **Duplicado** | A mesma observação pode ter sido recebida mais de uma vez, exigindo tratamento idempotente |
| **Sob investigação** | A validação está inconclusiva ou há provável problema na cadeia de medição |

Esses são **estados de qualidade da informação**, distintos dos estados de saúde do serviço (**saudável, degradado, indisponível, desconhecido, em manutenção**) definidos na IN-159. Uma métrica ausente não prova automaticamente que o serviço esteja indisponível, mas **impede afirmar que está saudável apenas com base em dado antigo**.

## Política de atualização e informação confiável

- Cada dado relevante deverá conservar **fonte, identificação do serviço/ambiente, instante do evento, instante da coleta ou recepção, unidade, regra de validação e qualidade da medição**.
- Limiares de defasagem, intervalo esperado e qualidade serão **configuráveis por tipo de fonte e serviço**; **não** fixar número arbitrário neste documento.
- Se faltar dado atual, o painel deve sinalizar **"desconhecido" ou "sem telemetria confiável"**, exibindo última medição e horário, sem reciclar um status antigo como se fosse novo.
- Dados de fontes em conflito não devem ser corrigidos por mera média ou decisão livre da IA; preservar evidências e, quando possível, reconciliar por regra verificável.
- Se uma fonte indicar erro enquanto outra parecer saudável, não suprimir sinal grave antes de investigar a diferença.
- Valores historicamente verdadeiros devem permanecer identificados como **históricos**, não como medida atual.
- As regras de métricas não deverão alterar valores operacionais no sistema de origem apenas para harmonizar o painel; correção de dados de fonte exige processo autorizado.

## Fluxo funcional aprovado

1. **Recepção e normalização:** coletar informações das fontes autorizadas, com unidade, origem, contexto e horário.
2. **Validação independente:** conferir esquema, identificadores, integridade, qualidade temporal, consistência e regras específicas.
3. **Classificação da confiabilidade:** marcar validade ou problema, registrar evidência e informar a Central de Observabilidade.
4. **Correlação:** relacionar falhas de coleta a dependências do inventário IN-153 e à Central de Incidentes IN-130, distinguindo defeito no coletor de falha do próprio serviço monitorado.
5. **Alerta:** notificar responsáveis conforme impacto; problema que impeça observar função crítica deverá ter tratamento proporcional.
6. **Recuperação:** se houver procedimento seguro e previamente homologado, executar correção dentro das permissões; caso contrário, sugerir solução e escalar.
7. **Verificação posterior:** confirmar independentemente que o coletor voltou a fornecer dados íntegros e atuais, sem concluir êxito só porque o agente relatou reinício.
8. **Auditoria e aprendizado:** registrar falha, causa verificada ou hipótese, tentativas, desfecho, novos indicadores e sugestão de melhoria.

## Exemplo operacional

Um servidor deixa de enviar métricas, mas o último valor informava condições normais. O painel passa a mostrar **"telemetria ausente/saúde não confirmada"**, preservando o horário da última leitura. O mecanismo independente compara outros sinais disponíveis, investiga conectividade e coletor, abre incidente quando apropriado e tenta recuperação homologada. Somente após novas verificações independentes restabelece a indicação de estado saudável.

## Conexão com métricas de leads e retenção

- O rastreamento deve distinguir **recebimento durável na API** de **entrega efetivamente confirmada no CRM**, como exigido pelas IN-148/IN-149.
- Contagens de recebidos, entregues, pendentes, falhos, expirados e excluídos não podem ser artificialmente corrigidas para ocultar divergência de fontes.
- Reenvios do mesmo envio devem ser correlacionados por identidade estável e **não contados como leads diferentes**; submissões genuinamente distintas não serão indevidamente fundidas.
- Preservar a janela de **3 dias de fila normal + 15 dias de fila de falhas** e a **possibilidade de exclusão automática de payload não entregue após completar 18 dias**, com verificações e auditoria, conforme IN-147.
- Manter a auditoria técnica minimizada de filas de leads por **90 dias**, conforme IN-128. Isso **não** determina um prazo geral de retenção para todas as métricas ou logs, que seguem pendentes de dimensionamento na IN-160.
- Falha de telemetria não suspende automaticamente recebimento de leads ou as proteções determinísticas da API, tampouco autoriza descartar dados por status incerto.

## Resiliência e mecanismos independentes

- A própria **cadeia de monitoramento e a qualidade da coleta** devem ser verificáveis mesmo quando o Portal Imagine, um agente ou o Supervisor Técnico estiver indisponível.
- Os alertas críticos independentes previstos nas IN-134/IN-136/IN-156/IN-159 devem permanecer operacionais segundo a infraestrutura disponível; não depender exclusivamente do painel.
- A IA pode analisar padrões e recomendar diagnóstico, mas **não pode fabricar medições, suprimir erros, modificar logs ou declarar o serviço saudável sem evidência**.
- Falha total de modelos não impedirá a detecção determinística de ausência, atraso, formato inválido ou violações de limites já configurados, conforme IN-157.
- Autocorreções só mediante playbook seguro homologado, escopo e permissões vigentes, com checagens independentes; ações críticas seguem exigindo **uma aprovação humana específica**, quando necessário, sem dupla aprovação.
- Não contratar coletores, novos provedores ou recursos pagos, nem ampliar cotas, sem autorização humana de despesas (IN-142/IN-158).
- A central de qualidade **integra a IN-159 e utiliza telemetria da IN-160**, não cria painel administrativo duplicado.

## Indicadores previstos

- Percentual de métricas válidas, ausentes, atrasadas, incompletas, inconsistentes ou duplicadas por serviço.
- Tempo de detecção de perda de telemetria e tempo de recuperação comprovada.
- Quantidade de incidentes de qualidade, falsos positivos e falhas de coleta recorrentes.
- Cobertura de observabilidade, lacunas conhecidas e proveniência de dados.
- Recuperações homologadas bem-sucedidas versus escaladas para responsável humano.
- Confiabilidade de relatórios executivos e de diagnósticos baseados em dados técnicos.

## Pendências antes de implementar

1. Contratos e catálogos de métricas, logs e traces por serviço, com origem, esquema, unidade e identidade.
2. Intervalos esperados de coleta, atraso máximo por fonte, janelas de defasagem, tolerâncias e exceções.
3. Regras de deduplicação, normalização temporal, correção de relógios, relação entre eventos tardios e reprocessamento.
4. Modelo dos estados de qualidade no painel e política para relatórios com dados incompletos.
5. Procedimentos homologáveis de recuperação de coletores, com testes e limitação de tentativas.
6. Critérios de abertura/escalonamento de incidentes, integração com alertas independentes e prevenção de tempestades.
7. Permissões, auditoria, segurança dos dados observados e retenção por categoria, com proteção LGPD e sem registrar credenciais.
8. Ferramentas de observabilidade, dimensionamento, custos e cenários de indisponibilidade dos coletores/armazenamento.
9. Testes com perda parcial, telemetria atrasada, dados duplicados, estados conflitantes e indisponibilidade do próprio Supervisor Técnico.

**Aprovado:** validação independente de métricas, detecção de ausências e atrasos, identificação de inconsistências/duplicações, recuperação de coletores por playbooks homologados, alertas de confiabilidade, auditoria e aprendizado contínuo.  
**Pendente:** parâmetros numéricos, ferramentas, modelo de dados, custo e testes.  
**Programação:** não iniciada.
