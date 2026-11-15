# CRM–Agents F240 — identidade de serviço: candidato e critérios de homologação

**Estado:** DRAFT / NO-GO / NO-MERGE / NO-DEPLOY. **Data:** 15/11/2026.

## Escopo entregue e verificável

A assinatura Ed25519 do Agents continua verificada pela implementação original
fixada no SHA `fe79e7e9a55e4d6728ab42c2ef578e40d9bd68ba`. A F240 substitui
**apenas a fonte de leitura da chave pública**: o contrato
`AiaTrustedPublicKeySource` permite trocar o keyring de F232b pelo
`AiaPgRestrictedKeyringLab`, que usa a role
`crm_aia_key_verify_ci` em PostgreSQL 16 descartável.

A role é não proprietária, `NOINHERIT`, `NOSUPERUSER`,
`NOBYPASSRLS`, `NOCREATEDB`, `NOCREATEROLE` e tem somente
`SELECT` sobre a tabela temporária de chaves **públicas**. A tabela
exige `ENABLE/FORCE ROW LEVEL SECURITY`; o filtro pelo tenant vale
somente na transação local da consulta e é limpo ao final.
O verificador só aceita chave canônica Ed25519 de 32 bytes, habilitada,
não revogada, com revisão positiva, emissor e audiência esperados.
Erro de conexão, role incorreta, ausência de política, tabela ou chave
produzem lista vazia, **sem fallback privilegiado**.

A CI usa **duas empresas sintéticas**, chaves diferentes com mesmo
`key_id`, consulta seletiva por tenant, assinatura original Ed25519,
nonce PostgreSQL atômico, bloqueio de replay, revogação, rotação,
assinatura da chave antiga rejeitada, chave malformada, role tentando
`INSERT/UPDATE/DELETE`, tentativa de ler IAM/memberships e perda da
tabela de keyring. O único emissor/rotacionador de chaves nessa
prova ainda é a conta administrativa **do teste**. Nenhuma chave privada
é escrita na tabela de verificação; todas as seed keys são públicas
e deterministicamente geradas para **teste exclusivo**.

## Fluxo candidato de serviço (não operacional)

1. O serviço Agents assina **bytes canônicos** do pedido e das claims.
2. A camada de entrada CRM deverá derivar a empresa confiável de um
   vínculo de serviço gerenciado pelo servidor, não de header, cookie
   ou `tenant_ref` escolhido pelo cliente. **Esse passo ainda não existe
   operacionalmente**.
3. O keyring F240, com role PG somente `SELECT`, obtém a chave pública
   pertencente à empresa e `key_id`. O `key_id` é apenas seletor,
   nunca autorização.
4. O verificador Agents original confere assinatura, digest, janela
   temporal, emissor, audiência, operação e tenant.
5. O nonce PostgreSQL16 é reivindicado atomicamente somente após
   assinatura válida. Uma prova duplicada é rejeitada entre conexões.
6. Testes anteriores F237–F239 tratam apenas delegação humana,
   sessão emissora, vínculo da empresa, revogação e isolamento
   no laboratório, **não autorização a acessar registros**.
7. Resposta pública permanece sempre `DENIED_OR_NOT_FOUND`,
   `projection=null`, `real_authorization=false`,
   `external_calls=0`.

## Gates obrigatórios antes de liberar operações reais

| Gate | Critério de aprovação objetiva | Estado F240 |
|---|---|---|
| Fonte de tenant | Empresa derivada de credencial de serviço controlada pelo CRM, não de header, claim ou marcador SQL manipulável | **PENDENTE** |
| Provisionamento | Cadastro, dual control e trilha de aprovação para emissores e chave pública, sem segredo privado no banco CRM | **PENDENTE** |
| Rotação/revogação | Procedimento seguro de chaves sobrepostas, rollback e revogação imediata entre processos | **PARCIAL em laboratório** |
| Permissões PG | Role com acesso apenas a chaves públicas e RLS FORCE; contas operacionais provisionadas e inspecionadas | **PARCIAL em CI** |
| Transporte | TLS/mTLS, identidade do peer, anti-replay e limites por serviço verificados em rede real isolada | **PENDENTE** |
| IAM humano | MFA recente, sessão original, revogação, controle de delegações e consentimento revisado | **PARCIAL em laboratório** |
| Políticas empresariais | RLS/RBAC real por empresa, usuário, recurso, finalidade e campo | **PENDENTE** |
| Auditoria e LGPD | Eventos sem PII, retenção, consultas e trilhas auditáveis, testes de integridade e incidentes | **PENDENTE** |
| Concorrrência | Corridas de rotação/revogação versus tráfego, falhas e restaurações sobre múltiplos processos | **PENDENTE** |
| Aprovação independente | Pareceres SEG/CORE/IAM/DBA/LGPD e cenários CRM #57 concluídos | **PENDENTE** |

**Risco residual a não ocultar:** `current_setting('app.tenant_id')`
não é uma credencial: qualquer código que controle a conexão pode tentar
definir o marcador. A role exclusiva do keyring e a policy RLS evitam
leitura acidental fora da empresa configurada pela transação, mas
**não substituem a identidade operacional confiável do serviço**.
A verificação de nonce existente em F232 também usa a conexão
administrativa da CI; a F240 não promove a integração a produção.

## Evidências

- Suite dedicada em `tests/Feature/AiaPostgresEd25519NonceCandidateTest.php`,
  método `test_f240_restricted_service_public_keys_rotate_revoke_and_fail_closed`.
- Workflow `.github/workflows/ci-postgresql-rls.yml`, marcador
  `AIA_F240_KEYRING_LAB=true`, checkout Agents com SHA pinada.
- Controle geral CRM–Agents: [PR #57](https://github.com/rafhael100/imagine-crm/pull/57):
  14 gates, sete revisões independentes, 13 cenários operacionais
  previamente registrados; não tratar esse quadro como aprovado.

**Sem deploy, endpoints de serviço, segredos, efeitos, novas despesas
ou acesso real a leads.** Manter a PR em DRAFT e `main`/DEV inalteradas.
