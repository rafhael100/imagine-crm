# CRM F232 — Ed25519 + nonce atômico PostgreSQL 16 descartável

**14/11/2026 · CI com fonte Agents imutavelmente fixada · NO-GO.**

## Avanço técnico

A F231 comprovou a assinatura Ed25519 com chave pública injetada e apenas
um contador anti-replay **local à instância PHP**. A F232 combina, pela
primeira vez no lado CRM, a **implementação original** do Agents na revisão
`fe79e7e9a55e4d6728ab42c2ef578e40d9bd68ba` com o ledger
PostgreSQL 16 real `AiaPgNonceCandidateLedger` já validado em CI.

O novo `AiaPgEd25519LabBridge` preserva esta ordem sem alternativas:
verificar assinatura destacada, issuer/audience, tenant, operação,
JSON canônico, digest dos bytes, nonce e janela temporal **antes** de
tentar `INSERT ... ON CONFLICT DO NOTHING` na base PostgreSQL descartável.
O banco impõe a unicidade `(issuer,audience,nonce)` entre processos e
empresas; não depende de uma lista local de nonces para a proteção final.

## Evidências reproduzíveis

`tests/Feature/AiaPostgresEd25519NonceCandidateTest.php` executa
somente com `AIA_ED25519_PG_LAB=true` e com as verificações da identidade
efêmera do GitHub Actions: banco `imagine_crm_ci`, host local,
`APP_ENV=testing`, repositório correto e usuário da conexão `postgres`.
Se o marcador estiver ativo e faltar qualquer pré-requisito, falha
explicitamente. Sem marcador, a suite geral ignora esta prova isolada.

- **3 tenants sintéticos**, três nonces únicos consumidos uma vez em
  PostgreSQL real e tentativas de replay por uma segunda conexão
  independente (inclusive com tentativa de trocar tenant).
- **11 negativos** da assinatura Ed25519 ou claims, incluindo claims
  fraudulentas mas corretamente assinadas. Nenhuma delas consome nonce
  ou altera a autorização. Verifica que o nonce continua utilizável
  depois de uma prova inválida.
- Indisponibilidade da tabela PostgreSQL gera negação uniforme sem
  projeção, sem qualquer `ALLOW`.
- O workflow `ci-postgresql-rls.yml` exige checkout Agents em SHA fixa,
  verifica a SHA, executa PHPUnit sobre PostgreSQL 16 real e mantém
  integralmente a suíte anterior.

## Bloqueios intencionais

O laboratório ainda usa chaves **determinísticas e públicas para teste**.
A assinatura verificada atesta apenas criptografia de fixture, não
identidade operacional; `AiaPgNonceCandidateLedger` é restrito à base
descartável da CI. Não existem rotas de autenticação de serviços,
segredos reais, autorização por registro/coluna, dados reais,
credenciais, deploy, configuração da VPS nem custos extras.

A resposta pública continua SEMPRE
`DENIED_OR_NOT_FOUND`, `real_authorization=false`,
`external_calls=0`, mesmo com assinatura e registro PG válidos.

O CRM [PR #57](https://github.com/rafhael100/imagine-crm/pull/57)
permanece DRAFT; **14 gates, 7 decisões independentes, 13 cenários
operacionais NAO_EXECUTADO. NO-GO.** O próximo bloqueio real é
autoridade da identidade serviço-serviço e política do CRM por
empresa/principal/recurso/campo, mais fonte protegida de chaves,
revogação, TLS e revisão independente. Esta PR está em **DRAFT / NO-MERGE /
NO-DEPLOY** e não altera `main`, produção ou VPS.
