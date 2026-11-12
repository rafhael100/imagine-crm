# CRM F3-137 — Jornada comercial integrada em memória, sem liberação operacional

**Data:** 12/11/2026 · **Ambiente:** somente DEV, PHP Core e PHPUnit · **Pré-requisitos implementados:** F3-123, F3-128, F3-129, F3-130, F3-132

## O avanço

O componente \`SyntheticCommercialJourneyInspector\` reúne as validações existentes de cinco pontos relevantes de uma venda, em vez de continuar tratando cada módulo isoladamente:

1. **Pontuação:** \`SyntheticLeadRuleScorePreview\` calcula a faixa e a pontuação de sinais inventados.
2. **Distribuição:** \`SyntheticLeadRoundRobinPreview\` escolhe somente uma posição local fictícia de agente, se houver capacidade.
3. **Funil:** \`SyntheticOpportunityTransitionLedger\` reduz alterações e resultado alegado de uma oportunidade sem identidade.
4. **Proposta:** \`SyntheticProposalSendPreflight\` reaproveita ciclo de versões e descontos e mantém bloqueios de política, identificação e destinatário.
5. **Atividade:** \`SyntheticTaskLifecycleInspector\` analisa conclusão e atraso alegados, reutilizando prazos do follow-up existente.

O contrato tem **11 chaves obrigatórias e fechadas**, exclusivamente com campos fictícios reconhecidos pelos módulos, sem IDs ou campos livres. Todos os validadores são executados antes de avaliar conflitos entre módulos: nenhuma seção inválida fica oculta atrás de outra regra.

## Consistência e avisos

O relatório só exibe indicadores agregados e avisos fechados:
- falta de capacidade do agente de laboratório;
- oportunidade alegadamente ganha sem aceite alegado da **última versão** da proposta;
- desconto acima de política gerencial ilustrativa;
- atividade fictícia vencida.

Mesmo que a jornada não tenha aviso de consistência, isso não a torna real, válida ou autorizada. O valor \`fictional_consistency_warnings=0\` significa apenas que os exemplos não dispararam os quatro avisos ilustrativos.

A saída tem \`recognized_wins=0\` e \`recognized_revenue_cents=0\` permanentemente. Todos os indicadores \`*_verified\`, \`authorized\` e \`can_*\` continuam falsos: nenhum lead é criado, pessoa atribuída, oportunidade movida, proposta enviada, tarefa criada, cobrança realizada ou registro persistido.

## Testes

\`SyntheticCommercialJourneyInspectorTest\` exercita a jornada completa com um exemplo favorável e um contraditório (sem capacidade, oportunidade marcada como ganha sem aceite da última versão, desconto que exige revisão e tarefa vencida), valida que a saída não contém identificadores, timestamps, mensagens ou dados pessoais e rejeita o cenário inteiro quando qualquer uma das entradas está malformada. Os testes de cada componente continuam sendo executados na mesma CI.

## Bloqueios e próximos passos de produto

Este código **não contém API, SQL, autenticação, RLS operacional, integração com CRM externo, comprovação de aceite, scheduler, disparo, pagamento ou cliente real**. O passo operacional necessita fechar os gates CORE/SEG/DBA/LGPD e decisões comerciais por tenant, depois implementar transações, políticas e endpoints com testes end-to-end em ambiente autorizado. Nenhuma mudança em main, VPS, produção, contas ou despesas.
