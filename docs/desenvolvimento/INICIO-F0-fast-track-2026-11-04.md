# CRM Imagine — F0: inicio controlado do desenvolvimento

**Data:** 04/11/2026
**Branch:** `dev/fast-track-crm-2026-11-04`.
**Autorizacao executiva:** [DEC-FASTTRACK-01](../planejamento/CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md).
**Status:** primeiro scaffold de classes e dependencias para desenvolvimento; **nao e um aplicativo Laravel completo ou executavel**, nao foram instaladas dependencias ou realizados testes.

## Conteudo criado na F0

- `composer.json`: aponta PHP >=8.3 e Laravel 13.x como versoes de referencia, **sem composer.lock ate instalacao oficial**.
- `app/Core/Tenancy/TenantContext.php`: tipo simples e validacao basica, **nao** comprovacao de membership.
- `app/Core/Authorization/AccessRequest.php`: contrato de recurso/acao/campo sem privilegio.
- `app/Core/Authorization/AuthorizationDecision.php`: resultado de negacao por padrao.
- `app/Core/Authorization/DenyByDefaultAuthorizer.php`: devolve negacao para toda operacao; **nenhum acesso habilitado**.
- `.env.example` e `.gitignore`: sem segredos reais, dump ou credencial.

## Proximas etapas tecnicas na branch

1. Inicializar a estrutura **oficial** de aplicacao Laravel 13 e instalar dependencias com Composer em ambiente de desenvolvimento autorizado; incorporar `composer.lock` e estrutura segura, nao copiar scaffolds obsoletos. **Composer nao estava disponivel na ferramenta de criacao deste commit.**
2. Contratar especificacao IAM/CORE01, CORE02, SEG-01/02, roles Postgres, ownership e RLS antes de criar endpoints de dados multiempresa.
3. Construir ambientes e fixtures **sinteticas**, roteiros negativos e testes versionados. Registrar resultado e ambiente **somente quando realmente executados**.
4. Separar os incrementos por PR e owner de negocio: IAM, DAT, FIN/CON, INT, ATD, VEN, AGD, AUT, IA, Portal/Sites.
5. Exigir revisao/security signoff para qualquer API com dados de clientes, import/export, credencial, envio externo ou cobranca, e aceite de release separado antes de qualquer uso real.

## Limites obrigatorios

- Sem banco real, SQL executado, pagamento, envio WhatsApp, conexao API externa, n8n, provisionamento, producao, despesas ou manipulacao de dados pessoais.
- Nao assumir que a branch ou o `composer.json` ja forneca um Laravel iniciado. Autoload so funcionara apos instalacao das dependencias.
- A autorizacao de F0 **nao** significa que os 67 CORE-D04..D10 e ARQ/SEG/INT/G01..G06 estejam tecnicamente fechados.
- Em particular, o `TenantContext` pode ser construido por codigo: **nao eh garantia de isolamento**. RLS em banco e policies backend nao estao implementadas ainda.

**Pronto para desenvolvimento de engenharia F0, nao pronto para clientes, merge em producao ou implantacao.**
