# Preservar follow-up ao salvar um lead sem mudanças

## Defeito e correção

O formulário de título permite clicar em Salvar com o valor atual. O método
`Workspace::updateLead` incrementava a versão mesmo nesse caso. Um follow-up
agendado conserva a versão causal do lead e, ao detectar essa diferença, cancela
a execução por segurança. O resultado era cancelar um acompanhamento válido
apenas porque o usuário confirmou um formulário sem editar seus dados.

A correção calcula as alterações efetivas depois da autorização, do lock, da
verificação da versão e da validação dos campos. Se o conteúdo não mudou, retorna
sem alterar a versão nem gravar `lead.updated`. O agendamento continua válido.

Versão desatualizada continua recebendo HTTP 409, mesmo com o mesmo título.
Títulos inválidos e transições de etapa inválidas continuam recusados. Uma edição
real mantém o incremento de versão e as proteções existentes do motor. A alteração
não autoriza ressuscitar execuções canceladas, sessões revogadas ou grants expirados.

## Validação na jornada existente

O teste HTTP completo agenda o acompanhamento de proposta, salva o mesmo título,
confere versão, auditoria e agendamento, processa a execução e exige a tarefa
correspondente. A repetição do processamento não pode duplicar o efeito.

O workflow `ci-private-crm-journey.yml` inclui explicitamente a branch P21 como
base permitida, mantendo as anteriores. Os testes continuam em contêineres e
bancos descartáveis, com identidades sintéticas. O workflow também exercita
cadastro/MFA, quotas, empresa A/B, CSV, tarefas, restauração e Chromium. Resultados
efetivos e URLs de evidência são registrados na PR, sem presumir sucesso.

Base: candidata P21 [#638](https://github.com/rafhael100/imagine-crm/pull/638).
Referência: [fechamento do CRM #557](https://github.com/rafhael100/imagine-crm/issues/557).

DRAFT / NO-MERGE / NO-DEPLOY. Sem mudança de schema, política comercial, acesso
operacional, banco compartilhado, DNS, cobrança ou dados reais.
