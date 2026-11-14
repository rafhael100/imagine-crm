# CRM BILL-ASAAS-INT-05 — integração dos fluxos Pix e assinatura (CI)

**DRAFT / NO-MERGE / NO-DEPLOY / NO-GRANT**. Esta HEAD não autoriza operação, cadastro real, pagamentos, ativação nem publicação.

## Por que unificar

As revisões [#554](https://github.com/rafhael100/imagine-crm/pull/554) e [#553](https://github.com/rafhael100/imagine-crm/pull/553) evoluíram em paralelo. As duas dependem do mesmo cadastro sintético de pagador, mas tinham workflows, tabelas e permissões separados. A integração evita aceitar testes isolados como se comprovassem a composição.

## Componentes na mesma HEAD

1. **Pagador:** reserva única por organização e ID Asaas sintético, recuperação GET-only, sem cadastros repetidos.
2. **Pedido Pix:** PK global por pedido, UUID, FK composta organização + pagador, preço calculado pelo catálogo, reserva antes de simulação HTTP, UNIQUE global de pagamento e gatilho de imutabilidade.
3. **Rascunho mensal:** segundo ledger no mesmo PostgreSQL16 de CI, com pagador já vinculado, contrato não aceito, preço provisório, trial de 14 dias, sem assinatura criada.
4. **Consulta assinaturas:** `GET /subscriptions` fake pelo pagador e referência, sem POST, sem direito; observação sintética com UNIQUE global do ID do provedor.
5. **Análise de cobranças da assinatura:** apenas GET e sinais de risco de estorno, sem conceder acesso ou emitir nota.
6. **Prova relacional integrada:** `PostgresAsaasBillingIntegratedLedgerTest` exige que um único pagador fictício possa sustentar pedido e rascunho no mesmo banco; a observação de assinatura e o vínculo de pagamento permanecem informativos, e um pagador ainda pendente não passa nem no pedido nem no rascunho.
7. **Workflow unificado:** primeiro o verificador de PostgreSQL efêmero SEC-CI-02, depois fixtures SQL e suites de webhook, cliente, Pix, pedido, assinatura e E2E conjunto, no mesmo runner. Testes não se executam em VPS nem com chaves reais.

## Lacunas para fechar projeto

- Modelagem operacional com FK para organização autenticada, constraints de domínio, isolamento RLS com roles de runtime não privilegiadas, migrations não destrutivas e backups/restore.
- Identidade real do responsável pelo CNPJ, consentimento e aceite de termos LGPD, OTP/trial de verdade, MFA, expiração e revogação.
- Sandbox Asaas autenticado com chaves **no cofre**; reconciliação server-to-server com eventos reordenados, chargeback, confirmação de pagamento e notas, sem falseamento de direitos.
- Fluxo completo do site, painel cliente e administração conectados a banco real, e homologação privada na Leaseweb.
- Revisão segregada SEG/DBA **#27**, CORE/LGPD **#259**, UX **#416**. O autor dos testes não pode autoaprovar esses gates.

**Sem merge e deploy até cumprir essas condições.** Os resultados de CI só se aplicam à SHA exata registrada posteriormente na PR.
