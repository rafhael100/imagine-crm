# QA F4-21 — Contenção de entradas do classificador sintético de follow-ups

## Implementação

`FollowUpAgingProjection::project` passa a exigir `list` verdadeira com até **100 registros sintéticos**. Cada registro deve conter **exatamente** as chaves `id` e `due_at`, em qualquer ordem. Campos inesperados (inclusive informações de contato) são rejeitados, assim como IDs inválidos, duplicações e datas malformadas já bloqueadas pela implementação anterior.

A limitação coincide com o teto por observação do adaptador F4-20. Isso reduz ambiguidades de contrato e volumes inesperados no laboratório, mas não é uma cota comercial, limite contratado ou requisito definitivo da aplicação operacional.

## Testes de regressão

Os testes cobrem aceitação da ordem inversa de campos, rejeição de chave extra, coleção associativa externa, limite exato de cem registros, limite excedido e ID de tipo não textual.

## Fronteiras de segurança

Os dados são apenas fictícios, processados em memória e sem operações. Nenhum endpoint, persistência, consulta por tenant, decisão de permissão `ALLOW`, migração, produção ou VPS é adicionado. O CI deve ficar verde na HEAD do PR antes de merge apenas na DEV. SEG/DBA independente #27, CORE e privacidade continuam pendentes.
