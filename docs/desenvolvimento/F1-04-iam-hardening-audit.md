# F1-04 — hardening interno do IAM em laboratório

Issue [#7](https://github.com/rafhael100/imagine-crm/issues/7). Base dev
`360dfc068d5eeb181d8c9da40ce0665696601482`, contendo F1-02/PR #4 e
F1-03/PR #6 (merge `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`).
Branch `codex/f1-04-iam-hardening-audit`; somente fixtures sintéticas/CI.

## Fontes e limites preservados

Lidos AGENTS.md atualizado, Issue #7, F1-02/reconciliação, F1-03 e contratos
CORE01/02/03/04/05/06/09, SEG-01/02. Main conferida em `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`:
as decisões posteriores permanecem as fixadas na [conciliação F1-02](F1-02-revisao-tecnica-e-conciliacao.md).
Não copiar propostas como homologação nem transportar permissões documentais
P1+P2 para grants executáveis. A autorização limitada da Issue #7 não é GO
para identidade/titularidade real, suporte, autenticação pública ou produção.

`DenyByDefaultAuthorizer`, fronteira transacional F1-03, revalidação de
membership e RLS F1-02 permanecem. GUC não autentica. Sem rotas, comandos
públicos de provisionamento, contas reais, serviços pagos, hardware, main,
merge, VPS, implantação ou encerramento dos 67 IDs CORE.

O download da base não concluiu no navegador. Foi reutilizado o snapshot local
da F1-03 publicada, após comparação GitHub `00c965df...360dfc0` demonstrar
**somente AGENTS.md alterado**; esse arquivo foi atualizado da leitura bruta
no GitHub. Branch remota criada da dev; Git local serve apenas ao diff,
sem publicar sua ancestralidade. composer.json/lock preservados.

## ADR — autoridade de provisionamento

`LaboratoryProvisioner` não recebe actor_id/role/verificado do chamador.
Deriva o ator do guard CRM + registry atual, com MFA recente e locks mantidos
pela `GuardPrincipalSource::withinCurrent(..., true)`. Não existe admin global
irrestrito. Setup sintético confiável insere mandatos com ator, versão de
credencial, alvo exato, ação, vencimento e revogação. Nenhuma API pode emitir
mandatos. Um ID recebido só seleciona uma linha; não é prova por si.

O serviço valida/locka o mandato na transação administrativa, exige correspondência
integral e recusa self-grant/recovery. Atualização do ator, expiração/revogação
ou escopo diferente negam inclusive replay de uma operação já concluída.
São contratos candidatos para teste, não evidência de titularidade de pessoas.

- `credential`: cria identidade global nova ou substitui credencial na versão
  esperada, exige login `lab-*`, senha sintética e confirmação do fator TOTP.
  O passo de confirmação já é consumido; não pode ser reutilizado no login.
- `recovery`: emite seis códigos de 192 bits por `random_bytes`, somente hashes
  persistidos. Incrementa epoch e revoga sessões anteriores, sem remover MFA.
- Substituição de credencial/fator também incrementa epoch e revoga sessões.
  Nenhum método cria membership, assento, perfil ou acesso comercial.
- UUID de operação serve à idempotência por ator/mandato/alvo/ação. Replay só
  informa `already_completed`, sem reemitir códigos ou aplicar novos parâmetros.
  Se o retorno dos códigos for perdido após commit, exige **nova operação e
  mandato vigente** de recuperação; não guardar segredo em resultado idempotente.
- `reconcile`: mandato exato e MFA recente, apenas revoga referências vinculadas
  a uma operação inconclusiva do alvo. Nunca reativa ou repete login/efeito.
  Troca de conta pode envolver duas identidades: reconciliar cada alvo apenas
  com seu mandato; a marca `compensated` não desbloqueia holds de outro alvo.

## ADR — auditoria mínima

`IamAudit` aceita vocabulário fechado, UUID de correlação, ação/resultado,
ator/identidade alvo opcionais, instante e chave de origem pseudonimizada.
Não aceita metadata livre, exception text, login, e-mail, IP bruto, senha,
hash de senha, token, TOTP, recovery ou conteúdo comercial. São registrados
provisão/rotação/emissão e consumo de recuperação, falhas de senha/MFA,
step-up, logout, revogação, refresh e anomalias de prova de sessão.
Falha de auditoria não produz sucesso silencioso.

Tabela experimental `crm_iam_audit_events`: runtime só INSERT, sem UPDATE,
DELETE ou leitura ampla; setup de CI consulta os resultados dos testes.
Não é implementação da Auditoria Geral, WORM, retenção homologada, exportação
ou investigação administrativa. CORE06/IN-162/LGPD distinguem categorias e
prazos; nenhuma limpeza ou retenção física definitiva foi inventada aqui.

## ADR — antiabuso

Origens derivadas apenas de `REMOTE_ADDR` do transporte direto, validadas e
pseudonimizadas com HMAC-SHA256/APP_KEY do laboratório. Forwarded/X-Forwarded-For
são ignorados. Atrás de proxy, limita-se o peer do proxy; não inferir IP do
cliente sem contrato de proxies confiáveis. Faltam avaliação por dispositivo,
redes compartilhadas, IPv6, acessibilidade e contestação de bloqueio.

`IamAbuseGuard` reserva orçamento por ação/identidade e um orçamento de origem
compartilhado entre ações/identidades (spray/recovery). Candidatos: janela
60 s, 10 por identidade/ação, 30 por origem e backoff exponencial após falha
(base 1 s, teto 60 s). Não são política definitiva CORE04-D06. Mensagem neutra
idêntica para identidade desconhecida, credencial/fator inválido e bloqueio.
Nenhum reset de orçamento por login bem-sucedido ou rotação de sessão.

Store explícito `CRM_IAM_ABUSE_STORE=redis`; sem failover para array/file/null.
Array permitido **somente em testing**. Mutex Redis protege atualizações de
orçamento; contenção, corrupção, erro de leitura/escrita ou backend indisponível
negam a autenticação. Logout pode revogar sem depender do cache. Testes F1-03
usam limites F1-04 altos/backoff zero apenas para isolar a regressão dos limites
originais; testes F1-04 exercitam limites, expiração, contenção, spray e pane.
CI PostgreSQL inclui Redis efêmero real, sem infraestrutura externa.

Limites: cache é volátil; perda/evicção de chaves não equivale a prova durável
de histórico. Mutex possui lease e não é fencing sob pausa arbitrária de
processo. Não manter operação SQL/HTTP sob o lease curto. Dimensionamento,
failover Redis sem perda de histórico, quotas de cardinalidade/DoS e proteção
de origem/dispositivo exigem Segurança antes de qualquer rota ou release.

## ADR — falhas parciais e consistência

Não há transação distribuída entre PostgreSQL, sessão e auditoria.
`IamOperationRunner` mantém estados `prepared → completed` ou `compensated`:

1. Na transação IAM, registra intenção e evento attempted; referências atuais
   recebem security_hold, sem sobrescrever hold anterior. Locks de credenciais
   precedem locks de sessão. A intenção deve commitar antes de alterar sessão.
2. Executa o núcleo F1-03; novas referências registram operation_id.
3. Persiste explicitamente a sessão do servidor. Uma sessão nova com operação
   não completed continua inútil como prova, mesmo que o guard tenha usuário.
4. Confirma auditoria/estado completed e libera holds em transação SQL.
5. Falha após início de mutação: revoga referências antigas e novas da operação,
   limpa sessão/guard e registra compensação. Não tenta retornar sucesso/repetir.
   Pré-condição negada antes de mutação pode preservar a sessão anterior somente
   após compensação auditada e liberação do hold (ex.: exigir step-up recente).

Se a compensação ou auditoria também falhar, prepared/holds permanecem e a
prova de sessão nega até reconciliação interna, com novo mandato. Se a intenção
nem chegou a commitar, não houve nova concessão; indisponibilidade desconhecida
não autoriza reconstruir uma sessão a partir do guard ou de parâmetros.
O serviço de sessão deve propagar falhas de persistência; um handler que retorne
sucesso falso não é resolvido pelo runner. Crash após commit mas antes da resposta
é resultado inconclusivo para o chamador: inspeção/reconciliação, nunca replay
automático de fator, código de recuperação ou efeito externo.

Contexto da operação é scoped e limpo em finally. Não reutilizar em worker/job,
inverter locks ou reentrar. A fronteira F1-03 ainda mantém locks IAM através do
término da transação de membership. Contratos de idempotência/outbox/compensação
com efeitos comerciais, sessões distribuídas, APIs e workers seguem abertos.

## Segregação de roles do laboratório

| Role | Recorte | Negação |
|---|---|---|
| crm_iam_ci | Login F1-03, operações/holds e INSERT de auditoria | Sem provisão de credenciais, mandatos, auditoria mutável ou negócio |
| crm_provision_ci | Criação/substituição de credenciais sintéticas, recovery, reconciliação e INSERT de auditoria | Sem memberships/negócio, criação de mandato ou alteração de auditoria |
| crm_app_ci | Memberships com RLS F1-02 | Sem catálogos/credenciais/fatores/auditoria/operações IAM |
| Setup CI | Migração, mandatos e fixtures fictícios | Nunca usado como runtime |

Todas as roles runtime sem superuser/bypassrls/ownership. Conexão crm_provision
exige usuário/senha próprios, sem fallback. SELECT global em hashes/fatores
cifrados e UPDATE(revoked_at) de mandato (exigido para row lock PostgreSQL)
são riscos explícitos da role administrativa candidata. Código não emite ou
renova mandatos. Compromisso SQL/runtime não é contido por DTO ou GUC; exige
cofre, segregação, auditoria independente e revisão DBA/CORE09/SEG.

Migração 000003 só em `database/experimental-migrations/auth`, opt-in explícito,
com recusa de produção/profile desligado no up/down. Testes conferem descoberta
padrão, instalação explícita e rollback; nenhum deploy foi realizado.

## ADR — WebAuthn/passkeys versus TOTP

TOTP permanece no laboratório por compatibilidade F1-03; segredo compartilhado
e código digitado não vinculam o desafio ao site legítimo. WebAuthn oferece
credenciais de chave pública com escopo RP ID/origem e desafios verificáveis;
a validação de origem é parte obrigatória do desenho. Referência primária:
[W3C WebAuthn Level 3](https://www.w3.org/TR/webauthn-3/).

Antes de implementação: pacote mantido/validação criptográfica especializada,
RP ID e origens HTTPS, user verification, desafios de uso único, sincronização
de passkeys, troca/perda de dispositivo, acessibilidade, attestation conforme
risco e recuperação sem rebaixamento de MFA. Nenhum algoritmo próprio, pacote,
provedor pago, conta/hardware ou política definitiva foi adicionado.
Referência de cache: [Laravel 13 atomic locks](https://laravel.com/framework/docs/13.x/cache).

## Evidências e pendências

Testes escritos; aguardam execução real em Laravel/SQLite e PostgreSQL 16/Redis.
PHP/Composer/Docker indisponíveis no terminal local; não declarar execução local.
Registrar links e números após os workflows. CORE04-D04..D10, CORE01/02/03/05/06/09,
SEG-01/02, ARQ/INT, FIN/CON/LGPD e revisão humana IAM/DBA/Segurança permanecem
abertos. CI verde valida somente o protótipo sintético; não é aprovação de
titularidade, Auditoria Geral, antiabuso produtivo, integração ou release.
