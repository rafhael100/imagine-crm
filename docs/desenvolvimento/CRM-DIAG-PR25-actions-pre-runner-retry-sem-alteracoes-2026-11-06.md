# Imagine CRM — DIAG PR #25 — Checks falhando antes da alocação de runner

**Data:** 06/11/2026 (America/Sao_Paulo). **Status:** BLOQUEIO OPERACIONAL DE CI — **causa raiz não determinada**, testes conjuntos não executados e **NÃO-GO DE MERGE**.

## 1. Escopo da etapa

A Direção solicitou o **próximo passo** após a criação do [PR #25](https://github.com/rafhael100/imagine-crm/pull/25), exclusivamente um veículo de CI para a combinação dos [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) e [PR #24](https://github.com/rafhael100/imagine-crm/pull/24). Este diagnóstico não autoriza merges, produção, SQL, alteração funcional, `ALLOW` ou fechamento de gates.

## 2. Evidências de diagnóstico verificadas na API GitHub

- **Caso controle:** CI individual do PR #23 no SHA `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` — [F0 run 42252193082](https://github.com/rafhael100/imagine-crm/actions/runs/42252193082) e [PG run 42252193108](https://github.com/rafhael100/imagine-crm/actions/runs/42252193108) em `success`, com etapas executadas. CI individual do PR #24 no SHA `072f369542d0667056627f60f1f12d07173d883b` — [F0 42270254064](https://github.com/rafhael100/imagine-crm/actions/runs/42270254064) e [PG 42270254086](https://github.com/rafhael100/imagine-crm/actions/runs/42270254086) em `success`.
- **Primeiro SHA de combinação:** `3cbe52e5cbaa505d45d85c9292856f4921b56de7`; workflows [F0 42277139605](https://github.com/rafhael100/imagine-crm/actions/runs/42277139605) e [PostgreSQL 42277139632](https://github.com/rafhael100/imagine-crm/actions/runs/42277139632) `failure` nas tentativas 1 e 2. Segunda tentativa mostra `steps=[]`, `runner_id=0`, `runner_name=''`, e logs retornam `BlobNotFound`.
- **Verificação do repositório:** `rafhael100/imagine-crm` é privado, ativo, com conexão de administração visível via `get_repo`. Essa permissão **não expõe** por si só as configurações de cobrança/account ou anotações dos check-runs nesta ferramenta.
- **Diagnóstico de repetibilidade sem alterações de código:** foi criado **commit vazio** `ffefef6d5de1ecc1cdf7b863bd3bb2d8ad35e7d7` com árvore Git **idêntica** à de `3cbe52e5cbaa505d45d85c9292856f4921b56de7`, tree `b7969b59ee76581bb32aa3278e16d7e5886ce61f`. Somente o ref da branch de validação foi avançado por fast-forward com `expected_sha`. O diff de arquivos permanece exatamente o mesmo: **7 arquivos PR #23 + 1 arquivo de testes PR #24**, sem alteração à DEV.
- **Novo run de CI após commit vazio:** [F0 42278565966](https://github.com/rafhael100/imagine-crm/actions/runs/42278565966) e [PostgreSQL 42278565995](https://github.com/rafhael100/imagine-crm/actions/runs/42278565995), ambos `completed / failure` em ~2s, antes de steps. Os check-runs de cada execução indicam `annotations_count=2`, mas o texto das anotações **não é recuperável por esta conexão**.
- **Fato verificável:** checks emitidos e execução interrompida antes de qualquer step de Checkout, instalação, migração, PHPUnit ou conexão de PostgreSQL. **Não há qualquer resultado de testes da combinação**. Resultados individuais não são prova de compatibilidade em runtime.

## 3. Possíveis causas, sem afirmação causal

Sem o **texto das anotações**, não é possível classificar o erro. Investigar por esta ordem: (a) mensagem do check-run no UI GitHub Actions; (b) GitHub billing/limite de execução em repositório privado, *somente se a mensagem apontar isso*; (c) autorização/política de runner GitHub-hosted ou Actions desabilitado/suspenso; (d) erro de validação de workflow/evento; (e) indisponibilidade temporária da plataforma. Não trocar YAML, segredos, permissões ou plano pago sem evidência.

## 4. Intervenção humana mínima necessária

**No GitHub autenticado:** abrir [F0 tentativa nova](https://github.com/rafhael100/imagine-crm/actions/runs/42278565966) ou [PostgreSQL tentativa nova](https://github.com/rafhael100/imagine-crm/actions/runs/42278565995), ler o aviso do run e abrir o check do job falho. **Copiar o texto integral das duas anotações/erros apresentados pelo GitHub**; não enviar token, secret ou dado pessoal. Como não há steps/logs, esse texto é o único sinal de erro disponível.

**Decisão futura:** com a mensagem exata, um novo ato delimitado pode aprovar correção de configuração no escopo do PR #25 se possível sem custos, ou exigir intervenção de proprietário da conta para GitHub billing/runners. Reexecutar somente após resolver a causa, conferir HEAD e os dois CI. **Não realizar outro retry às cegas**.

## 5. Estado e barreiras

PR #25 fica **DRAFT / NO MERGE**; PRs #23 e #24 permanecem **DRAFT**, base `dev/fast-track-crm-2026-11-04` original intocada; PRs #2/#21, `main`, VPS e produção também inalteradas por esta investigação. Gates C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE seguem abertos. Revisão SEG/DBA independente permanece pendente.


## 6. Diagnóstico confirmado pela UI do GitHub — evidência posterior

O responsável forneceu **duas capturas de tela da UI autenticada do GitHub Actions**, uma da falha F0 (job `PHP 8.4 / Laravel 13 / PHPUnit`) e outra do PostgreSQL/RLS (job `PostgreSQL 16 / RLS / PHP 8.4`). Em ambas, a anotação principal é **literalmente**:

> The job was not started because recent account payments have failed or your spending limit needs to be increased. Please check the 'Billing & plans' section in your settings

**Diagnóstico confirmado:** bloqueio de **faturamento/limite de gastos da conta GitHub** impedindo o início de runners GitHub-hosted; **não houve execução dos testes**. A mensagem contém uma alternativa (`payments have failed` **OU** `spending limit needs to be increased`), portanto a situação específica (pagamento recusado, falha de pré-autorização, cota de computação inclusa exaurida, orçamento/limite de Actions) **ainda precisa ser verificada em Billing & Licensing**. Não tratar como bug do código, YAML ou PostgreSQL sem nova evidência.

Há outra anotação, com nível **notice**, anunciando a mudança do rótulo `ubuntu-latest` para Ubuntu 26 a partir de 19/10/2026; **ela não é o motivo indicado da falha**.

**Encaminhamento legítimo do titular da conta (sem autorizar gastos):** abrir `https://github.com/settings/billing`, conferir `Overview` e o histórico/estado do pagamento, `Payment information`, `Budgets and alerts` (inclusive escopos de Actions e orçamentos com bloqueio ao atingir limite) e uso incluído em repositórios privados. Se o pagamento falhou, regularizar/atualizar por decisão do titular; se o orçamento de Actions foi atingido, deliberar eventual valor máximo e meios para evitar sobrecusto antes de aumentá-lo. Um **bloqueio por falha de pré-autorização** também pode afetar uso gratuito; se não houver saldo ou problema visível, contactar GitHub Support para revisão. Nenhuma alteração financeira foi feita pela equipe ou pelo assistente.

**Fonte pública de orientação (não exposição de dados da conta):** https://docs.github.com/pt/billing/how-tos/troubleshooting/locked-account ; https://docs.github.com/en/billing/how-tos/set-up-budgets ; https://docs.github.com/en/billing/concepts/product-billing/github-actions

**Condição para retomar:** após confirmação de que o GitHub permite iniciar jobs, reexecutar F0 e PostgreSQL em PR #25 HEAD atual `ffefef6d5de1ecc1cdf7b863bd3bb2d8ad35e7d7`, observar as suítes combinadas, sem merge automático. **PRs #2/#21/#23/#24/#25 e gates continuam bloqueados para integração.**
