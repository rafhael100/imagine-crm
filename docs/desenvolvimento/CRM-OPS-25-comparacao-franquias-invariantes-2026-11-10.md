# CRM Imagine — OPS-25: comparação sintética de franquias e consistência REV-F5

**Levantamento:** 10/11/2026 (America/Sao_Paulo) · **Destino:** exclusivamente `dev/fast-track-crm-2026-11-04`.  
**Natureza:** testes e prévia offline com valores totalmente inventados. Não há medição, comparação de contas reais nem funcionalidades de cobrança.

## 1. Incrementos concluídos somente na DEV

| PR | Entrega verificável | Commit squash DEV |
|---|---|---|
| [#147 — REV-F5-03](https://github.com/rafhael100/imagine-crm/pull/147) | `SyntheticQuotaSnapshotComparison` recebe dois `QuotaSnapshotReadout` fictícios, exige **mesmo conjunto completo de medidores**, aceita 1..12 medidores sem somar grandes volumes, mantém ordem do primeiro conjunto e calcula deltas de uso, limite, restante e excesso; compara bandas e tendências sem criar autoridade. | `9dc41b31ab79704d022e03ce97f94cc4a52b931c` |
| [#148 — UX-REV-F5-03](https://github.com/rafhael100/imagine-crm/pull/148) | Painel offline com escolha acessível de 4 exemplos (leads, usuários, automações, armazenamento ilustrativo), cenários A/B, delta de uso/limite, saldo/excesso e banda; testes PHP de paridade com a classe e navegador desktop/320px. | `84d2a8daa3bfda119cad29dd346ed0112519433e` |
| [#149 — QA-REV-F5-04](https://github.com/rafhael100/imagine-crm/pull/149) | PHPUnit cruza `QuotaSnapshotReadout`, `SyntheticQuotaSnapshotComparison` e `SyntheticQuotaImpactPreview` em **36 combinações** de capacidade/uso, incluindo zero, sob/no/sobre limite, `PHP_INT_MAX`, inversão da ordem dos medidores e flags inertes. | `10ee6d6d3e0b264d76ae601db2bb28ebc8671eba` |

Nenhum PR registra estado de conta real, seleciona tenant/usuário, confere um ciclo de faturamento, grava consumo, concede franquia, autoriza exportação ou cobra valores. Os contratos de laboratório retornam apenas números/códigos e flags sem autoridade, como `authorized=false`, `can_charge=false`, `can_enforce=false`, `can_export=false`, `can_reserve=false`.

## 2. Evidências GitHub Actions por SHA

- **#147**: [F0 pós-merge #42755887306](https://github.com/rafhael100/imagine-crm/actions/runs/42755887306), [PostgreSQL/RLS pós-merge #42755887296](https://github.com/rafhael100/imagine-crm/actions/runs/42755887296) — `success` no commit `9dc41b31`.
- **#148**: [F0 pós-merge #42756293499](https://github.com/rafhael100/imagine-crm/actions/runs/42756293499), [PostgreSQL/RLS pós-merge #42756293314](https://github.com/rafhael100/imagine-crm/actions/runs/42756293314) — `success` no commit `84d2a8da`.
- **#149**: [F0 pós-merge #42756582639](https://github.com/rafhael100/imagine-crm/actions/runs/42756582639) — `success` no commit `10ee6d6d`; [PostgreSQL/RLS pós-merge #42756582565](https://github.com/rafhael100/imagine-crm/actions/runs/42756582565) — verificar conclusão antes de declarar `success`.

## 3. Limites e riscos para implementação futura

1. **Identidade e fonte de dados:** a igualdade de nomes dos medidores entre dois snapshots *não identifica empresa*, usuário, vínculo, plano, data ou origem. Uma API real deverá validar origem confiável, contexto autenticado, tenant e membership e negar comparações entre organizações, inclusive nos casos em que ambas usam o mesmo nome `leads`.
2. **Consistência temporal e transacional:** o código não define período, relógio do sistema, snapshot transacional, retenção, concorrência, idempotência nem rastreabilidade. Exigir versão, período de competência, governança de reprocessamento e testes de eventos duplicados antes de apresentar histórico contratual.
3. **Franquia e preço:** mudança de limite não é upgrade, crédito ou compra. O motor comercial exigirá contratos de planos, upgrades/downgrades, gratuidades, créditos IA, reservas, reversões e auditoria; faturamento depende de decisões FIN/CON específicas.
4. **LGPD e direitos:** demonstrar minimização de campos, visibilidade por perfil e escopo, exclusão/revogação e segregação de relatórios antes de qualquer leitura de dados pessoais ou exportação.
5. **Autorização/SEG-DBA:** [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) permanece com R01/CAM/EST pendentes; [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) aguarda parecer de responsável SEG/DBA independente identificado. Testes do autor, CI verde e branch DEV não cumprem esse parecer.
6. **Release:** `DenyByDefaultAuthorizer` deve permanecer deny-only, a superfície pública permanece limitada a `GET|HEAD /up`, e nenhum incremento desta rodada deve ser publicado em `main`, VPS ou produção por analogia. Necessários QA/LGPD/CORE/SEG/FIN e autorização de release expressa.

**Resultado efetivo:** incrementos de comparação e QA laboratoriais concluídos; produto multiempresa, cobrança, relatórios e primeiro acesso a cliente real continuam **NO-GO**.
