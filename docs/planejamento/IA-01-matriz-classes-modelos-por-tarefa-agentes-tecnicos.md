# IA-01 — Matriz Aprovada de Classes de Modelos por Tarefa (Agentes Técnicos Imagine)

**Status:** **APROVADA funcionalmente em 02/11/2026** (resposta "ok") — adoção das classes **econômica, intermediária e avançada** para roteamento por tarefa dos agentes técnicos. **Modelos, fornecedores, limites financeiros, valores e implementação ainda não aprovados.**  
**Data:** 02/11/2026.  
**Fase:** somente planejamento e documentação. Não programar, contratar, provisionar ou ativar provedores e consumo.
**Base funcional já aprovada:** [IN-157 — Modelos e Contingência](IN-157-gestao-inteligente-modelos-ia-contingencia.md), [IN-158 — Custos](IN-158-monitoramento-inteligente-custos-consumo-agentes.md), [IN-166 — Permissões](IN-166-gestao-inteligente-ferramentas-permissoes-agentes.md), [IN-167 — Arquitetura Consolidada](IN-167-consolidacao-arquitetura-agentes-tecnicos.md). A escolha da VPS exclusiva para agentes é uma decisão de arquitetura distinta; consultar [ARQ-01](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md).

## 1. Objetivo

Detalhar uma **matriz operacional por complexidade e classe de custo/capacidade** para os sete agentes técnicos e Supervisor. O catálogo multimodelo, a seleção por tarefa e o fallback homologado **já foram aprovados na IN-157**; **este documento não solicita reaprovação dessas capacidades**. **Aprovada está a política de três classes**, enquanto modelos específicos, preços, limites mensais, fornecedores e metas de qualidade continuam como decisões posteriores.

## 2. Classes aprovadas (sem fixar fornecedores)

| Classe | Exemplos de tarefas elegíveis | Requisitos de qualidade |
|---|---|---|
| **Econômica** | Classificar/etiquetar incidente, sintetizar logs já minimizados, sumarizar estado, apontar roteamento inicial, explicações simples | Contratos de saída e validação de dados; erros sem consequência externa; escalação quando ambiguidade ou baixa confiança |
| **Intermediária** | Correlacionar logs/alertas, investigar falhas recorrentes, elaborar hipótese causal, sugerir playbook homologado, revisão técnica de rotina | Evidências, referências a fatos observados, validação independente e limites de tempo/tokens |
| **Avançada** | Investigação multissistema complexa, análise de mudanças críticas, diagnóstico de incidentes não triviais, interpretação de código e opções de recuperação de alto risco | Contraprovas, revisão de impacto, confirmação do estado real, mecanismos de autorização e eventualmente aprovação humana por risco |

**Observação:** a classe descreve **a adequação e o custo relativo esperados**, não uma qualidade garantida, produto ou preço. Modelos devem ser testados, homologados e avaliados por tarefa antes de entrarem no catálogo utilizável. Uma tarefa com regras estáveis e verificações determinísticas deve **preferencialmente não utilizar IA** quando isso oferecer resultado seguro e adequado.

## 3. Diretrizes de roteamento aprovadas

1. **Primeiro regras determinísticas:** monitoramento, validações de integridade, autenticação, fila, alertas críticos e recepção de leads independentes dos modelos.
2. **Identificar o objetivo:** tipo de atividade, criticidade, recursos da tarefa, dados tratados, restrições de fornecedor/ambiente, orçamento e permissões autorizadas.
3. **Selecionar classe mínima suficiente:** iniciar pela classe que esteja homologada para aquela tarefa e atenda qualidade, latência, privacidade e custo, sem tentar a classe mais econômica quando ela não for capaz de entregar segurança.
4. **Escalonamento controlado:** se a qualidade requerida não for alcançada, considerar classe superior homologada, sujeita a limites de tentativas, tokens, gastos e tempo; não repetir ações externas já realizadas.
5. **Fallback entre provedores:** somente alternativas **já homologadas**, compatíveis com a atividade, elegíveis por risco, LGPD, contratos e orçamento. A IN-157 prevalece em caso de conflito.
6. **Autorização independente:** modelo propõe, orquestrador/serviço técnico valida playbook, identidade, escopo por cliente/ambiente, orçamento e aprovação humana quando necessária antes de qualquer ferramenta com efeitos reais.
7. **Verificação do efeito:** checar estado efetivo do serviço e rastrear tarefa, versão do modelo, consumo e resultado verificável.
8. **Sem opção segura:** suspender tarefa dependente de IA, registrar incidente e alertar responsável, mantendo as verificações determinísticas.

## 4. Matrizes por especialidade — exemplos para homologar, não concessão de autonomia

| Especialidade | Econômica | Intermediária | Avançada |
|---|---|---|---|
| Integrações | Rotular erro e agrupar ocorrência | Investigar webhook/fila/idempotência | Causa raiz em falhas complexas de entrega de leads |
| Infraestrutura | Resumo de métricas | Análise de eventos e saturação | Plano de mitigação em incidente multissistema |
| Domínios/DNS/SSL | Classificação de aviso | Diagnóstico de renovação e validação | Avaliar mudanças sensíveis e riscos de propagação |
| Automações/n8n | Resumo de execução | Diagnóstico de workflow e retries | Avaliar correção sistêmica em cadeia de fluxos |
| Backup/recuperação | Classificar relatórios | Análise de consistência e falhas | Planejar recuperação com múltiplas dependências |
| Segurança | Agrupar sinais | Triagem de eventos e riscos | Investigação sensível e recomendações de contenção |
| Publicação/Cloudflare | Sumário de logs de deploy | Diagnóstico de build/publicação | Avaliação de rollback e mudanças complexas |
| Supervisor Técnico | Triagem e resumo | Priorização de incidentes | Análise de trade-offs e coordenação de incidentes graves |

O Supervisor **não tem superprivilégios**; complexidade do modelo não altera alçadas ou permissões. Não enviar senhas, tokens ou dados pessoais desnecessários aos modelos.

## 5. Métricas e guardrails a especificar

- Definir modelos homologados e versionados por classe e tarefa, região e critérios de privacidade e dados permitidos; avaliar contratos com cada fornecedor.
- Comparar **qualidade real por tarefa**, inclusive taxa de saídas válidas, acerto em cenários controlados, resolução verificada, falsos positivos/negativos e falhas de ferramentas.
- Medir latência percentil, consumo de tokens, custo estimado vs faturado, custo por investigação resolvida, tentativas, contexto, escalonamentos e indisponibilidade.
- Estabelecer tempos de corte e máximos de tentativas e custo por tarefa/agente/dia/mês sem comprometer segurança; números ainda **não aprovados**.
- Tratar falhas de modelos e APIs sem deixar o agente em loop; proteger contra prompt injection em logs/sites/tickets.
- Homologar modelos e tarefas de maior risco com testes e autorização humana; revisar mudanças de versão sem permitir autopromoção ou concessão de acesso.
- Segregar **custos internos Imagine** dos créditos comerciais do CRM; conter consumo mesmo quando a IA fornecer sugestões de gasto extra.

## 6. Itens ainda não decididos

1. Modelos e fornecedores específicos para cada tarefa (OpenAI, Anthropic, Google ou outros), sujeitos a capacidade, conformidade e homologação futura.
2. Ordem de preferência e política de fallback por domínio e criticidade.
3. Tetos financeiros e preço efetivo por modelo/fornecedor, cotas por tarefas e limite mensal interno.
4. Regras concretas de segurança para envio de dados e validação das políticas de retenção e processamento por provedor.
5. Metas quantitativas, banco de testes e dimensionamento final do servidor e serviços auxiliares.

**Decisão tomada em 02/11/2026:** o responsável **aprovou** a classificação **econômica / intermediária / avançada** como base funcional para orientar o catálogo da IN-157. **Não foram aprovados modelos, provedores, valores, contratações nem programação.**

**Próxima etapa sugerida:** comparar modelos e fornecedores concretos como **candidatos**, por classe e tarefa, com testes de qualidade, disponibilidade, segurança de dados e custos; submeter escolhas finais e orçamentos à aprovação separada.
