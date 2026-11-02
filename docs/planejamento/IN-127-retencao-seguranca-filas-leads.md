# IN-127 — Retenção, Reenvio e Diagnóstico das Filas de Leads

**Estado:** APROVADA quanto aos prazos de 3 dias e 15 dias e ao reenvio/diagnóstico automático; aspectos complementares pendentes de revisão.  
**Data:** 01/11/2026.  
**Etapa:** planejamento/documentação; **não autoriza implementação**.  
**Dependências:** IN-124 (APIs principal e contingência, armazenamento durável), IN-125 (formulários) e IN-126 (monitoramento e recuperação).

## Decisões expressamente aprovadas

1. **Leads aguardando entrega — até 3 dias:** os dados recebidos e persistidos por qualquer uma das duas APIs ficam em fila de entrega, com **novas tentativas automáticas** quando a entrega ao CRM não tiver sido confirmada.
2. **Leads com falha — até 15 dias na fila de falhas:** envios que não forem entregues durante a etapa normal deverão permanecer recuperáveis na fila de exceções/falhas por **15 dias**. A fila deve permitir reprocessamento automático quando o problema for recuperável e reprocessamento manual por equipe autorizada quando necessário.
3. **Exibir o erro que está ocorrendo:** cada tentativa malsucedida deve ter diagnóstico pesquisável na área administrativa do Portal Imagine, inclusive tipo de erro, mensagem clara, componente envolvido, data e hora da tentativa, número de tentativas, status atual e próxima tentativa quando aplicável.
4. **Alertas:** problemas persistentes devem gerar avisos administrativos; nenhuma ocorrência pode desaparecer da visualização apenas porque uma tentativa falhou.
5. **Segurança dos diagnósticos:** registrar códigos técnicos/HTTP e causas úteis ao suporte sem expor chaves de API, tokens, senhas, conteúdo sensível de formulários ou dados pessoais desnecessários nos logs.
6. **Evitar duplicidades:** usar identificador único por envio e idempotência ao retentar, alternar entre APIs, fazer reconciliação ou reprocessamento manual.

## Interpretação operacional explicitada para validação

A redação dos dois prazos sugere **etapas sucessivas**: até **3 dias de fila de entrega**, seguidos por **até 15 dias na fila de falhas**, resultando em **até 18 dias** desde o recebimento se ambas as etapas forem integralmente utilizadas. Essa forma de contagem é uma **interpretação de trabalho** e deverá ser confirmada antes de ser fixada como regra de expiração definitiva. Também será detalhado o marco inicial da contagem em casos de falha identificada antes dos 3 dias.

Em falhas temporárias, repetir automaticamente em intervalos progressivos; em erros permanentes ou de autenticação/configuração, apresentar diagnóstico e evitar repetição agressiva até a causa ser corrigida. Após correção, retomar de forma automática quando seguro e/ou por intervenção autorizada.

## Exemplo de visualização administrativa

| Informação | Exemplo ilustrativo |
|---|---|
| Site/formulário | site-exemplo / Contato |
| Identificador | ID único de envio |
| Situação | Falha na entrega |
| Motivo | Falha de autenticação com CRM (HTTP 401) |
| Última tentativa | Data e hora |
| Tentativas | Quantidade |
| Próxima ação | Corrigir credenciais; retentar após validação |
| Ações autorizadas | Ver histórico, diagnosticar, reprocessar |

## Pontos não aprovados e pendentes de detalhamento

- **Após entrega bem-sucedida:** a proposta anterior de manter o conteúdo por 24 horas antes da limpeza **não foi expressamente confirmada**.
- **Histórico/auditoria:** a proposta anterior de 90 dias **não foi expressamente confirmada**.
- **Descarte ao fim da fila de falhas:** definir avisos antecipados, exportação/recuperação, política de eliminação segura e exceções legais; não apagar silenciosamente. Sem compromisso de retenção ilimitada.
- Frequência e limites das tentativas; distinção entre erros temporários e permanentes; marco da contagem do prazo de 15 dias; comportamento quando o CRM associado foi suspenso ou alterado.
- Armazenamento criptografado, backups de filas, retenção de conteúdo pessoal, limites de volume, métricas e custos.
- Revisar o prazo máximo de 18 dias conforme interpretação acima antes da implementação.

## Restrições permanentes
- Sites Imagine **não possuem banco de dados próprio**.
- API principal e API de contingência ficam em infraestruturas independentes, com armazenamento persistente próprio, conforme IN-124.
- Apenas confirmar o envio ao visitante **após persistência durável por uma das APIs**.
- A área administrativa do Portal Imagine gerencia diagnósticos, alertas, filas e reprocessamento, mas sua indisponibilidade não impede recepção pelas APIs.
- Falha simultânea das duas APIs pode impedir o recebimento; não prometer garantia absoluta de perda zero.

**Status funcional:** novas decisões aprovadas e documentadas; parâmetros pendentes separados. **Programação não iniciada.**
