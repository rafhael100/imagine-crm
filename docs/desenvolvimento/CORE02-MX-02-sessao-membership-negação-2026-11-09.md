# CORE02-MX-02 — Invariantes negativas entre sessão, membership e ação

**Data:** 09/11/2026. **Status:** cobertura de laboratório DEV, **não** homologação de autenticação/autorizações reais. Complementa [CORE02-MX-01](CORE02-MX-01-matriz-negativa-autorizacao-2026-11-09.md), [CRM-REV-01](../planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) e [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27).

## Fluxo verificado

O teste `SessionMembershipDenyMatrixTest` exercita a composição existente `GuardPrincipalSource → TenantAccessBoundary → ActiveMembershipStore → DenyByDefaultAuthorizer`, com a **sessão experimental e provas MFA do laboratório** fornecidas pelo `SyntheticIam`. O repositório de membership usado nesse teste é um **double em memória**, não uma fonte confiável de associações reais. A fonte PostgreSQL com privilégios restritos/RLS já tem uma suíte independente que continua obrigatória.

### Casos

- **54 pares de ação/campo** (6 ações do catálogo × 9 classificações) contra uma sessão sintética autenticada e membership descritiva ativa: todos terminam com `AuthorizationException`, **nenhum efeito de negócio executado**. A leitura de membership é observada 54 vezes, sem conversão de snapshot em direito.
- **Revogação e escopo:** após uma negação em membership ativa, a mesma instância de boundary também nega associações suspensas, desativadas e de outra organização. Não reutiliza o antigo resultado como liberação.
- **Confusão de sujeito:** `expectedPrincipalId` divergente da sessão é negado **antes** de consultar membership; após `logout` a tentativa é negada por autenticação antes da consulta. Não há efeito.
- Todos os casos usam identificadores inventados, nunca dados ou contas de clientes.

## O que NÃO prova nem cria

O teste **não** implementa permissões, papéis, perfis, acessos a tabelas de leads, política de equipes, RLS de produção, conta real, MFA para cliente nem um autorizador. Não define `ALLOW`. A membership em memória é propositalmente forjável e serve para demonstrar por que o fechamento de autorização não pode ocorrer apenas com objetos sintaticamente consistentes.

A revisão independente SEG/DBA, a matriz CORE02-P0 de recursos/ações/campos, as decisões de consentimento/LGPD e os contratos de delegação/identidade técnica continuam pendentes. Nenhuma CI substitui a homologação desses componentes.

**Escopo da entrega:** testes e documentação em branch filha DEV, sem `main`, migrations, grants, rotas, VPS, deploy, gastos ou dados reais. Merge permitido somente após revisão de diff, HEAD/base exatos e duas suítes CI verdes.
