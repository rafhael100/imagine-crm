# F3-46 — correção de versão e paridade do contrato de RESPOSTA CRM ↔ Agents

**Data:** 10/11/2026. **Origem:** inspeção do Agents EVID-10, PR #159, commit de merge `4a03adf5f91e760950adaa3239fb2c8dba05da6d`.

## Defeito localizado

O contrato de **pedido** sintético do Agents é `SYNTHETIC_CRM_AIA_READ_V1`; o de **resposta pública** é `SYNTHETIC_CRM_AIA_PUBLIC_V1`. O CRM F3-44 emitia incorretamente o identificador de pedido no envelope de resposta. A suíte F3-45 não detectava o erro porque conferia somente a requisição do commit anterior, anterior à introdução do schema de saída.

## Correção técnica

- `AiaReadOnlyMockBoundary::inspect` devolve agora `contract=SYNTHETIC_CRM_AIA_PUBLIC_V1`. Conserva `state=DENIED_OR_NOT_FOUND`, `projection=null`, `real_authorization=false`, `external_calls=0` em **todas** as entradas.
- PHPUnit do CRM e script de compatibilidade atualizados para conferir a resposta; nenhuma aceitação positiva foi adicionada.
- O workflow faz checkout do Agents em **SHA imutável que contém ambos os schemas**, `4a03adf5f91e760950adaa3239fb2c8dba05da6d`.
- A prova CI verifica `additionalProperties=false`, cinco chaves obrigatórias, estados de resposta enumerados, projeção nula em negação, limite e nomes de `ref/stage`, ausência de autorização real e `external_calls=0`, além da paridade prévia de requisições (PHP 8.3/8.4).
- A documentação histórica de F3-44/F3-45 contém errata explícita.

## Gates e limites

O contrato de resposta é **sintético**. Mesmo com CI verde, nenhuma autorização real é concedida nem existe endpoint do CRM; nenhuma leitura de lead, SQL, HTTP de serviço, dados de cliente, rede de produção, credencial, grant, deploy ou liberação comercial. PR #57, SEG/DBA #27/#175 e R01 G01..G11 seguem pendentes. Este pacote pode ser integrado somente à DEV após revisão de diff e CI verde na HEAD exata, preservando `main`/VPS.
