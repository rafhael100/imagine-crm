# BILL-ASAAS-INT-04 — integridade organização ↔ pagador ↔ pedido (CI)

**DRAFT / NO-MERGE / NO-DEPLOY / NO-GRANT**. Nenhum cliente, pagamento, banco ou serviço operacional.

## Problema fechado neste recorte

A reserva de pedido INT-03 tinha PK e unicidade de pagamento, mas era possível registrar `organization_ref=A` com `provider_customer_id` vinculado à organização B. Verificação em PHP isoladamente não protege contra processos concorrentes ou bypass da aplicação.

## Medida estrutural

- A tabela sintética de vínculos de cliente recebe `UNIQUE (organization_ref, provider_customer_id)`.
- A tabela sintética de pedidos recebe uma FK composta para esse par, com `ON UPDATE/DELETE RESTRICT`.
- Assim, um pedido só pode existir se o pagador já estiver vinculado **à mesma organização**, e não apenas existir globalmente. O vínculo é protegido por PK/UNIQUE e por trigger de imutabilidade da INT-03.
- O criador de pedidos mantém somente INSERT por colunas; a FK não requer permissão de leitura de registros de outras empresas.
- SQL de CI e PHPUnit com duas conexões inserem os pares fictícios e tentam uma referência cruzada, que deve falhar **antes de qualquer HTTP**.

## Limites de segurança

A FK **não** substitui RLS operacional, autenticação de tenant, verificação de CNPJ/propriedade, consentimento, migrações de produção, cofre de chaves, precificação aprovada, transação final de checkout, GET real de reconciliação de pagamento, NF ou autorização de direitos. Os testes usam PostgreSQL16 do GitHub e `Http::fake` apenas.

Revisão obrigatória: SEG/DBA #27, CORE/LGPD #259, UX #416. Nenhum merge/deploy ou envio de cobrança real.
