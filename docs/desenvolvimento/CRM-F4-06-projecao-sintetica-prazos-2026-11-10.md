# CRM F4-06 — Projeção sintética de prazos de acompanhamento

**Status:** implementação offline proposta; sem homologação funcional, sem integração e sem efeitos de negócio.

## Escopo

O componente puro `App\Support\Synthetic\FollowUpAgingProjection` recebe uma lista sintética de identificadores e horários de vencimento, mais um instante explícito de referência. Retorna três grupos determinísticos: `overdue` (instante anterior ao relógio), `due_24h` (entre o agora e as próximas 24 horas, inclusive), `due_later` (após 24 horas). Preserva a ordem de entrada em cada grupo.

Rejeita ausência de campos, identificadores vazios ou repetidos, timestamps sem offset e datas inválidas. O relógio nunca é lido do sistema; a definição de horário e offset é explícita.

## Limites obrigatórios

- Apenas arrays sintéticos; nenhum controlador, endpoint, migration, tabela, fila, webhook, envio ou conexão com clientes.
- Não atribui ownership, papel, tenant, entitlement nem permissão; NÃO substitui `DenyByDefaultAuthorizer`.
- Não altera contratos reais de SLA, prioridades comerciais ou regras de notificações.
- Sem custos, VPS, produção, merge automático ou mudança na `main`.

## Evidência e revisão

`tests/Unit/FollowUpAgingProjectionTest.php` cobre bordas de 0 e 24 horas, equivalência de offsets, ausência de timezone, IDs duplicados e falta de vencimento. Executar a suíte Laravel/PHPUnit no CI da branch; teste escrito não é teste executado. Exigir resultados verdes na mesma SHA antes de considerar merge apenas para DEV. A issue #99 documenta falha intermitente de GitHub Actions com zero jobs; não interpretar esse estado como sucesso.

SEG/DBA #27, revisão independente, gates CORE e homologação funcional permanecem abertos.
