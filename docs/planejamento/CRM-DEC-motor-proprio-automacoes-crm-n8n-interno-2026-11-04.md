# CRM-DEC — Motor próprio de automações do CRM Imagine e limite de uso do n8n

**Data da aprovação:** 04/11/2026.
**Manifestação do responsável:** “ok”, em resposta direta à pergunta: “Você aprova essa separação, mantendo a validação formal do licenciamento antes da implantação?”.
**Estado:** **DIRETRIZ ARQUITETURAL APROVADA PARA PLANEJAMENTO**, não liberação de programação, homologação jurídica ou contratação.

## 1. Deliberação expressa

1. **Aprovar a opção L1 como arquitetura definitiva de referência do CRM Imagine:** as **automações configuradas por empresas clientes serão definidas e executadas pelo motor próprio do CRM**, apoiando-se nos motores centrais de **Fluxos, Gatilhos, Condições, Ações, Eventos, Filas, Datas e Aprovações** já planejados. **Não duplicar** módulos/serviços funcionais já aprovados.
2. **Não utilizar o n8n como backend obrigatório ou editor oculto** das automações de clientes do CRM. O construtor visual, APIs públicas, comandos de IA e demais interfaces de clientes **não deverão construir, editar, habilitar ou executar workflows do n8n em nome de clientes** pela via da Community Edition sem enquadramento de licença comercial formalmente confirmado.
3. **Preservar o n8n como orquestrador de processos internos da própria Imagine** e de integrações técnicas que comprovadamente sejam permitidas pela licença aplicável; mesmo tarefas internas envolvendo dados/credenciais de terceiros devem passar por revisão do caso concreto. Não pressupor que toda automação “interna” esteja automaticamente autorizada na Community.
4. **Integrações com n8n somente por contratos tipados, versionados e escopados**, observando segregação entre produtos, empresa/tenant e ambiente, autenticação, auditoria, idempotência, autorização, reconciliação e confirmação de efeitos oficiais nos respectivos sistemas. Êxito de job n8n/HTTP não equivale a conclusão da operação CRM/financeira.
5. **Fluxos essenciais (captação durável e independente de leads, operações fundamentais do CRM, filas oficiais e alertas críticos)** não dependerão da disponibilidade do n8n. Mantidas a VPS n8n separada da VPS exclusiva dos sete agentes técnicos, Supervisor e orquestrador, e a independência do motor de Performance AI.
6. A separação preserva quotas, permissões e créditos de IA por empresa, módulos e planos no CRM; custo do n8n e dos agentes técnicos é de operação interna da Imagine, contabilizado separadamente.

## 2. Justificativa e limites

A decisão reduz dependência contratual e tecnológica do n8n em funcionalidades SaaS voltadas aos clientes, preservando controle multiempresa, segurança, autonomia e continuidade do CRM. **Não é confirmação de que a Community Edition pode ser utilizada gratuitamente em todos os casos internos da Imagine**. Uso, termos, modelo de credenciais de terceiros e contratos comerciais exigem análise e manifestação formal antes da implantação.

**Licença — gate obrigatório:** verificar as condições vigentes da Sustainable Use License e modalidades Enterprise/OEM em função dos casos concretos de workflows; solicitar posicionamento por escrito ao fornecedor para situações limítrofes. Caso seja identificada necessidade de licença comercial para determinados processos internos, **cotar e submeter a aprovação, ou redesenhar esses processos para motor próprio**, sem desrespeitar os termos.

Fontes oficiais a consultar: https://support.n8n.io/article/can-i-use-your-license-for-my-use-case ; https://n8n.io/legal/eula/ ; https://n8n.io/oem/ .

## 3. Requisitos de implementação a especificar posteriormente

- Catálogos de gatilhos/condições/ações e contratos oficiais por módulo, com permissões por ação e escopo de tenant.
- Versionamento, editor visual, simulação sintética, ativação/pausa, limitação por plano, observabilidade, auditoria e histórico.
- Idempotência, limites de concorrência e consumo, retry/compensação, prevenção de loops, fila durável e estados oficiais, sem falsos positivos de sucesso.
- Coexistência com chatbot, IA do CRM, WhatsApp, notificações, agenda, financeiro, canais e integrações, sem concessão implícita de recursos pagos.
- Isolamento e recuperabilidade em caso de queda n8n/servidor de agentes, sem perda de leads e com alertas externos.
- Licenciamento n8n formalmente validado e custo incluído no orçamento global onde necessário.

**Pendências existentes preservadas:** AUT-P01..14, AUT-D01..12, CORE/INT P0, ARQ-01, ARQ-02, ARQ-03, SEG-01/02, G01..G06, revisão final de quotas, preços e contratos.

## 4. Natureza da aprovação

**Aprovado:** a decisão de **motor próprio no CRM para automações de clientes** e **n8n limitado a operações internas cuja licença seja compatível**, preservando validação formal pré-implantação.

**Não aprovado:** migração/instalação de workflows, compra ou adesão à licença n8n, versão, fornecedor de servidor, custos, cronograma, RPO/RTO, teste real, programação, implantação, acesso ao painel da VPS ou configuração de serviços. A arquitetura global ainda requer fechamento documental e homologação.
