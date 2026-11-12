# CRM F3-124 — Campos obrigatórios para movimentação de etapa

**Data:** 11/11/2026 · **Escopo:** PHP Core e PHPUnit na DEV, nenhuma rota, SQL ou registro real · **Rastreabilidade:** VEN-R02/R03 e VEN-T01, VEN-P02/P03 pendentes.

## Regra implementada

`SyntheticStageFieldRequirementsInspector` recebe índices de etapa de origem/destino (0..3 e diferentes), uma matriz ilustrativa de campos presentes **somente com booleanos** e uma matriz ilustrativa de quatro listas de requisitos por etapa. Os únicos códigos de campo aceitos no laboratório são `amount`, `assignee`, `contact`, `expected_close` e `source`.

O componente verifica a **estrutura completa** dos cinco flags e das quatro listas, inclusive de etapas não selecionadas, evitando que políticas ocultas inválidas passem despercebidas ao alterar o destino. Rejeita tipos errados, campos adicionais, repetidos ou não reconhecidos, políticas de tamanho incorreto e tentativas de enviar valores pessoais; a mensagem de erro é sempre genérica.

A resposta mostra apenas quantos campos são exigidos, quantos faltam e os códigos de requisito ausentes em ordem estável. Não exibe valores, nomes, títulos, empresa, IDs ou qualquer dado pessoal.

## Separação entre validade de formulário e permissão

**`requirements_met=true` nunca autoriza mover uma oportunidade.** Pode significar apenas que a matriz inventada não aponta pendências; não prova que a política é oficial, que o usuário pode editar, que a oportunidade pertence a um tenant, que existe CAS transacional ou que regras de etapa foram aprovadas.

`policy_verified`, `fields_verified`, `authorization_verified`, `authorized`, `can_move`, `can_override_required_fields` e `can_persist` permanecem falsos inclusive no exemplo de todos os campos preenchidos.

## Testes

`SyntheticStageFieldRequirementsInspectorTest` cobre faltantes, etapa de destino sem exigências, campos válidos sem autorização, saída canônica estável, integração demonstrativa com `SyntheticOpportunityTransitionLedger` recém-integrado, políticas malformadas, campos pessoais, etapas inválidas, ordem e duplicação de campos.

**Gates:** política de campos e permissão por empresa, aprovação comercial, transição real e isolamento de dados dependem de VEN-P02/P03 e CORE/SEG/DBA/LGPD. Nenhum endpoint, IAM/ALLOW, PostgreSQL, PII, custo, VPS, `main` ou produção foi alterado.
