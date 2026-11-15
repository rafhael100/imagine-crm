# CRM-IAM-PG-HTTP-02 — Sessão MFA validada + membership real no PostgreSQL16 via HTTP Laravel

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN PÚBLICO / NO-ALLOW / NO-CHARGE**

## Evolução após CRM-IAM-HTTP-01

A PR #597 prova a sequência de senha, MFA, sessão, negativa de negócio e logout no HTTP Laravel com identidades inventadas. Mas uma *membership* fabricada em memória não demonstra que a ligação entre **a identidade real da sessão e uma associação persistida no PostgreSQL** foi preservada. Esta entrega combina o IAM SQL existente, o `GuardPrincipalSource` e a `PostgresActiveMembershipStore` real em uma transação HTTP de laboratório.

## Implementação e evidências

`tests/Feature/CrmIamPostgresHttpMembershipTest.php` só executa a prova em **PostgreSQL16 de CI** com roles separadas `crm_iam_ci` e `crm_app_ci`; em SQLite os dois testes são marcados como ignorados explicitamente.

- Cria identidades fictícias pelo trait `SyntheticIam` (senha/TOTP e sessões SQL); duas organizações fictícias C e D, com membership A→C **ativa, versão 2**, e B→D **ativa, versão 3**. Dados são descartados pelo cleanup do PHPUnit.
- Registra **somente no processo de teste**, nunca no código de rotas publicado, `GET /_ci/iam/pg-membership/{tenant}`. A rota passa por `GuardPrincipalSource->withinCurrent`, que comprova a sessão do usuário com MFA, e depois por `PostgresActiveMembershipStore->within`, que abre transação dedicada, **FORCE RLS** e revê exatamente a associação do principal autenticado com aquele tenant.
- O resultado contém apenas IDs de fixture e versão da membership; os indicadores `is_business_authorized=false`, `can_read_leads=false` e `can_charge=false` ficam negados. Nenhuma política de ALLOW, lead, cliente, contato, crédito, contrato ou valor é retornado.
- Testa ausência de sessão → **401**, usuário A em C → **200 de metadados fictícios**, tentativa de A em D (associação exclusiva de B) → **403**. Em seguida a administração da fixture marca A/C como **revoked/version 3** e a próxima requisição HTTP deve receber **403** imediatamente, mesmo que a sessão MFA continue autenticada.
- Outro teste tenta subverter o escopo com `X-Tenant-Id`, `X-Identity-Id`, `X-Principal-Ref`, bearer e query `tenant=D`. Continua autenticando apenas A, negando D. Em todas as consultas, transação PostgreSQL é encerrada e a conexão fora de escopo retorna **zero linhas**.
- A matriz de regressão foi ampliada para **cinco casos** PostgreSQL: além de membership ativa/suspensa e spoofing, simula (i) revogação explícita da sessão IAM no registro SQL, com próxima chamada passando de 200 para **401**, (ii) incremento da versão de credenciais com membership ainda ativa, que também passa a **401**, e (iii) o mesmo principal A autorizado a duas empresas ativas, alternando `C→D→C→D` e obtendo sempre a membership exata, sem contexto herdado ou acesso a leads. Nenhum desses retornos constitui ALLOW de negócio.
- Os testes dependem da configuração do GitHub Actions PostgreSQL/RLS já existente; nenhum controller, migration, endpoint público, schema, role, flags ou secret é alterado.

## Limite de autorização

**HTTP 200 de metadata sintética NÃO é uma permissão comercial.** Esse teste permite ver apenas a associação fictícia usada no próprio ensaio; qualquer acesso a leads, relatórios, atividades, automações, faturamento e dados de pessoa deve continuar passando pelo `TenantAccessBoundary + DenyByDefaultAuthorizer`, que ainda nega tudo. As sessões reais de clientes continuam sem provisionamento, OTP, Redis operacionais ou publicação.

Critérios P0 pendentes: serviço IAM em rede privada e com persistência, aprovação dos papéis RLS, roles de usuário/empresa, auditoria imutável, MFA e recuperação com segredos reais protegidos, consentimento/privacidade e homologação de TLS/VPS. Gates [#27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), [#259 CORE/LGPD](https://github.com/rafhael100/imagine-crm/issues/259), [#557 GO](https://github.com/rafhael100/imagine-crm/issues/557) continuam abertos.
