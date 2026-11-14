# CRM-LEADS-PG05 — Integridade HMAC e nonce sintéticos Sites → CRM

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT**.

## Alteração funcional controlada

PG04 fixou organização, site, formulário, vínculo, submissão e destino no primeiro recebimento. PG05 verifica antes dessa operação a **consistência HMAC-SHA256 de um envelope estritamente fictício**. O algoritmo e o armazenamento de nonce são testados em PostgreSQL 16 do GitHub Actions, não fazem chamadas de rede nem representam autenticação de servidor real.

A classe `LaboratorySignedSiteCapture`:
- Rejeita invocação fora do CI hospedado do repositório com `CRM_LEADS_LAB=synthetic-only` e chave exatamente igual ao valor **público de testes** configurado no workflow, sem credenciais ou segredos reais.
- Canonicaliza um domínio versionado + organização + vínculo + submissão + site + formulário + destino + lead + título fictício + fonte + nonce UUID + emissão/validade, com valores de formato estrito.
- Calcula `hash_hmac('sha256')` e compara com `hash_equals()`, sem divulgar os campos autenticados ou as chaves em uma resposta negativa.
- Limita validade a **até 300 s**, rejeita assinatura expirada e timestamp adiantado mais que 30 s no relógio de testes.
- Só depois de verificar integridade chama o ledger de PG04, cuja membership transacional, RLS, FK diferida, chave de submissão e auditoria continuam inalteradas.
- Persiste o nonce assinado em `crm_lab_lead_capture_keys` com `UNIQUE (organization_id,signed_nonce)` **na mesma transação** de criação do lead/auditoria; um nonce não pode ser trocado por UPDATE do papel da aplicação. Reenvio assinado idêntico dentro do período devolve replay e não duplica, enquanto outro evento com o mesmo nonce retorna conflito conservador.
- Impede que a chamada sintética sem assinatura reproduza uma claim originalmente assinada, porque a reserva compara explicitamente a nulidade/igualdade do nonce.

Testes usam duas conexões e duas empresas fictícias, contemplando assinatura válida, replay, nonce reutilizado, alteração de site/fonte/título/nonce, assinatura incorreta, expiração, TTL longo, adiantamento de timestamp, chave de CI inválida e ausência de rotas publicadas.

## Restrições que permanecem importantes

A chave no workflow é **pública, inventada e NÃO SECRETA**, apropriada apenas para vetores reprodutíveis no runner. Um `hash_equals` positivo com essa chave **não comprova origem externa autorizada**. Os timestamps são passados pelo teste; não constituem verificação de relógio de servidor de produção. O método anterior `captureSynthetic()` continua acessível em CI para outros testes e não foi convertido em interface operacional.

Para integrar Sites de verdade, ainda faltam emissor autenticado, rotação de chaves em cofre, credenciais ligadas a serviço/empresa aprovados, TLS e política de assinatura de payload, rate limit, nonce compartilhado distribuído e reprocessamento, auditoria LGPD, consentimento, políticas `ALLOW` revisadas, integração bilateral entre repositórios, migrations operacionais e testes de staging.

Este código **não** habilita nenhum endpoint, entrada externa, OAuth, acesso a clientes, cobrança, migração real ou VPS. Mantêm-se abertos os gates [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416) e a [issue de lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557).
