# ENT-04 — Leitura agregada de franquias sem direitos (DEV ONLY)

**Data:** 09/11/2026. **Base:** [CRM-REV-02](../planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) e ENT-01/02 (DEV).

## Objetivo

Compor até doze **medidores sintéticos** distintos para um futuro painel de consumo da própria organização, exclusivamente a partir de instâncias já fornecidas de `QuotaUsageProjection`. A quantidade máxima é um limite local da **apresentação**, não do plano.

As linhas contêm apenas identificador técnico de medidor, limite, usado, restante, excesso e faixa descritiva. Os valores de exemplo não são quotas reais de uma empresa. As faixas são `zero_capacity`, `in_range`, `at_limit` e `over_limit`.

## Contenção técnica

- Cada medidor deve ser único e ter `requestedIncrease=0`. Listas vazias, indexadas de modo não sequencial, itens desconhecidos, duplicações e mais de doze itens falham fechadas.
- Zero é limite real configurado em dado sintético; **não é ilimitado**.
- Os cálculos são somente inteiros, imutáveis, sem consulta externa, normalização de valores nem edição. O resumo jamais autoriza inclusão, upload, despesas ou cobrança de excedente.
- Antes de utilizar valores reais, **serviço autorizado distinto** precisa comprovar identidade/membership, mesma organização, contrato/plano vigente, unidade por medidor, fonte confiável, isolamento de consulta, consistência e direitos por campo. Misturar medições de tenants é proibido; esta classe não valida isso por si.
- Estado de consumo, cobrança e concessão são objetos distintos. Limite exibido não implica direito. Plano pago não pode ser suspenso por dívida de site vinculado.

## Testes

`QuotaSnapshotReadoutTest` cobre estados, limite zero, ordenação, duplicatas, overflow evitado com `PHP_INT_MAX`, 12 vs 13 medidores, valores inválidos e ausência de ações de concessão/persistência. Exigir duas CIs verdes em SHA exata e revisão de diff antes de integrar **apenas DEV**.

**Não altera** preço, trial, cortesia, direitos, rotas, migration, RLS, API, emissão de aviso, storage, `main`, VPS ou produção. Os gates CORE/SEG/AUT/FIN/CON/LGPD seguem abertos.
