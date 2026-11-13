# CRM OPS-68 — Isolamento de rotas e kernel HTTP no Composer --no-dev (13/11/2026)

A presente trilha documenta duas entregas **efetivamente mescladas apenas na DEV**. Ambas usam o **mesmo runner GitHub descartável, sem acesso à VPS, credenciais, clientes ou dados pessoais**. APP_ENV=production é somente uma variável de execução para simular o comportamento do Laravel; **não houve implantação**.

## Cabeças e merges conferidos

- DEV após F3-261: 22e2dd0a5ad5ed11f718ea106389a8e4f464f3a1.
- main preservada: e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012.
- F3-260 [PR #513](https://github.com/rafhael100/imagine-crm/pull/513), commit de merge DEV 0e94874abccd37dcfacd19ed56e49f7c6deed907. A CI já exigia somente GET/HEAD /up em ambiente testing + Composer sem dependências dev; agora repete o inventário com APP_ENV=production simulado e APP_DEBUG=false, sem servidor real.
- F3-261 [PR #514](https://github.com/rafhael100/imagine-crm/pull/514), commit de merge DEV 22e2dd0a5ad5ed11f718ea106389a8e4f464f3a1. Script CLI fail-closed inicia o kernel HTTP Laravel em memória sob o mesmo pacote no-dev e executa 11 requisições: GET/HEAD /up 200, nove caminhos de signup, trial, OTP e fixtures __ci 404, sem cookies ou redirecionamentos.

## Dez evidências CI na SHA exata da PR (completed/success)

| Entrega | SHA HEAD testada | F0 Laravel | PostgreSQL/RLS | Sites | AIA | No-dev/HTTP |
| --- | --- | --- | --- | --- | --- | --- |
| [#513 F3-260](https://github.com/rafhael100/imagine-crm/pull/513) | 75886c793fcd11c896f2dd0af1f0033a2750dc97 | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43510585352) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43510585264) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43510585195) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43510585218) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43510585191) |
| [#514 F3-261](https://github.com/rafhael100/imagine-crm/pull/514) | 51ac5818b88e13ee48f6bb7375df7738685acaae | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43511207551) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43511207397) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43511207541) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43511207524) | [success](https://github.com/rafhael100/imagine-crm/actions/runs/43511207381) |

Estes resultados são **pré-merge das PRs** na SHA exata. Não equivalem aos checks pós-merge dos commits DEV; estes devem ser checados separadamente ao conferir a nova HEAD. Não ampliar um resultado positivo de script local para uma promessa sobre VPS, rede, storage ou dados reais.

## Risco residual e próximos gates

- A rota /up sozinha não cria login, onboarding, persistência, plano ou lead; o CRM continua **sem rota pública de cadastro**. Nenhum fluxo comercial está operacional para clientes.
- Ainda é necessário certificar a implantação real em **staging privado autorizado**, com HTTPS, gateway, DB e roles separados, hardening, backups e testes negativos por empresa.
- Não mesclar automaticamente os laboratórios PG16 concorrentes DRAFT de OTP/trial: [#468](https://github.com/rafhael100/imagine-crm/pull/468), [#470](https://github.com/rafhael100/imagine-crm/pull/470) e [#477](https://github.com/rafhael100/imagine-crm/pull/477). Esses recortes dependem de análise de segurança, atomicidade, identidade e LGPD.
- Gates [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416) permanecem **NO-GO**.
- Main, VPS, produção, gastos, credenciais, dados de clientes e provedores de mensagem não foram alterados.

**Próximo objetivo técnico:** validar um plano de staging privado **somente leitura** (inventário de roles, rotas, migrações, políticas de recuperação) antes de implementar quaisquer endpoints de cadastro autorizados e escalonamento operacional.
