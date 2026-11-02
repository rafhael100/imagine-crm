# PORTAL-COM-07 — Vencimento mensal configurável e calendário recorrente

**Status:** **DIRETRIZES FUNCIONAIS APROVADAS em 02/11/2026:** (1) vencimento mensal configurável com padrão no mesmo dia do mês da primeira cobrança e (2) **nos meses sem o dia de referência, vencimento no último dia calendário daquele mês e retorno ao dia original no mês seguinte**. A segunda decisão foi aprovada por novo **“ok”** do responsável à pergunta específica. **Fins de semana e feriados permanecem pendentes de decisão.**

**Escopo:** assinatura recorrente dos sites Imagine (Onepage, Landing Page e outros serviços elegíveis). Não altera o parcelamento de implantação/serviços adicionais nem o CRM Imagine SaaS.  
**Fase:** exclusivamente planejamento/documentação; não iniciar código, alterações contratuais ou cobranças.  
**Referências:** [PORTAL-COM-06 — prazo até a primeira mensalidade](PORTAL-COM-06-inicio-mensalidade-configuravel-por-campanha.md); [PORTAL-COM-05 — condições da mensalidade e reajuste anual](PORTAL-COM-05-gestao-administrativa-precos-campanhas-reajuste-anual.md); [PORTAL-D04 — cobrança recorrente](PORTAL-D04-cobranca-recorrente-sites.md); [PORTAL-01 — matriz de pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md).

## 1. Regras aprovadas

1. **Administração do vencimento mensal:** o painel administrativo pode configurar o **dia de vencimento recorrente por serviço e/ou campanha**, sem editar código. O contrato/pedido deve identificar qual condição está sendo contratada.
2. **Padrão vinculado à primeira cobrança:** se não houver uma escolha diferente e expressa na oferta, o **dia do mês da primeira mensalidade devida será o dia de referência dos vencimentos mensais seguintes**.
3. **Preservar o dia de referência:** desvios de calendário num mês que não possui determinado dia **não devem deslocar permanentemente o dia de referência dos próximos meses**. A forma exata do ajuste do mês curto será decidida.
4. **Primeira cobrança independente:** o prazo até a primeira mensalidade continua administrável **por serviço e por campanha** (COM-06), atualmente **90 dias como padrão**, e o primeiro vencimento efetivo deve ser exibido no aceite.
5. **Campanhas e contratos existentes:** mudar o dia padrão ou um calendário de uma nova campanha **não altera os vencimentos de clientes já contratados**. Eventuais mudanças de dia de um cliente exigem fluxo administrativo, registro e direitos/consentimento aplicáveis.
6. **Marcos diferentes:** **N mensalidades promocionais** começam a contar da primeira mensalidade devida; **reajuste anual** ocorre somente após **12 meses de contrato** e conforme o índice aceito; o dia de vencimento recorrente não substitui esses relógios.

## 2. Modelo de precedência sugerido para especificação

- Data e vencimentos **expressamente pactuados** no contrato aceito prevalecem.
- Na contratação nova, se a campanha tiver um dia especial de vencimento homologado e aceito, utilizar o dia configurado da campanha.
- Na ausência de regra específica de campanha, utilizar o padrão do produto, se houver.
- Na ausência de configuração diferente, usar o **mesmo dia do mês da primeira mensalidade devida**.

Se o dia específico de campanha for diferente do dia da primeira cobrança, o **intervalo até o segundo vencimento** precisa de regra explícita e simulação antes do aceite, para evitar vencimentos muito próximos ou ciclos cobrados duas vezes. Este detalhe ainda depende de aprovação.

## 3. Regra para meses curtos — APROVADA em 02/11/2026

**Regra aprovada:** **se o mês não tiver o dia de referência escolhido, vencer no último dia calendário daquele mês**, e **retomar o dia originalmente pactuado no mês seguinte que o tiver**. Exemplo:
- referência dia **31** → janeiro **31**, fevereiro **28** (ou **29** em ano bissexto), março **31**;
- referência dia **30** → janeiro **30**, fevereiro **28/29**, março **30**;
- referência dia **29** → fevereiro **28** em ano não bissexto; os demais meses têm dia 29.

**Aprovado expressamente** como regra funcional. Não confundir a data ajustada de um mês curto com alteração definitiva do dia de referência contratado. O comportamento em feriados e fins de semana, por meio de pagamento, ainda será deliberado separadamente.

## 4. Fins de semana, feriados e meios de pagamento — ainda pendentes

- **Calendário de vencimento contratual** e **data real de débito/liquidação** são coisas diferentes. Pix Automático e cartão dependem de fluxo e prazos do PSP, autorização e políticas técnicas. Definir o que ocorre quando o vencimento cai em sábado, domingo ou feriado, sem antecipar cobrança inesperadamente.
- Não presumir que todos os meios possuem o mesmo comportamento de débito; avisos, retentativas e identificação de cada competência precisam ser configurados.
- Pagamento via Pix manual, boleto e cartão seguem apenas as modalidades efetivamente aprovadas/contratadas na PORTAL-D04; este documento não cria novo método.
- Evitar duplicidade quando muda a forma de pagamento, quando a primeira mensalidade cai num dia incomum ou quando um ciclo sofre retentativa.
- Não modificar de forma unilateral o contrato, o índice de reajuste ou a condição promocional do assinante.

## 5. O que falta para o fechamento

1. **Meses sem o dia contratado — RESOLVIDO:** usar o último dia calendário desse mês e retornar ao dia âncora nos meses seguintes.
2. Definição de fins de semana/feriados e tratamento por meio de pagamento.
3. Aprovação das regras quando dia especial de campanha é diferente da primeira mensalidade e das mudanças de vencimento solicitadas por clientes.
4. Definição do marco concreto dos 90 dias (contratação, ativação etc.), políticas de emissão/aviso, vencimento, conciliação e inadimplência.
5. Homologação de calendário com ano bissexto, fuso, atraso e retentativas, sem antecipar cobranças nem perder direitos contratados.

**Resumo vigente:** vence mensalmente em dia configurável, **por padrão no mesmo dia da primeira mensalidade**. Se o mês não tiver o dia contratado, vencerá no **último dia do mês**, recuperando o dia âncora no mês seguinte. **Pendente: fins de semana/feriados e efeitos operacionais por meio de pagamento.** Nenhuma implementação ou cobrança autorizada.


## 6. Registro da aprovação complementar dos meses curtos (02/11/2026)

A pergunta explicitou a regra: para vencimento nos dias **29, 30 ou 31**, se o mês não comportar a data, utilizar **o último dia do mês** (28/29 em fevereiro) e **voltar ao dia contratado no mês seguinte**. O responsável respondeu **“ok”**, aprovando essa regra. A data de ajuste é calculada apenas para o ciclo respectivo; não altera o dia âncora nem a duração da mensalidade promocional, preço contratado, reajuste anual ou outros vencimentos. A regra de fins de semana e feriados não foi aprovada nesse aceite e será deliberada separadamente. **Sem programação ou cobrança real.**
