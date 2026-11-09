# CRM Imagine — F1-01: esquema IAM candidato e isolamento PostgreSQL RLS (evidências CI)

**Data:** 04/11/2026.
**Branch:** `dev/fast-track-crm-2026-11-04`; [PR #2 em rascunho](https://github.com/rafhael100/imagine-crm/pull/2).
**Estado:** implementação inicial **EXPERIMENTAL de desenvolvimento isolado**, **NÃO** homologada para acesso real nem produção.
**Autorização:** [DEC-FASTTRACK-01](../planejamento/CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md).
**Dependências preservadas:** CORE01-D04/D05/D06/D07, CORE02-D04..D10, CORE03/04/05/06/07/08/09, SEG-01/SEG-02, INT e Segurança, além dos demais P0 e gates de liberação.

## 1. Código executado em ambiente temporário

- [Migration experimental](../../database/migrations/2026_11_04_000001_create_crm_identity_organizations_memberships.php) com `crm_global_identities`, `crm_organizations` e `crm_memberships`; referências por UUID, FKs e unicidade lógica `organization_id × global_identity_id`. Não inclui CNPJ, planos, dados comerciais ou dados reais.
- RLS ativado com `ENABLE ROW LEVEL SECURITY` e `FORCE ROW LEVEL SECURITY` apenas para `crm_memberships`, com política de leitura/escrita `USING`/`WITH CHECK` comparando `organization_id` ao contexto de transação.
- PostgreSQL 16 efêmero criado por [GitHub Actions](../../.github/workflows/ci-postgresql-rls.yml); migration aplicada **somente nesse contêiner descartável**, usando role de migração separada. Role de consulta **`crm_app_ci`** foi criada apenas no runner, sem superuser, `BYPASSRLS`, ownership da tabela ou acesso direto ao catálogo global.
- [Testes experimentais](../../tests/Feature/PostgresMembershipRlsTest.php) com organizações A/B e identidades fictícias, sem informações de clientes.

## 2. Evidências de execução

- [Workflow PostgreSQL RLS — execução 41899182886](https://github.com/rafhael100/imagine-crm/actions/runs/41899182886): **SUCESSO**, **6 testes PostgreSQL RLS, 11 asserções** no job específico; suíte completa nesse runner: **14 testes, 21 asserções, 0 falhas**.
- [Execução paralela 41899193429](https://github.com/rafhael100/imagine-crm/actions/runs/41899193429): **SUCESSO**.
- Testes cobrem: (1) negar leitura sem tenant; (2) A↔B e ausência de contexto residual entre transações; (3) negar INSERT cruzado; (4) negar UPDATE cruzado; (5) proibir leitura global pelo role aplicativo; (6) comprovar que o role não é superuser/BYPASSRLS ou dono da tabela.
- [CI F0 com SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/41899182871) também passou; **não é evidência de RLS**. O teste RLS é separado no job PostgreSQL.
- `composer.lock` agora está versionado na branch (Laravel **13.40.0** observado); o workflow **temporário de escrita** para gerar lockfile foi removido após concluir essa tarefa. Workflows restantes usam `permissions: contents: read`.

## 3. Lacunas críticas de segurança — NÃO CONSIDERAR RESOLVIDAS

1. **Identidade/autenticação/mandato não implementados.** `PrincipalReference`, `MembershipSnapshot` e `TenantContext` são objetos que qualquer código pode construir, não prova autenticada. `DenyByDefaultAuthorizer` permanece negando operações de negócio.
2. **O setting `app.tenant_id` NÃO é prova de identidade.** Como roles PostgreSQL podem, em geral, alterar parâmetros personalizados, uma aplicação que aceite SQL arbitrário ou tenant não validado pode contornar a intenção da política. A futura camada Laravel deverá resolver/autenticar membership, autorização, escopo e entitlement, ligar o contexto à operação/transação e impedir caminhos que aceitem input arbitrário ou vazem SQL. **RLS é defesa adicional, não autenticação.**
3. **Somente uma família tenant-bound está protegida nesta prova.** Entidades globais, suporte, exportações, anexos, busca, backups, jobs, agentes/IA e integração Site↔CRM necessitam contratos e testes próprios.
4. **O role de migração da CI é privilegiado** e por isso não demonstra políticas aplicáveis a manutenção/backup/suporte. Em produção serão necessários papéis separados, controle de schema/owner, segredos e revisão independente.
5. **Regra final de estados de membership, perfis/negações P1..P4, retenção e exclusão** não foi decidida por esta migration. Os nomes de tabela/chaves e política podem mudar durante revisão; não migrar banco real com a versão experimental.
6. **CI verde não fecha P0 nem libera deploy.** Não existe login do CRM, contas reais, endpoint de negócios, autorização por campo ou RLS integral auditada.

## 4. Próximos incrementos coordenados

- **F1-02 — segurança IAM:** vincular identidade autenticada, organização e membership com decisão Laravel por recurso/ação/campo, política explícita de escopos e recusa segura; preservar papéis P1..P4 como decisão técnica em aberto até revisão.
- **F1-03 — RLS operacional:** parametrização exclusivamente transacional através de caminho autenticado, roles mínimas, testes com reutilização de conexão, SQL injetável/entrada indevida, workers, revogação e ações negativas com provas.
- **F1-04 — integridade de domínio:** revisar unicidade histórica, estados/versionamento, constraints de tenant, auditoria, LGPD e fontes de autoridade; não inferir CNPJ/planos/assentos de migração simplificada.
- **Integrações posteriores:** somente depois dos contratos CORE/SEG/INT para endpoints de DAT, Portal/Sites, ATD, VEN, AGD, AUT, IA, FIN/CON, importação e anexos.

**Situação:** desenvolvimento e testes sintéticos avançaram na branch; **os 67 IDs técnicos CORE e todos os gates transversais permanecem abertos quanto ao fechamento e homologação**. Nenhum merge para `main`, conexão com dados reais, alteração de produção, compra, cobrança ou implantação foi realizada.
