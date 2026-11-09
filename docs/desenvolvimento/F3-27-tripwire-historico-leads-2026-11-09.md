# F3-27 — Tripwire de release para laboratório de histórico de leads

**Data:** 09/11/2026. **Escopo:** suíte negativa DEV e documentação, sem política real. Complementa [F3-26](F3-26-contrato-negativo-historico-leads-2026-11-09.md), [UX-F3-27](UX-F3-27-sequencia-ilustrativa-historico-leads-2026-11-09.md) e [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Invariantes de segurança que não podem mudar por acidente

O teste `LeadHistoryReleaseBoundaryTest` impede que a presença dos novos objetos de laboratório se transforme silenciosamente em uma API real. Confere:

1. **Nenhuma rota de leads, contatos, histórico, timeline, atividades ou eventos** está publicada na aplicação experimental. Rotas reais exigirão revisão CORE/SEG/DBA e uma alteração consciente dos gates.
2. Mesmo uma fatia com evento sintático bem formado, tenant e autor declarados retorna `PROVENANCE_UNVERIFIED`. O autorizador efetivo continua recusando `read`, `search`, `export`, `update` e `delete` relativos a `lead.history`.
3. Troca de tenant e reuso de referência de evento retornam sinais mínimos sem identificadores, e **sem direito de ler/escrever/exportar**.
4. As classes de histórico mantêm apenas os métodos de diagnóstico, sem métodos de buscar dados, persistir, baixar, publicar, despachar, conceder direito ou autorizar efeitos.

A referência sintática de lead não indica existência, owner, fonte oficial, temporalidade, prova de ato humano ou aprovação de LGPD. A ausência de rota em **DEV** não é prova de segurança de toda a infraestrutura nem de produção.

**Fronteiras:** zero schema, SQL, migration, grant, BYPASSRLS, endpoint de negócio, arquivo de cliente, integração, domínio histórico real, `main`, VPS, produção ou gasto. A revisão independente SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) permanece obrigatória, assim como DAT/CORE/INT/LGPD. Merge somente DEV depois das duas suítes verdes, revisão de diff e SHA exata.
