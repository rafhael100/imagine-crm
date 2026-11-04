# F1-02 — tratamento da revisão técnica e conciliação de fontes

Data: 04/11/2026. [PR #4](https://github.com/rafhael100/imagine-crm/pull/4),
mesma branch `codex/f1-02-tenant-auth-boundary`, base
`dev/fast-track-crm-2026-11-04`. Referência: [revisão COMMENT](https://github.com/rafhael100/imagine-crm/pull/4#pullrequestreview-5776228177).
Não há APPROVE, merge ou GO de release. A revisão não possui comentários inline.

## Tratamento por apontamento

| Item da revisão | Tratamento nesta atualização | Limite preservado |
|---|---|---|
| 1 — origem guard/provider CRM | Removido o acoplamento ao `web`. Factory aceita somente guard dedicado `crm_identity` com driver session/provider eloquent/modelo `GlobalIdentity`; implementação e provider efetivos são conferidos. Configuração ausente/incorreta, guard request/token, modelo errado e UUID inválido negam. Testes reais de SessionGuard usam apenas fixtures confiáveis. | O guard dedicado continua ausente do config entregue. Verificação de credenciais, identidade vigente, MFA e revogação global de sessão dependem de CORE04/SEG/F1-03. Mapear modelo não comprova esses requisitos. |
| 2 — tenant/transação/custom GUC | Preservados reconsulta, lock, role restrita, recusa de pool contaminado e transação aninhada. Adicionados testes que demonstram explicitamente que a role runtime pode alterar a GUC e ler linha de membership suspensa via SQL direto, mas o adaptador Laravel nega; tenant ausente/malformado também é negado. | **Não é proteção contra SQL arbitrário/injection.** Validação de arquitetura e threat model por Segurança/DBA/CORE01 permanece pendente. Nenhuma assinatura/atestado, função privilegiada ou novo grant foi inventado. |
| 3 — reconciliação documental | Fontes fixadas abaixo; atualização do status histórico F0-03 e referência explícita no ADR F1-02. Nenhuma árvore documental foi mergeada/copied como nova decisão. | P1+P2 é referência documental posterior, não motor ACL habilitado. Catálogos, exceções e contratos físicos seguem abertos. |
| 4 — aceite apenas experimental | Escopo e critérios de revisão explicitados neste registro; CI será registrada separadamente por revisão testada. | CI verde não é aceite de Segurança, aprovação humana, GO para merge, login real, cliente, VPS ou produção. |

## Matriz de conciliação documental

Snapshot consultado da main: `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`.
Somente leitura das fontes; ancestralidade e base do PR foram preservadas.

| Fonte imutável | Estado reconciliado | Consequência para este PR |
|---|---|---|
| [DEC-FASTTRACK-01](https://github.com/rafhael100/imagine-crm/blob/e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012/docs/planejamento/CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md) | GO limitado para scaffold/desenvolvimento isolado, não liberação de produção | A Issue #3 e o pedido de revisão autorizam este recorte sintético; não concedem direitos de cliente. |
| [DEC-F1-P0-01](https://github.com/rafhael100/imagine-crm/blob/e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012/docs/planejamento/CRM-DEC-aceite-base-documental-F1-IAM-RLS-SEG-2026-11-04.md) e [pacote F1-P0](https://github.com/rafhael100/imagine-crm/blob/e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012/docs/planejamento/CRM-F1-IAM-RLS-SEG-PACOTE-TECNICO-GO-CHECKLIST-v0.1.md) | Base documental aceita; sem homologação física/GO F1 sensível | Não confundir numeração de temas documentais F1-01..05 com incrementos de código de F1-01/F1-02/F1-03. |
| [F1-CT01](https://github.com/rafhael100/imagine-crm/blob/e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012/docs/planejamento/CRM-F1-CT01-especificacao-fisica-IAM-RLS-MFA-SEG-proposta-v0.1.md) | Especificação física candidata, sem homologação; CT-F1-02 compara GUC com atestado verificável | O adaptador GUC é prova experimental limitada, não escolha produtiva homologada; alternativa de atestado continua para Segurança/DBA. |
| [Decisão delegada CORE02-D05](https://github.com/rafhael100/imagine-crm/blob/e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012/docs/planejamento/CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md) | Seleção documental de P1+P2 escopada; supera apenas a ausência de escolha da base histórica | Preservar a diretriz sem inferir catálogo executável, papéis, escopos, grants ou fechamento integral de CORE02-D05. |

Textos históricos de DEV-GATE/CORE que proíbem programação devem ser lidos com
DEC-FASTTRACK e o pedido explícito deste recorte. Isso não autoriza implantação
nem transforma escolha candidata em homologação. A numeração de 67 pendências
CORE permanece preservada, sem encerramento por esta revisão.

## Aceite experimental e dependências abertas

O critério de conclusão deste trabalho é: alterações pequenas no mesmo draft,
testes negativos Laravel/PostgreSQL efetivamente executados, logs vinculados e
nenhum privilégio operacional habilitado. O código somente é candidato a
revisão humana para eventual incorporação à branch de desenvolvimento;
incorporação não foi executada nem aprovada por este registro.

| Dependência | Validação ainda necessária / competência |
|---|---|
| CORE04-D04..D10 / F1-03 | Provider e origem de identidade verificada; credenciais, MFA obrigatório conforme papel, recovery, expiração e revogação real de sessões. IAM/Segurança/UX/Operação. |
| CORE01-D04..D07 / CT-F1-02 / SEG-01/02 | Contrato produtivo de contexto, SQL injection/atestado, roles/pools/workers, concorrência e integridade completa; revisão Segurança/DBA/CORE01. |
| CORE02-D04..D10 / CORE07/08 | Matriz real por recurso/ação/campo/escopo, delegação, exceções, entitlement/MFA e revogação. Owners de módulo/IAM/Segurança. |
| CORE03/05/06/09 | Jobs, mandatos de suporte, auditoria e identidades técnicas/cofre. Sem ativação por analogia à sessão humana. |
| ARQ/INT/G01..G06/FIN/CON/LGPD | Baseline, custos/contratos, integrações, dados e release sob aceites próprios. |

Nenhuma validação por especialista ou aceite humano é declarada realizada
por automação nesta atualização. Próximo incremento é **F1-03 sujeito a seu
escopo próprio**; não foi iniciado neste PR. O `DenyByDefaultAuthorizer`
permanece negando tudo, sem MFA real, grants, autenticação pública, dados de
clientes, conexão à VPS ou implantação.

## Evidência desta revisão

Testes adicionais foram escritos para configuração dedicada, recusa de fallback,
provider/guard efetivo incompatível, ausência de sessão e UUID inválido, além
dos casos PostgreSQL acima. Execução local indisponível: PHP/Composer/Docker
continuam ausentes do PATH. Os resultados novos serão registrados após os
workflows existentes; os 33 testes da revisão anterior não são evidência
de execução destas alterações.
