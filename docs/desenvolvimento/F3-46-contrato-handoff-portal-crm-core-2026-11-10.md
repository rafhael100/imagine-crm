# CRM F3-46 — Contrato puro reutilizável de integridade Portal → CRM

**Evolução em código da aplicação, não apenas fixture de CI:** os arquivos `app/Core/Leads/PortalServiceHandoffInspector.php` e `PortalServiceHandoffAssessment.php` definem um componente testável de domínio para **inspecionar metadados assinados** da integração com o Portal, sem nenhum efeito operacional.

O contrato é compatível com as assinaturas exercitadas nas F3-40 a F3-45 e no Portal F119/F121, sem copiar códigos de autenticação dos laboratórios. Valida o envelope técnico de oito campos por meio do `SyntheticLeadEnvelopeV1Inspector` já presente no CRM, cinco referências de vinculação fornecidas **separadamente** pelo chamador backend, uma assinatura HMAC-SHA256 com seis UUIDs ordenados, timestamps inteiros, nonce minúsculo, intervalo máximo de 300 segundos e tolerância de 30 segundos de diferença futura. O código possui separação de domínio para `deliver` e `lookup`, impedindo usar uma assinatura da consulta para transmitir uma submissão.

## Fronteira de segurança

A **correspondência criptográfica não é uma autorização**. O método aceita uma chave passada pelo chamador, sem comprovar de onde ela veio ou que foi provisionada em cofre; a vinculação de organização/site/formulário também precisa vir de fonte autenticada. Por isso, mesmo se a validação retorna `LAB_REQUEST_MAC_MATCH_UNTRUSTED`, a classe `PortalServiceHandoffAssessment` mantém todos os métodos de autorização **sempre falsos**: `canAuthenticateService`, `canAcceptLead`, `canPersistLead`, `canAcknowledgeVisitor`, `canRetryUnknownOutcome`, `canEnableRoute` e `canDeliverToCrm`. O retorno expõe apenas um código estável, sem chave, identificadores, timestamp, nonce, PII ou payload.

Não há storage, route handler, alteração em `bootstrap/app.php`, Laravel service provider, grants, migration operacional, DNS ou worker. Uma mesma assinatura repetida continua sintaticamente compatível no componente puro; a unicidade de nonce depende de banco transacional confiável, não deste inspector.

## Evidências

`tests/Unit/PortalServiceHandoffInspectorTest.php` verifica operações `deliver/lookup`, separação de domínio, cada uma das cinco referências fixadas, alteração de payload, `schema_version` inválida, campos pessoais proibidos, listas e mapas incompletos, nonce/MAC/chave inválidos, relógio expirado, TTL excedido, skew, replay e ausência incondicional de autoridade. O workflow `ci-f3-46-portal-handoff-core.yml` executa PHPUnit em **PHP 8.3 e 8.4**, instala dependências travadas de `composer.lock`, e verifica também os gates existentes `PublicRouteSurfaceSealTest` e `LeadIntakeReleaseGateTest`.

## Próximos marcos e bloqueios

Esta é uma fundação de domínio para futuro adapter de servidor, **não o receptor operacional aprovado**. As etapas críticas ainda exigem identidade serviço-a-serviço em infraestrutura confiável, vínculo tenant independente e validado, autorização por escopo, gestão/rotação de segredos, TLS, anti-replay persistente, dados de contato protegidos e consentidos, outbox real, recibo persistente confiável, observabilidade e homologação SEG/DBA/LGPD. Os 67 gates CORE e a F70 do Comercial não se encerram por este teste.

Branch filha da F3-45, PR em rascunho; `main`, VPS, DNS, produção, custos e dados reais inalterados.
