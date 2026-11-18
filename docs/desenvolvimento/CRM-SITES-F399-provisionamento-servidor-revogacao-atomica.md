# CRM-SITES-F399 — Provisionamento autoritativo de formulários no laboratório PostgreSQL16

**DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / NO-PII / NO-ALLOW / NO-CHARGE**.

## Fluxo implementado no banco descartável do GitHub CI

1. A tabela `crm_lab_site_provision_orders` é **exclusivamente do servidor/fixture owner** e contém o pedido, empresa, contrato, revisão, produto, referência de site/formulário/destino, estado financeiro, consentimento e versão. Não há grants de escrita/leitura para `crm_app_ci`.
2. `LaboratorySiteBindingProvisioner::provision(empresa,pedido)` lê **somente essa tabela**, dentro de transação e `FOR UPDATE`; recusa pedido inexistente, empresa errada, contrato inválido, pagamento ainda pendente, consentimento não registrado e pedido não aprovado. Parâmetros do navegador **não podem escolher site/form/destino**. A criação do binding na tabela existente de captação é idempotente.
3. Uma revogação administrativa simulada atualiza o pedido; trigger no PostgreSQL revoga o binding na mesma transação. A trigger PG07 original de bindings retém a trava advisory até o commit, serializando ingressos concorrentes. Após revogar, reativar o pedido ou provisionar novamente é proibido.
4. O emissor **original** do Sites F398 na SHA `1e24f47f7dd96c0e5d4b68f5a71aed7c861179f4` assina um evento fictício. O CRM recebe no `LaboratorySignedSiteCapture` original e só insere um lead no PostgreSQL16 com FORCE RLS após o vínculo do servidor estar ativo; replay não duplica; empresa sem vínculo é negada.
5. A tabela append-only de eventos de provisionamento de laboratório registra a criação e a revogação. Não há API pública, fila real nem contato com clientes.

## Itens negativos da suíte

- Pedido ausente; pagamento `pending`; consentimento `missing`; estado `draft`; acesso a pedido de outra empresa.
- Role `crm_app_ci` não lê pedido e não insere no histórico de provisionamento; CAN_PUBLISH/CAN_CONTACT/CAN_ACTIVATE continuam `false`.
- Evento Sites assinado mas sem vínculo não cria lead; segundo envio do mesmo evento não duplica; org B sem vínculo não herda o da A.
- Revogação em transação torna o vínculo `revoked`, incrementa versão e bloqueia nova captação; reativação do pedido falha; chamada fora do GitHub CI é negada.

## O que ainda não existe

Os estados `settled`, `recorded` e `approved` são **fixtures falsas**: não são validação real de Asaas, IAM/MFA, contrato ou consentimento. O serviço usa papel proprietário do banco **apenas no CI** e não pode ser conectado a controller ou worker operacional. Antes da homologação na Leaseweb: separar papel restrito de provisionamento, comprovar autoridade de Portal e PSP, MFA e consentimento LGPD, identidade S2S mTLS, fonte confiável de cadastro, outbox com idempotência/TTL 72h–432h, segredos em vault, logs sem PII, recovery real e revisão SEG/DBA.

GitHub: Sites PR #479; CRM integração anterior PR #605; CRM gates #27, #259, #557; Sites gate #38.

## CI

Workflow `ci-crm-sites-f399-pg16.yml` usa somente runner hospedado, PostgreSQL16 descartável, `SEC-CI-02` original antes de cada criação SQL, classes originais Sites/CRM e um único método PHPUnit com `--fail-on-skipped`. Os resultados somente são declarados após a execução da HEAD exata.