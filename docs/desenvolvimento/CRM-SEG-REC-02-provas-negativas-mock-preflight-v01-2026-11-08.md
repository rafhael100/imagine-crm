# CRM Imagine — SEG-REC-02 v0.1 — Provas negativas de barreiras de CI (mock/offline)

**Data:** 08/11/2026. **Natureza:** programação e testes sintéticos **não destrutivos**, autorizados apenas em branch filha isolada. **Nenhum GO de merge**, código produtivo, SQL persistente, teste de restore, VPS, produção ou concessão de `ALLOW`.

## 1. Contexto/escopo

- Diretriz [SEG-REC-01](https://github.com/rafhael100/imagine-crm/blob/docs/func-p0-01-s01-r01-r02-2026-11-06/docs/desenvolvimento/CRM-SEG-REC-01-matriz-roles-operacoes-backup-recuperacao-v01-2026-11-08.md): matriz de papéis, operações proibidas, backups e recuperação; proposta documental não homologada.
- [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27): revisão independente SEG/DBA **ainda pendente**, incluindo os riscos P0 de operações destrutivas em banco persistente.
- Branch `dev/seg-rec-02-mock-preflight-2026-11-08`, derivada da HEAD do [PR #28](https://github.com/rafhael100/imagine-crm/pull/28) `cf3ef4a7b441ed00cf50c8f20dbbe66c21874439`. Consequentemente, também contém dependências de [PR #26](https://github.com/rafhael100/imagine-crm/pull/26) e [PR #23](https://github.com/rafhael100/imagine-crm/pull/23). **O diff acumulado NÃO autoriza integrar esses PRs**.

## 2. Arquivos novos e efeito exato

1. `tests/Fixtures/SegRec02FakeCiCli.php`: executável **FALSO** de linha de comando, copiado sob os nomes `docker` e `psql` **somente em diretório temporário privado de PHPUnit**. Emula um cluster PostgreSQL conhecido, contêiner `postgres:16` e falhas sintéticas: falta/duplicidade de contêiner, imagem, bind, conexão, role, nome e `system_identifier`. Não se conecta ao Docker nem ao PostgreSQL; não executa DDL.
2. `tests/Unit/SegRec02MockPreflightTest.php`: injeta os executáveis falsos no `PATH` **apenas do subprocesso de teste**, com configuração e credenciais fictícias; exercita o script `scripts/ci/verify-ephemeral-postgres.php --verify` pré-existente, sem editá-lo. Verifica que o cenário simulado esperado usa somente `docker ps/inspect/exec` e `psql -c SELECT`; os cenários negativos encerram em `exit 42`. As ações simuladas são registradas em arquivo temporário e removidas após cada teste.

**Cobertura do SEG-REC-02:** 15 cenários negativos de infraestrutura, 2 de perfis/contextos proibidos, 1 cenário sintético positivo e inspeção das chamadas simuladas para evitar DDL. Nenhuma situação real de banco inacessível, papel DDL ou RLS produtivo é provada pelos mocks.

## 3. Barreiras mantidas e risco residual

- Os scripts operacionais `scripts/ci/verify-ephemeral-postgres.php`, o workflow PostgreSQL e o código da aplicação **não foram alterados** neste recorte. Testes negativos atuais complementam o CI executado sobre os PRs anteriores, não substituem a revisão de runtime, roles/ACL reais ou rede.
- Testes não chamam `migrate:fresh`, `db:wipe`, `migrate:refresh`, DROP ou qualquer SQL de escrita. Não contêm credenciais reais nem dados de clientes.
- **Um binário falso pode alegar uma identidade de cluster falsa.** O teste serve exclusivamente para provar a reação lógica do verificador a respostas simuladas. Um usuário com controle sobre PATH, processo ou runner pode falsificar ferramentas: o preflight do CI **não é garantia absoluta contra execução destrutiva**. As barreiras mais fortes são isolamento físico do CI, credenciais DB e controles de change/recovery, sujeitos à análise de SEG/DBA.
- `postgres` em CI continua superuser apenas do contêiner sintético, não da futura VPS ou banco comercial.
- Sem novos grants/roles/DDL/ALTER/SQL de runtime, `ALLOW`, API comercial ou mudança no banco. Nenhuma prova PITR/restore, nenhuma validação de dados reais.

## 4. Critério de validação do recorte

- Suite `Tests\\Unit\\SegRec02MockPreflightTest` deve passar tanto no workflow F0 quanto no PostgreSQL/RLS do GitHub Actions na **HEAD exata** do PR, com dados falsos e sem acesso DB nas novas provas.
- Verificar código de saída 42 nos 17 cenários negativos, chamadas simuladas somente read-only e remoção de diretórios de ferramentas falsas.
- Falhas exigem correção **somente nesta branch**, nova CI e documentação das evidências. Sucesso em PR não equivale a push real DEV, evidência de production safety, nem homologação SEG/DBA.

## 5. Próximos gates, sem autorização de execução

- **SEG-REC-03**: ensaio de backup base+WAL/PITR em PostgreSQL novo, completamente descartável, com dataset sintético e aprovação **distinta**. O presente recorte não efetua backups nem restaurações.
- **SEG-REC-04**: parecer competente e independente quando exigido, matriz de risco P0/P1, definição de roles/ACLs/instância e decisão explícita da Direção sobre integrações individuais.
- Todos os PRs #23/#24/#25/#26/#28 e o novo PR desta atividade permanecem DRAFT sem merge; DEV, `main`, VPS, dados reais e todos os gates continuam preservados.
