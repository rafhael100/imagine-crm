# CRM Imagine — OPS-52: dossiê de revisão SEG/DBA e entrada Portal → CRM

**Data da coleta:** 12/11/2026. **Status do documento:** INSUMO PARA REVISÃO INDEPENDENTE — **NÃO É PARECER**, GO, homologação nem autorização de merge das PRs críticas.

**Escopo restrito:** organizar a evidência rastreável da DEV e os critérios que ainda precisam de decisão competente sobre [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) e [CORE/SEG/DBA/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259). A documentação e os laboratórios não acessaram produção, VPS, dados pessoais ou segredos reais. Não foram contratados revisores e ninguém foi designado ficticiamente.

## 1. Evidências reproduzíveis já existentes (somente CI efêmera)

| Recorte | Prova executada com dados inventados | F0 | PG 16/RLS | Sites | Agents |
|---|---|---|---|---|---|
| [SEC F3-168 PR #382](https://github.com/rafhael100/imagine-crm/pull/382) | Inventário exato de seis contas LOGIN (inclui superusuário descartável do CI); ausência de grants PUBLIC em schema, DB e objetos CRM; cinco roles sem poderes elevados | [#43242448978](https://github.com/rafhael100/imagine-crm/actions/runs/43242448978) | [#43242449006](https://github.com/rafhael100/imagine-crm/actions/runs/43242449006) | [#43242449145](https://github.com/rafhael100/imagine-crm/actions/runs/43242449145) | [#43242448921](https://github.com/rafhael100/imagine-crm/actions/runs/43242448921) |
| [SEC F3-169 PR #383](https://github.com/rafhael100/imagine-crm/pull/383) | Ledger com quatro migrations conhecidas em três lotes, descoberta da migration tenant separada dos laboratórios auth/async; execução somente de leitura | [#43243080016](https://github.com/rafhael100/imagine-crm/actions/runs/43243080016) | [#43243079263](https://github.com/rafhael100/imagine-crm/actions/runs/43243079263) | [#43243079233](https://github.com/rafhael100/imagine-crm/actions/runs/43243079233) | [#43243079219](https://github.com/rafhael100/imagine-crm/actions/runs/43243079219) |
| [SEC F3-170 PR #384](https://github.com/rafhael100/imagine-crm/pull/384) | Cinco políticas de RLS conhecidas, USING/WITH CHECK com GUC de tenant e ENABLE/FORCE RLS; falha caso apareça política nova ou predicado alterado | [#43243893157](https://github.com/rafhael100/imagine-crm/actions/runs/43243893157) | [#43243893069](https://github.com/rafhael100/imagine-crm/actions/runs/43243893069) | [#43243893064](https://github.com/rafhael100/imagine-crm/actions/runs/43243893064) | [#43243893177](https://github.com/rafhael100/imagine-crm/actions/runs/43243893177) |

Essas PRs foram revisadas e integradas **apenas em** \`dev/fast-track-crm-2026-11-04\`, após quatro workflows success nas suas HEADs e \`behind_by=0\`. O CI PostgreSQL migra com o **superusuário descartável postgres** e testa outras cinco roles sem bypass. Isso **não aprova** credenciais/roles em VPS ou production, nem prova que um banco não descartável pode ser migrado sem risco.

## 2. SEG/DBA #27 — matriz de decisões ainda pendentes

| ID | Evidência técnica parcial na DEV | Lacuna que impede aceite | Quem deve revisar | Estado |
|---|---|---|---|---|
| SEGDBA-01 P0 | Workflow estrito e preflight antes de cada migration; #382 prova catálogo de ACL sem DDL | Barreira **externa** à migration contra \`migrate:fresh\`, \`db:wipe\`, \`DROP\` e privilégios de owner no banco real; política operacional por ambiente | DBA + AppSec | ABERTO |
| SEGDBA-02 P0 | \`verify-ephemeral-postgres.php\` compara contêiner PG16 e identidade do cluster por localhost; testes negativos do perfil CI | Garantias independentes de rede, volume, secrets, runner e ciclo de vida da infraestrutura **real**; fronteira com bancos permanentes | Infra + DBA + AppSec | ABERTO |
| SEGDBA-03 P1 | #382 enumera LOGIN e ACL PUBLIC; \`PostgresSecurityBaselineTest\` confirma ACL por tabela/coluna das cinco roles | Matriz de roles **reais**, cofre/rotação/revogação, contas de migration distintas das contas runtime, vínculo com ownership | DBA + IAM/SEG | EVIDÊNCIA PARCIAL |
| SEGDBA-04 P1 | #383 prova ledger de instalação limpa CI: 1 tenant + 2 auth + 1 async nos lotes esperados | Inventário e reconciliação de **basename legado, drift, schema parcial, rollback interrompido, backup/PITR e restore** em staging privado autorizado; não experimentar com clientes | DBA + Operações | EVIDÊNCIA PARCIAL |
| SEGDBA-05 P1 | #384 prova inventário das cinco políticas RLS e FORCE; suites PG verificam contextos multi-tenant e revogação | \`app.tenant_id\` é **mutável**, não autentica humano/tenant; política real por recurso/ação/campo, controle de pooled connections/jobs, identidade de serviço e segurança SQL | CORE + IAM + SEG + DBA | ABERTO |
| SEGDBA-06 P1 | \`CiEphemeralPreflightV2Test\` cobre push estritamente para DEV e PR com destino DEV/main, eventos inválidos recusados; \`CiWorkflowSourceGuardTest\` impede relaxamentos estáticos | Verificar pacote/versão final do workflow e aceite por SEG/DBA; preflight CI não é isolador de rede absoluto | AppSec + Engenharia CI | MITIGAÇÃO CI / ACEITE PENDENTE |
| SEGDBA-07 GOV | Issue #27 com escopo, PRs #23/#26 DRAFT e rastreabilidade dos testes | **Revisor independente não designado**, declaração de conflitos, assinatura/evidência por achado, riscos residuais aceitos, decisão separada sobre cada PR | Direção + SEG/DBA | ABERTO |

**Proibição:** não transformar o ledger verde, a ACL read-only, a existência de \`FORCE RLS\` ou um parecer do autor/IA em conclusão de SEGDBA-01/02/04/05/07. As PRs #23/#26 continuam fora do fast-track de merge automático.

## 3. CORE/SEG/DBA/LGPD #259 — decisões independentes antes de qualquer endpoint Portal → CRM

| Área | Evidência pré-existente de laboratório | Falta para operar |
|---|---|---|
| Identidade técnica, assinatura e autorização de empresa | PR #254 (Core puro DRAFT), PR #256 (testes cruzados DRAFT), envelopamento/HMAC e contratos tipados | Identidade de serviço confiável, autorização independente do \`binding_reference\`, cofre/rotação/revogação, replay em storage confiável |
| Persistência e confirmação real | PostgreSQL duplo descartável, concorrência e rollback em PR #256 | Transação real nonce + lead + outbox, receptor proprietário, ACK somente após commit durável, reconciliação de resultado desconhecido |
| Dados pessoais e LGPD | Fixtures sem PII nem dados de cliente | Finalidade/consentimento, base legal, minimização, controlador/operador, retenção/exclusão, criptografia, DSAR e prova por tenant |
| RLS e isolamento multiempresa | Suites CI com cinco roles e policies fechadas | Matriz **homologada** de permissões por recurso/ação/campo, identidade vinculada ao tenant, segregação API/worker, backup/restauração privada |
| Operações e homologação | CI em SQLite/PG16 e paridade de Sites/Agents | Staging isolado autorizado, revisão de arquitetura/dependências, monitoramento, recuperação, rollback, limitação de taxa, testes de integração reais |
| Governança e GO | Issues #27 e #259 abertas, PRs DRAFT preservadas | Donos, revisão de competência e independência, riscos aceitos explicitamente, manifestação de produto e autorização separada para merge/deploy |

**Não existe atalho de CI:** toda linha acima precisa de decisão/evidência própria antes de endpoint público, coleta de dados ou publicação de formulário comercial.

## 4. Formulário de decisão para revisão competente (a ser preenchido, não pré-aprovado)

Para **cada achado**:
- Identificador e objeto/versão imutável (PR + HEAD SHA + commit do arquivo revisado):
- Revisor (nome, competência, organização, independência e conflitos):
- Ambiente e evidência efetivamente reproduzida (não apenas URL de teste histórico):
- Testes negativos, cenários de falha e limites da amostra:
- Risco residual (impacto, probabilidade, mitigação, responsável e data de revalidação):
- Decisão **itemizada**: APROVADO / APROVADO COM CONDIÇÕES / REPROVADO / PENDENTE / NÃO APLICÁVEL COM JUSTIFICATIVA:
- Evidência de aprovação e competência da autoridade decisória:

A Direção deve tomar a decisão final de escopo e de eventual autorização de merge operacional **separadamente**, depois de verificar aceites mínimos e pendências bloqueantes. Atualizar a issue vinculada e a documentação de governança com a evidência original; não fechar achados por contagem de PRs.

## 5. Ordem técnica recomendada para reduzir caminho crítico

1. **Uma rodada conjunta de revisão, sem liberação de efeitos:** responsáveis de CORE/IAM, SEG, DBA, LGPD e Produto analisam os dois grupos de issues e os documentos normativos CORE, mantendo decisões individuais.
2. **Fechar risco de migração e privilégios fora do código de migration:** credenciais de execução, isolamento físico e recuperação em staging privado aprovado; definir comandos permitidos/bloqueados, owners e procedimento de restore.
3. **Concluir ameaça Portal → CRM e LGPD:** identity/membership do serviço, contrato de dados pessoais consentidos, limites, idempotência persistente, ACK durável e auditoria.
4. **Congelar contratos mínimos de operação e testar em ambiente autorizado:** primeiro escopo autenticado/read-only estrito; depois escrita/captação, se especificadas e aprovadas. Não instalar nem abrir endpoints antes de cada decisão.
5. **Somente então solicitar GO de produção:** versão identificada, matriz de riscos aceita, plano de rollback, backup/restore validado e autoridade explícita.

**Situação no momento da elaboração:** NO-GO para cliente real, \`ALLOW\`, API, migrations persistentes, \`main\`, VPS e lançamento. Este documento é uma ferramenta de revisão, não substitui a pessoa responsável pela revisão.
