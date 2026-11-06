# CRM Imagine — FUNC-P0-01/S01-R10-A13 — Matriz decisória: comandos destrutivos e riscos residuais PR #23 (PROPOSTA v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Natureza:** análise documental de risco e alternativas à Direção de Produto — **AINDA NÃO RATIFICADAS**; não autoriza código, SQL, testes novos, CI novo, merge, VPS, deploy, licenças, custos ou acessos de clientes.

## 1. Escopo expresso deste trabalho

A Direção respondeu **'ok vamos'** após a proposta de **decidir quais controles adicionais são necessários contra operações destrutivas e quais riscos podem permanecer no ambiente estritamente experimental**, e somente depois avaliar possível merge do PR #23 na DEV. Trata-se de autorização para **aprofundar a análise e apresentar escolhas**, não para aceitar riscos de forma tácita nem fazer merge.

- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) **OPEN / DRAFT / NÃO MERGED**, base `dev/fast-track-crm-2026-11-04` SHA `8c9683acfd8decf98544402acda360d7e0f0a489`, HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`.
- [CI F0](https://github.com/rafhael100/imagine-crm/actions/runs/42252193082): success, 384 testes passed (66 skip); [CI PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42252193108): success, 449 passed (1 skip). Resultados somente para laboratório e SHA informados.
- [R10-A12 — pauta pendente SEG/DBA](CRM-REV-FUNC-P0-01-S01-R10-A12-pauta-seg-dba-PR23-controles-destrutivos-2026-11-06.md); [R10-A11 — revisão anterior](CRM-REV-FUNC-P0-01-S01-R10-A11-PR23-riscos-P0-migrations-revalidacao-2026-11-06.md).
- **Importante:** revisão realizada pelo assistente também envolvido na autoria do código; **não houve revisor independente**. As alternativas abaixo são recomendações técnicas, não certificação.

## 2. Fronteira crítica entre guard de migration e destruição de objetos

`guard()` em `up()`/`down()` da migration experimental controla somente a execução desses métodos; a migração candidata está fora da descoberta Laravel padrão. **Comandos como `migrate:fresh` e `db:wipe` podem remover tabelas antes de executar qualquer método de migration.** Assim, proteção de migrations **não substitui** credenciais de banco com privilégio mínimo, isolamento de ambientes, fluxo de release, backups/restore e aprovação de DDL.

**Atual:** teste sintético assegura ausência de DDL experimental via descoberta padrão e bloqueio quando flag, ambiente, driver, host/nome de banco não correspondem. Não existe evidência de que um processo com privilégios DDL esteja impedido de usar comandos destrutivos contra banco persistente. Não registrar como 'resolvido' esse risco.

## 3. Matriz de riscos residuais e evidências de tratamento

| ID | Vetor | Mecanismo/limite | Prioridade/contexto | Tratamento recomendado | Prova pendente | Status |
|---|---|---|---|---|---|---|
| R13-01 | Comandos destrutivos Laravel | `migrate:fresh` e `db:wipe` podem apagar objetos ANTES de avaliar `guard()` da migration | P0 operacional em banco persistente | Não usar credenciais DDL fora de banco sintético; negar esses comandos por política de execução/CI e tratar releases em contrato distinto | Ambiente, credencial e comando; controle/approvals reais e testes negativos em laboratório | Sem ação real autorizada |
| R13-02 | Host loopback não prova descartabilidade | `localhost` pode apontar a instância persistente ou túnel com privilégios perigosos | P0 quando houver DDL | Isolar credenciais/ambientes, limitar network/roles; confirmar DB + schema de destino, não só nome | Revisor SEG/DBA deve conferir topologia/roles com evidência não sensível | Parcial, controle lógico já implementado |
| R13-03 | Histórico e drift de migration | Registro antigo no ledger pode coexistir com schema parcialmente alterado; testes cobrem somente caso controlado | P1 para integração DEV, P0 para deploy | Verificação estática de versões, plano de reconciliação sem `fresh`/`wipe`; nunca reparar banco persistente por inferência | Matriz de drift por estados e teste em schema gerado/descarte | Parcial no CI, nada de banco real |
| R13-04 | Review de autoria | Código foi produzido e revisto pelo assistente/conta vinculada; não equivale a validação independente | Bloqueio de homologação conforme requisitos | Identificar revisor competente, conflito de interesse e decisão expressa sobre requisitos de independência aplicáveis; sem inventar assinatura | Parecer autêntico com escopo, versão, ressalvas e conclusão | Pendente |
| R13-05 | Falsa segurança por merge DEV | Merge de branch de laboratório poderia ser confundido com publicação ou concessão de ALLOW | P0 de governança | Permitir apenas processo de revisão em DEV sob autorização específica; não vincular branch a deploy nem liberar `main` | Comprovar proteção de branch/CI/sem secrets e bloqueio de deploy | Não verificado operacionalmente |

## 4. Regras por contexto operacional — proposta, não liberação

| Contexto | Limite de execução | Controle documental/evidência | Status de autorização nesta etapa |
|---|---|---|---|
| CI sintético efêmero | Permitido **somente** nos jobs já aprovados sob services locais e fixtures sintéticas; comandos destrutivos novos dependem de autorização expressa | Banco efêmero com nome/credencial restrita, encerrado com job; teste de negativa, sem dados reais | Sim, apenas desempenho/evidência do recorte já autorizado |
| Branch filha do DEV | Pode receber código e testes futuros somente com GO delimitado, PR DRAFT e revisão; **não** usar VPS | Sem acesso autônomo a banco persistente; CI isolado ligado ao PR | Sim, de forma pontual |
| DEV integrada por merge futuro | Exige decisão específica após HEAD/CI/revisão e tratamento dos riscos; não implanta | Branch DEV não é ambiente de produção e merge não cria permissão de deploy | Ainda não autorizado |
| VPS de desenvolvimento | Nenhuma mudança decorrente da aprovação de R10-A13 | Qualquer acesso, role, DDL, backup e implantação dependem de autorização própria | Não |
| Homologação / staging / produção | Nenhuma autorização para `migrate`, `refresh`, `fresh`, `wipe` ou banco real | Competência/parecer/recovery, isolamento, DDL segregado e gates fechados antes de GO | Não |

## 5. Alternativas A13-D01..D05 — apresentar para aceite posterior

| Decisão | Tema | Alternativa A recomendada | Alternativa B não recomendada |
|---|---|---|---|
| A13-D01 | Política mínima para comandos destrutivos | **A (recomendada):** declarar `migrate:fresh`, `db:wipe`, `migrate:refresh`, rollback amplo e SQL administrativo destrutivo **proibidos fora de banco efêmero autorizado**, e exigir controle de permissões/rotina de release independente da guarda de migration. | B: confiar apenas em APP_ENV/flag/nome de DB como barreira suficiente. |
| A13-D02 | Mitigação de banco errado | **A (recomendada):** reconhecer host loopback + `current_database()` como mitigação parcial, exigir identidade da instância, isolamento de credenciais e revisão do pipeline antes de qualquer DDL persistente. | B: considerar R-01 eliminado pelos testes de CI. |
| A13-D03 | Backlog técnico limitado | **A (recomendada):** propor um incremento separado de guardrails de CI/pipeline, testes **somente em esquema descartável** e documentação de comandos, sem mudar migrations de negócio nem conceder `ALLOW`; **programação depende de GO próprio**. | B: modificar credenciais, deploy, VPS ou impedir genericamente comandos no Laravel sem estudo de efeitos colaterais. |
| A13-D04 | Tratamento do histórico e drift | **A (recomendada):** manter teste atual de ledger como evidência parcial e exigir um roteiro de reconciliação de schema histórico; execução futura somente em namespace descartável com proteção de outras tabelas. | B: assumir que a mesma migration no ledger prova integridade de qualquer schema real. |
| A13-D05 | Critério para eventual merge DEV | **A (recomendada):** manter PR #23 em DRAFT até revisão competente/independente quando exigida, decisão de risco residual, reconferência de SHA/CI e autorização expressa de merge exclusivamente DEV; nada de produção. | B: fazer merge automaticamente por CI verde ou pelo aceite genérico de planejamento. |

**Nenhuma alternativa A13-D01..D05 está aprovada neste arquivo.** Sua eventual ratificação só cria diretrizes documentais preliminares; eventual autorização de tarefa Codex deve ser um ato diferente e inequívoco. Não inferir dispensa de revisão independente caso o gate exija.

## 6. Mínimo produto para futuro incremento técnico (sem execução agora)

- Escopo candidato: atualizar unicamente o fluxo de **CI/execução do laboratório** para exigir uma identidade de alvo efêmero verificável antes de qualquer operação destrutiva; reforçar documentação de comandos seguros/não seguros e testes de rejeição usando schema descartável.
- Evitar prometer que um bloqueio no PHP protegeria `db:wipe` que atua antes de `up/down`; se for necessário bloquear CLI perigosa, desenhar proteção **no ponto de entrada e nas credenciais/pipeline**, com compatibilidade Laravel, revisor competente e sem tornar aplicativos de produção dependentes de um flag de testes.
- Não criar roles, mudar workflows de deploy com segredos, nem fazer testes destrutivos na VPS. Verificar se a mitigação por isolamento de credenciais operacionais, branch protections e execução CI efêmera já é suficiente para merge **apenas documentalmente decidido** na DEV; não utilizar CI como substituto de aceite normativo.
- Casos candidatos de prova futura: operação padrão sem tabelas experimentais; tentativa destrutiva com alvo incorreto negada **antes de DDL** por camada apropriada; role de aplicação sem DDL; ambiente correto com fixture sintética recuperável; rollback apenas explícito; logs sem segredos; efeitos em `migrate:status`, history e re-run.

## 7. Próxima decisão operacional que realmente destrava trabalho

Após avaliação das alternativas, a Direção pode **autorizar especificamente** um novo incremento técnico de guardrails de CI somente em branch filha, com testes sintéticos e PR DRAFT. Isso não autoriza merge do PR #23; não é necessário revalidar genericamente todas as decisões CORE para **preparar** esse recorte. Se a avaliação SEG/DBA competente atestar suficientes os controles atuais para o laboratório, pode-se submeter **separadamente** o merge DEV à decisão da Direção, respeitando os requisitos de independência e risco residual aplicáveis.

## 8. Preservações

**PR #23 DRAFT; `main`, `dev/fast-track-crm-2026-11-04`, PRs #2/#21 e VPS sem alteração nesta tarefa.** C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates continuam ABERTOS. A acumulação de funções internas G01–G06 não equivale a parecer independente.
