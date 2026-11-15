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


## F232b — trust anchor do serviço obtido exclusivamente do PostgreSQL

Como continuidade na mesma PR, a classe
`app/Support/Synthetic/AiaPgServiceKeyringLab.php` consulta a
**chave pública Ed25519 registrada pelo lado servidor** em uma tabela
descartável `aia_lab_ed25519_service_keys`. A chave não é lida de
cabeçalhos, parâmetros fornecidos pelo chamador ou do envelope assinado;
apenas `key_id` e tenant esperado, previamente limitados, selecionam
uma linha PostgreSQL com issuer/audience fixos.

A ponte `AiaPgEd25519LabBridge::matchesWithServerKeyring`
exige JSON canônico, consulta a chave da empresa correta e somente então
executa a verificação Ed25519 original Agents e a reserva atômica de nonce.

O teste adicional usa **PostgreSQL 16 real e descartável** para verificar
leitura de chave confiável, negativa de chave desconhecida ou de outra
empresa, revogação, rotação para novo par público de laboratório,
rejeição da assinatura da chave antiga e falha segura quando o registro
de chaves fica indisponível. Não inclui endpoint, credenciais reais
ou permissões efetivas, nem habilita `ALLOW`.

Essa tabela é criada e apagada **somente pelo teste**, não existe
migration de aplicação, registro no container Laravel, sistema de
segredos ou configuração na VPS. O experimento demonstra o padrão de
fonte de confiança servidor/CRM, não o IAM operacional.


## F233 — prévia de escopo por empresa, delegação, responsável e campo

A próxima fase usa a **mesma CI com PostgreSQL 16 descartável** e amplia
a verificação para políticas armazenadas pelo **lado CRM**:

- `AiaPgServiceScopeLab::matchesCandidate` consulta fatos **distintos**:
  delegação do principal e finalidade para um serviço/chave/empresa/operação,
  vínculo do recurso ao principal e uma concessão específica para cada
  campo solicitado (`ref` e `stage`). O pedido assinado é **seletor de
  fatos**, não fonte de autorização.
- `AiaPgEd25519LabBridge::matchesWithServerScope` exige **antes** a
  assinatura original Agents, chave pública pertencente ao CRM e nonce
  único no PostgreSQL; somente então compara os fatos da política.
- `inspectWithServerScope` não transforma o resultado em `ALLOW`.
  Mesmo quando a prévia encontra escopo, a resposta pública permanece
  `DENIED_OR_NOT_FOUND`, sem projeção e sem acesso ao banco de leads.

A expansão do teste `AiaPostgresEd25519NonceCandidateTest` cria e apaga
tabelas **exclusivas da CI** para delegações, responsáveis e campos, e
exercita 14 variantes: escopo completo, tenant sem autorização, delegação
desativada/revogada/expirada, responsável revogado, campo desativado,
projeção reduzida, principal/recurso/finalidade diferentes, chave
revogada e tabela de política indisponível. O uso de nonce repetido é
sempre negado independentemente de alterações posteriores nas regras.

**Limitações importantes:** os fatos são inteiramente fictícios; o
PostgreSQL deste ensaio usa a conexão privilegiada da CI, **não**
comprova uma role operacional de leitura mínima nem RLS para os dados
de leads. O modelo de responsável e delegação não é membership humana
autenticada nem consentimento LGPD real. Não existe endpoint, worker,
rota Laravel, migration produtiva, role da VPS, chave privada real,
concessão ao usuário ou acesso a cliente.

**NO-GO permanece.** Para homologação funcional ainda é necessário
revisão independente de SEG/DBA, CORE/AUT, LGPD, isolamento com role
não privilegiada/RLS, verificação de membro e recurso reais, logs
auditáveis sem PII e os **14 gates / 13 cenários operacionais** do
CRM-AIA-RO-01.
