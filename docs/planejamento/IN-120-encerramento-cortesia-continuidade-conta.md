# IN-120 — Encerramento da Cortesia e Continuidade da Conta CRM

**Estado:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026.  
**Etapa:** planejamento e documentação, sem autorização para programação.  
**Dependências:** IN-118 (portabilidade e backups), IN-119 (cortesia do CRM Básico e 30 dias de transição), políticas gerais de assinatura/cancelamento/retenção do CRM Imagine.

## Contexto
O plano CRM Básico possui preço público de referência de **R$ 69,00/mês** e pode ser concedido como cortesia quando houver contrato de site Imagine elegível vinculado. Quando acaba o último vínculo elegível que sustenta a cortesia, inicia-se um período de **30 dias de transição**, conforme IN-119.

## Regras funcionais aprovadas

1. **Durante os 30 dias de transição:** manter o CRM em funcionamento normal, preservando os registros e as funções previstas no plano, com avisos claros sobre o fim da cortesia e opções de continuidade.
2. **Contratação expressa do Básico:** se o cliente contratar o CRM Básico pago dentro da transição, manter a conta e seus dados, sem interrupção. A cobrança ocorre conforme a contratação aceita; **não ativar cobrança automaticamente** por término da cortesia.
3. **Sem contratação ao término dos 30 dias:** suspender a operação normal da conta CRM sem cobrança automática, em vez de excluí-la imediatamente.
4. **Conta suspensa:** oferecer acesso restrito e autenticado para (i) contratar/reativar a conta, e (ii) **exportar ou baixar seus dados**. Não permitir funções operacionais comuns nessa condição, até reativação.
5. **Retenção:** preservar os dados de acordo com a política geral já aprovada de **120 dias de retenção após o cancelamento** da conta. Distinguir evento de suspensão e evento formal de cancelamento; não presumir que a simples suspensão já inicie o relógio dos 120 dias.
6. **Não excluir dados na suspensão.** Qualquer exclusão ao término da retenção dependerá das políticas gerais, dos avisos, das obrigações legais e das salvaguardas técnicas aplicáveis.
7. **Reativação:** manter o caminho de retomada contratual, preservando dados enquanto ainda estiverem sob retenção; não prometer recuperação de dados já regularmente eliminados.
8. **Auditoria:** registrar mudança de elegibilidade, início/fim da transição, comunicações, aceites de contratação, suspensão, reativação e cancelamento com datas e responsáveis.
9. **Autonomia e portabilidade:** respeitar IN-118; a suspensão por fim da cortesia não pode impedir que o cliente obtenha os dados disponíveis para exportação ou faça download de backups acessíveis conforme política vigente.

## Fluxo resumido

Último site elegível deixa de sustentar a cortesia → aviso e início da transição → **30 dias com CRM operacional** → 
- **Cliente contrata:** continuidade como CRM Básico pago, sem perda de dados.
- **Cliente não contrata:** **suspensão**, sem débito automático; acesso restrito para contratação/reativação e exportação.

A retenção de **120 dias** da política geral se aplica **após cancelamento** e deve ser integrada ao processo sem confundir cancelamento com suspensão.

## Detalhes operacionais a especificar antes de desenvolver

- Marco temporal exato do início dos 30 dias, calendário e fuso de referência.
- Cronograma e canais das notificações preventivas; textos de avisos.
- Quando uma conta suspensa por fim de cortesia é formalmente cancelada, à luz das políticas gerais e outros produtos ativos.
- Efeito de recuperação da elegibilidade (ex.: novo site elegível antes do cancelamento), sem criar assinaturas ou cortesias duplicadas.
- Como funcionam solicitações de exportação e geração de backup com a conta suspensa, com limitação de custos abusivos e controles de segurança.
- Regras de reativação e casos com assinatura preexistente, múltiplos sites ou mais de uma empresa/conta CRM.
- Compatibilização com conservação legal, proteção de dados, auditabilidade, política financeira e combate a fraude.

**Status:** regra funcional aprovada; detalhes acima seguem pendentes. **Nenhuma implementação autorizada.**


## Complemento COM-08 — suspensao temporaria por inadimplencia do site (02/11/2026)

Aprovado posteriormente: **suspensão efetiva de site inadimplente após tolerância de 7 dias** também **suspende acesso operacional do CRM Básico cortesia quando aquela conta não tiver outro site ativo elegível**. Não suspender CRM pago independente, nem a cortesia sustentada por outro contrato. Este **bloqueio por dívida** é um estado distinto da **transição normal de 30 dias por cancelamento formal do último vínculo elegível**, que continua aprovada neste documento. A integração entre ambos os estados em caso de inadimplência que evolui a cancelamento exige especificação e aceite adicional antes da programação. Preservar conta, dados, exportação/portabilidade, possibilidade de regularização e reativação; **não converter automaticamente para CRM pago**. A cortesia de novos sites pode ser configurada por serviço e campanha. Referência [PORTAL-COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md).

## Complemento COM-09 — duas modalidades de transição após cancelamento do último site (02/11/2026)

O responsável aprovou **exceção explícita** à operação normal de 30 dias desta IN-120: **se o último site elegível foi suspenso por inadimplência e depois cancelado por essa causa, os 30 dias de transição passam a ser com acesso operacional do CRM Básico cortesia restrito**, permitindo **exportação, download e contratação expressa de CRM pago**, sem restabelecer o uso das funções operacionais. **Para cancelamento comum permanece a regra original desta IN-120:** 30 dias com CRM operacional normal. Transição de 30 dias inicia no cancelamento formal do último vínculo, não na primeira pendência nem no bloqueio prévio do site. Preservar dados, não cobrar sem aceite, não alterar CRM pago ou cortesia sustentada por outro site. Retenção 120 dias somente após cancelamento formal da **conta CRM**. Detalhes [COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md). Sem programação.
