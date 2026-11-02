# PORTAL-COM-07 — Vencimento mensal configurável e calendário recorrente

**Status:** **DIRETRIZES FUNCIONAIS APROVADAS em 02/11/2026:** (1) vencimento mensal configurável com padrão no mesmo dia do mês da primeira cobrança e (2) **nos meses sem o dia de referência, vencimento no último dia calendário daquele mês e retorno ao dia original no mês seguinte**. A segunda decisão foi aprovada por novo **“ok”** do responsável à pergunta específica. **DECISÃO MAIS RECENTE (02/11/2026):** tentar a cobrança na **data configurada**, mesmo em sábado, domingo ou feriado bancário; se **não concluir** em dia não útil, permitir regularização **até o próximo dia útil sem juros nem multa**. **Pix Automático depende de configuração válida do PSP e verificação jurídica para débito em dia não útil**. A implementação do calendário bancário, retentativas e particularidades dos meios segue para detalhamento.

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

## 4. Fins de semana, feriados e meios de pagamento — decisão expressa posterior na seção 7; detalhes técnicos pendentes

- **Calendário de vencimento contratual** e **data real de débito/liquidação** são coisas diferentes. Pix Automático e cartão dependem de fluxo e prazos do PSP, autorização e políticas técnicas. Definir o que ocorre quando o vencimento cai em sábado, domingo ou feriado, sem antecipar cobrança inesperadamente.
- Não presumir que todos os meios possuem o mesmo comportamento de débito; avisos, retentativas e identificação de cada competência precisam ser configurados.
- Pagamento via Pix manual, boleto e cartão seguem apenas as modalidades efetivamente aprovadas/contratadas na PORTAL-D04; este documento não cria novo método.
- Evitar duplicidade quando muda a forma de pagamento, quando a primeira mensalidade cai num dia incomum ou quando um ciclo sofre retentativa.
- Não modificar de forma unilateral o contrato, o índice de reajuste ou a condição promocional do assinante.

## 5. O que falta para o fechamento

1. **Meses sem o dia contratado — RESOLVIDO:** usar o último dia calendário desse mês e retornar ao dia âncora nos meses seguintes.
2. **Regra funcional de dias não úteis aprovada:** tentar cobrar no dia configurado e, se falhar em sábado/domingo/feriado bancário, permitir pagamento até o próximo dia útil sem juros nem multa. **Pendente:** compatibilidade de cada meio e do PSP (Pix Automático pode ter ajuste-padrão para dia útil), calendário de feriados locais, autorização e estratégia de retentativas.
3. Aprovação das regras quando dia especial de campanha é diferente da primeira mensalidade e das mudanças de vencimento solicitadas por clientes.
4. Definição do marco concreto dos 90 dias (contratação, ativação etc.), políticas de emissão/aviso, vencimento, conciliação e inadimplência.
5. Homologação de calendário com ano bissexto, fuso, atraso e retentativas, sem antecipar cobranças nem perder direitos contratados.

**Resumo vigente:** vence mensalmente em dia configurável, **por padrão no mesmo dia da primeira mensalidade**. Se o mês não tiver o dia contratado, vencerá no **último dia do mês**, recuperando o dia âncora no mês seguinte. **Pendente: fins de semana/feriados e efeitos operacionais por meio de pagamento.** Nenhuma implementação ou cobrança autorizada.


## 6. Registro da aprovação complementar dos meses curtos (02/11/2026)

A pergunta explicitou a regra: para vencimento nos dias **29, 30 ou 31**, se o mês não comportar a data, utilizar **o último dia do mês** (28/29 em fevereiro) e **voltar ao dia contratado no mês seguinte**. O responsável respondeu **“ok”**, aprovando essa regra. A data de ajuste é calculada apenas para o ciclo respectivo; não altera o dia âncora nem a duração da mensalidade promocional, preço contratado, reajuste anual ou outros vencimentos. A regra de fins de semana e feriados não foi aprovada nesse aceite e será deliberada separadamente. **Sem programação ou cobrança real.**


## 7. Correção e aprovação expressa — tentativa no vencimento mesmo em dia não útil; tolerância após falha (02/11/2026)

**Prevalência:** esta decisão é **posterior e substitui** qualquer leitura da proposta de simplesmente **adiar o vencimento e a primeira tentativa para o dia útil seguinte**. O responsável declarou que **“o pagamento será passado no dia que está configurado, porém se porventura der algum problema e o pagamento que era no sábado, domingo ou feriado bancário ele pode pagar no próximo dia útil sem juro e multa”**.

### Regra funcional aprovada

1. **Data de vencimento original preservada:** o ciclo possui a data **contratada/configurada** (inclusive em fim de semana/feriado). Se a data de referência 29, 30 ou 31 não existir, aplica-se primeiro a regra **já aprovada** do último dia do mês; esse dia pode também ser não útil.
2. **Tentativa de pagamento na data configurada:** a Imagine deve solicitar a cobrança automática naquela data, **inclusive em sábado, domingo ou feriado bancário**, desde que o meio, o provedor, o mandato e a legislação aplicável permitam essa operação. **Não adiar voluntariamente e indiscriminadamente** toda cobrança para o próximo dia útil.
3. **Sucesso:** pagamento recebido e conciliado na data originalmente programada → **quitado**, sem cobrança repetida.
4. **Falha ou não liquidação em dia não útil:** disponibilizar possibilidade de o cliente **pagar/regularizar até o próximo dia útil bancário**, **sem juros, multa ou marcação de atraso com consequências financeiras durante esse prazo**, respeitado o meio e contrato. O vencimento original permanece registrado; a **data-limite de regularização sem encargos** é um campo distinto.
5. **Segunda-feira feriado:** vencer no sábado → prazo até **segunda-feira** se útil, ou **terça-feira/próximo dia útil** caso segunda não seja útil. Usar calendário de feriados bancários aplicável, inclusive locais quando exigido.
6. **Nenhuma suspensão/cobrança punitiva antes de vencer a tolerância de dia não útil** por aquela falha. Ao final do período, qualquer inadimplência/retentativa depende da política PORTAL-D04 e contrato; este aceite **não fixa taxas futuras, prazo extra geral em dias úteis ou regime de inadimplência**.
7. **Recuperação segura:** permitir pagamento manual e/ou tentativa autorizada conforme regras do PSP, com verificação do estado real, idempotência e prevenção de débito duplicado. **Não presumir autorização para repetidas tentativas de cartão/Pix** quando o meio não permitir ou o mandato for cancelado.
8. **Preservar calendário dos próximos meses:** o deslocamento da data-limite sem encargos por falha **não altera o dia contratado** dos ciclos futuros.

### Cuidados por modalidade, sem contrariar a decisão comercial

- **Pix Automático:** regulamentação técnica do Banco Central contempla cobranças para datas úteis e não úteis, mas **o ajuste para próximo dia útil pode ser a configuração padrão do PSP**. Para tentar liquidar no dia não útil, a Imagine/PSP precisará **desabilitar o ajuste automático quando permitido e certificar-se do respaldo jurídico**; se o parceiro não suportar, informar a limitação e escolher fluxo homologado, sem prometer operação inviável. Segundo Instrução Normativa BCB nº 513 e guia oficial, há requisitos próprios de autorização, agendamento, calendário e novas tentativas.
- **Cartão de crédito recorrente:** programar a solicitação de pagamento no dia contratado, inclusive não útil, se o adquirente permitir; **confirmação efetiva** e calendário de repasse não são equivalentes à data de tentativa. Se ocorrer falha em dia não útil, aplicar a mesma tolerância comercial ao cliente. Política de retentativas e notificações depende da adquirente e do contrato.
- **Pix avulso/manual:** opção de regularização quando habilitada e devida, com QR Code identificável e valor sem multa até a data-limite. Não duplicar Pix Automático eventualmente liquidado.
- **Boleto (se futuramente aprovado):** considerar regra específica de vencimento em dia não útil, em que o Banco Central informa ser possível o pagamento no próximo dia útil sem multa/juros; não presumir que boleto tem débito automático no fim de semana.

### Exemplo didático

Vencimento **sábado, 14/11/2026** (exemplo de calendário), cartão recorrente autorizado: solicitar cobrança no sábado; se pago, quitar; se falhar, admitir pagamento até **segunda-feira, 16/11/2026**, **somente se esta for dia útil bancário aplicável**, sem juros ou multa. Se segunda for feriado bancário local, permitir no próximo dia útil bancário, sem antecipar penalidade.

### Fronteiras da aprovação

Esta decisão aprova **(a)** tentar cobrança na data, **(b)** tolerância sem juros/multa até próximo dia útil após falha no dia não útil, **(c)** preservar data contratual, **(d)** conciliação sem duplicidade. **Não aprova** regras gerais de inadimplência após esse prazo, forma de pagamento específica que ainda esteja apenas proposta, calendário exato/integração do PSP, retentativas obrigatórias ou débito em dia não útil caso seja juridicamente inviável. Nenhuma programação ou cobrança iniciada.

**Fontes oficiais para revisão de integração:**
- Banco Central, Instrução Normativa nº 513: https://www.bcb.gov.br/estabilidadefinanceira/exibenormativo?numero=513&tipo=Instru%C3%A7%C3%A3o+Normativa+BCB
- Banco Central, guia de implementação Pix Automático: https://www.bcb.gov.br/content/estabilidadefinanceira/pix/automatico/guia_pix_automatico.pdf
- Banco Central, boleto com vencimento em dia não útil: https://www.bcb.gov.br/meubc/faqs/p/boleto-com-vencimento-em-dia-nao-util-e-boleto-vencido

## 8. Etapa seguinte aprovada — pendência e aviso após o prazo (02/11/2026)

O responsável respondeu “ok” à regra posterior: **se não houver quitação até o próximo dia útil bancário disponível para regularizar a falha em dia não útil**, o sistema **registra mensalidade pendente e notifica o cliente, sem suspensão imediata**. O intervalo adicional de tolerância e as regras de suspensão **não foram definidos**. Manter data contratada, prazo sem encargos, status efetivo da conciliação e tratamento de comprovantes distintos. A política funcional de inadimplência está consolidada na [PORTAL-D04](PORTAL-D04-cobranca-recorrente-sites.md); não determinar juros, multa, suspensão nem efeitos no CRM neste documento.
