# CRM Imagine — F3-17 — Limites de confiança no contexto de tenant RLS

**Data:** 09/11/2026. **Natureza:** teste de segurança **exclusivamente em banco PostgreSQL 16 efêmero da CI**, com UUIDs e registros sintéticos. **P0 de arquitetura aberto — não homologado.**

Dependências: [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), [Issue #35 F3](https://github.com/rafhael100/imagine-crm/issues/35), F3-13/F3-16, AGENTS.md e [F3-14 PR #49](https://github.com/rafhael100/imagine-crm/pull/49), ainda DRAFT.

## Hipótese importante

A RLS da tabela experimental `crm_lab_async_ledger` usa `tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid`. A role da aplicação na CI é limitada (`NOSUPERUSER`, `NOBYPASSRLS`), mas a variável `app.tenant_id` **pode ser definida pela própria conexão**. Portanto:

- RLS isoladamente **não comprova** a identidade do operador, associação ativa à empresa ou autorização por recurso/ação/campo.
- Com a mesma role SQL, um chamador capaz de escolher a variável pode visualizar as linhas de A e de B em transações diferentes (ou na mesma transação, se mudar o valor).
- `set_config(..., true)` dentro de uma transação **reduz risco de vazamento de estado entre operações**; não autentica a origem do valor nem impede que seja escolhido indevidamente.
- `set_config(..., false)` grava o valor no escopo de sessão e permite contaminação entre requisições/jobs na mesma conexão, até RESET/encerramento.

Esses fatos já eram reconhecidos por AGENTS.md, mas o F3-17 acrescenta **evidência reprodutível CI** com um papel sem `BYPASSRLS` sobre a tabela **pré-existente**.

## Testes adicionados

1. **Alteração não confiável de contexto**: fixtures A e B criadas pela conta administrativa do *job efêmero*; a mesma role restrita lê A com a GUC A e consegue ler B ao mudar a GUC para B, **sem uma prova de membership**. O teste documenta uma pré-condição de segurança ausente, não autoriza esse caminho em produto.
2. **Contexto ausente / rollback**: ausência de GUC resulta em zero linhas; `SET LOCAL` dentro de uma transação some após `ROLLBACK`; não deve persistir entre requisições.
3. **GUC de sessão intencionalmente contaminada**: escopo de sessão pode vazar a seleção de tenant; o teste usa `try/finally` e `RESET app.tenant_id` para limpar o estado antes de encerrar.

Todos os testes dependem da mesma proteção já existente: `GITHUB_ACTIONS=true`, PostgreSQL local `127.0.0.1`, banco `imagine_crm_ci`, role `crm_async_ci`, RLS forçado, nenhum `UPDATE/DELETE/TRUNCATE` para a role de teste, e duas organizações sintéticas. No SQLite são ignorados. **Nenhuma migration/DDL, mudança de policy, API, alteração de grants ou criação de role.**

## Exigências para a evolução real — sujeitas a revisor SEG/DBA

- Servidor autenticar a identidade e resolver vínculo ativo/mandato legítimo **antes** de selecionar tenant; parâmetros do cliente não podem escrever diretamente o contexto SQL.
- Exigir validação específica de organização, membership, plano, recurso, operação e campos, com `DenyByDefaultAuthorizer` preservado até homologação.
- Definir conexão e transação controladas, contexto `SET LOCAL` apenas após verificação, limpeza incondicional no pool/worker e testes com revogação em meio ao processamento.
- Considerar modelos de múltiplas roles/assinaturas protegidas ou isolamento físico adicional; RLS é defesa em profundidade, não camada única de autorização.
- Não utilizar superusuário/owner em runtime, nem propagar credenciais administrativas do serviço efêmero de CI para VPS.
- Submeter projeto de tabelas dedicadas, policy, grants, FKs compostas, chave idempotente de destino e cenários de replay/partição ao parecer independente antes de qualquer migration funcional.

## Gates de promoção

F3-17 fornece **evidência de limite de segurança**, não a correção operacional desse limite. Permanecem abertos os **67 gates CORE**, SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), AUT, INT, LGPD, [PR #49](https://github.com/rafhael100/imagine-crm/pull/49) e gates de código/produção. Nenhum `ALLOW`, SQL/schema novo, endpoint, dado real, infraestrutura, `main`, VPS, deploy, custo ou cliente foi afetado.
