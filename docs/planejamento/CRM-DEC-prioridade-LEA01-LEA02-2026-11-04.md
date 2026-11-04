# CRM-DEC — Prioridade de planejamento dos modelos LEA-01 e LEA-02 do Básico

**Data:** 04/11/2026.
**Manifestação:** “ok” em resposta à recomendação de **priorizar LEA-01 e LEA-02 como primeiros modelos a detalhar para o Básico, utilizando avisos internos autorizados, e decidir separadamente a criação automática de tarefas comerciais**.
**Estado:** **PRIORIDADE DE PLANEJAMENTO APROVADA**, e **NÃO aprovação individual/funcional dos modelos, direito de alerta comercial, tarefa ou ativação comercial**.

## 1. Decisão registrada

1. Analisar primeiramente **LEA-01 — Novo lead confirmado** e **LEA-02 — Lead sem retorno** como candidatos à biblioteca de modelos prontos do CRM Básico.
2. Preferir a análise de **avisos internos comerciais autorizados** como primeiro efeito de baixo risco; o modelo depende de definir se notificações comerciais configuráveis A03 são direito do Básico, seu catálogo e seu contrato. Não confundir com os **avisos operacionais obrigatórios da Agenda (SO)**, já incluídos e fora da quota de slots comerciais.
3. **Não incluir automaticamente a criação de tarefas comerciais A01**, porque LEG-08/LEG-13 aprovam notas/histórico manuais básicos, sem demonstrar direito genérico a tarefas comerciais avançadas. Isso será **decisão separada, explícita e registrada**.
4. Os novos fluxos de interesse são executados pelo **motor próprio de automações do CRM**, nunca por workflows n8n gerados por cliente; os eventos oficiais pertencem ao módulo DAT e os efeitos ao proprietário de notificações/tarefas.
5. Demais candidatos TSK-01, AGD-01/02/03 e OPP-01 ficam em backlog de revisão, sem direito presumido nem alteração de classificação por silêncio.
6. Baselines **Básico 3, Essencial 5, Profissional 20, Empresarial 50, Trial 2** slots ativos e quotas chatbot 3/10/30 continuam sujeitos à revisão final do planejamento. Básico público e Básico beneficiado por Site elegível têm os mesmos recursos por organização CRM, sem multiplicar slots ao contratar novos sites para a mesma organização.

## 2. Limites e gates que não foram aprovados

- O “ok” **não equivale a aprovação individual de LEA-01, LEA-02, nenhum gatilho técnico T01/T13, efeito A01/A03, duração padrão de inatividade, antispam, retenção ou disponibilidade por plano**.
- Nenhuma comunicação ao cliente por WhatsApp, e-mail, aplicativo externo ou agente IA foi incluída; tais meios dependem de contrato/permissão próprios.
- Recepção do formulário Imagine na API principal ou contingência não é confirmação de lead criado no CRM; LEA-01 só pode usar evento de negócio DAT/CRM confirmado.
- LEA-02 exige critério de **interação válida**, sem contar simplesmente leitura do CRM, HTTP 202 ou sugestão de IA, e deve evitar alertas repetidos.
- Preservar **AUT-P01..14, AUT-MAT-P01..P10, LIB-P01..P12, LIB02-P01..P08, CORE/INT/SEG e gates P0**, sem homologação nem autorização de programação.
- Não há autorização de compra, configuração, novas VPS, testes reais, código, publicação de modelos nem mudanças no ambiente dos clientes.

## 3. Continuidade documental

Produzir [AUT-LIB02 — seção 8: prioridade LEA-01/LEA-02 e alternativas para tarefas](CRM-AUT-LIB02-revisao-individual-modelos-basico-v0.1.md), como **proposta** com cenários verificáveis e escolha comercial separada; posteriormente submeter **LIB02-P01** e, se resolvida, os modelos individualmente para decisão expressa. Preservar o inventário histórico e as rejeições já documentadas.

**Fontes:** [AUT-LIB02](CRM-AUT-LIB02-revisao-individual-modelos-basico-v0.1.md), [AUT-LIB01](CRM-AUT-LIB01-biblioteca-modelos-basico-editores-por-plano-v0.1.md), [MAT01](CRM-AUT-MAT01-matriz-elegibilidade-automacoes-por-plano-v0.1.md), [LEG-08](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/08-plano-basico-cortesia.md), [LEG-13](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md), [REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md).

**Somente documentação.**
