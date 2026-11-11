# CRM F3-47 — Decodificador JSON estrito para metadados Portal–CRM

A F3-46 acrescentou a inspeção pura de contrato HMAC ao `app/Core` do CRM. A F3-47 acrescenta **um decodificador de entrada JSON reutilizável no código principal da aplicação**, com limite explícito de tamanho e defesa contra ambiguidades de chaves duplicadas. Este componente continua sem rota, sem identidade, sem autorização, sem gravação, sem consentimento e sem recibo operacional.

## Motivação técnica

`json_decode` em PHP, como muitos parsers JSON, aceita chaves repetidas e normalmente mantém o último valor: por exemplo, um objeto com dois campos `organization_reference` poderia ser interpretado de forma diferente por dois intermediários. Antes do inspector F3-11 validar o envelope, `PortalTechnicalEnvelopeDecoder` executa uma leitura léxica **do objeto plano** que reconhece strings escapadas, identifica chaves duplicadas **após decodificação Unicode**, limita o conjunto a exatamente oito entradas e impede aninhamento. Em seguida executa `json_decode(... JSON_THROW_ON_ERROR)` e exige a aprovação **apenas estrutural** do inspector já existente.

## Regras aplicadas

- Corpo JSON de **2 a 2.048 bytes**, sem expansão silenciosa, arrays, valores booleanos, nulos, objetos aninhados ou campos extras.
- Exatamente os oito campos técnicos do envelope `imagine.lead-intake` versão inteira 1; apenas strings JSON nos IDs/protocolo e número inteiro para versão.
- Tratamento correto de aspas, barras invertidas e strings Unicode; `"protocol"` e `"\u0070rotocol"` representam a **mesma chave** e não podem coexistir.
- Proibição de campos pessoais `name`, `email`, `phone`, `message`, `consent` e de qualquer carga arbitrária no envelope técnico. **Isso não substitui o contrato futuro para payload consentido em armazenamento separado e protegido.**
- Erros com códigos genéricos sem eco de referências de empresas, valores de formulário ou dados de contato. Ordem das chaves não é fonte de autoridade.
- O resultado válido é somente **um array de metadados não confiáveis**. A F3-46 ainda exige binding independente e MAC, mas essa verificação também não libera efeitos operacionais.

## Evidências

O arquivo `app/Core/Leads/PortalTechnicalEnvelopeDecoder.php` é código do núcleo, sem efeitos colaterais. `tests/Unit/PortalTechnicalEnvelopeDecoderTest.php` contém casos de duplicidade literal e escapada, JSON inválido, caracteres de controle, escapes e UTF-8 inválidos, tipos errados, objetos, arrays, campos extras, limite de bytes e exemplo reordenado. O workflow `ci-f3-47-strict-json-decoder.yml` valida PHP puro em 8.3 e executa a suíte PHPUnit com Laravel/PHP **8.4**, respeitando as dependências travadas que atualmente exigem Symfony 8.1 e PHP >=8.4.1. Também exige que os testes de bloqueio de rotas e de F3-46 permaneçam verdes.

## Gates

**Não é um endpoint.** Para integração real ainda faltam identidade e autorização de serviço, política de tenancy, consentimento, conteúdo de lead protegido, nonce ledger físico operacional, outbox, recibo durável, antiabuso, rate limiting, staging e revisão SEG/DBA/LGPD. F70 Comercial permanece bloqueada. Nenhuma `main`, VPS, DNS, produção, acesso a clientes ou custo foi alterado.
