# F1-03 — autenticação, sessões, MFA e revogação experimentais

Data: 04/11/2026. [Issue #5](https://github.com/rafhael100/imagine-crm/issues/5).
Base: `dev/fast-track-crm-2026-11-04` em `06ac9c37bca630948b2967003df4721960bc7304`,
com F1-02 integrada por `3ec4cfa`. Branch: `codex/f1-03-auth-mfa-sessions`.
Escopo: somente desenvolvimento/CI com fixtures fictícias. Sem merge/main/VPS.

## Fontes e precedência

Lidos AGENTS.md atualizado para F1-03, Issue #5, F1-01, os dois registros
F1-02, CORE04 (D01..D03 documentais; D04..D10 abertos), CORE01/02/03/05/06/07/08/09,
SEG-01/02 e a conciliação documental de F1-02. A Issue #5 autoriza este recorte
executável sintético, sem transformar os textos candidatos em homologação.

As decisões posteriores da main permanecem nas fontes imutáveis do
[registro F1-02](F1-02-revisao-tecnica-e-conciliacao.md): DEC-FASTTRACK, pacote
F1-P0, CT-F1-01 e P1+P2 documental. Nenhum merge documental, catálogo ACL ou
grant comercial é produzido. DenyByDefaultAuthorizer permanece inalterado.

Fontes de implementação: [Laravel 13 autenticação](https://laravel.com/framework/docs/13.x/authentication),
[sessões](https://laravel.com/framework/docs/13.x/session),
[RFC 6238](https://www.rfc-editor.org/rfc/rfc6238.html) e
[PostgreSQL 16 SELECT/locking](https://www.postgresql.org/docs/16/sql-select.html).

## ADR F1-03 — escolhas candidatas para o laboratório

1. **Fechado por padrão:** `identity.experimental=false`. Mesmo habilitado,
   somente ambientes Laravel `local`/`testing` podem resolver o serviço.
   Nenhuma rota HTTP foi adicionada. O guard `web` não autentica o CRM.
2. **Guard dedicado + prova atual:** profile explícito configura
   `crm_identity`/SessionGuard e Eloquent/GlobalIdentity. A fonte exige também
   SessionAuthority: registro ativo no banco, identidade correspondente,
   credencial ativa/verificada, versão vigente, MFA, vínculo ao ID de sessão,
   expiração absoluta e inatividade. A consulta se repete mesmo com guard
   cacheado. Construir um modelo ou DTO não substitui a prova no registry.
3. **Credenciais globais protegidas:** tabela candidata de hash de login
   normalizado, hash de senha Laravel, status/verificação/versionamento e fator.
   Não se infere identidade por e-mail/CNPJ nem se cria membership. Origem da
   verificação inicial ainda depende do owner IAM; somente fixtures de testes
   são provisionadas nesta entrega. Sem cadastro, verificação por e-mail,
   redefinição de senha ou inscrição pública de fator.
4. **MFA candidato:** TOTP RFC 6238 SHA-1, 6 dígitos, passo de 30 segundos e
   tolerância de um passo. HMAC do PHP, sem primitiva criptográfica inventada;
   vetores SHA-1 de 8 dígitos do RFC usados como contraprova. Último passo
   consumido é persistido com lock por identidade para impedir replay entre
   sessões. TOTP não resiste a phishing como WebAuthn; WebAuthn/Fortify e
   pacote dedicado continuam alternativas para revisão antes de uso real.
   Não foram alterados composer.json/lock ou adquiridas dependências.
5. **Todos os perfis fictícios deste laboratório exigem MFA.** Isso é recorte
   restritivo para demonstrar o fluxo, não regra nova de obrigatoriedade para
   funcionários reais. Política por papel/tenant continua CORE04/CORE02.
6. **Fatores e recuperação:** TOTP cifrado/autenticado pelo Encrypter Laravel
   com APP_KEY efêmera de CI. Códigos de recuperação de alta entropia devem
   ser armazenados com hash; fixtures demonstram consumo único/versionado
   após senha válida e fator previamente confirmado. Perda de fator E códigos
   não abre fallback por e-mail, suporte ou desligamento de MFA. Enrollment,
   regeneração, prova de titularidade, auditoria/notificações e cofre produtivo
   continuam abertos; não há API para gravar uma nova credencial/fator.
7. **Sessões server-side:** token de registry aleatório de 256 bits somente
   no armazenamento de sessão do servidor; banco guarda SHA-256 do token e
   binding do ID da sessão. Sessão pendente não faz login no guard. MFA válido
   revoga o desafio, invalida ID anterior e autentica sem remember-me. Logout,
   refresh, step-up e mudança de conta rotacionam/limpam o contexto quando
   aplicável. Refresh não estende a validade absoluta nem a janela de MFA.
   Cookie do laboratório é Secure/HttpOnly/SameSite=Strict; sessão cifrada e
   encerrada ao fechar navegador; driver cookie é recusado.
8. **Prazos e limites são candidatos, não homologados:** desafio 5 min,
   inatividade 30 min, absoluto 12 h, step-up 5 min; 5 tentativas/60 s por hash
   de login e por identidade no MFA. Os 30 min da Administração Geral são
   preservados como fonte; estes números NÃO são aprovação para aplicar a
   todos os papéis/tenants. Sem refresh token longo ou dispositivo lembrado.
9. **Revogação:** self-service somente das próprias sessões, por identidade
   derivada do guard e prova atual. Revogar todas inclui desafios pendentes.
   Alteração de versão/status da credencial invalida novas provas de sessões
   antigas; desligamento de membership continua restrito ao tenant pela F1-02.
   Não cancelar retroativamente efeitos oficiais nem dar poder a serviços/IA.
10. **Concorrência:** locks de credencial antes do registry e códigos; consumo
    de TOTP/recovery e ativação são transacionais, sem replay automático.
    Um teste PostgreSQL mantém o lock em uma conexão IAM e comprova bloqueio
    em outra. Laravel SessionGuard e armazenamento de sessão não participam
    de commit distribuído com PostgreSQL: falha parcial é tratada fechando a
    sessão; falta de registry válido nega acesso. Pool/worker e throttle
    distribuído, locks de requisição e prevenção de DoS requerem revisão própria.

## Persistência e roles

Migration `000002` é candidata, em `database/experimental-migrations/auth`,
fora do diretório autodescoberto e sem registro automático no provider.
O comando padrão `php artisan migrate` não a encontra, mesmo com o profile
ligado. A CI a aplica em passo separado com `CRM_AUTH_EXPERIMENTAL=true` e
`php artisan migrate --path=database/experimental-migrations/auth --force`.
Tanto up como down recusam profile desligado e ambientes fora de local/testing;
SQLite é apenas adaptador de teste. Em CI, schema/fixtures são preparados pelo role de setup,
depois os serviços usam **crm_iam_ci** sem SUPERUSER/BYPASSRLS/ownership:

| Role | Acesso mínimo no laboratório | Negação verificada |
|---|---|---|
| `crm_iam_ci` | SELECT no catálogo global mínimo de IDs e credenciais; UPDATE somente last_totp_step; SELECT/INSERT/UPDATE no registry; SELECT e UPDATE(consumed_at) em recovery | Sem acesso à tabela tenant-bound crm_memberships; sem modificar senhas/fatores/estado pelo serviço runtime |
| `crm_app_ci` | Grants F1-02 apenas em crm_memberships com RLS | Sem SELECT em credenciais, registry, recovery e catálogo global |
| Setup efêmero da CI | Migration e fixtures conhecidas/fictícias | Recusado como role runtime pelo adaptador IAM |

A conexão `crm_iam` exige username/password próprios, sem fallback ao usuário
do banco de negócio. Não existem credenciais reais no código. IAM global usa
ACL de tabela/coluna e tem autoridade interna específica; não é RLS comercial
por tenant. A proteção do catálogo depende dessa separação e de código SQL
confiável. SQL arbitrário/credencial IAM comprometida não é resolvido por este
registry nem pelo custom GUC. `app.tenant_id` continua alterável e não autentica.
O serviço IAM não tem grants de negócio; o serviço tenant não recebe os hashes.

## Uso e testes autorizados

Não habilitar em VPS/produção. Em CI, testes ligam o profile explicitamente,
criam credenciais/fatores/códigos sintéticos e usam o banco efêmero. APP_KEY
transitória protege os fatores; não há dados/copias de ambiente de cliente.
As APIs internas são `begin`, `completeMfa`, `stepUp`, `refresh`, `sessions`,
`revoke`, `revokeAll` e `logout`. Nunca aceitar identity_id/session-token do
body como origem de autoridade. Futuro adaptador HTTP terá de usar middleware
web/CSRF, rate limiting e revisão de segurança; hoje só há rota temporária
definida DENTRO DO TESTE, com CSRF efetivamente forçado (Laravel normalmente
o ignora no PHPUnit). Cookies e resposta 419 são verificados nesse adaptador.

Testes novos cobrem senha inválida/identidade inexistente com erro neutro;
identidade não verificada/inativa; MFA ausente/incorreto/replay; recuperação
única; cifragem/hashes; desafio e sessão expirados; refresh/ID antigo; guard
cacheado após revogação; versão de credencial; impersonation/request; sessão
de outra identidade ou ID; troca de conta e limpeza de tenant/CSRF; step-up;
throttle persistente entre rotações; ausência de rotas públicas; profile
desabilitado/produção/cookie; roles PostgreSQL e concorrência; autenticação
sem direito comercial, membership suspensa/desligada e isolamento A/B.

Os seams unitários F1-02 agora fornecem explicitamente uma SessionAuthority
falsa de teste; os testes AuthKernel usam a prova REAL do registry em SQLite
e PostgreSQL. Nenhum mock é configurado em runtime.

## Pendências e escopo de revisão

CORE04-D04..D10: provider/verificação de identidade real, enrollment/MFA por
papel, recovery e titularidade, política de expiração, antifraude, step-up por
ação, auditoria e revogação distribuída. CORE01-D04..D07: modelo completo,
pool/worker/atestado e integridade. CORE02/07/08: catálogo, perfis/campos/
entitlements e escopos. CORE03/05/06/09, SEG-01/02: jobs, mandatos, auditoria,
cofre e identidades técnicas. INT/ARQ/G01..G06/FIN/CON/LGPD permanecem abertos.
Nenhum dos 67 IDs CORE é encerrado. Segurança/DBA/IAM e owners precisam revisar
este recorte antes de merge; release requer aceite separado. Sem merge/deploy.

## Evidências de execução

Execução real em 04/11/2026 (Brasília; logs UTC 05/11), revisão de código
`8b0bc660454a53f86f5cc67b0a2e9e74ace54cca`, no [PR DRAFT #6](https://github.com/rafhael100/imagine-crm/pull/6):

| Execução | Resultado observado |
|---|---|
| [Laravel 13.40.0 / PHP 8.4 / SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/41941045098/job/126961619144) — `php artisan test --no-ansi` | 64 aprovados, 21 ignorados por exigirem PostgreSQL, 151 asserções; sucesso |
| [PostgreSQL 16 / RLS / PHP 8.4](https://github.com/rafhael100/imagine-crm/actions/runs/41941045307/job/126961619928) — `php artisan test` | 85 aprovados, 212 asserções; nenhum ignorado; sucesso |
| Mesmo job PostgreSQL — filtro `PostgresMembershipRlsTest` | 18 aprovados, 49 asserções; sucesso |
| Local — `git diff --check` | Sucesso; nenhuma falha de whitespace |

A primeira execução identificou hash dummy bcrypt malformado no caminho de
identidade inexistente. Corrigido o formato, sem desativar a verificação de
algoritmo; o teste de erro neutro passou nos dois bancos. Permanecem avisos
da plataforma sobre migração Ubuntu/Node das actions, sem falha dos testes.
`composer.lock` preservado (SHA256 `08C77FE9D787BE841633DEA38461BBBDC4C3EDFA419FF676D8FADF7581636F5C`).

PHP/Composer/Docker não estão disponíveis no terminal local; os resultados
acima são da CI efêmera com fixtures sintéticas. Publicação pela sessão
autenticada do GitHub; snapshot Git local serve apenas ao diff e não foi
publicado como ancestralidade. Branch remota deriva diretamente da dev.
CI verde comprova somente este recorte; não encerra CORE/SEG nem autoriza
merge, autenticação pública ou implantação.

## Revisão técnica do PR #6 — isolamento e revogação concorrente

Atende aos dois itens da [revisão de 357e5ad](https://github.com/rafhael100/imagine-crm/pull/6#pullrequestreview-5778051021).

**Migrações:** a migration auth foi retirada da trilha padrão. Os testes
verificam a descoberta padrão em production/local/testing, o opt-in e rollback
SQLite e a recusa explícita em production/profile desligado. Em PostgreSQL,
um schema aleatório descartável do banco efêmero de CI executa o comando
padrão com APP_ENV lógico production: cria apenas o schema F1-01 já existente,
sem tabelas auth e sem registrar a migration experimental. Em seguida o mesmo
schema testa a execução explícita autorizada em testing. Isso não é ensaio ou
aprovação de implantação; a trilha F1-01 e os demais gates seguem candidatos.

**Contrato de corte F1-03/F1-04:** `current()`/`assertCurrent()` são provas
pontuais e não autorizam efeitos. `TenantAccessBoundary::execute()` agora usa
`GuardPrincipalSource::withinCurrent()` e `SessionAuthority::withinCurrent()`.
A transação IAM bloqueia credencial e depois sessão, revalida versão/status,
MFA, binding e expiração, e só então entra na transação de membership/negócio.
Os locks IAM permanecem até esta transação retornar, inclusive commit ou
rollback, e a política deny-by-default permanece inalterada. Ordem obrigatória:
credencial → sessão → membership → autorização → operação → término do negócio
→ término IAM. Nenhuma chamada deve inverter essa ordem ou reentrar na IAM.

Uma revogação de sessão ou mudança de versão/status de credencial que tenha
commitado antes da obtenção dos locks impede a entrada em membership/autorização.
Se a unidade obtiver primeiro ambos os locks e validar, uma revogação concorrente
aguarda o término da unidade; a confirmação da revogação não pode preceder esse
término. Efeitos já em voo não são cancelados retroativamente. Expiração é
avaliada nesse corte, sem promessa de cancelar unidade que expire em execução.
Novas unidades sempre revalidam, inclusive com guard cacheado. Timeouts, conflito
e exceções propagam e encerram transações sem replay automático.

Os testes PostgreSQL usam conexões independentes e o membership store real.
Na fronteira de autorização, tentam UPDATE concorrente da sessão e do epoch da
credencial com lock_timeout curto: ambos bloqueiam (55P03). Depois da negação e
rollback, confirmam locks/contexto limpos, commitam a revogação e demonstram que
nem membership/autorização são alcançadas novamente. Não há authorizer ALLOW
nem callback comercial executado nos testes. A cobertura antiga de A/B e RLS
continua obrigatória.
Uma consulta de membership sem efeito comercial também confirma que o lock IAM
continua bloqueando a revogação depois do commit interno do store.

**Limites preservados:** são duas conexões/transações, sem commit distribuído.
Um futuro commit de negócio seguido de falha IAM pode produzir resultado
ambíguo e não pode ser repetido automaticamente; idempotência, outbox/auditoria,
compensação e falhas parciais exigem contrato CORE/SEG antes de ALLOW. O lock
IAM serializa também unidades da mesma identidade e não resolve cancelamento
de APIs externas, filas ou workers. Nenhum desses canais está habilitado.
O custo/contenção, prazos máximos e política de efeitos em voo precisam de
revisão IAM/DBA/SEG. Os 67 IDs CORE e demais gates continuam abertos.

Resultados desta revisão, executados em CI efêmera com dados fictícios na
revisão `a3beca70fc529344f14905eea8929d365a7c3d4f` (04/11/2026 Brasília):

| Execução | Resultado dos logs |
|---|---|
| [Laravel/SQLite — 41945324836](https://github.com/rafhael100/imagine-crm/actions/runs/41945324836/job/126975998536) | 67 aprovados, 24 ignorados por exigirem PostgreSQL, 167 asserções; sucesso |
| [PostgreSQL 16 — 41945324868](https://github.com/rafhael100/imagine-crm/actions/runs/41945324868/job/126976162084) | 90 aprovados, 1 ignorado exclusivo de SQLite, 260 asserções; sucesso |
| Mesmo job PostgreSQL, filtro RLS | 18 aprovados, 49 asserções; sucesso |
| Local — `git diff --check` | Sucesso; PHP/Composer/Docker indisponíveis, sem execução local das suítes |

Os dois casos concorrentes (sessão e versão da credencial), a migração padrão
em schema PostgreSQL vazio e o opt-in isolado passaram. Os erros 55P03/42501 e
violações RLS registrados pelo serviço PostgreSQL são os negativos esperados
e tratados pelos testes. `composer.lock` manteve o SHA256 registrado acima.
Solicitada segunda revisão técnica no PR #6; manter DRAFT. Nenhum gate CORE,
SEG ou de implantação foi encerrado por estes resultados.
