# F3-AIA — reciprocidade do novo emissor canônico F227

**14/11/2026 — somente laboratório, NO-GO.**

O CRM consumia uma revisão Agents anterior ao emissor HMAC atômico. Esta entrega atualiza o pin de checkout da CI CRM para o Agents squash DEV `5a2c8af18448aeebfe8eda0d7570f34ffbe24611` ([PR #316](https://github.com/rafhael100/imagine-ai-agents/pull/316), CI pós-merge aprovada) e executa o código **original** do novo `MockCrmReadRequest::issueSyntheticSignedFixture` contra o `AiaHmacFixturePreflight` e `AiaFixtureNonceLedger` originais do CRM.

O runner `scripts/ci/check-aia-f227-signer-reciprocal.php`, sem Composer, rede ou banco, valida emissão conjunta de bytes/prova, matching independente no CRM, replay e **13 mutações negativas isoladas**. Também demonstra explicitamente, em dois objetos de ledger diferentes, que **o mesmo nonce pode ser aceito de novo entre instâncias**. Esta fragilidade é intencional e impede qualquer alegação de autenticação multiworker. Mesmo uma prova aceita retorna somente `DENIED_OR_NOT_FOUND`.

O pin é SHA fixo e conferido no workflow, rodando PHP 8.3 e 8.4. As suítes F0/PostgreSQL/Sites continuam obrigatórias e não foram substituídas. O código não adiciona rota, API, chave secreta real, IAM, ACL, cache compartilhado, migrations, `main` ou deploy.

**CRM #57 permanece OPEN/DRAFT, 14 gates e 13 cenários de homologação real continuam NO-GO.** A solução operacional futura precisa de credencial protegida/revogável, verificador de identidade confiável, replay store durável/atômico e autorização por recurso em staging revisado.
