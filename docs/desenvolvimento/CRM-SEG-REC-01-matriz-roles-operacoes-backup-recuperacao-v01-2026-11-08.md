# CRM Imagine — SEG-REC-01 v0.1 — Matriz de papéis, comandos, backups e recuperação

**Data:** 08/11/2026 (America/Sao_Paulo). **Estado:** DIRETRIZ DOCUMENTAL DE SEGURANÇA, autorizada para detalhamento; **AGUARDA AVALIAÇÃO**, não homologada tecnicamente. **Não é parecer SEG/DBA independente, autorização de código, criação de usuários/roles, SQL, aplicação de ACL, configuração de VPS, merge ou deploy.**

**Vinculação:** [Issue #27 — SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) (HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`), [PR #26](https://github.com/rafhael100/imagine-crm/pull/26) (HEAD `86ec918036e895f99cd6d1d98ca6469021846d2c`), [PR #28](https://github.com/rafhael100/imagine-crm/pull/28) (HEAD `cf3ef4a7b441ed00cf50c8f20dbbe66c21874439`). Todos abertos/DRAFT sem merge; a branch DEV e `main` são preservadas.

**Precedência:** respeitar o planejamento P0-ARQ01-F03/F04 (aprovado somente como base), IN-118 (backup/exportação do cliente, não backup operacional), F1-09 (recuperação apenas sintética do ledger) e o [relatório de avaliação interna SEGDBA-27](CRM-SEGDBA-27-avaliacao-interna-e-plano-de-revisao-recuperacao-2026-11-08.md). Eventuais divergências e opções futuras exigem ratificação própria; nenhuma homologação é presumida.

## 1. Objetivo e fronteiras por ambiente

| Ambiente | Dados e finalidade | Segregação obrigatória proposta | Execução destrutiva |
|---|---|---|---|
| CI GitHub-hosted / PR | exclusivamente fixtures sintéticas; PostgreSQL 16 e Redis descartáveis criados pelo job, sem acesso a recursos reais | workflow e verificador vinculados a repositório, evento/ref, banco/cluster/container e credenciais efêmeras; sem segredos de produção | **Não usar comandos wipe/fresh/refresh no workflow atual.** Qualquer futuro teste destrutivo exige autorização específica, contêiner descartável dedicado e escopo comprovado |
| DEV Leaseweb (a instalar) | desenvolvimento/testes por contêiner; banco sintético e dados de desenvolvimento isolados | identidade da VPS, redes, volumes, papel runtime/DDL, ACL e backup dev separados; proibir compartilhamento de segredos de clientes e de produção | **Proibido por padrão**; somente futuro procedimento excepcional aprovado com banco efêmero separado |
| Homologação futura | ambiente a definir, possivelmente durável | credenciais distintas e controles de mudanças, recuperação e anonimização | **Proibido por padrão**; migração incremental e validação controlada |
| Produção futura | dados reais multiempresa e anexos | banco de produção isolado da aplicação, conexão privada/cifrada, mínimo privilégio, firewall, backup offsite e logs de alteração | **Proibido** executar `migrate:fresh`, `db:wipe`, `migrate:refresh`, DROP indiscriminado; mudanças apenas por migration revisada/aprovada, com rollback/backups e janela autorizados |

**Não assumir que VPS de desenvolvimento compartilhada para projetos Imagine se equipara à futura separação física da produção**. A diretriz atual de PostgreSQL produtivo em instância distinta do Laravel está **em estudo e não implantada**. O nome de banco ou host loopback não prova descarte; `system_identifier` ajuda a identificar cluster, mas não comprova ausência de caminhos persistentes ou túnel.

## 2. Papéis PostgreSQL propostos — desenho, não concessão

**Importante:** nomes abaixo são *perfis lógicos candidatos*, NÃO roles já criadas. Em CI existem papéis sintéticos `crm_app_ci`, `crm_iam_ci`, `crm_provision_ci`, `crm_async_ci` e `crm_async_audit_ci`; não equivalem a homologação de perfis reais. O `postgres` superuser do job só pode existir no contêiner descartável do CI. Cada instância necessita inventário e desenho de ACL específicos antes de implementação.

| Perfil lógico candidato | Capacidades mínimas a especificar | Vedações / limites | Evidência futura |
|---|---|---|---|
| Operador de plataforma/infra | administração da VPS e do provedor, manutenção controlada, recuperação do console | sem uso diário da credencial DB superuser pelo app; MFA/segregação, trilha de sessões e mudanças | inventário de operadores, política de break-glass, alertas e revogação |
| DBA/provisionador de migrações (DDL) | criar/alterar somente objetos explicitamente autorizados no DB/schema alvo durante janelas aprovadas; papel temporário | nunca injetar no runtime Laravel ou no Actions público; sem reutilizar credenciais de CI; não ter acesso a outros bancos | owners, GRANT/REVOKE e histórico de execução revisados por DBA |
| CRM runtime de negócio | SELECT/INSERT/UPDATE/DELETE estritamente nas tabelas/colunas e operações homologadas por política futura | **sem** SUPERUSER, BYPASSRLS, CREATEDB, CREATEROLE, TRUNCATE, DROP, ownership das tabelas, CREATE em schema, privilégios de DDL | matriz por tabela/ação/campo, teste cross-tenant e RLS efetivo |
| CRM IAM runtime | consulta/atualização somente de identidade/sessão/MFA sob contratos IAM homologados | não ler/escrever dados de outras empresas por inferência; sem grants gerais, BYPASSRLS ou ownership | lista de tabelas, colunas e operações IAM, testes de sessão revogada |
| Provisionamento de identidade | operações explícitas de criação/gestão de identidade e credenciais, exclusivamente sob fluxo autorizado | não confundir provisioning com permissão empresarial irrestrita; não reutilizar em requests de negócio | aprovação por operação, limites/versões e auditoria |
| Filas/async runtime | filas e ledger com acesso mínimo a objetos específicos do módulo; sem realizar efeitos comerciais não autorizados | sem reenvio automático ou estado de sucesso comercial por contrato sintético; sem DB superuser | permissões por tabela, testes idempotentes, controles de tenant |
| Leitura técnica/auditoria | consultas apenas a metadados e relatórios sanitizados necessários | sem bulk PII, acesso arbitrário a todos tenants ou exportação irrestrita | contratos de minimização, logs, segregação, trilha de acesso |
| Backup físico/WAL | somente permissões e mecanismo tecnicamente necessários para solução homologada (a definir com DBA e ferramenta escolhida) | não usar conta de aplicação; evitar poder de apagar/alterar histórico de backups por credencial que gera backup; não presumir `pg_read_all_data` suficiente para backup físico | prova de backup base + WAL, ACL do destino, criptografia e restore em laboratório |
| Restauração emergencial | operar recuperação controlada em alvo isolado, após aprovação de incidente/procedimento | não disponibilizar como role comum de runtime; não restaurar sobre instância existente sem plano, backup e autorização | registro de janela/identidade, teste isolado e validação pós-restore |

**Regras transversais propostas:** atributos `NOSUPERUSER`/`NOBYPASSRLS`, sem ownership para aplicação, sem privilégios implícitos por PUBLIC, isolamento de schemas, `search_path`, default privileges, herança/grants, política de acesso a sequences, funções `SECURITY DEFINER`, chaves/rotação/segredos, expiração e revogação. Verificação real deve incluir privilégios efetivos (diretos e herdados), não apenas declarações de role. **FORCE RLS reduz bypass do owner, mas não supera superuser/BYPASSRLS.** O GUC `app.tenant_id` não é autenticação e deve ser definido a partir de identidade/sessão/membership validadas, dentro de contexto transacional controlado.

## 3. Matriz de operações por ambiente

| Operação/ação | CI efêmera atual | DEV sintética futura | Banco persistente/produção futura | Observações |
|---|---|---|---|---|
| SELECT diagnóstico sem PII, leitura de metadados | permitido no escopo CI já validado | somente após controle de acesso ao DEV | permitido apenas com permissão formal e mínima | registrar quem, quando, por quê; não expor credenciais |
| `php artisan migrate` padrão | utilizado no workflow CI atual | requer homologação de alvo e credencial | somente migration incremental com aprovação de mudança, backup e controle de concorrência | migrar tabelas não equivale a poder destruir indiscriminadamente |
| `migrate --path=database/experimental-migrations/...` | somente perfil opt-in + preflight CI e alvo descartável | bloqueado até GO específico | **proibido** aplicar migrations experimentais | `guard()` de migration não é proteção universal |
| `migrate:rollback` | testes existentes limitados a schema sintético isolado | somente futura autorização explícita | sem rotina automática; analisar reversibilidade, risco de perda e caminho forward-fix | DDL PostgreSQL e histórico do ledger devem ser conciliados |
| `migrate:fresh`, `migrate:refresh`, `db:wipe` | **não usar no workflow atual** | **proibido por padrão**; GO futuro somente em alvo descartável dedicado | **proibido** | pode apagar tabelas antes de `up/down` avaliar suas guards |
| `DROP`, `TRUNCATE`, SQL administrativo destrutivo | sem execução nesta etapa | proibido por padrão; exceção formal limitada ao laboratório sintético | proibido como operação ordinária; tratamento excepcional segue incident/change management | proibição por procedimentos + permissões, não somente scanner de texto |
| criar/alterar GRANT, OWNER, POLICY ou role | existente apenas para montagem de CI descarte conforme workflow já autorizado | sujeito a revisão SEG/DBA e GO distinto | mudanças revisadas por DBA sob janela aprovada e auditoria | proibir runtime role de manipular RLS/ownership |
| backup base/WAL, restauração PITR | **não configurados/testados nesta etapa** | especificar e testar somente após GO futuro | backup operacional necessário antes de qualquer entrada em produção; restore ensaiado e verificado | não assumir ferramenta, prazo, custo ou RPO/RTO homologados |

**Barreiras mínimas exigidas antes de uma execução destrutiva no futuro:** (a) prova de identidade física e isolamento da instância; (b) prova de que banco/volume são descartáveis e não contêm dados reais; (c) credenciais limitadas ao alvo; (d) plano de rollback e dados de teste reconhecidos; (e) aprovação por ato específico; (f) saída auditável. A presente etapa **não executa nem autoriza** essas ações.

## 4. Inventário de infraestrutura: informações exigidas, ainda não obtidas

| Item | O que deve ser conferido | Estado atual |
|---|---|---|
| Leaseweb DEV | provisionamento e IP/console, imagem Ubuntu, isolamento Docker, volumes, firewall, backup da máquina | **NÃO VERIFICADO nesta revisão**; não solicitar/expor credenciais neste documento |
| Cluster PostgreSQL DEV | host, porta, `current_database`, identidade cluster, versão, volume, ownership/roles, rede | **NÃO VERIFICADO** |
| Banco futuro de produção | instância distinta do app, jurisdição, TLS, acesso privado, ACL, auditoria | **PLANEJAMENTO; não implantado/homologado** |
| GitHub Actions | runner, evento, ref, credenciais e isolamento do service; four preflights | **CI de PR #28 validada em laboratório**; evento push na DEV somente simulado |
| Destinos de anexos/backup | R2 / Leaseweb Object Storage / alternativas, localização, políticas de retenção e exclusão | **CANDIDATOS, não escolhidos/contratados neste recorte** |
| Revisor independente | nome, experiência, escopo, vínculo e conflito de interesse | **NÃO DESIGNADO** |

## 5. Backup operacional PostgreSQL e recuperação — contrato candidato

**Objetivo:** reconstrução verificável da plataforma em falha de banco, perda de VPS, erro de migration, comprometimento de credenciais ou corrupção, com anexos e filas coerentes. **Não confundir** backup físico operacional com IN-118 (arquivos exportáveis, backup por empresa e migração via interface do cliente). Uma exportação SQL ou CSV **não permite PITR físico**; backup base + WAL contínuo permitem uma recuperação do cluster para instante escolhido, se cadeia íntegra e testada.

**Decisões ainda abertas e critérios exigidos:**

| ID | Definir/homologar futuramente | Critério de aceite da evidência |
|---|---|---|
| REC-01 | solução (pgBackRest candidata, ou equivalente), versões e compatibilidade PostgreSQL 16 | matriz de versões, segurança, licença/operabilidade, procedimento documentado e suportabilidade |
| REC-02 | destino externo, região, fornecedor/domínio de falha e credencial distinta | isolamento da VPS e do operador runtime, TLS, criptografia, rotação e capacidade de impedir alteração/exclusão indevida de cópias |
| REC-03 | política de base backup, arquivamento contínuo WAL, intervalos e cadeia de recuperação | logs/checksums, histórico de backups, métricas de atraso WAL e alertas independentes; não há RPO prometido sem medição |
| REC-04 | retenção e exclusão, imutabilidade/versionamento e crescimento do storage | períodos ratificados por base legal e contratos; proteção contra ransomware; custo por storage/egress/operação |
| REC-05 | restaurar em cluster **novo e isolado**, com dataset sintético conhecido | conferir WAL/timeline/ponto de corte, roles, grants, RLS, constraints, contagens/digests e integridade sem tocar base origem |
| REC-06 | restauração de anexos e coerência entre banco, objetos, filas e integrações | checkpoints/manifestos correlacionáveis; nada de reenvio duplicado de leads/eventos; reconciliar estados externos |
| REC-07 | RPO/RTO aceitos, periodicidade de ensaio, responsabilidades e plano de incidente | medir tempo/perda durante testes autorizados; referências históricas RPO 15 min e RTO 4 h **não aprovadas** |
| REC-08 | prova de falha real e reversibilidade sob controle | procedimento humano, logs, backup do estado anterior, critérios de abortar/interromper restore e validação antes de liberar usuários |

A regra já aprovada para **anexos** em F03 prevê réplica incremental alvo a cada **15 minutos** e retenção técnica de **30 dias**; **não reutilizar esses números como política do banco PostgreSQL**. Uma cópia da VPS ou um `pg_dump` isolado **não é evidência de PITR**. Controles LGPD e exclusão precisam compatibilizar backups e retenções legítimas, sem alegar exclusão instantânea dentro de cópias imutáveis.

## 6. Procedimento proposto de validação, sem comandos executados

1. **REC-G0 — Pré-requisitos documentais:** inventário completo, revisor SEG/DBA designado, credenciais e permissões mínimas, finalidade do teste, aprovação específica para ensaio, custo e destino.
2. **REC-G1 — Dataset sintético:** construir base descartável com duas organizações fictícias, identidade/membership, operações de ledger inertes e objetos fictícios versionados; nenhuma informação pessoal ou segredo real.
3. **REC-G2 — Captura:** base backup + WAL, manifesto e carimbos temporais, prova de arquivamento e acesso estritamente de backup ao destino.
4. **REC-G3 — Recuperação:** criar outro cluster/volume descartável isolado, recuperar cópias e WAL para ponto deliberado; **nunca** sobrescrever banco de produção/desenvolvimento durável.
5. **REC-G4 — Provas:** conferir integridade física/lógica, permissões e RLS, dados de ambos os tenants, anexos, log de operações, ausência de vazamento, tempo medido e diferença entre ponto de escrita e ponto restaurado.
6. **REC-G5 — Evidência/parecer:** anexar logs sanitizados, resultados, limitações, custo e riscos residuais, com revisor independente e decisão da Direção sobre eventual homologação.

Não iniciar REC-G1..G5 sem GO futuro distinto. Registro de teste unitário `F1-09` e CI `F0/PostgreSQL` do PR #28 **não** significa PITR testado.

## 7. Questões objetivas para a decisão SEG/DBA

- **SEGREC-D01:** Qual arquitetura real de roles, grants e ownership (incluindo schemas/functions, herança, default privileges) impede DDL por runtime e mantém IAM/RLS correto?
- **SEGREC-D02:** Quais controles físicos de rede, volumes, contas e segredo impedem que scripts públicos/CI alcancem qualquer PostgreSQL durável?
- **SEGREC-D03:** Como bloquear operaçōes de wipe/fresh/refresh/DROP fora de banco descartável e provar isso por permissões reais? Quais exceções controladas existem?
- **SEGREC-D04:** Qual cadeia de backup base+WAL, destino externo e política de retenção atende custos e LGPD? Quem monitora falhas?
- **SEGREC-D05:** Como lidar com migração legada/ledger divergente, recovery, rollback/forward-fix e falhas parciais, sem usar destruição automática?
- **SEGREC-D06:** Qual cenário e métrica provarão RPO/RTO e correlação banco↔anexos↔filas após restore?
- **SEGREC-D07:** Quem será o especialista SEG/DBA responsável e independente quando exigido; como documentará sua análise e riscos residuais?

**Critério para considerar o SEG-REC-01 documental preparado:** documento público de decisão **sem segredos**, com matriz de perfis, operações, ambientes, aceites de backup e responsabilidades; riscos identificados para revisão. **Não fechar gates técnicos nem a Issue #27 por preparação documental.**

## 8. Referências técnicas verificadas

- PostgreSQL 16 — Role attributes: https://www.postgresql.org/docs/16/role-attributes.html
- PostgreSQL 16 — Row security: https://www.postgresql.org/docs/16/ddl-rowsecurity.html
- PostgreSQL 16 — Continuous archiving and PITR: https://www.postgresql.org/docs/16/continuous-archiving.html
- pgBackRest User Guide (candidato, não aprovado): https://pgbackrest.org/user-guide.html
- Planejamento CRM: `docs/planejamento/P0-ARQ01-F03-postgresql-backups-dimensionamento.md`, `P0-ARQ01-F04-orcamento-object-storage-backup-recuperacao.md`, `IN-118-central-backup-portabilidade-dados.md`.

## 9. Estado, limites e próximos gates

**SEG-REC-01 — documento preparado; AINDA NÃO HOMOLOGADO e não é parecer externo.** Abrem-se, somente como propostas, etapas posteriores `SEG-REC-02` (mock/testes não destrutivos, programação apenas sob GO novo), `SEG-REC-03` (ensaio de restauração totalmente descartável, autorização independente) e `SEG-REC-04` (parecer SEG/DBA e decisão empresarial). **Issue #27 permanece aberta**; PRs #23/#24/#25/#26/#28 DRAFT sem merge; DEV, `main`, VPS, ambientes de clientes, `ALLOW`, CI de código, comandos SQL e produção intocados. Gates CORE, SEG-01/02, AUT, INT, LGPD, FIN/CON e DEV-GATE permanecem abertos.
