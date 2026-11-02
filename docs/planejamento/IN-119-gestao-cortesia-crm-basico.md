# IN-119 — Gestão Automática da Cortesia do CRM Básico

**Estado:** APROVADA funcionalmente em 01/11/2026.  
**Fase:** planejamento/documentação; programação NÃO autorizada.  
**Relacionamentos:** Imagine Sites, contratos, planos e assinaturas, integração entre site/landing page e CRM; IN-118 (backup, exportação/importação e portabilidade).

## Objetivo
Gerenciar automaticamente a concessão, manutenção e fim da cortesia comercial do plano CRM Básico para clientes com sites Imagine elegíveis, com transparência e tempo de transição antes de eventual cobrança ou restrição.

## Regras funcionais aprovadas

1. **Produto comercial:** o plano CRM Básico tem preço público de **R$ 69,00/mês**, sujeito à revisão geral de preços posteriormente; quando aplicável, poderá ser concedido como cortesia vinculada a contrato elegível do Imagine Sites.
2. **Concessão:** a contratação de um site elegível habilita a cortesia do CRM Básico para uma conta CRM vinculada, conforme regras de vinculação aprovadas. Ao contratar outro site/landing page, o cliente pode escolher vincular a uma conta CRM existente ou criar outra.
3. **Múltiplos sites na mesma conta:** dois ou mais sites vinculados a uma única conta CRM não geram múltiplas cortesias nem créditos acumulativos nessa mesma conta.
4. **Manutenção:** se um site for cancelado, mas houver outro contrato de site elegível ainda ativo que sustente a cortesia da mesma conta CRM, o benefício permanece.
5. **Fim da elegibilidade:** o cancelamento do último site elegível que sustenta a cortesia desencadeia aviso ao cliente sobre o encerramento do benefício e **período de transição de 30 dias**. O CRM não deve ser bloqueado imediatamente apenas por esse motivo.
6. **Durante a transição:** dados preservados e cliente capaz de usar as funcionalidades de **backup, exportação e download** para manter cópias próprias. A política de continuidade dos outros recursos do CRM durante esse período deve ser detalhada no desenho operacional.
7. **Opção de permanência:** oferecer ao cliente a continuidade por assinatura do CRM Básico a **R$ 69,00/mês**, mediante contratação/consentimento explícito, sujeita à tabela comercial vigente.
8. **Plano superior:** cliente que adota Essencial, Profissional ou Empresarial paga segundo as condições do plano contratado; a cortesia do Básico não equivale a crédito acumulável nem gera automaticamente desconto no plano superior.
9. **Retorno ao Básico:** cliente que reduzir de plano superior para Básico poderá usufruir da cortesia se a conta ainda atender aos critérios de elegibilidade.
10. **Operação centralizada e rastreável:** alterações de elegibilidade, vínculo com site, início/fim da transição e notificações devem ser gerenciadas por mecanismos auditáveis de contratos/assinaturas.

## Cenários de referência

| Evento | Resultado |
|---|---|
| Primeiro site elegível contratado | Conceder CRM Básico como cortesia na conta vinculada |
| Outro site ligado à mesma conta | Preservar uma única cortesia, sem duplicar |
| Novo site em conta CRM distinta | Aplicar critérios de elegibilidade da conta distinta; política antifraude/quantidade por titular pendente de detalhamento |
| Um entre vários sites elegíveis da conta é cancelado | Cortesia permanece se houver outro site elegível |
| Último site elegível da conta é cancelado | Aviso e transição de 30 dias, sem bloqueio imediato |
| Cliente opta pela assinatura paga | Continuidade pelo preço e condições comerciais vigentes |
| Cliente usa plano superior | Cobrança do plano contratado, sem acumulação automática de créditos da cortesia |
| Cliente retorna ao Básico e continua elegível | Pode voltar a usufruir da cortesia |

## Restrições e cuidados

- **Não ativar pagamento recorrente automaticamente** apenas pelo fim da cortesia; é necessária a contratação expressa do cliente. O destino da conta sem aceite ao fim dos 30 dias deve respeitar a política geral de suspensão, cancelamento e retenção, a ser integrada na especificação.
- Preservar as regras de retenção já aprovadas para o CRM e a capacidade de exportação/portabilidade conforme IN-118 e obrigações legais.
- A alteração técnica do CRM vinculado a um site, solicitada por chamado, permanece gratuita; migração de dados entre contas fica por conta do cliente.
- Separar status do site, do contrato que sustenta a cortesia, do benefício comercial e da assinatura CRM; evitar cancelamento acidental de outra conta ou site.
- Registrar no histórico justificativas e eventos de elegibilidade, notificações enviadas e escolhas do cliente.
- **Não presumir** prazo de lembretes, políticas de cobrança, regra de contagem dos 30 dias (marco inicial exato), funcionamento completo na transição nem alcance de cortesia em múltiplas contas até aprovação específica.

## Pendências para especificação posterior
- Marco de início e término dos 30 dias; suspensão/reativação por restabelecimento da elegibilidade e regras para site em inadimplência.
- Detalhes dos avisos e canais (início, lembretes e encerramento).
- O que acontece ao final do prazo sem aceite (status, acesso e retenção), respeitando regras gerais já aprovadas do SaaS.
- Fluxo de mudança entre planos, limites e conta existente versus nova; definição de critérios antifraude sem restringir casos legítimos.
- Conciliação com promoções, condições comerciais personalizadas e prazos das assinaturas.

**Próximo passo:** seguir planejamento agrupado e obter novas aprovações. **Nenhum desenvolvimento deve ser iniciado.**


## Complemento COM-08 — beneficio por campanha e suspensao de acesso por inadimplencia (02/11/2026)

Nova direção funcional: a elegibilidade **CRM Básico cortesia passa a ser configurável no painel por serviço e campanha**, e deverá constar do contrato/aceite para **novas vendas**; benefícios já concedidos não são revogados pela edição de campanhas. **Caso especial de inadimplência:** se o site for **suspenso após 7 dias de tolerância** e a conta CRM depender **exclusivamente desse site** para cortesia, **bloquear acesso operacional do Básico cortesia no mesmo evento**, preservando dados, portabilidade e reativação segura. **Outros sites elegíveis ativos e planos CRM pagos independentes impedem esse bloqueio**. Este bloqueio **temporário por inadimplência não revoga nem substitui automaticamente** a transição de 30 dias já aprovada para **cancelamento formal do último vínculo elegível**. Os fluxos em que uma suspensão evolui para cancelamento precisam de harmonização para respeitar ambas as políticas e o contrato. Ver [PORTAL-COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md). Sem programação.


## Complemento COM-09 — exceção na transição por inadimplência (02/11/2026)

**Decisão posterior aprovada:** os **30 dias de CRM Básico cortesia em operação normal** continuam válidos para o **cancelamento comum do último site elegível**. Se o site estava **suspenso por inadimplência** e é posteriormente **cancelado por essa causa**, iniciam-se os **30 dias de transição no cancelamento formal do último vínculo**, porém **sem reativar a operação do CRM que já estava bloqueada**. O cliente poderá entrar em acesso restrito, **exportar/baixar dados e contratar expressamente CRM pago**, sem cobrança automática nem exclusão. Outro site elegível ou CRM pago independente preservam acesso. A retenção do CRM de 120 dias só começa em seu **cancelamento formal próprio**, não no cancelamento do site. Ver [COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md). Sem programação.
