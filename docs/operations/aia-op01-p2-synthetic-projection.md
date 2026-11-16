# CRM OP-01 P2 — primeira projeção positiva de campos mínimos em PostgreSQL16 descartável

**16/11/2026 — SOMENTE CI/DB fictício; NO-GO / NO-MERGE / NO-DEPLOY / SEM DADOS REAIS.**

A primeira fase P0 ([CRM #629](https://github.com/rafhael100/imagine-crm/pull/629)) ligou um adaptador inerte ao `TenantAccessBoundary` e ao `DenyByDefaultAuthorizer` **reais**, sem conceder autorização. O novo recorte P2 responde a outra pergunta: *uma role de PostgreSQL 16 realmente restrita consegue projetar apenas `ref` e `stage`, inclusive em duas empresas com o mesmo identificador opaco de lead, sem expor campos de contato?*

O componente `Op01SyntheticLeadProjectionLab` roda **apenas** em `GITHUB_ACTIONS`, repositório correto, `APP_ENV=testing`, `DB_DATABASE=imagine_crm_ci`, `DB_HOST=127.0.0.1` e flag específica. Não há binding no container, rota, comando, migration produtiva ou cliente HTTP. A role `crm_aia_leadreader_p2_ci` não é superuser, não bypassa RLS e recebe apenas `CONNECT` no banco, `USAGE` no schema e `SELECT (tenant_ref,ref,stage)` na tabela temporária. PostgreSQL aplica `ENABLE/FORCE ROW LEVEL SECURITY` e isolamento por `app.tenant_id` **somente como segunda barreira; este marcador não autentica nenhuma pessoa**.

### Provas reais de CI de banco

- **P01 sintético:** lê `ref` e `stage` de um lead de teste na empresa ALFA.
- **P02 sintético:** a ordem `[stage,ref]` é aceita e a projeção final permanece canônica `[ref,stage]`.
- Campos isolados `ref` e `stage` funcionam; `contact_email`, `secret_note` e `SELECT *` são recusados por privilégio de coluna.
- Lead exclusivo da empresa BETA não pode ser lido sob o tenant ALFA. Referência comum em ambas as empresas retorna apenas a linha do escopo atribuído pelo backend fictício.
- Nega campos repetidos, ausentes, desconhecidos, listas não canônicas, campos não string e referência malformada.
- Nega perda de RLS, concessão adicional de SELECT a campo confidencial, privilégio incorreto, marcadores herdados em conexão reutilizada e tabela indisponível.
- A transação não deixa `app.tenant_id` herdado após a projeção; a role e a tabela são excluídas ao término.

**Limites:** apesar de poder retornar uma projeção positiva em laboratório, a classe não usa sessão autenticada, membership, ACL, consentimento, criptografia de serviço, nonce ou o ERP/CRM operacional. Ela **NÃO** está ligada ao adaptador P0 e **não** representa um `ALLOW` do CRM. Os campos `ref` e `stage` permanecem candidatos, não esquema físico aprovado. A liberação real exige parecer independente CORE/SEG/IAM/DBA/LGPD, os 14 gates, 7 decisões e 13 cenários da [PR #57](https://github.com/rafhael100/imagine-crm/pull/57), domínio da credencial/mTLS e política transacional de revogação.

**Próximo recorte sugerido:** preparar o contrato versionado de entrada/saída e as regras de registro minimizado no laboratório, sem integrar uma leitura real até homologação.
