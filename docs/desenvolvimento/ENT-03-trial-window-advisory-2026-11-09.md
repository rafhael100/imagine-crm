# ENT-03 — Prévia informativa do intervalo de trial (DEV ONLY)

**Data:** 09/11/2026. **Escopo:** apenas código PHP puro e testes unitários com instantes fictícios, sem acesso a usuários ou integração comercial.

## Base funcional e objetivo

A [CRM-REV-02](../planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) separa o **trial CRM de 14 dias** da cortesia por site, das assinaturas pagas e dos demais regimes de retenção. Para oferecer uma futura visualização sem misturar as regras de acesso, ENT-03 representa uma janela ilustrativa de 14 × 86.400 segundos contados de um instante UTC já fornecido, exibindo fase descritiva, término, segundos restantes e dias arredondados para cima.

Os três rótulos são **before_start**, **within_window** e **after_window**. O marco final exato já é **after_window**. O cenário anterior ao início retorna o intervalo completo apenas para visualização. Nenhum rótulo concede, revoga ou reativa acesso.

## Implementação

- `app/Core/Plans/TrialWindowPhase.php`: rótulos sem valor de autorização.
- `app/Core/Plans/TrialWindowAdvisory.php`: valores inteiros imutáveis, proteção contra tempos negativos e overflow, sem relógio próprio, banco, sessão, empresa, telefone ou CNPJ.
- `tests/Unit/TrialWindowAdvisoryTest.php`: fronteiras de início e fim, um segundo antes/depois, contagem em dias, `PHP_INT_MAX`, falha fechada e ausência de métodos que concedam direito.

## Pendências explícitas (NÃO implementadas)

1. Escolher e auditar o marco confiável de início do trial e a semântica contratual de dias corridos, fuso/ativação (instantes UTC são **hipótese apenas deste laboratório**).
2. Uma tentativa por CNPJ, OTP dos dois contatos, estado da assinatura e autorização por tenant/membership — exigem contratos e gates CORE/SEG/AUT/FIN/CON.
3. Expiração que bloqueia operação, conversão voluntária, retenção de **60 dias pós-expiração**, exclusão LGPD e notificações — **não** são efeitos desta classe.
4. Não misturar 60 dias do trial com 120 dias de assinatura CRM cancelada, 18 dias da fila Site→CRM ou regras de cortesia.

**Não contém** routes, migrations, eventos, filas, grants, `ALLOW`, cobranças, envio externo, API, mudanças em `main` ou deploy. Merge, caso aprovado por revisão e CI, somente para `dev/fast-track-crm-2026-11-04`. Este documento não é homologação.
