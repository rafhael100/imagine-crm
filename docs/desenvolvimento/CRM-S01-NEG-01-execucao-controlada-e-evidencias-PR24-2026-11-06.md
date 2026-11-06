# CRM Imagine — S01-NEG-01 — Execução controlada de contratos/testes negativos, PR #24

**Data:** 06/11/2026 (America/Sao_Paulo). **Natureza:** diário de execução de engenharia experimental com autorização expressa para este recorte, **não homologação da funcionalidade S01, revisão independente ou permissão de merge**.

## 1. Mandato e escopo autorizado

A Direção respondeu **'autorizado'** à pergunta inequívoca: **'Você autoriza esse recorte técnico S01-NEG-01 para implementação e testes sintéticos?'**. O objeto era exclusivamente reforço dos **contratos e testes negativos** de sessão/identidade, MFA, membership, tenant, `read_self` não habilitado e campos; branch filha da DEV, CI sintético, PR DRAFT, sem ALLOW ou funcionalidade.

Fonte: [DEC-R10-A15-D01..D05](../planejamento/CRM-DEC-FUNC-P0-01-S01-R10-A15-D01-D05-alternativas-A-2026-11-06.md), documento preliminar ratificado, mas sem GO genérico de funcionalidade.

## 2. Artefatos verificáveis

- Repositório: `rafhael100/imagine-crm`.
- Base da branch filha: `dev/fast-track-crm-2026-11-04` em `8c9683acfd8decf98544402acda360d7e0f0a489`.
- Branch criada: `dev/s01-neg-01-security-contracts-2026-11-06`.
- Commits de código: `dc2293359cf7f61e170ba5b846caeee985ff3d06` (nova suíte) e `072f369542d0667056627f60f1f12d07173d883b` (caso de classificação inválida). **HEAD submetida ao CI:** `072f369542d0667056627f60f1f12d07173d883b`.
- [PR #24](https://github.com/rafhael100/imagine-crm/pull/24) **OPEN / DRAFT** para a DEV original, **não integrado**.
- **Único arquivo de código criado:** `tests/Feature/S01NegativeBoundaryTest.php`. Nenhum arquivo de produção, `routes/`, `app/`, migrations, schema, workflow, SQL ou config modificado pela implementação.
- Execução foi realizada via ferramentas GitHub do assistente e GitHub Actions; **não** através de executor Codex, servidor VPS ou ambiente de cliente.

## 3. Casos de teste adicionados e evidência

| Caso / foco | Prova negativa | F0/SQLite | PostgreSQL16/RLS |
|---|---|---|---|
| Claim por request/header | Tenant/identidade arbitrários não substituem sessão e não acessam membership | Passou | Passou |
| Password-only/MFA pendente | Sem MFA concluído, callback e membership não são alcançados | Passou | Passou |
| Sessão revogada no registro | Revogação impede uso de callback e membership ainda que guard tenha usuário cached | Passou | Passou |
| MFA válida + membership ativa | Mesmo com identidade e vínculo corretos, `organization.self/read_self` permanece negado por ausência de política aprovada; conexão sem tenant herdado após exceção | Ignorado por requerer PG | Passou |
| Membership de outro principal/tenant | Mesmo request-controlando outro tenant, não se utiliza o vínculo de terceira identidade; transação e GUC retornam limpas | Ignorado por requerer PG | Passou |
| Claims de campos não previstos (3 variações) | Parsing antes de autenticação bloqueia campos/classes inválidos sem executar callback | Passaram | Passaram |
| Envelope construído coerentemente | Objeto sintético, campos de outros módulos e `read_self` não fazem surgir método `ALLOW` ou acesso | Passou | Passou |
| Rotas não aprovadas | GET para caminhos candidatos CRM self/API/login permanece 404 | Passou | Passou |

**Observação importante:** os testes não provam que uma tela S01 real foi implementada, nem que o contrato final de campos CAM01/02 está homologado. O parser de intenção é deliberadamente restrito ao vocabulário experimental, portanto a intenção S01 não é concedida.

## 4. Runs e assertivas observadas

- [CI F0 / PHP 8.4 / Laravel 13 / SQLite — run 42270254064](https://github.com/rafhael100/imagine-crm/actions/runs/42270254064): `completed/success`, **391 passed, 66 skipped, 2236 assertions**. Os dois casos PostgreSQL-only de S01 foram ignorados por ambiente como esperado.
- [CI PostgreSQL16/RLS — run 42270254086](https://github.com/rafhael100/imagine-crm/actions/runs/42270254086): `completed/success`, **456 passed, 1 skipped, 3031 assertions**. **S01NegativeBoundaryTest** reportou `PASS` em todos os 10 cenários efetivamente executados (incluindo os três data sets).
- Os logs das suítes PostgreSQL contêm erros SQL esperados de casos negativos antigos (permissão, RLS, bloqueios); isso **não** equivale a falha do workflow, que fechou `success`. Sem prova real de produção.

## 5. Dependência de outros PRs e bloqueios

**PR #23 — migrations (DRAFT):** esta suíte nasce da DEV `8c9683ac`, **não** inclui mudanças isoladas no PR #23. Se o PR #23 for integrado na DEV antes do PR #24, revalidar compatibilidade do PR #24, migrações experimentais e CI contra a nova HEAD da DEV. Nenhuma ordem de merge entre os PRs foi autorizada.

**Segurança:** `DenyByDefaultAuthorizer` permanece único e sem `allow`; nenhum teste positivo de autoconsulta S01, login de cliente, tela, política de plano, `ALLOW`, SQL, grants ou endpoint foi introduzido.

**Gates:** C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS. Revisão humana técnica e independente quando exigida segue pendente; o próprio executor não é revisor independente.

**Estado final desta entrega:** PR #24 DRAFT com CI sintético F0 e PostgreSQL verde e pronto **para revisão técnica**, não para merge automático. `main`, DEV base, PRs #2/#21/#23 e VPS não alterados por este recorte.

## 6. Próximo passo

Revisar PR #24 com foco em não regressão do deny-only e independência da branch da DEV. Depois de revisão, eventuais correções em branch filha e novo CI; **qualquer merge futuro na DEV exige autorização expressa distinta e reconferência de SHA/CI**. Nenhuma integração deste PR equivale a GO funcional S01 ou produção.