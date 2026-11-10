# CRM Imagine — pacote de trabalho para revisão independente SEG/DBA

**Data do levantamento:** 10/11/2026. **Status:** material de apoio à revisão, **não é parecer**, homologação, aceitação de risco nem autorização técnica para ativação. A issue de referência é [#27](https://github.com/rafhael100/imagine-crm/issues/27). A autoria deste pacote não comprova independência: **nenhum revisor independente foi designado ou validado aqui**.

## 1. Limite do material e trilhas separadas

| Artefato | Estado conferido em 10/11/2026 | Natureza |
|---|---|---|
| [PR #23](https://github.com/rafhael100/imagine-crm/pull/23), HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` | Aberto, **DRAFT**, sem merge | Deslocamento/guards de migrations experimentais; não protege sozinho contra comandos destrutivos externos |
| [PR #26](https://github.com/rafhael100/imagine-crm/pull/26), HEAD `86ec918036e895f99cd6d1d98ca6469021846d2c` | Aberto, **DRAFT**, sem merge | Preflight CI experimental com base histórica incluindo alterações do #23; requer revisão de diff cumulativo |
| [PR #49](https://github.com/rafhael100/imagine-crm/pull/49), HEAD `a2f29c8e19185e3451fe953f6fe9c9c9bcbbe3ef` | Aberto, **DRAFT**, sem merge | Desenho documental de ledger de leads; nenhuma tabela, integração ou contrato aprovado |
| [PR #55](https://github.com/rafhael100/imagine-crm/pull/55), HEAD `9d8e23929e9a7cedf90517ad1b1679e4afc38ff3` | Integrado **somente DEV** | SEC-CI-02: controle de evento, runner e PostgreSQL descartável; mitigação restrita ao ensaio |
| [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) | S01 aprovado **para especificação documental**, não para código de leitura | Sem direitos `ALLOW`, sessão operacional, tenant autorizado ou endpoint |
| [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35) | Backlog DEV de código inerte e UX | Ensaios não autorizam efeitos de negócio |

**Não confundir CI verde com aprovação do responsável SEG/DBA.** Não alterar as branches nem as aprovações históricas dos PRs acima em razão deste pacote.

## 2. Ordem proposta de avaliação técnica e evidências mínimas

A tabela mantém os códigos e níveis de risco da [issue #27](https://github.com/rafhael100/imagine-crm/issues/27). `PENDENTE` significa que **nenhuma evidência independente foi recebida/confirmada nesta tarefa**; não implica que o risco esteja ausente.

| Risco | Pergunta a responder pelo revisor competente | Evidência exigida | Estado |
|---|---|---|---|
| SEGDBA-01 · P0 · ações destrutivas | O que impede `migrate:fresh`, `db:wipe`, `migrate:refresh`, `DROP` ou SQL arbitrário de destruir tabelas **antes** do guard em `up/down`? | ACLs, ownership, segregação de papéis, bloqueios externos à migration, revisão negativa de comandos em ambiente descartável, rollback | **PENDENTE** |
| SEGDBA-02 · P0 · identidade do banco | O PostgreSQL é de fato um contêiner descartável da execução GitHub-hosted e não um banco persistente acessível por proxy/túnel? | Identidade de cluster, rede, isolamento de runner, ciclo de vida de volume, ausência de DSN alternativo e revisão dos preflights | **PENDENTE** |
| SEGDBA-03 · P1 · privilégios | O usuário de aplicação jamais é owner/superuser/`BYPASSRLS` e não reutiliza segredo de migração? | Matriz de roles e grants mínimos, separação entre migração/execução, `NOBYPASSRLS`, evidência de revogação e limpeza de contexto | **PENDENTE** |
| SEGDBA-04 · P1 · compatibilidade/recuperação | Como será tratada a base persistente com migrations legadas, schema parcial, divergência de policy ou rollback interrompido? | Matriz de estados históricos, plano conservador de reconciliação, backup externo e restauração comprovada em base descartável autorizada | **PENDENTE** |
| SEGDBA-05 · P1 · autenticação/RLS | Como comprovar que `app.tenant_id` não é identidade, que um usuário com dois vínculos não cruza empresas e que revogação invalida novas leituras? | Testes negativos de sessão/MFA/membership em múltiplos tenants, transação com `FORCE RLS`, role não-bypass, limpeza no pool; inventário de campos | **PENDENTE** |
| SEGDBA-06 · CI · eventos | O SEC-CI-02 #55 eliminou o conflito `push` versus `pull_request` sem aceitar outros eventos ou hosts? | Revisão do código efetivamente integrado e logs de cenários positivos/negativos da SHA fixada; rechecagem após mudanças futuras | **PENDENTE de revisão**, mitigação DEV existe |
| SEGDBA-07 · governança | A avaliação é realmente independente, registrada, reproduzível e vinculada a versões exatas? | Nome/papel, competência, declaração de independência e conflito, parecer assinado ou rastreável, riscos residuais e decisões separadas por PR | **PENDENTE** |

## 3. Plano de inspeção — nenhum comando destrutivo autorizado

1. **Congelar alvos e versões.** Registrar HEADs de DEV, #23, #26, #49, #55 e dos arquivos efetivamente revisados; verificar o diff cumulativo do #26 sobre #23. Um resultado CI histórico não substitui testes da versão revisada.
2. **Montar o mapa de fronteiras.** Identidades humanas, serviço CI, papéis PostgreSQL, proprietários de objetos, rede, volumes, nomes de banco, ambientes e trilha de segredos. Usar apenas metadados sintéticos/redigidos; **nunca** reproduzir chaves reais neste documento.
3. **Separar falhas de CI de segurança.** Verificar os preflights do #55 e gatilhos dos workflows; um runner GitHub-hosted com PostgreSQL de teste não comprova política no banco futuro da VPS.
4. **Analisar migrations e rollback sem executar fora do laboratório.** Avaliar a descoberta padrão, o ledger histórico e cenários de schema parcial. Qualquer experimento precisa de banco descartável especificamente autorizado e plano de restauração antes da execução.
5. **Revisar IAM/RLS e o futuro caminho de leitura.** Identity, sessão, membership, organização e escopo de campo devem ter fontes oficiais diferentes; parâmetros de URL, GUC e etiquetas de fixture não provam vínculo nem `ALLOW`. Comportamento de negação deve ser testado antes de qualquer eventual grant.
6. **Registrar evidência e decisão por achado**, com responsável, gravidade, impacto, probabilidade, condição de bloqueio, teste negativo, mitigação, risco residual e resposta do aprovador competente.
7. **Revisar PRs separadamente.** #23 e #26 continuam DRAFT mesmo após uma revisão parcial; #49 é proposta de esquema de leads e não pode virar migration operacional por analogia. A eventual homologação deve ser explícita para cada superfície.

## 4. Critérios de NO-GO de integração operacional

- Revisor especializado não identificado ou parecer inexistente; evidência exclusiva do mesmo autor não satisfaz segregação.
- Ausência de modelo aprovado de identidade, sessão, membership ativa, seleção confiável de organização ou direitos por recurso/ação/campo/escopo.
- Conta de runtime com privilégio de DDL/owner/`BYPASSRLS` ou operação sobre banco persistente sem contenções externas.
- Falta de plano de backup/restore e compatibilidade de ledger/migrations comprovado em ambiente descartável autorizado.
- Tentativa de usar `tenant_id`, `app.tenant_id` ou etiqueta `fixture-...` fornecida pelo chamador como autorização.
- Tentativa de ligar componentes `App\\Support\\Synthetic\\FollowUp*` a controllers, rotas, filas, jobs, API ou exportação real sem gate de leitura formal.
- Publicação, deploy, troca de `main`, despesas, credenciais ou manipulação de dados reais como atalho para fechar a revisão.

A existência de uma CI com testes PostgreSQL/RLS não basta para satisfazer qualquer item NO-GO.

## 5. Modelo de registro do parecer — preencher somente por quem tiver competência

**Revisor responsável:** PENDENTE — não designado nesta tarefa.  
**Disciplina e competência demonstrável:** PENDENTE.  
**Independência e conflito de interesse:** PENDENTE.  
**Data da avaliação e HEADs verificadas:** PENDENTE.  
**Ambientes e dados efetivamente utilizados:** PENDENTE.  
**Achados por código SEGDBA-01..07 com links de evidência:** PENDENTE.  
**Riscos residuais, responsáveis e mitigação:** PENDENTE.  
**Decisão para PR #23:** NÃO DECIDIDA.  
**Decisão para PR #26:** NÃO DECIDIDA.  
**Decisão para PR #49:** NÃO DECIDIDA.  
**Decisão de eventual leitura real de follow-ups:** **NO-GO / NÃO AUTORIZADA**.  
**Aceite explícito da Direção por item de risco:** PENDENTE.

A designação de alguém tecnicamente competente e segregado do autor pode ocorrer pela própria estrutura da organização, sem pressupor contratação paga. Este documento **não designa** essa pessoa, não presta o parecer em seu lugar nem solicita segredo ou acesso ao banco.

## 6. Conexão com o desenvolvimento atual

Os PRs #173 e #174 adicionaram **somente ensaios com empresas fictícias** e um tripwire estático contra vincular as classes F4 sintéticas a entrada de runtime. Eles não provam isolamento efetivo por tenant, autenticação, cache, agregação LGPD ou direitos positivos. A continuidade DEV permitida segue restrita a código sintético, documentação e regressões. A passagem para serviços com dados de cliente dependerá de decisão técnica separada e explícita dos gates acima.
