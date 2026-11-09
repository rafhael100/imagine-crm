# F3-25 — Tripwire de release para portabilidade ainda não homologada

**Data:** 09/11/2026. **Escopo:** teste de regressão exclusivamente em ambiente de CI/DEV, com fixtures e contratos negativos, sem efeito operacional. Complementa a [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), F3-20..24 e [LeadIntakeReleaseGateTest](../../tests/Feature/LeadIntakeReleaseGateTest.php).

## Por que este gate existe

As classes experimentais de importação são úteis para preparar o fluxo e investigar metadados, mas **não** podem ser confundidas com uma funcionalidade autorizada. O teste `PortabilityReleaseBoundaryTest` protege invariantes de **negação** durante a fase laboratorial:

1. Nenhuma rota pública cujo segmento identifique importação, exportação, portabilidade, backup, upload ou anexos deve existir antes de liberação específica, inclusive se a rota for apenas GET.
2. Uma prévia CSV sintética completa, sem alertas e com referências de tenant exatamente coincidentes **continua negada** pela classe de escopo `SyntheticMappedImportDenyGate` (`SCOPE_NOT_VERIFIED`).
3. Tentativas de organização diferente, exportação, campos extra/duplicados, indicação de fórmula e CSV sem linhas permanecem com `CONTRACT_INVALID` e `allowed=false`.
4. As classes de laboratório não devem ganhar `import`, `export`, `upload`, `persist`, `allow` ou outras portas de operação sem uma revisão técnica que explicitamente substitua este teste e satisfaça os gates independentes.

## Limites da evidência

- É um **tripwire negativo**, não a implementação do autorizador real. Não atesta segurança final de API, dados pessoais, grants, RLS, processamento de arquivos, backup/restauração, identidade/autenticação, campos ou infraestrutura.
- Não altera `DenyByDefaultAuthorizer`, rotas, migrations, políticas, banco, permissões, contratos financeiros ou comportamentos do negócio.
- Não significa que toda rota GET seja perigosa ou proibida para sempre; futuras rotas de portabilidade precisam de autorização por competência, contrato seguro e teste específico antes de remover a restrição conservadora.
- O gate será executado nos pipelines existentes de Laravel e PostgreSQL/RLS com dados sintéticos. **Um sucesso não libera `main`, VPS nem produção.**

**Gates pendentes:** CORE 67 identificadores técnicos, SEG/DBA independente, AUT, INT, LGPD, políticas por tenant/recurso/campo, confirmação humana, segurança dos arquivos e plano de homologação.

**Integração autorizável:** somente branch DEV, após diff revisado e dois checks verdes na SHA exata.
