# P0-DEC — Aprovação documental da especificação CT-03 / CT-04

**Data do registro:** 2026-11-02.
**Natureza:** decisão expressa do responsável pela arquitetura do CRM Imagine, sobre a especificação documental de CT-03 e CT-04; não constitui homologação técnica nem autorização de implementação.
**Fonte de proveniência:** mensagem de aprovação no diálogo atual do projeto. Não há ID permanente de mensagem fornecido; não inventar.
**Documento aprovado:** [P0-CON — CT-03 e CT-04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md).
**Regras transversais de origem:** [INT-R01](INT-R01-governanca-contratos-homologacao.md) e [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).

## Aceite expresso — transcrição literal da mensagem atual

> Aprovo a opção 1: a especificação documental P0-CON de CT-03 e CT-04, incluindo comandos tipados, identificação e correlação, estados técnicos separados dos resultados oficiais de negócio, matriz proposta de transições, idempotência contextual, concorrência controlada, retentativas seguras, reconciliação e tratamento de resultados inconclusivos, preservando os módulos proprietários e serviços centrais. Vamos continuar o detalhamento por operação, mantendo parâmetros e pendências ainda não decididos em revisão, sem criar automações nem iniciar programação.

## Interpretação do alcance (sem ampliar a decisão)
- **Aprovado como base documental:** princípios e estrutura do CT-03/CT-04 descritos na minuta, inclusive distinção das dimensões de resultados, transições documentadas como propostas de desenho, idempotência, concorrência, reconciliação, retentativas seguras e módulos proprietários.
- **Não significa especificação de produção aprovada:** nomes físicos de campos, esquemas JSON/API, enums definitivos, políticas numéricas de timeout/retry, TTL, concorrência, aprovações por ação, códigos por provedor, retenções específicas, limiares, fornecedores e ferramentas permanecem pendentes.
- **Não cria:** INT-13, motor de estados, Central de Aprovações, cofre, Auditoria Geral ou outros serviços duplicados.
- **Sem implementação:** nenhum software programado, integração conectada, teste executado, ambiente alterado ou produção afetada por este aceite.
- **Pendências:** P0-CON-01, P0-OWN-01, P0-SEC-01, P0-VER-01, P0-ACC-01, P0-TR-01 e P0-DOC-01 continuam abertas até critérios completos de validação e aceites posteriores.

## Próxima etapa autorizada
Detalhamento documental por operação existente das INT-01 a INT-12, com responsabilidade, pré-condições, identidade/autorização, estado técnico, confirmação oficial, idempotência, falhas, recuperação, evidência e referência às famílias CT, requisitos TR e cenários HT. Quaisquer novos critérios/propriedades apresentados são **propostas para revisão**, salvo quando sustentados por decisão anterior inequívoca.

**Estado:** decisão registrada e válida; não transformar o aceite de arquitetura em homologação de API ou programação.
