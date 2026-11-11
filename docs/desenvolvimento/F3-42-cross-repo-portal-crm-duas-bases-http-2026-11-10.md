# CRM F3-42 — Teste cruzado real: Portal F119/F122 + CRM F3-41, HTTP e bancos independentes

## Avanço desta entrega

As provas anteriores foram executadas **separadamente**: o Portal F121 simulava um receptor CRM no seu próprio repositório, e o CRM F3-41 simulava um emissor Portal. A F3-42 elimina essas duas substituições em um único teste automatizado: importa as **classes PHP verdadeiras do Portal** diretamente do commit fixado `af414ed7aece433e65b4f813be12ba3546cc5c8a`, PR Portal #307, e as combina com o **receptor PHP verdadeiro do CRM F3-41**, no próprio repositório CRM, num runner temporário.

O checkout do Portal usa `actions/checkout` com revisão SHA imutável, validada por `git rev-parse HEAD`. Não copia código do Portal para o CRM nem altera as duas aplicações runtime. Qualquer mudança futura no contrato exige atualizar explicitamente essa referência de revisão e executar a matriz novamente.

## Arquitetura efetivamente exercitada

- Dois serviços PostgreSQL **em contêineres distintos e descartáveis**, com versões também diferentes: **Portal PostgreSQL 18 na porta local 5433**, banco `portal_commercial_lab`; **CRM PostgreSQL 16 na porta local 5432**, banco `imagine_crm_handoff_ci`. Os schemas só existem nas respectivas bases e não são compartilhados.
- A classe do Portal `PortalF119SyntheticServiceReservation` autentica a integridade de uma requisição técnica com HMAC de teste e grava metadados de envio, nonce e evento `pending_review` no PostgreSQL 18, usando papéis sintéticos com RLS.
- Um cliente HTTP PHP do teste chama o **roteador F3-41 do CRM** em `127.0.0.1:19141` e passa o envelope técnico de oito campos, com HMAC gerado por chave de teste independente do segredo de entrada Portal. A classe CRM F3-40 registra nonce + metadados no PostgreSQL 16 e devolve recibo assinado **apenas após commit**.
- A classe **original do CRM** `SyntheticLeadReceiptMacInspector` reconhece a assinatura e o contexto; assinatura com chave de requisição é rejeitada como recibo. Uma segunda chamada com o mesmo nonce recebe `409`, e repetir o envio no Portal com nonce novo preserva a mesma submissão lógica.
- Para uma segunda submissão, o consumidor em laboratório reserva um lease pelo **guard F122 real do Portal**. O receptor CRM confirma no seu banco mas simula perda de resposta HTTP. O Portal grava a decisão `unknown` na sua própria base e **rejeita reenvio**.
- O cliente consulta o endpoint técnico de leitura F3-41 com assinatura de **domínio diferente**, obtendo apenas um recibo de laboratório do registro encontrado. Esse resultado não encerra automaticamente o hold do Portal, nem confirma ao visitante. O CRM mantém um único nonce de envio; a consulta não insere outro.
- Verificações negativas: alteração de destino detectada pela classe real Portal, troca de empresa recusada pelo CRM, isolamento entre schemas, separação de chaves e saídas de HTTP sem identificador de submissão, PII ou permissões produtivas.

## CI e arquivos

`tests/Labs/F3_42/test_real_portal_crm_cross_repo.php` é o cliente/integrador PHP que importa ambas as implementações e verifica os dois bancos.

`.github/workflows/ci-f3-42-cross-repo-portal-crm.yml` fixa o SHA do Portal, inicializa PostgreSQL 18 e 16 em contêineres separados, carrega apenas fixtures F118, F119, F122 e CRM F3-40, gera duas chaves HMAC distintas em memória e executa a prova em **PHP 8.3 e 8.4**. As portas de PostgreSQL e HTTP só têm binding local ao runner de CI, e o processo HTTP é encerrado ao final.

Nenhuma rota do Laravel, `main`, migration operacional, dado de cliente, banco persistente, VPS, DNS, produção ou custo de fornecedor foi alterado.

## Evidência vs. produto utilizável

Este teste **comprova compatibilidade de código entre dois repositórios em execução**, não implementação operacional do serviço. Não realiza transmissão de nomes, e-mails, telefone, mensagem ou consentimento; não possui autenticação real de serviço (chaves geradas pelo job), TLS, IAM confiável, autorização por tenant, log de auditoria LGPD, banco operacional do CRM, worker real, persistência de leads com conteúdo, monitoramento ou backup.

O CRM ainda retorna invariavelmente `realCrmPersisted=false`, `canAcknowledgeVisitor=false`, `canAutoRetryUnknownOutcome=false` e `productionAuthorized=false`. Um MAC sintético consistente e um registro no banco de CI **não** autorizam confirmar orçamento a um cliente.

O próximo marco requer projeto e revisão independente SEG/DBA/LGPD de endpoints privados, IAM de serviço, ligação confiável à empresa, modelo de dados e consentimento, outbox com worker, recibo operacional, conciliação de falhas e staging autorizado. A F70 do Imagine Comercial continua fechada.
