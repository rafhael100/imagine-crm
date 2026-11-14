# CRM P0 — entrada de leads com consentimento, assinatura e vínculo (laboratório, NO-GO)

## Avanço em relação ao contrato apenas estrutural

O fluxo Portal → Sites → CRM já provou formato de metadados e obrigatoriamente recusou a criação de leads, porque faltam consentimento e autorização. Este novo laboratório **não altera o endpoint real**: ele ensaia com PostgreSQL18 descartável as condições mínimas de admissão para **dois leads inteiramente fictícios**, armazenados e recuperados após confirmação de commit.

A tabela `crm_p0.bindings` contém autorizações **falsas**, configuradas somente pelo superusuário de laboratório. A tabela `crm_p0.leads` não pode ser lida ou modificada diretamente pelo login SQL de ingestão. Uma função `SECURITY DEFINER` com `search_path` restrito exige:
- comando HMAC que vincula organização, binding, submissão, site, formulário, destino, revisão, consentimento afirmativo, revisão de aviso, e-mail fictício e expiração de até 120 segundos;
- vínculo ativo com revisão exata em fonte **separada do pedido recebido**;
- consentimento explicitamente verdadeiro e aviso sintético `ci-notice-1`;
- contato no domínio `@example.invalid`, para impossibilitar coleta acidental de e-mails reais no laboratório;
- recibo de inclusão somente após gravação, idempotência que retorna `duplicate` para repetição idêntica e recusa conflito de payload para mesmo envio.

## Cobertura

Os testes PHP8.3 e PHP8.4 validam PostgreSQL18 real e descartável, dois recebimentos, repetição idempotente, negação de SELECT sem privilégios, tentativa de escalonamento SQL, alterações no e-mail, aviso, consentimento, revisão e organização, assinatura adulterada, prazo vencido/excessivo, binding inativo/revogado e verificação após nova conexão. Nenhum tráfego de CRM, e-mail, Sites real, API, webhook, visitante real ou credencial de produção é utilizado.

O resultado é **um ensaio positivo controlado** distinto da fronteira de metadados NO-GO na PR do Portal. Ele não configura uma API real nem comprova base legal para tratamento de dados pessoais.

## Pendências para captação real

Devem ser implementados e auditados: prova da origem autenticada serviço-a-serviço com mTLS ou equivalente e chaves não extraíveis da aplicação, consentimento e evidência do formulário com data/hora gerada no servidor, revisão de política LGPD, identificação de finalidade, opt-out, retenção/exclusão, fila/retries, deduplicação por tenant, recibo pós-commit assinável, limites antiabuso, observabilidade, backup, isolamento de empresas e integração HTTP sob JWT/serviço de autenticação real. A arquitetura de HMAC e owner do laboratório depende de auditoria AppSec/DBA, segredos e gestão de versões.

**Status:** DRAFT / NO-GO / zero clientes reais / zero publicações / zero deploy.
