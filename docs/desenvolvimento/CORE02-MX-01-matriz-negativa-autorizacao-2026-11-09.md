# CORE02-MX-01 — Matriz negativa de autorização (laboratório DEV)

**Data:** 09/11/2026. **Status:** instrumento experimental de testes e revisão, **não** política de direitos aprovada. **Mandato:** continuidade DEV inerte conforme `AGENTS.md`; [CRM-REV-01](../planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), itens N-01..N-13 e CORE-P0-02; [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), revisão SEG/DBA independente pendente.

## 1. Cobertura de segurança negativa (reproduzível)

O teste `AuthorizationDenyMatrixCoverageTest` usa **somente** o catálogo `lab.record` de `ExperimentalResourceCatalog`, com identificadores fictícios. Executa o produto cartesiano:

| Dimensão | Valores de laboratório | Quantidade |
|---|---|---:|
| Ação | `read`, `search`, `update`, `export`, `report`, `execute` | 6 |
| Classificação do campo | `F-ID`, `F-BIZ`, `F-PII`, `F-RES`, `F-CONS`, `F-FIN`, `F-SEC`, `F-EVID`, `F-ART` | 9 |
| Escopo declarado | `tenant`, `record`, `team`, `unit` | 4 |
| Canal declarado | `interactive`, `job`, `ai`, `support` | 4 |
| **Total** | 6 × 9 × 4 × 4 | **864 decisões, todas DENY** |

O resultado esperado do autorizador atual é **54 `POLICY_NOT_CONFIGURED`**, **162 `SCOPE_NOT_VERIFIED`** e **648 `CHANNEL_AUTHORITY_MISSING`**, com `allowed=false` em todas as combinações. Esses códigos são somente sinais sanitizados; **não** validam a real pertinência de escopo/ator/campo nem representam direitos aprovados.

Testes adicionais negam tentativas de uso direto de campos, reuso de envelope contra outro contexto (inclusive com strings iguais), troca de tenant, membership suspensa/desativada ou divergente, todas as nove classificações simultâneas e campos adicionais do tipo `role`, `is_admin`, `tenant_verified`, `allow` e `field_grants` fornecidos na descrição. O catálogo continua limitado ao recurso fictício `lab.record`.

## 2. Matriz de decisão futura (todos os privilégios permanecem não homologados)

| Ator/contexto candidato | Evidência necessária para interpretar uma futura solicitação | Sem conclusão automática |
|---|---|---|
| Administrador da empresa | Autenticação/MFA, associação vigente à organização, perfil e recurso/ação/campo, limites do plano | A função de administrador não concede exportação universal nem acesso a outra organização |
| Funcionário / gestor de equipe | Membership, equipe e visibilidade vigente, autoria/posse dos registros, ações diferenciadas | Pertencer à equipe não é permissão para excluir, transferir ou exportar |
| Administrador Imagine | Identidade administrativa, mandato e finalidade, segregação do suporte, ações centrais homologadas | Administrador global não lê dados comerciais do cliente por padrão |
| Acesso excepcional de suporte | Solicitação/caso autorizado, escopo, janela, aprovação aplicável, trilha e revogação | Ticket, instrução do cliente ou conta técnica não criam bypass irrestrito |
| Agente/automação/job | Identidade técnica distinta, contrato da ferramenta, mandato por ação/tenant, revalidação no momento da execução | Prompt, webhook, contexto serializado ou job previamente agendado não fornecem autorização |
| Auditoria e investigação | Finalidade, categoria de evidência, escopo de consulta e direito de exportação separados | Ler logs não equivale a alterar dados de negócio |

Nenhuma célula da tabela constitui `ALLOW` ou decisão final de RBAC/ABAC. Em particular, não foram inventados nomes finais de perfis, exceções de suporte, classificação real de campos nem regras comerciais.

## 3. Gates para substituir a negação geral por política efetiva

1. Definir os proprietários de decisão por módulo, recurso, ação, campo e efeito; reconciliar as matrizes aprovadas de Básico e planos pagos, sem presumir igualdades entre eles.
2. Fixar origem autenticada de principal, tenant selecionado e membership vigente; versionamento/revogação de sessão, MFA e consentimento onde aplicável.
3. Aprovar matriz de escopos (próprio/equipe/empresa/registro), classificações e diferenças entre `read`, `search`, `update`, `delete`, `export`, `report` e execução por agente.
4. Comprovar isolamento Laravel + PostgreSQL RLS, inclusive roles, job assíncrono, limpeza de contexto transacional e ausência de `BYPASSRLS`.
5. Definir mandato de suporte e identidade técnica, auditoria com minimização/retentão LGPD, resposta a falha de autenticação e revogação.
6. Exigir revisão independente SEG/DBA e aceite itemizado dos bloqueios CORE/AUT/INT/LGPD, seguidos de homologação e autorização de release separados.

## 4. Limites da entrega

**Não há** alteração no `DenyByDefaultAuthorizer`, `AuthorizationIntent`, catálogo, grants, rotas, tabelas, migrations, storage, usuários, pagamentos, segredos, `main` ou produção. Toda evidência é de código e fixtures em CI. Os **67 identificadores CORE** e a revisão independente SEG/DBA da Issue #27 continuam **abertos**.

Os números 864/54/162/648 são **contagens de casos de teste**, não porcentagem de funcionalidade, direitos criados, nível de segurança ou cobertura global do produto. A CI não substitui inspeção humana nem prova de isolamento multiempresa em produção.
