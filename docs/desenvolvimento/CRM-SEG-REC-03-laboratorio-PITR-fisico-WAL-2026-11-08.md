# CRM Imagine — SEG-REC-03 — Ensaio físico PostgreSQL 16, WAL e recuperação até ponto nomeado

**Data:** 08/11/2026. **Escopo autorizado pela Direção:** ensaio de recuperação **somente com dois clusters PostgreSQL descartáveis e dados sintéticos** em GitHub-hosted runner; branch filha/PR DRAFT. Sem merge, VPS, credenciais reais, SQL persistente, clientes, mudança na `main` ou DEV, `ALLOW` e sem liberar gates.

## 1. Genealogia e isolamento

- Base do recorte: HEAD `1a7e87351bcde7fc9461fbba55f3c931073c8d9b` do [PR #29](https://github.com/rafhael100/imagine-crm/pull/29) SEG-REC-02. Este herda #28, #26 e #23. **O diff contra a DEV inclui essas dependências em DRAFT; NÃO autorizar merge agregado**.
- Branch: `dev/seg-rec-03-disposable-pitr-2026-11-08`; PR destino `dev/fast-track-crm-2026-11-04` exclusivamente para disparar CI no GitHub, em **DRAFT / NO MERGE**.
- Workflow novo `.github/workflows/ci-seg-rec-03-pitr.yml`: executa somente evento `pull_request` para DEV e somente PRs originados no próprio repositório; `permissions: contents: read`; runner GitHub-hosted `ubuntu-24.04`; sem credenciais de ambiente ou secrets de produção.
- Script novo `scripts/ci/seg-rec-03-physical-pitr-lab.sh`: valida contexto/repositório/ref e recusa variáveis externas de conexão PG. Cria **recursos Docker específicos da execução** (duas redes internas isoladas, duas instâncias PostgreSQL 16 separadas, um volume fonte, um volume backup físico, um volume WAL). Nenhuma porta é publicada; nenhum bind mount da VPS/host; limpeza via trap de recursos criados pelo próprio job, sem `docker system prune` ou `DROP`.
- **Atenção:** volumes Docker são efêmeros pela política de runner hospedado e removidos pelo script; esse laboratório não é destino de retenção nem backup externo durável.

## 2. Passos exatos previstos (não presumir sucesso)

1. Iniciar PostgreSQL 16 origem em rede interna, com `archive_mode=on`, `wal_level=replica`, `archive_command` apontando para o volume WAL criado dentro do job. Base `seg_rec_03` e senha são **constantes artificiais exclusivas** do laboratório.
2. Criar tabela `seg_rec_03_proof` e um registro `before_backup`. Coletar **backup físico** em volume independente via `pg_basebackup -Fp -Xs`, durante o funcionamento da origem.
3. Inserir `before_cut`, criar `pg_create_restore_point('seg_rec_03_cut')`, inserir `after_cut` e forçar arquivamento com `pg_switch_wal()`; aguardar confirmação de incremento do contador `pg_stat_archiver.archived_count`.
4. Simular perda de disponibilidade da origem com `docker stop` **somente do contêiner do job**, sem apagar sua base nem acessar outros clusters.
5. Montar o backup físico em outro volume já preparado e iniciar PostgreSQL 16 destino em **outra rede interna**, com arquivo `recovery.signal`, `restore_command='cp /wal/%f %p'`, `recovery_target_name='seg_rec_03_cut'`, `recovery_target_action='promote'`. O volume WAL fica read-only no destino.
6. Exigir como prova final que origem esteja parada, destino tenha saído do recovery (`pg_is_in_recovery()=false`), os dois eventos anteriores estejam presentes **e `after_cut` ausente**, sinalizando recuperação física até ponto escolhido e não simples retorno ao estado mais recente.
7. Registrar duração ilustrativa de inicialização/validação apenas no job. **Não reportar como RTO/RPO real**: não há carga de produção, anexos, rede de cliente, filas, replicação externa ou incidência real.

## 3. Critérios, evidências e limites

- **CI PASS do workflow SEG-REC-03** requer confirmação dos passos 1–6 na **HEAD exata**, com logs sanitizados de operação. O F0 e PostgreSQL/RLS preexistentes devem continuar verdes na mesma HEAD.
- É um ensaio **real de backup físico + WAL/PITR dentro de um laboratório descartável**, não restauração simulada de linhas SQL. Entretanto, **não testa ainda** pgBackRest (candidato de produção), Object Storage R2/Leaseweb, cópias externas, retenção/imunidade contra exclusão, replicação de anexos, custo, credenciais reais, criptografia fim-a-fim, RLS pós-restore de app multiempresa, integridade interserviços, processo de incidente nem alta disponibilidade.
- O teste não está configurado para produção, não concede permissão de migration, não executa `migrate:fresh`/`db:wipe`/`DROP`, não abre porta para host. **Parar/remover somente os contêineres criados pelo próprio job é parte autorizada do ciclo descartável, não uma operação na DEV/VPS.**
- O CI público de PR é sujeito a alterações de código por contribuidores; por isso **não expor secrets, credenciais de produção ou runners próprios** em seu contexto. Antes de uso operacional é exigida revisão SEG/DBA separada, controles de permissões de branch, isolamento e garantia de destino.
- A recuperação `pg_basebackup + WAL` comprova fundamentos do PITR conforme [PostgreSQL 16](https://www.postgresql.org/docs/16/continuous-archiving.html) e [parâmetros de recuperação](https://www.postgresql.org/docs/16/runtime-config-wal.html); **não homologa ferramenta definitiva** nem as metas históricas de RPO 15 min/RTO 4 h, ainda preliminares.

## 4. Situação decisória e próximo gate

[Issue #27 — SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27) permanece **aberta** e o revisor competente e independente continua **não designado**. O SEG-REC-01 está documentalmente preparado, SEG-REC-02 tem testes mock CI verdes, mas os riscos P0 de comando destrutivo fora da CI, privilégio de banco durável e isolamento futuro **não são encerrados** por este laboratório. Merge de cada PR e configuração de VPS/produção requerem autorização própria.

**Evidências de execução:** a serem anexadas após os checks; não deduzir sucesso antes dos logs da HEAD exata.
