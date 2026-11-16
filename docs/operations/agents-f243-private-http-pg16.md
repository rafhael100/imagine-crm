# CRM F243 — cadeia HTTP privada F248 → assinatura Agents → nonce PG16

**15/11/2026 · somente CI descartável · DRAFT / NO-GO / NO-MERGE / NO-DEPLOY**

Esta entrega torna o ensaio recíproco mais próximo do fluxo técnico definitivo, ainda **sem efetuar nenhuma leitura de lead**:

1. O teste constrói `Illuminate\Http\Request` fictícia, sem socket nem rota registrada, com corpo canônico e assinatura Ed25519 original Agents (SHA `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5`).
2. `AgentsSignedIngressCandidate` da árvore **CRM funcional #614/F248** valida os limites de entrada, mas entrega exclusivamente um callback de diagnóstico, disponível só quando `CRM_AIA_F248_PARITY_LAB`, `GITHUB_ACTIONS` e ambiente `testing` estiverem simultaneamente ativos.
3. A nova `AgentsPrivatePgDiagnostic` não está vinculada a rotas ou container. Ela verifica marcadores rígidos da CI, escopo sintético server-owned, o verificador original Ed25519 e só então invoca o **ledger PostgreSQL16 existente no CRM privado**.
4. O primeiro ensaio com uma chave/nonce fictícios registra uma reserva na tabela temporária; o segundo, com outra conexão física, recebe negação de replay. Ambos continuam retornando a mesma negativa pública HTTP 404.
5. Há regressões de assinatura falsificada, escopo de recurso divergente, cabeçalho de tenant injetado, indisponibilidade da tabela PG, ausência de rota e não divulgação de projeção. O parser Agents F239 valida a resposta JSON canônica e o Cache-Control normalizado pelo Symfony.

`ci-crm-aia-f243-http-pg.yml` usa PostgreSQL 16 descartável, PHP 8.4, dependências já fixadas e checkout SHA-fixado do Agents. Não modifica a VPS, não migra schemas de produção, não gera rota de leitura, não armazena chave privada real e não envolve qualquer tenant ou dado de cliente. Esta classe **não contém um autorizador CRM real**, apenas um diagnóstico sintético de autenticidade/anti-replay e formato de recusa.

Para uso operacional faltam: IAM serviço-serviço, chave e vínculo de empresa do lado CRM, RLS/ACL por membro, finalidade, campo e recurso, revogação, gestão de segredos, TLS/mTLS, auditoria e revisão independente. A transição deverá reconciliar de modo revisável CRM #602, CRM #614 e Agents #332. Os **14 gates, 7 decisões e 13 cenários da CRM #57 continuam NO-GO**.
