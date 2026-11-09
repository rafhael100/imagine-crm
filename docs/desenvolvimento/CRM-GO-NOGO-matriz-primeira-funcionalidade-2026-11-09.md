# CRM Imagine — matriz GO/NO-GO para primeiro recorte funcional

**Data:** 09/11/2026. **Estado:** proposta operacional, **não homologação**.  
**Base:** `dev/fast-track-crm-2026-11-04`; aplicar exclusivamente em DEV.  
**Escopo candidato S01:** futura autoconsulta read-only do resumo da própria organização e da própria membership, sem recursos de clientes reais até GO específico.

## 1. O que a CI efetivamente demonstra

Os testes Laravel, PostgreSQL/RLS e Chromium verificam apenas recortes identificados com dados artificiais, dentro de ambientes descartáveis. Eles **não** comprovam autenticação efetiva de usuário, autorização por ação/campo, LGPD, permissões por plano, segregação de ambientes persistentes, backup de produção, operação de vendas ou segurança da VPS. O `DenyByDefaultAuthorizer` segue exclusivo; nenhum `ALLOW` é autorizado.

Os protótipos F3-31/F3-32 agregam pontuações e snapshots artificiais em memória. Diferenças entre snapshots **não** rastreiam leads ou conversões reais. Eles não habilitam SQL, endpoints, integração de formulários ou persistência.

## 2. Gates verificáveis para a primeira funcionalidade

| Gate | Evidência exigida antes de implementação/GO | Estado |
|---|---|---|
| R01-D01..D05 / FUNC-P0-01 | Aceites itemizados de campos, membership, estados, segurança e homologação; owners por competência | **PENDENTE** — propostas não aprovadas |
| CORE02-D04..D10 | Classificação de campos; políticas por recurso/ação/campo/escopo; precedência de grants/denies, revogação; provas de impossibilidade de autoelevação | **ABERTO** |
| IAM/CORE04/CORE07 | Sessão humana verificada, MFA por risco, identidade e vínculo vigentes, tenant server-side, revogação entre requests | **ABERTO** |
| SEG/DBA #27 | Parecer de revisor independente identificado, matriz P0/P1, controles para migrations destrutivas, credenciais, isolamento, restores e RLS non-bypass | **ABERTO** |
| CORE01/CORE09/SQL | Testes cross-tenant e múltiplas memberships; transação/contexto limpo, pool seguro, credenciais de operação com mínimos privilégios | **ABERTO** |
| CORE06/LGPD | Finalidade, classificação e minimização, trilha sanitizada, retenção, acesso legal e tratamento de incidentes | **ABERTO** |
| CORE08/FIN/CON | Matriz de direito mínimo nos estados trial, cortesia, inadimplência, cancelamento e retenção; sem derivar permissão do pagamento | **ABERTO** |
| QA/CORE10 | Plano S01-T01..T22 revisado, evidência com SHA, ambientes e cenários negativos + inspeção manual WCAG | **ABERTO** |
| Infraestrutura | VPS autorizada e acessível, ambientes isolados, deploy/rollback seguros, backups e restore comprovados | **PENDENTE** |

**O aceite de cada gate depende de evidências concretas por item e de responsável competente.** Assumir responsabilidade decisória não substitui um parecer independente SEG/DBA.

## 3. Próximo fluxo de decisão (sem inventar aprovação)

1. Submeter as alternativas A de **R01-D01..D05** da [proposta S01-R01](FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) à direção, mantendo-as **não aprovadas** até manifestação expressa. Não inferir resultado de estados comerciais.
2. Designar efetivamente Segurança/DBA independente para a [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27). Solicitar evidência assinada/atribuída para P0/P1; não marcar revisão do autor como independente.
3. Confrontar inventário de campos, matriz de concessões e negações, provas de sessão e SQL/RLS com CORE02 e owners. Fechar cada subgate apenas com registro de aceite.
4. Autorizar **separadamente** implementação restrita, casos positivos e eventual `ALLOW` do recorte S01. Se faltar qualquer pré-condição impeditiva, manter NO-GO.
5. Só discutir integração `main`, VPS e produção após homologação própria, testes reprodutíveis e decisão explícita de release.

## 4. Controles de contenção enquanto os gates permanecerem abertos

- É permitido continuar com **código puro, provas negativas, CI e protótipos sintéticos**, sem dados reais, rotas externas, autorizações de negócio, migrations operacionais, cobrança ou custos adicionais.
- PRs #2 e #21 continuam DRAFT para `main`. PRs #23/#24/#25/#26/#28/#29/#30/#49/#57 mantêm suas restrições próprias e não devem ser integrados apenas por CI verde.
- `app.tenant_id` ou GUC PostgreSQL não são autenticação; nomes de empresas/roles/planos não provam entitlement.
- Nenhum documento, fixture, teste ou comentário deve declarar o CRM finalizado ou utilizável para clientes sem evidência de implantação e homologação.

**Referências:** [Issue S01 #22](https://github.com/rafhael100/imagine-crm/issues/22), [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [STATUS-ATUAL.md](../../STATUS-ATUAL.md), [AGENTS.md](../../AGENTS.md).
