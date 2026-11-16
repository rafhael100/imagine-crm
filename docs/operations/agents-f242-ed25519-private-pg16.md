# CRM F242 — assinatura Ed25519 Agents + nonce único PostgreSQL16 no aplicativo privado

**15/11/2026 — DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / ZERO NOVOS GASTOS**

## Objetivo

Executar um teste em **Laravel funcional CRM #614** com a implementação **original Agents F231 Ed25519** (código fixado no SHA `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5`) e o `AiaPgNonceCandidateLedger` **já existente** na árvore funcional do CRM. Não copia classe de validação, não adiciona transporte e não cria novas rotas.

## Prova técnica

A classe `AgentsPrivatePgNonceTest` cria temporariamente a tabela `aia_lab_nonce_atomic_probe` no serviço PostgreSQL 16 descartável do próprio GitHub Actions, com chave única `(issuer,audience,nonce)`. Antes de qualquer SQL, a suíte exige flags explícitas, `APP_ENV=testing`, repositório correto, DB `imagine_crm_ci`, host local e usuário `postgres`. Uma conexão secundária utiliza PID físico independente.

- Uma assinatura Ed25519 válida é verificada pelo código Agents original e reivindica o nonce na primeira conexão.
- Um segundo verificador PHP recém-criado ainda reconhece criptograficamente a mesma assinatura, mas a segunda conexão PostgreSQL rejeita o nonce já registrado.
- Tentativa de alterar tenant não pode reutilizar o identificador, pois a unicidade cobre `issuer/audience/nonce` independentemente de tenant.
- Assinatura adulterada e expiração são recusadas **antes** de registrar nonce; uma prova válida subsequente pode consumi-lo uma única vez.
- Indisponibilidade da tabela retorna negativa, sem fallback para permissões.
- Em todos os casos a resposta mock pública permanece `DENIED_OR_NOT_FOUND`, sem projeção, external calls ou autorização real.

O workflow `ci-crm-aia-f242-ed25519-pg.yml` executa F242 e o teste PostgreSQL atômico antigo existente, preservando-o. O banco é criado e destruído pelo runner; não existe migration operacional, deploy, custo adicional nem acesso a registros de clientes.

## Pendências para implementação real

Ainda faltam: identidade de peer provisionada com chave privada sob gestão segura, vínculo de empresa **derivado de dados confiáveis CRM**, autorização IAM/ACL/RLS por principal/recurso/campo, revogação e rotação, comportamento em falhas/concorrência de vários nós, mTLS, consentimento LGPD, auditoria minimizada e aprovação independente SEG/DBA/CORE/IAM. A árvore de laboratório CRM #602 não deve ser fundida diretamente ao aplicativo privado #614.

**CRM #57:** 14 gates, 7 decisões independentes e 13 cenários operacionais permanecem pendentes de aceite. Aprovação CI aqui significa apenas prova sintética de banco e assinatura, não publicação ou GO.
