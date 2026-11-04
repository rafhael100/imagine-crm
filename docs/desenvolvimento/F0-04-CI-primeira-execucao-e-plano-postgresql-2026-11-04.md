# CRM Imagine — F0-04: primeira execução automatizada e preparação de PostgreSQL RLS

**Data:** 04/11/2026
**Branch de desenvolvimento:** `dev/fast-track-crm-2026-11-04`
**PR:** https://github.com/rafhael100/imagine-crm/pull/2 (rascunho)
**Decisão habilitadora:** [DEC-FASTTRACK-01](../planejamento/CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md)

## 1. Evidências verificadas de CI
- Workflow `.github/workflows/ci-f0.yml`: GitHub Actions faz checkout da branch, prepara PHP 8.4/Composer 2, valida `composer.json`, resolve dependências, cria `.env` transitório e chave local, executa `php artisan test`, sem migrations nem acesso à VPS.
- [Execução 41896405948](https://github.com/rafhael100/imagine-crm/actions/runs/41896405948): status **success**, Laravel **13.40.0** instalado no runner, **8 testes aprovados, 10 asserções, 0 falhas**.
- [Execução 41896403595](https://github.com/rafhael100/imagine-crm/actions/runs/41896403595): status **success**.
- As primeiras verificações cobrem contratos inertes de contexto/identidade e negação por padrão, além de rotas de saúde/ausência de página inicial de negócio. **Não comprovam RLS, persistência segura, MFA, login, confidencialidade de dados reais ou integridade entre tenants no banco.**
- **Observação crítica de reprodutibilidade:** no log consta `No composer.lock file present. Updating dependencies to latest instead of installing from lock file`. O `composer.lock` criado no runner é transitório e **não está versionado**. Deve ser analisado e adicionado ao PR como próximo incremento para fixar a árvore de dependências.
- A aplicação em produção, clientes, integração externa e dinheiro continuam **não habilitados**.

## 2. Próximo incremento técnico F1 — domínio multiempresa com banco efêmero

**Ambiente:** PostgreSQL **somente em CI/desenvolvimento isolado**, com dados totalmente fictícios e roles sem privilégios de superusuário/BYPASSRLS. Não reutilizar instância, credenciais, backups ou dados do CRM real.

**Ordem recomendada de implementação:**
1. Fechar diagrama ER inicial de **identidade global ↔ membership ↔ organização CRM**, sem equiparar CNPJ à identidade do tenant; conferir CORE01-D04..D07, CORE07, CORE08, SEG.
2. Especificar e revisar migration candidata de identidades globais, organizações e memberships com unicidade lógica, estados, histórico e FKs tenant-consistentes; **não** executar em produção.
3. Documentar roles PostgreSQL e matriz por tabela e operação. O role cotidiano não pode ter `BYPASSRLS`; `USING` e `WITH CHECK` precisam proteger leitura e escrita tenant-bound. Políticas de owner/`FORCE RLS` e transações/pooling requerem revisão.
4. Estabelecer caminho autenticado para resolução do tenant, não baseado em header/body ou identificador livre do usuário; aplicar negação se membership ou direito não comprovado.
5. Criar CI separada de integração com PostgreSQL efêmero e fixtures de duas organizações A/B, com casos negativos de `SELECT`, `INSERT`, `UPDATE`, acesso por tenant errado, membership revogada e troca de contexto na mesma conexão/pool. Marcar como **testes escritos mas não executados** até execução efetiva.
6. Publicar logs de CI sem segredos e resultados concretos no PR; revisar dependências CORE02/03/04/05/06/07/08/09, SEG-01/02 e INT quando aplicável.
7. Liberar endpoints tenant-bound **somente** após contraprovas de isolamento e política de autorização verificada. Sem acesso de clientes ou produção antes de gates próprios.

## 3. Estado e limitações

A autorização executiva acelera desenvolvimento **sem reduzir padrões de segurança**. Os **67 IDs técnicos CORE**, SEG-01/02, ARQ/AGT, INT P0/G01..G06 e FIN/CON não foram encerrados por CI verde.

**Nada nesta etapa exige que o usuário forneça senhas, chaves, conexões de produção ou acesso à VPS para rodar a CI atual.** O provisionamento posterior de ambiente de desenvolvimento isolado deve ser explícito e autorizado, sem compartilhar segredos no chat.
