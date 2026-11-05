# CRM Imagine — STATUS ATUAL

**Última atualização manual:** 04/11/2026 (America/Sao_Paulo).  
**Fonte:** repositório `rafhael100/imagine-crm`, branches, Issues, Pull Requests e checks do GitHub consultados nesta atualização.  
**Natureza:** fotografia de acompanhamento; **não é aprovação técnica, liberação de produção, nem substitui os documentos normativos**. Para uma decisão, conferir sempre a evidência e o estado atual no GitHub.

## 1. Resumo executivo

- **Fase:** desenvolvimento técnico controlado e **experimental** do núcleo Laravel 13 / PostgreSQL 16, com dados sintéticos e bloqueios de segurança preservados.
- **Branch autorizada de desenvolvimento:** [`dev/fast-track-crm-2026-11-04`](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04) — base de código observada antes desta atualização documental: `360dfc068d5eeb181d8c9da40ce0665696601482` (a HEAD pode avançar com commits de documentação).
- **`main`:** permanece separada; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) segue **DRAFT**, sem integração à `main`.
- **Em revisão:** [Issue #7 — F1-04](https://github.com/rafhael100/imagine-crm/issues/7) e [PR #8 — F1-04](https://github.com/rafhael100/imagine-crm/pull/8), **DRAFT, aberta, sem merge**.
- **Infraestrutura:** nenhuma implantação na VPS ou produção foi comprovada ou autorizada por este registro. A VPS central compartilhada é a preferência arquitetural, com contrato/configuração/operação sujeitos a decisões separadas.
- **Próxima ação recomendada:** revisão técnica e de segurança da F1-04, conferência dos resultados e aceite explícito antes de eventual merge **somente em `dev/fast-track-crm-2026-11-04`**.

## 2. Quadro das entregas

| Marco | Evidência no GitHub | Estado comprovado | O que não significa |
| --- | --- | --- | --- |
| F0-02 — bootstrap Laravel e negação por padrão | [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) | DRAFT de `dev` para `main` | Não autoriza merge em `main` nem produção |
| F1-01 — prova de conceito IAM / PostgreSQL RLS | [Evidências F1-01](docs/desenvolvimento/F1-01-modelo-IAM-RLS-evidencias-CI-2026-11-04.md) | Testes experimentais documentados | Não prova autorização real de usuários |
| F1-02 — incremento de segurança IAM | [PR #4](https://github.com/rafhael100/imagine-crm/pull/4) | **Merge em `dev`**, commit `3ec4cfa0621140056eab274826a639aba125e0a6` | Não é release |
| F1-03 — núcleo experimental de credenciais, MFA e sessões | [PR #6](https://github.com/rafhael100/imagine-crm/pull/6) | **Merge em `dev`**, commit `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10` | Não ativa login público ou produtivo |
| F1-04 — hardening IAM, auditoria e antiabuso | [Issue #7](https://github.com/rafhael100/imagine-crm/issues/7) / [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) | **PR aberta em DRAFT**, head observado `63b007f0048c09d3d794077b6ca1ae437dd89dcd`; **não integrada** | CI verde não substitui revisão/aceite |

## 3. Testes e evidências disponíveis

**F1-03, após o merge na branch DEV:** o [CI Laravel/SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/41946853493) e o [CI PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/41946853510) foram registrados como concluídos com sucesso. A execução anterior a esse merge contou com 67 testes aprovados/24 ignorados em SQLite e 90 aprovados/1 ignorado no PostgreSQL, conforme o [AGENTS.md](AGENTS.md) e os links do workflow.

**F1-04, PR #8, head `63b007f`:** dois checks do GitHub Actions consultados estavam **completed / success**:
- [PHP 8.4 / Laravel 13 / PHPUnit](https://github.com/rafhael100/imagine-crm/actions/runs/41951441102/job/126996072497).
- [PostgreSQL 16 / RLS / PHP 8.4](https://github.com/rafhael100/imagine-crm/actions/runs/41951441341/job/126996072746).

**Revisão humana da PR #8:** nenhuma revisão submetida foi encontrada na consulta. Não afirmar que há aprovação, homologação ou cobertura integral de segurança. Revalidar checks caso o SHA da PR mude.

## 4. Bloqueios e limites preservados

1. Todos os **67 IDs técnicos CORE** e pendências pertinentes de CORE01/02/04, SEG-01/02, ARQ/INT/FIN/CON/LGPD continuam sujeitos aos próprios critérios de revisão e aceite; CI de laboratório não os fecha.
2. `DenyByDefaultAuthorizer` continua negando efeitos de negócio. `app.tenant_id` do PostgreSQL não é evidência autônoma de autenticação; isolamento tenant, membership e autorização exigem validação de backend confiável.
3. **Não habilitar** autenticação pública, dados de clientes, contas reais, faturamento, integrações comerciais, funcionalidades de produção ou gastos não autorizados.
4. Não executar merge automático em `main`, deploy, provisionamento ou mudanças na VPS por uma simples PR ou atualização deste resumo.
5. Não divulgar senhas, tokens, segredos ou dados reais na documentação, CI e mensagens ao Codex.

## 5. Próximo passo operacional

**Revisar a [PR #8](https://github.com/rafhael100/imagine-crm/pull/8)**, vinculada à [Issue #7](https://github.com/rafhael100/imagine-crm/issues/7): conferir escopo autorizado, diferenças de código, evidências dos checks, testes negativos, segurança de provisionamento, auditoria, limites de abuso, concorrência/compensações, bloqueios CORE/SEG e possíveis achados.

- **Se houver problemas:** devolver correções ao Codex na própria PR; rodar novamente os testes necessários e reavaliar o SHA.
- **Se estiver tecnicamente aprovada e houver autorização do responsável:** integrar **exclusivamente na branch DEV autorizada**, respeitando revisão humana e demais gates aplicáveis.
- **Depois:** atualizar este documento com os links, SHA, resultado da revisão, testes e próxima Issue autorizada. Não presumir automaticamente a tarefa seguinte.

## 6. Como usar e manter este arquivo

- **`AGENTS.md`:** instruções duradouras para o Codex, válidas por tarefa e branch.
- **`STATUS-ATUAL.md`:** resumo executivo que muda conforme as entregas.
- **`docs/planejamento/`:** decisões e especificações de referência.
- **GitHub Issues / Pull Requests / Actions:** tarefas, código e provas técnicas.
- **GitHub Project “Imagine — Central de Desenvolvimento”:** quadro de acompanhamento transversal, quando configurado.

**Regra de manutenção:** atualizar após marcos relevantes (aprovação, merge, mudança de bloqueios, início/conclusão de testes e definição da próxima etapa), **após conferir as fontes**. Atualizar o texto não prova execução de testes ou deploy nem muda o estado real da Issue/PR. As atualizações podem ser propostas pelo ChatGPT e registradas por commit; nenhuma rotina automática de atualização está habilitada por este documento.

**Links rápidos:** [Repositório](https://github.com/rafhael100/imagine-crm) · [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) · [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) · [Issue #7](https://github.com/rafhael100/imagine-crm/issues/7).
