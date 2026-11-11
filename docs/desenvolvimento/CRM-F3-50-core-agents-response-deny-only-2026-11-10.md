# CRM F3-50 — componente Core para inspecionar a resposta pública do Imagine Agentes de IA

**Data:** 10/11/2026. **Âmbito:** código de domínio PHP puro + testes de integração sintética, sem disponibilização de API.

## Valor técnico

O F3-44/F3-47 validava um contrato OP-01 de resposta de IA apenas no laboratório e no script da CI. Esta etapa acrescenta dois componentes **reutilizáveis dentro do Core do CRM**:

- `app/Core/Leads/AgentsReadResponseInspector.php`: recebe resposta não confiável e a seleção de campos previamente estabelecida; verifica contrato público exato `SYNTHETIC_CRM_AIA_PUBLIC_V1`, conjunto fechado de cinco propriedades, `real_authorization=false`, `external_calls=0`, negativa com projeção nula e, para respostas de sucesso sintético, apenas `ref/stage` com valores `SYNTHETIC_*`, mesma ordem e limite de dois campos.
- `app/Core/Leads/AgentsReadResponseAssessment.php`: mantém somente `reasonCode` sem expor dados/IDs/tenant/secret. Mesmo quando a forma sintética é compatível, `canReadRealLead`, `canUseCustomerData`, `canGrantAgentAccess`, `canWriteLead`, `canExportLead`, `canEnableRoute` sempre retornam `false`.

O componente distingue `SYNTHETIC_DENIAL_ONLY`, `SYNTHETIC_PROJECTION_UNTRUSTED`, `PUBLIC_REPLY_INVALID` e `PROJECTION_INVALID`, sem tratar nenhuma como concessão real. O motivo é a distinção entre *forma e proveniência confiável*: um mock pode gerar `READ_SYNTHETIC_ONLY`, mas não autentica o serviço ou o solicitante do CRM.

## Testes verificáveis

- `tests/Unit/AgentsReadResponseInspectorTest.php`: sintaxe de projeções sintéticas, negativos de campos, falta/excesso de chaves, ALLOW, versão errada, dados pessoais, coerção de tipo, falsa autorização, sequências de campos malformadas e 512 chaves inesperadas.
- A CI cruzada existente executa o PHP real do Agents (commit imutável já pinado em F3-49) e chama o novo inspector Core para seis respostas reais da fachada sintética. Deve reconhecer duas projeções fictícias e quatro negativas, mantendo todas sem autoridade CRM.
- O workflow faz lint dos dois novos componentes e do PHPUnit em PHP 8.3/8.4; o F0 completa a suíte Laravel/PHPUnit e o PostgreSQL/RLS mantém a regressão de banco.

## Critérios e limites

Somente após revisão integral do diff, F0/PG/RLS e CRM↔Agents CI aprovados no commit exato e `behind_by=0` da DEV, este pacote inerte pode ser integrado **somente à DEV**. A `main`/VPS continuam preservadas.

**Não há** controlador, rota, container binding, chamada ao Agents, credencial, grants, migração, acesso a clientes, payload persistido ou autorização. A prova não preenche OP-01/G01..G11, SEG/DBA #27, CORE/IAM, LGPD, segurança de runtime ou liberação comercial. Nunca interpretar `SYNTHETIC_PROJECTION_UNTRUSTED` como `ALLOW`.
