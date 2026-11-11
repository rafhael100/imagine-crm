# CRM F3-48 — Pipeline de preparação técnica Portal–CRM sem efeitos operacionais

A F3-48 integra **dois componentes agora existentes no código principal do CRM**: o decodificador JSON estrito F3-47 e a inspeção de integridade HMAC/vinculação de empresa F3-46. O objetivo é que um futuro adaptador de servidor tenha **uma única fronteira de preparação de metadados**, sem reproduzir verificações em várias rotas ou depender de fixtures de testes.

## Fluxo executado

`PortalIngressPreparationBoundary::inspect(rawJson, serverBinding, authentication, now, fixtureKey, serverOperation)` faz, nessa ordem:

1. Decodifica o JSON técnico com limite de 2 KB, exatamente oito campos, sem dados pessoais ou chaves duplicadas (inclusive Unicode). Qualquer erro retorna somente `TECHNICAL_JSON_REJECTED` ou `DUPLICATE_JSON_KEY_REJECTED`, sem ecoar entrada ou identificadores.
2. Encaminha o envelope já estruturado ao `PortalServiceHandoffInspector`, que exige vínculo de organização/site/formulário/destino independente do corpo HTTP, tempo e nonce válidos, HMAC com chave separada e domínio próprio para `deliver` ou `lookup`.
3. Para entrada sintaticamente consistente, retorna `LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED`, explicitamente **sem afirmar que o serviço foi autenticado** ou que o vínculo veio de banco autorizado.

`PortalIngressPreparationAssessment` mantém todos os direitos de operação `false`: autenticação, aceite, persistência, confirmação ao visitante, criação de job, retry de resultado desconhecido e abertura de rota. A avaliação expõe apenas um código estável. **Nenhuma operação de banco, HTTP, worker, sessão ou fila é realizada.**

## Evidências

O código principal está em `app/Core/Leads/PortalIngressPreparationBoundary.php` e `PortalIngressPreparationAssessment.php`; nenhuma rota, controller, middleware ou serviço runtime foi vinculado.

Os testes `tests/Unit/PortalIngressPreparationBoundaryTest.php` cobrem a chamada combinada, empresas e destinos alterados com assinatura sintética válida, operações de domínio diferentes, corpo com campos pessoais, JSON duplicado, erros de MAC, prazo, nonce e invariantes de ausência de autoridade. Uma suíte independente `tests/Labs/F3_48/php83_composed_ingress_smoke.php` comprova compatibilidade de PHP puro 8.3 sem atualizar `composer.lock`. O workflow PHPUnit PHP 8.4 executa também as regressões F3-46/F3-47 e o gate de rota pública.

## Limites

A integração funcional ainda depende de identidade de serviço real, autorização do tenant, rotação de chaves, persistência de nonce, armazenamento protegido de conteúdo consentido, outbox, recibo durável, reconciliação em infraestrutura real, rate limiting e revisão SEG/DBA/LGPD. F70 Imagine Comercial permanece bloqueado. Não houve alteração de `main`, VPS, DNS, custos, dados de clientes, APIs públicas ou deploy.
