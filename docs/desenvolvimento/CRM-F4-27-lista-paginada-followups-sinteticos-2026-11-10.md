# CRM F4-27 — Lista paginada de acompanhamentos sintéticos (laboratório offline)

**Data:** 10/11/2026. **Situação:** incremento de código inerte para DEV, sem interfaces ou permissões operacionais.

## Objetivo

Preparar os cálculos para uma futura visão de trabalho de acompanhamentos com ordenação, filtros de vencimento e paginação. A classe `App\Support\Synthetic\FollowUpFixtureWorklistPage` recebe apenas observações *fictícias*, já montadas em memória; **não lê banco de dados, não verifica direito de acesso e não conecta a aplicação a clientes**.

## API experimental

```php
FollowUpFixtureWorklistPage::paginate(
    fixtureScope: 'fixture-sales-a',
    items: [
        ['id' => 'fixture-001', 'due_at' => '2026-11-10T09:00:00Z', 'scope' => 'fixture-sales-a'],
    ],
    asOf: '2026-11-10T10:00:00Z',
    bucket: 'overdue', // all, overdue, due_24h, due_later
    page: 1,
    pageSize: 10,
);
```

- Reusa `FollowUpFixtureScopeConsistency` e `FollowUpAgingProjection`: limites de 100 registros, formato estrito, unicidade de IDs, etiqueta consistente e timestamps ISO-8601 com offset explícito.
- Calcula faixas de `overdue`, `due_24h` e `due_later`; `all` concatena todas sem perda dos totais.
- Ordena por **instante absoluto**, depois por ID sintético para desempate estável. Datas na resposta são normalizadas em UTC com seis dígitos de microssegundos.
- Retorna apenas os registros sintéticos da página e indicadores numéricos globais; `selected_total` pertence ao filtro e `counts.total` pertence à amostra inteira.
- Página mínima = 1; tamanho entre 1 e 25. Mesmo uma amostra vazia tem uma página sem itens. Uma página inexistente, filtro desconhecido ou entrada inválida gera `InvalidArgumentException`, nunca um estado operacional ou uma conclusão presumida.

## Regressões

O `FollowUpFixtureWorklistPageTest` verifica ordenação independente da ordem da entrada, offsets equivalentes, precisão em microssegundos, limite exato de 24 h, filtros, páginas parciais, 100 itens e 25 por página, zero resultados, paginação inválida, mistura de etiquetas mesmo com IDs distintos, registros inesperados e duplicados.

A proteção existente `FollowUpRuntimeSeparationTripwireTest` passa a bloquear também a referência à nova classe nos pontos de entrada Laravel. Isso é verificação de fonte estática e não cobertura de qualquer uso indireto.

## Restrições e próximo gate

**NÃO É UMA WORKLIST DE CLIENTES.** A etiqueta `fixture-sales-a` é declarada por quem chama o método; pode ser falsificada. IDs e datas são apenas inventados; em uma aplicação real, mesmo o retorno de uma lista de IDs exige autorização por principal, membership atual, tenant, recurso, ação e campos, além de classificação LGPD, RLS e auditoria.

O recorte S01 da autoconsulta e o ledger de leads não possuem aprovação de implementação funcional. Os gates CORE, LGPD e [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) continuam abertos. Sem `ALLOW`, API, controller, rota pública, migrations, banco persistente, dados reais, serviços pagos, VPS, `main` ou produção. Revisão de diff e ambos os workflows aprovados na SHA exata são obrigatórios para eventual merge **somente DEV**.

O [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) é material **DRAFT de revisão independente** e permanece sem merge; este incremento não atende nem fecha suas condições.
