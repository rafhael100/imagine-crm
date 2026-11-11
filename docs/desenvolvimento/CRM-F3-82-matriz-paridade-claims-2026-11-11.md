# CRM F3-82 — Matriz de consistência entre os quatro verificadores técnicos

**Data:** 11/11/2026. **Escopo:** somente suíte de testes, sem alterar qualquer classe de runtime.

## Problema protegido

O Core ganhou verificadores de reivindicações técnicas (F3-77), colisões em lote de dois campos (F3-78), vínculo declarado da submissão (F3-79) e colisões em lote de três campos (F3-80). Sem validação cruzada, implementações futuras poderiam considerar a mesma referência técnica de submissão **repetida** em um verificador e **distinta** em outro, ou tratar divergência de vínculo como prova de deduplicação de pessoa.

## Teste implementado

`SyntheticSubmissionClaimsCrossInspectorMatrixTest` gera de forma **determinística** uma matriz combinando três organizações **fictícias**, três submissões técnicas e dois vínculos (18 trios), acrescentando duas variantes com hexadecimal maiúsculo (20 trios no total). Compara todos os pares **ordenados no triângulo superior**, exatamente **190 comparações**, e exige:

- Toda classificação por organização/submissão do `SyntheticSubmissionClaimInspector` corresponde ao `SyntheticSubmissionBindingClaimInspector` quando há diferença de organização ou submissão.
- Somente o caso de **mesma organização/submissão declaradas** pode ser subdividido no contrato de três campos em “vínculo igualmente declarado” ou “vínculo contraditório”.
- A lista de posições de pares dos dois inspetores de **lote** corresponde exatamente à aplicação dos respectivos inspetores individuais, inclusive ordem determinística e variáveis hexadecimal maiúsculas.
- Todas as avaliações e resultados de lote retornam **false** em retry autorizado, inferência de contato (nos inspetores individuais), fusão, persistência e ACK, sem jamais refletir UUIDs técnicos, pessoa, nome ou e-mail.
- Entrada aninhada adversarial em qualquer posição rejeita **o lote inteiro**, com exceção genérica e sem mapa parcial.

Os casos são PHP unitários automaticamente executados no workflow F0 e na suíte PostgreSQL/RLS, sem APIs, banco, rede, dependência externa ou fixture de cliente.

## Gate

Nenhum teste de compatibilidade dos contratos internos autentica serviço, prova tenant, valida um binding, substitui ledger persistente ou autoriza contato, retry, envio ou importação real. Não houve modificação em `main`, VPS, produção, credenciais, SQL, migrações, IAM/ALLOW, custos ou dados pessoais. Revisão independente CORE/SEG/DBA/LGPD da [issue #259](https://github.com/rafhael100/imagine-crm/issues/259) continua NO-GO. Merge somente DEV após quatro workflows success na HEAD exata e diff/base checados.
