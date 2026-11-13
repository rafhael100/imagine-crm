# CRM OPS-66 — Correções HTTP OTP e orçamento antiabuso (13/11/2026)

**Escopo:** provas sintéticas no kernel HTTP Laravel (rotas registradas exclusivamente por PHPUnit). Esta nota é rastreabilidade de código revisado e CI; **não** é liberação de cadastro, autenticação, trial, provedor de mensagem ou VPS.

## Baselines verificadas no GitHub

- **DEV HEAD após os dois merges:** dc39c805ca883bf6885e60c6bccc8cfd01c05556.
- **Main preservada:** e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012.
- F3-256 — [PR #507](https://github.com/rafhael100/imagine-crm/pull/507), merge DEV c614154441f029cd8fbfcf864cd7514b4e96ad42. Altera somente tests/Support/SignupTrialHttpFixture.php e cria tests/Feature/SignupTrialHttpDuplicateKeyBoundaryTest.php. Rejeita, antes de mutar estado fictício, JSON com chaves repetidas, inclusive fixture codificada por escape Unicode.
- F3-257 — [PR #508](https://github.com/rafhael100/imagine-crm/pull/508), merge DEV dc39c805ca883bf6885e60c6bccc8cfd01c05556. Cria tests/Feature/SignupTrialHttpAttemptBudgetTest.php. Exercita pelo kernel Laravel o limite local de cinco tentativas por OTP, 60 verificações por origem e três emissões por contato, incluindo cabeçalhos, mensagens neutras e ausência de rota pública.

## Evidências de CI na HEAD exata da PR

| PR | SHA testada | Checks completed/success |
| --- | --- | --- |
| [#507](https://github.com/rafhael100/imagine-crm/pull/507) | 354bba074ab57ffce4b8e72b5c93831a67721608 | [F0](https://github.com/rafhael100/imagine-crm/actions/runs/43487164339), [PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43487164514), [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43487164317), [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43487164761), [HTTP route seal](https://github.com/rafhael100/imagine-crm/actions/runs/43487164304), [Composer no-dev](https://github.com/rafhael100/imagine-crm/actions/runs/43487164229) — **6/6 success** |
| [#508](https://github.com/rafhael100/imagine-crm/pull/508) | ec751c7a69d00567003e52b8d819b2855f5b459d | [F0](https://github.com/rafhael100/imagine-crm/actions/runs/43487728824), [PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43487728814), [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43487728852), [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43487728826) — **4/4 success** |

Estes testes executam código real de PHP/Laravel no runner efêmero, mas **não** verificam comportamento de infraestrutura privada nem garantem que nenhum erro de segurança adicional exista. A defesa por contagem de dois-pontos da F3-256 é válida **somente para o microprotocolo restrito**, onde nomes e valores permitidos não contêm esse sinal. **Não a reutilizar como parser genérico de JSON de produção.**

## Bloqueios remanescentes — NO-GO

1. [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27): revisão independente de privilégios, RLS, migrações, isolamento, backup e recuperação.
2. [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259): identidade real de serviço e por empresa, autorização, consentimento/finalidade, retenção, atomicidade e reconciliação durável da integração Portal → CRM.
3. [Design/UX #416](https://github.com/rafhael100/imagine-crm/issues/416): identidade Node original e jornada validada nas telas reais; a [PR visual #485](https://github.com/rafhael100/imagine-crm/pull/485) continua distinta das provas HTTP.
4. Provas PG16 concorrentes [#468](https://github.com/rafhael100/imagine-crm/pull/468), [#470](https://github.com/rafhael100/imagine-crm/pull/470), [#477](https://github.com/rafhael100/imagine-crm/pull/477) permanecem em revisão — CI isolada não substitui aceite de banco privado.

**Critério para próximo estágio funcional:** staging privado com usuários/empresas de teste, credenciais segregadas e gate competente de segurança/DBA/LGPD; **sem alterar main, produção, VPS, sem disparar mensagem/WhatsApp e sem dados pessoais reais** até autorização e homologação específicas.
