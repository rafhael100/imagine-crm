# F1-04 — revisão e sincronização com dev

Data: 05/11/2026 (America/Sao_Paulo). Issue #7, [PR DRAFT #8](https://github.com/rafhael100/imagine-crm/pull/8).
Atende à [revisão técnica](https://github.com/rafhael100/imagine-crm/pull/8#pullrequestreview-5784677156),
que solicitou atualizar a branch antes da segunda conferência, sem nova falha
funcional comprovada nem autorização de integração do PR.

## Escopo verificado

Origem exclusiva: `dev/fast-track-crm-2026-11-04`, HEAD
`212088d9389087129374dadc64de021b4abf15c5`. Destino exclusivo:
`codex/f1-04-iam-hardening-audit`, antes em
`6cfbecbf1a5c85a6e755f4606c49db7602f8e0b8`.

Os três commits de dev posteriores à base comum `360dfc0` são:

- `3ac60575c8ad4a4867c6f4ebadb483d671093558`: criação de STATUS-ATUAL.md;
- `7b8e8b58d41c5196a7d88a54820889ffceb638a9`: orientação AGENTS/status/handoff;
- `212088d9389087129374dadc64de021b4abf15c5`: esclarecimento do snapshot documental.

A [comparação da origem](https://github.com/rafhael100/imagine-crm/compare/360dfc068d5eeb181d8c9da40ce0665696601482...212088d9389087129374dadc64de021b4abf15c5)
mostra somente AGENTS.md e STATUS-ATUAL.md. Ambos foram lidos antes da
sincronização e conferidos integralmente depois: idênticos às versões de dev.
O status datado continua histórico; não foi reescrito como aprovação da F1-04.

## Sincronização executada

[Commit ead125596792fc1a9afc25894e36245331caa44d](https://github.com/rafhael100/imagine-crm/commit/ead125596792fc1a9afc25894e36245331caa44d),
com pais `6cfbecbf1a5c85a6e755f4606c49db7602f8e0b8` e
`212088d9389087129374dadc64de021b4abf15c5`. O diff contra o primeiro pai
contém apenas AGENTS.md e STATUS-ATUAL.md. Histórico/revisões da F1-04
preservados, sem rebase, force push ou origem main.

Foi usada a ação nativa **Update branch / Update with merge commit**, somente
de dev para a branch F1-04. **O PR #8 não foi integrado à dev ou main** e
permaneceu DRAFT. O Git local não conseguiu enumerar credenciais do Windows
(Access is denied); não foi criado ou publicado token para contornar a falha.

Com autorização expressa do responsável nesta sessão, a opção **Always suggest
updating pull request branches** foi habilitada temporariamente para expor a
ação nativa. Após a sincronização, voltou a **desligada**, com persistência
conferida por recarga da página. Auto-merge não foi habilitado.

## Evidências no HEAD sincronizado

Execução efetiva em 05/11/2026, SHA
`ead125596792fc1a9afc25894e36245331caa44d`:

| Workflow / ambiente | Resultado dos logs | Link |
|---|---|---|
| Laravel 13 / PHP 8.4 / SQLite | 77 aprovados, 30 ignorados por exigirem PostgreSQL/Redis, 252 asserções; completed/success | [Run 42027297323 / job 127244598816](https://github.com/rafhael100/imagine-crm/actions/runs/42027297323/job/127244598816) |
| PostgreSQL 16 + Redis efêmeros | 106 aprovados, 1 ignorado exclusivo de SQLite, 399 asserções; completed/success | [Run 42027297285 / job 127244599080](https://github.com/rafhael100/imagine-crm/actions/runs/42027297285/job/127244599080) |
| Mesmo job PostgreSQL — filtro RLS | 18 aprovados, 49 asserções; sucesso | Mesmo log acima |

O teste de orçamento Redis compartilhado/contenção executou e passou no job
PostgreSQL; não foi inferido a partir dos skips SQLite. Os dados são fictícios.
PHP/Composer/Docker permanecem indisponíveis no terminal local; as suítes foram
executadas na CI, sem execução local declarada. O registro deste documento
acrescenta apenas evidências; o SHA final com esse registro e os novos checks
deve constar do PR e da resposta de entrega.

## Controles e pendências preservados

Nenhuma mudança de implementação, configuração de IAM, grants, migrations,
workflows ou dependências nesta revisão. Deny-by-default, RLS, locks de sessão,
MFA, mandatos restritos, auditoria minimizada, antiabuso/falha fechada,
reconciliação e `identity.experimental=false` permanecem como na F1-04 revisada.
Todas as decisões normativas foram preservadas. Sem dados reais, rotas públicas,
integração comercial, main, VPS ou implantação.

Continuam abertos os 67 IDs CORE e CORE01/02/04/06/09, SEG-01/02,
ARQ/INT/FIN/CON/LGPD e demais gates. Mandatos/titularidade reais, política MFA,
Redis volátil/lease sem fencing, proxies/dispositivos/DoS, auditoria/retencão e
consistência produtiva SQL/sessão/worker/outbox exigem contratos e revisão
próprios. Próximo passo: segunda conferência humana do PR #8 e do SHA final;
CI verde não autoriza merge, release ou etapa seguinte automaticamente.
