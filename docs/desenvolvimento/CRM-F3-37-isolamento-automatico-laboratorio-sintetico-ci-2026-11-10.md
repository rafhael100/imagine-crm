# F3-37 — proteção automática contra ligação do laboratório sintético ao runtime

**Data:** 10/11/2026. **Ambiente:** branch DEV do CRM Imagine, CI e testes unitários apenas. **Estado:** proteção estática adicional; não é homologação de segurança e não habilita acesso a dados reais.

## Problema identificado

O teste atual `FollowUpRuntimeSeparationTripwireTest` usa uma lista explícita dos nomes de classes que não devem aparecer em rotas, HTTP, providers, jobs, console ou bootstrap. Se uma nova classe experimental for adicionada a `App\Support\Synthetic` sem atualização manual dessa lista, uma referência indevida poderá escapar desse teste.

Isso é especialmente relevante nos recortes CSV F3-20..F3-36: as ferramentas fictícias de revisão, versão, conflito de idempotência e perda de ACK **não possuem autenticação, competência por tenant, trilha, integridade transacional nem autorização para importar**.

## Implementação

`tests/Unit/SyntheticRuntimeIsolationTest.php`:

- Descobre automaticamente os nomes de todos os arquivos `.php` de `app/Support/Synthetic/` a cada execução, validando o nome de classe esperado e garantindo que o catálogo de laboratório não esteja vazio.
- Examina recursivamente **todos os arquivos PHP de `app/`, `routes/` e `bootstrap/`**, excluindo exclusivamente as próprias implementações em `app/Support/Synthetic/`, que podem depender umas das outras. A cobertura é mais abrangente do que a lista preexistente de entrypoints.
- Rejeita referências literais aos nomes das classes descobertas, às formas de namespace `Support\Synthetic` e `Support\\Synthetic` e ao caminho `Support/Synthetic`. A triagem detecta imports, aliases, includes e referências estáticas comuns.
- Verifica por testes negativos que uma classe **futura** de laboratório também é detectável, mesmo que não exista hoje; verifica o caso benigno de código de aplicação que não importa o laboratório.
- Mantém `FollowUpRuntimeSeparationTripwireTest` inalterado. O novo teste é camada complementar que reduzirá a chance de regressão quando mais protótipos forem criados.

## Garantias e limitações

Uma falha deste teste significa que o código de aplicação contém uma ligação textual direta que deve ser removida ou explicitamente reavaliada sob novo gate. Um resultado verde **não prova isolamento em runtime**, autorização, ausência de código dinâmico/reflexão não padronizada, proteção de rotas, tenant, RLS, segurança de importação, privacidade ou ausência de todos os caminhos indiretos. O teste estático também pode sinalizar palavras em comentários/textos benignos: qualquer falso positivo deve ser investigado e corrigido de forma auditável, sem desabilitar a proteção de maneira genérica.

**NO-GO:** nenhum controller, rota, binding, API, `ALLOW`, autenticação positiva, SQL, migração, parser, upload/import/export real, cliente, credencial, deploy, VPS, gasto ou alteração em `main`. A revisão independente [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) e [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) permanecem pendentes; o contrato F3-32 continua proposta não aprovada. Atualização somente DEV após revisão integral do diff e F0/PostgreSQL RLS verdes na SHA exata.
