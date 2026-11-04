# CRM-DEC — Definição funcional de LEA-02: primeiro atendimento pendente

**Data:** 04/11/2026.
**Manifestação:** “ok” em resposta à pergunta expressa: “Você aprova essa definição de LEA-02?” — proposta de avisar quando a empresa não tiver registrado atendimento efetivo do lead.
**Estado:** **APROVADA A SEMÂNTICA DE LEA-02**, não os parâmetros nem a implementação.

## 1. Decisão aprovada

- **LEA-02 identifica a falta de primeiro atendimento efetivo da empresa a um lead**, constatada por fatos válidos e registrados no CRM. O foco do modelo é evitar leads recebidos sem acompanhamento real.
- **Não confundir** com a situação em que a empresa já tentou contatar o lead e **o cliente não respondeu**. A política de tentativas, retorno e follow-up posterior será especificada como categoria/ciclo **distinto**, sem assumir automaticamente um modelo novo, slot ou direito comercial.
- **Leitura da ficha, visualização de aviso, troca de etiqueta/responsável, atualização de status meramente administrativa ou geração de texto por IA não comprova, por si só, atendimento efetivo.**
- **Mensagem enviada pelo cliente sem ação comprovada da equipe não encerra o primeiro atendimento pendente.** A definição de atendimento com IA, se contratada, exigiria decisão expressa e separada; não conceder novo direito ao Básico.
- O encerramento da condição de primeiro atendimento pendente exige **interação efetiva identificável, atribuível ao ator autorizado e aceita pelo módulo proprietário DAT/Atendimento**, sob contrato de eventos ainda a homologar.
- O efeito previsto de LEA-02 permanece o **aviso interno comercial** já escolhido por TAR-A, **sem criação automática de tarefas**, sujeito à homologação do modelo e do direito de execução A03.
- Arquivamento, exclusão, conversão ou encerramento autorizado podem encerrar a **elegibilidade do aviso** conforme estado oficial, mas **não significam que houve atendimento**. Preservar essa diferença na auditoria.

## 2. Matéria ainda não decidida

A aceitação do conceito **não define** se **tentativa comprovada, mas sem sucesso** (ligação não atendida, mensagem sem resposta) encerra o relógio de primeiro atendimento ou apenas registra uma tentativa, tampouco quais ações manuais estruturadas provam atendimento efetivo no Básico. Isso exige aprovação específica e distinção entre “tentativa”, “atendimento efetivo” e “cliente respondeu”.

**Prazos NÃO aprovados:** 24 horas corridas sugeridas como padrão; opções de 4/12/24/48/72h; horas úteis versus corridas; cálculo com fuso, expedientes, mudança de prazo e SLA. **Não inferir aceite numérico do mesmo “ok”.**

**Contratos NÃO aprovados:** gatilho T13, evento de contato válido, catálogo de fontes, idempotência e ciclo, comando de aviso A03, destinatário, retenção, custos, quotas, antiabuso, testes e elegibilidade final do Básico. A criação e publicação dos modelos LEA-01/LEA-02 continuam **pendentes de aprovação individual**.

## 3. Rastreabilidade, dependências e continuidade

- [CRM-AUT-LIB04 v0.1](CRM-AUT-LIB04-criterios-interacao-prazos-antiduplicidade-LEA01-LEA02-v0.1.md): **LIB04-P01 resolvida somente quanto ao significado do modelo**; LIB04-P02..P09 continuam abertas, assim como a subdecisão sobre tentativas sem sucesso.
- [TAR-A aprovada](CRM-DEC-escolha-TAR-A-avisos-internos-LEA01-LEA02-2026-11-04.md): efeito interno, sem tarefa comercial.
- [LIB02](CRM-AUT-LIB02-revisao-individual-modelos-basico-v0.1.md), [REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md), CORE/SEG/INT/AUT-P01..14: preservar regras, donos de dados e gates P0.

**Próximo estudo:** propor uma classificação mínima verificável dos registros `atendimento efetivo`, `tentativa de contato sem sucesso`, `resposta do cliente` e `nota administrativa`, com direitos do Básico, sem criar código ou estados definitivos por presunção.

**Restrição:** somente planejamento e GitHub; nenhuma programação, ativação de automação, implantação, compra, teste real ou alteração comercial autorizada.
