# CRM F3-49 — atualização da referência Agents e endurecimento de entradas OP-01

**Data:** 10/11/2026. **Ambiente:** desenvolvimento sintético e GitHub Actions. **Política:** nenhuma autorização operacional.

## Atualização bilateral verificável

A CI de CRM/Agents usava o commit Agents `4a03adf5f91e760950adaa3239fb2c8dba05da6d` (EVID-10). Na revisão direta do branch de desenvolvimento do Agents, foi observada a HEAD `36de10eecbd11e04e7684ab3d15989ab4e9749dd`:

- Os dois JSON Schemas OP-01 — pedido e resposta pública — mantêm exatamente os mesmos blobs Git.
- A classe real `src/MockBridge/MockCrmReadRequest.php` mudou desde o commit anterior, incluindo uma verificação prévia de quantidade de chaves antes de ordenar chaves não confiáveis.
- A prova `tools/op01_php_public_response_probe.php` preserva o mesmo blob.

Para aproximar o teste da implementação corrente do Agents sem usar uma branch móvel, a CI passa a fixar o commit completo acima, conferindo-o antes de executar. Nenhuma alteração no contrato real, schema de produção ou API foi autorizada.

## Regressões

- Matriz cruzada CRM ↔ Agents adiciona 512 propriedades inesperadas em uma única requisição forjada, além de campos aninhados e valores booleanos.
- O PHPUnit CRM exercita um conjunto equivalente de rejeições por `hasFixtureShape`; a resposta do CRM permanece negativa e fixa.
- A prova F3-47 continua a executar a fachada PHP pública real do Agents com as seis fixtures e a bloquear a promoção de `READ_SYNTHETIC_ONLY` à autorização do CRM.

## Limites

A atualização de SHA exige nova CI verde na HEAD do PR. A verificação não prova autorização, consentimento, segurança de proxy, serviço de identidade, RLS multiempresa operacional nem homologação de dados. A revisão SEG/DBA independente continua pendente, assim como OP-01, CORE/AUT/LGPD e Portal ↔ CRM. Proibido alterar `main`, VPS, produção, custos ou habilitar `ALLOW`.
