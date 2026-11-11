# F3-45 — prova executável de compatibilidade CRM ↔ Imagine AI Agents (OP-01)

**Data:** 10/11/2026. **Natureza:** teste de contrato entre códigos reais de dois repositórios no laboratório CI. Não é uma integração funcional, nem autorização para ler leads.

## O que está realmente testado

- O workflow `.github/workflows/ci-crm-aia-synthetic-parity.yml` obtém o **código do CRM da branch do PR** e o código **real do Agents** em commit fixado `562896763f900a8a264ea46aa35e2aeacd5e0903` (merge de F1-12 / PR Agents #106).
- O script PHP carrega `MockError`, `TenantContext` e `MockCrmReadRequest` do Agents, mais `AiaReadOnlyMockBoundary` do CRM; verifica o schema JSON de origem e compara ambos validadores com casos positivos de **formato**, negativos de forma/campos/roles e contexto tenant divergente.
- Mesmo uma requisição válida no Agents ou um contexto sintético igual não libera leitura no CRM: toda inspeção devolve `DENIED_OR_NOT_FOUND`, `projection=null`, `real_authorization=false` e `external_calls=0`.
- Matriz PHP 8.3/8.4, sem Composer ou migrations no workflow específico. O checkout dos fontes é a única comunicação externa do runner, usando permissão read-only de GitHub e sem segredos de cliente.
- O commit do Agents está fixado para evitar drift silencioso; revisão e atualização do SHA serão necessárias para comparar revisões futuras. Este teste **não** afirma compatibilidade com mudanças posteriores do Agents.

## Distinções de evidência

- A CI F3-45 só demonstra **paridade sintática** dos formatos no conjunto finito de casos e negativa uniforme; não realiza autenticação real, ACL, rate-limit, mTLS, tráfego entre serviços implantados, escopo de campo real, tenant verificado, auditoria transacional ou RLS.
- Os PRs CRM #209/#210/#211/#215 são outra trilha sintética Portal ↔ CRM, com dependências empilhadas e sem merge. Não os considerar homologados por esta CI.
- Regras AIA OP-01 e parecer SEG/DBA #27, PR #57 e PR #175 seguem pendentes. `main`, VPS e produção permanecem intocados.

## Critérios para integração desta prova à DEV

Revisão de diff, confirmar `behind_by=0` em relação à HEAD DEV, workflow F0 e PostgreSQL/RLS verdes na SHA exata, mais a nova CI cruzada PHP 8.3/8.4 verde. Após aprovação técnica, merge **apenas na DEV**, nunca `main`.

**NO-GO** para API real, leitura de clientes, segredos, autorização `ALLOW`, banco persistente, deploy, VPS, custos e fechamento automático dos gates CORE/SEG/AUT/LGPD.
