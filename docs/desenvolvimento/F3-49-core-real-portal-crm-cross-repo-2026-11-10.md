# CRM F3-49 — Teste ponta a ponta de código real: Portal + núcleo CRM + receptor CRM

A F3-42 comprovou interop entre os **laboratórios** PHP do Portal F119/F122 e CRM F3-41. As F3-46/47/48 introduziram código de domínio reutilizável em `app/Core/Leads`, mas não haviam sido executadas **junto ao código do outro repositório**.

A F3-49 faz essa união com um teste integrado novo, sem criar endpoint público ou armazenamento operacional. O workflow anteriormente usado na F3-42 é estendido para também executar `tests/Labs/F3_49/test_real_core_portal_crm_e2e.php`, usando um checkout GitHub do Portal **fixado ao SHA imutável** `af414ed7aece433e65b4f813be12ba3546cc5c8a`.

## Percurso completo verificado

1. O código novo **do núcleo do CRM** `PortalIngressPreparationBoundary` recebe JSON técnico do protocolo Portal, interpreta a string pelo decodificador F3-47 (limite, chaves duplicadas e oito campos), exige o vínculo fixado independentemente e verifica o HMAC/domínio da operação via F3-46. Mesmo quando consistente, devolve apenas `LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED`, sem autorização.
2. A classe original do laboratório `PortalF119SyntheticServiceReservation` executa a reserva técnica e o evento `pending_review` no **PostgreSQL 18 descartável do Portal**, com um segundo segredo HMAC de fixture. Esse registro **ainda não é CRM**.
3. O cliente da suíte envia a mesma referência, assinada com chave de teste separada, por HTTP `127.0.0.1:19141` ao receptor PHP **CRM F3-41**; o receptor transaciona com **PostgreSQL 16 descartável distinto** e assina um recibo técnico após o commit.
4. O verificador **existente no núcleo do CRM** `SyntheticLeadReceiptMacInspector` valida a compatibilidade do recibo (somente metadados), enquanto confirma que uma assinatura de requisição não é assinatura de recibo.
5. A repetição do nonce é recusada pelo receptor; a base remota ainda possui uma única submissão lógica.
6. Com assinaturas sintéticas válidas, cada alteração individual de empresa, vínculo, site, formulário e destino é rejeitada **independentemente pelo núcleo CRM, pelo laboratório Portal e pelo receptor HTTP CRM**. O novo núcleo também rejeita chaves JSON duplicadas via Unicode e campos pessoais inseridos no envelope.

## O que a evidência não significa

A prova ocorre inteiramente em um runner GitHub-hospedado; só compartilha metadados UUID de teste. O banco Portal usa PostgreSQL 18 em `127.0.0.1:5433`, o banco CRM usa PostgreSQL 16 em `127.0.0.1:5432`. O teste HTTP é exclusivamente loopback e é encerrado ao fim da execução. Não há rede de produção, cliente, consentimento, lead com payload, API Laravel, worker, identidade real, chave gerenciada, MFA, token OIDC, armazenamento criptografado ou recibo operacional.

Todas as saídas de avaliação preservam `canAcceptLead=false` e `canAuthenticateService=false`; o receptor HTTP preserva `realCrmPersisted=false`, `canAcknowledgeVisitor=false` e `productionAuthorized=false`. Um registro em banco **real de CI** demonstra somente atomicidade de teste, não entrega de CRM a cliente.

**Gates:** F70 Comercial fechado; revisão independente SEG/DBA/LGPD, IAM serviço–serviço, vínculo autorizado, payload protegido, outbox/worker e homologação staging continuam necessários. Sem merge `main`, deploy, VPS, DNS, dados reais ou despesas adicionais.
