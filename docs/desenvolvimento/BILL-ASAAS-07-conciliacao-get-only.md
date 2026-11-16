# BILL-ASAAS-07 — Conciliar vínculo de pagador por GET-only, sem novos cadastros

**Estado:** revisão DRAFT, laboratório GitHub PostgreSQL com HTTP Asaas totalmente simulado. **Não existe endpoint público, cadastro real nem webhook operacional.**

## Problema tratado

Após `BILL-ASAAS-05/06`, cada organização possui uma referência externa exclusiva e a operação `GET/POST /customers` é permitida apenas uma vez. Um resultado incerto faz a reserva permanecer em `review_required`, impedindo novos POSTs. Porém, se o Asaas criou o pagador e o CRM perdeu a resposta, a organização precisa de uma forma segura de **localizar** e confirmar o vínculo, sem criar outro cliente.

## Novo recorte de código

- `AsaasSandboxCustomerGateway::lookupOnlySyntheticCustomer()`: método estritamente de leitura `GET https://api-sandbox.asaas.com/v3/customers?externalReference=...`, com limite de dois registros, autenticação de Sandbox existente e validação de resposta. Retorna `sandbox_customer_found` ou `sandbox_customer_absent` sem autorização de POST, pagamento, ativação ou envio.
- `LaboratoryAsaasCustomerReconciler::lookupAndReconcile()`: serviço exclusivo da CI, executado com papel PostgreSQL **distinto** `crm_billing_link_review_ci`, que não pode fazer INSERT nem apagar registros. Confere que existe reserva local para a organização/referência, rejeita transações abertas, consulta o Sandbox simulado e só vincula um `cus_...` encontrado com a mesma referência, com unicidade física.
- Uma repetição do mesmo vínculo é idempotente. Uma colisão com pagador de outra organização ou uma resposta divergente **não altera** o vínculo nem cria uma nova organização. Nenhum dado de cliente, API key ou ID do provedor é devolvido pelo conciliador.
- Quando a pesquisa não encontra pagador, tem paginação ambígua, retorna erro ou não corresponde à referência, o caso permanece `review_required`. **Jamais interpreta “não encontrado” como autorização para cadastrar novamente.**
- A origem da credencial e da autorização de operador **ainda não está implementada para clientes reais**: não há API de revisão administrativa, cofre ou rotas públicas.

## Segurança e testes

A fixture `tests/Labs/billing/asaas-customer-link-ci.sql` adiciona `crm_billing_link_review_ci` com SELECT apenas das colunas sintéticas necessárias e UPDATE restrito a estado/ID do pagador. O receptor de webhooks e o ator de criação permanecem separados.

`PostgresAsaasCustomerReconcileTest` roda em PostgreSQL 16 efêmero com `Http::preventStrayRequests` e `Http::fake`, testando:

1. Recuperação bem-sucedida após uma resposta incerta e replay sem segunda consulta.
2. Pagador ausente — sem POST e com estado conservador.
3. Resultados ambíguos, referência errada e falha do provedor — sem vínculo.
4. Colisão global de identificador de pagador entre organizações — nenhuma sobrescrita.
5. Bloqueio de atores/roles errados e de transação SQL aberta.
6. Ausência de rota pública, permissões de cobrança e alteração em `main`.

**Antes de liberar produção** ainda precisamos de operador humano autenticado e autorizado, trilha de auditoria imutável, isolamento RLS por empresa, conferência do CNPJ/titularidade, credenciais Asaas Sandbox verdadeiras em cofre, política de recuperação de falha, termos/consentimento, contratos e preços homologados, emissão fiscal, VPS/TLS/backups e revisão dos gates SEG/DBA #27, CORE/LGPD #259, UX #416.

**NO-MERGE / NO-DEPLOY / NO-GO de clientes.** O GET-only é uma prova de recuperação e não uma integração Asaas real.
