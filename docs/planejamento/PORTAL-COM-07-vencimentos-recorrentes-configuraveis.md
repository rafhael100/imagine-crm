# PORTAL-COM-07 — Vencimento mensal configurável e calendário recorrente

**Status:** **DIRETRIZ FUNCIONAL PARCIALMENTE APROVADA em 02/11/2026**: o responsável respondeu **“ok”** à recomendação expressa de permitir **vencimento mensal configurável**, com **padrão no mesmo dia do mês da primeira cobrança** e tratamento adequado dos meses sem essa data. **Os critérios exatos de ajuste para meses curtos, fins de semana e feriados ainda serão deliberados; não são regras específicas aprovadas por esse “ok”.**

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

## 3. Regra para meses curtos — proposta para próxima aprovação (NÃO APROVADA)

**Recomendação:** **se o mês não tiver o dia de referência escolhido, vencer no último dia calendário daquele mês**, e **retomar o dia originalmente pactuado no mês seguinte que o tiver**. Exemplo:
- referência dia **31** → janeiro **31**, fevereiro **28** (ou **29** em ano bissexto), março **31**;
- referência dia **30** → janeiro **30**, fevereiro **28/29**, março **30**;
- referência dia **29** → fevereiro **28** em ano não bissexto; os demais meses têm dia 29.

**Não tornar padrão automático antes da aprovação expressa.** Alternativas possíveis: dia útil seguinte em caso de data inexistente (quando fizer sentido para cada meio de pagamento), dia fixo anterior, ou impedir escolher 29–31 no dia de vencimento. A primeira opção oferece maior previsibilidade mensal, mas precisa ser deliberada.

## 4. Fins de semana, feriados e meios de pagamento — ainda pendentes

- **Calendário de vencimento contratual** e **data real de débito/liquidação** são coisas diferentes. Pix Automático e cartão dependem de fluxo e prazos do PSP, autorização e políticas técnicas. Definir o que ocorre quando o vencimento cai em sábado, domingo ou feriado, sem antecipar cobrança inesperadamente.
- Não presumir que todos os meios possuem o mesmo comportamento de débito; avisos, retentativas e identificação de cada competência precisam ser configurados.
- Pagamento via Pix manual, boleto e cartão seguem apenas as modalidades efetivamente aprovadas/contratadas na PORTAL-D04; este documento não cria novo método.
- Evitar duplicidade quando muda a forma de pagamento, quando a primeira mensalidade cai num dia incomum ou quando um ciclo sofre retentativa.
- Não modificar de forma unilateral o contrato, o índice de reajuste ou a condição promocional do assinante.

## 5. O que falta para o fechamento

1. **Aprovação do tratamento dos meses sem o dia contratado**: recomendação de último dia do mês e retorno ao dia âncora.
2. Definição de fins de semana/feriados e tratamento por meio de pagamento.
3. Aprovação das regras quando dia especial de campanha é diferente da primeira mensalidade e das mudanças de vencimento solicitadas por clientes.
4. Definição do marco concreto dos 90 dias (contratação, ativação etc.), políticas de emissão/aviso, vencimento, conciliação e inadimplência.
5. Homologação de calendário com ano bissexto, fuso, atraso e retentativas, sem antecipar cobranças nem perder direitos contratados.

**Resumo:** vence mensalmente em dia configurável, **por padrão no mesmo dia da primeira mensalidade**. Meses curtos exigem regra específica a deliberar; sugestão do **último dia do mês**, restaurando o dia de referência posteriormente. Nenhuma implementação ou cobrança autorizada.
