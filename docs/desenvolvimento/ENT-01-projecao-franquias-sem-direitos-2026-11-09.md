# Imagine CRM — ENT-01 — Projeção aritmética de consumo e excedentes

**Data:** 09/11/2026. **Estado:** código puro em DEV para reutilização futura; não é motor de cobrança nem autorização de plano. Relacionado à [CRM-REV-02](../planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), [CORE-P0-08](../planejamento/CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md), à [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27) e à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Entrega

A classe PHP `App\Core\Plans\QuotaUsageProjection` calcula, de modo imutável e determinístico, **saldo antes**, **saldo após**, **consumo projetado**, **excedente já existente**, **excedente projetado** e se a soma aritmética está dentro do limite **informado pelo chamador**.

Ela aceita a unidade inteira sem transformações (`leads`, `users`, `automations`, `storage_bytes` etc.) e não contém tabela comercial embutida. Não interpreta `0` como ilimitado: `0` representa explicitamente capacidade zero. Valores negativos, nome de medidor inválido e estouro de inteiro são recusados. Exemplos dos testes exercitam limites candidatos de trial (2 usuários, 20 leads, 2 funis e 2 automações) apenas como **fixtures ilustrativas**.

## Fronteira crítica

O cálculo **não** sabe quem é o usuário, a empresa, seu plano, o direito ativo, qual fonte enviou o consumo, se o saldo está consistente ou se o pedido pode ser admitido. Mesmo se `fitsWithinConfiguredLimit()` retornar `true`, isso **não** autoriza gravar um lead, criar um usuário, efetuar importação, enviar mensagem, consumir IA, reservar recurso, criar assinatura ou cobrar. Números passados por cliente não são fatos oficiais. Aplicação real exige, separadamente:

1. **Origem confiável do direito** por organização, plano e concessão, com regras de cortesia e trial distintas, membership ativa e `DenyByDefaultAuthorizer` até política revisada.
2. **Medidor persistente transacional/concorrente**, commit do módulo proprietário e atualização de uso idempotente para evitar duas reservas ultrapassarem a franquia.
3. **Unidade explícita por recurso**. Em particular, **20 MB de anexos** no trial precisa ter conversão bytes MB/MiB aprovada, e **5 MB por arquivo** é restrição distinta da franquia de armazenamento. A classe não converte ou arredonda bytes e não escolhe limites definitivos.
4. **Plano e trial separados:** um trial por CNPJ verificado, 14 dias, retenção de 60 dias e limites candidatos; organizações Básico cortesia distintas não renovam trial nem somam quotas. Ainda dependem das evidências e gates comerciais/CORE.
5. **Alterações de plano e excedentes:** não apagar dados preexistentes só porque um novo limite ficou menor, e não emitir compra/upgrade/gerar fatura automaticamente.

## Critérios de evidência

- `tests/Unit/QuotaUsageProjectionTest.php` contém exemplos de limite exato, excedente de novas operações, quota zero, saldo parcial, uso acima do limite, `PHP_INT_MAX`, argumentos inválidos, estado imutável e ausência de métodos de autorização/reserva.
- CI Laravel e PostgreSQL devem passar na HEAD exata da PR.
- Sem migration, SQL, HTTP, filas, autenticação, credencial, envio de dados, `ALLOW`, `main`, VPS, produção ou custo.
- Os **67 CORE**, SEG/DBA, AUT/INT/LGPD, FIN/CON e [PR #49](https://github.com/rafhael100/imagine-crm/pull/49) permanecem abertos, independentemente da CI.

**Próximo passo possível em DEV:** motor puro de estados e janelas de trial *somente depois* da definição explícita da semântica de início/término e retenção, sem presumir regra de fuso/DST ou data de faturamento.
