# PORTAL-D04 — Cobranças Recorrentes dos Sites Imagine

> **ATENÇÃO — POLÍTICA POSTERIOR (03/11/2026):** a diretriz anterior de **10% de desconto no Pix Automático** foi substituída por **5%**; cartão recorrente permanece sem o desconto Pix. Para implantação, Pix à vista **5%** ou cartão até **3x** sem desconto; sem Pix parcelado. Ver [registro vigente](PORTAL-COM-POLITICA-VIGENTE-PIX-05-CARTAO-3X-2026-11-03.md). As condições históricas do fluxo financeiro permanecem preservadas para reconciliação e não representam homologação nem autorização de cobrança.

**Status:** **10% DE DESCONTO PARA MENSALIDADES PAGAS POR PIX AUTOMÁTICO APROVADO COMO DIRETRIZ COMERCIAL em 02/11/2026**. O preço-base R$ 87 por produto, ativação das modalidades recorrentes, provedores e parâmetros financeiros continuam pendentes. Proposta de Pix Automático + cartão recorrente sujeita a homologação/contratação específica.  
**Origem:** pergunta do responsável "E nos pagamentos recorrentes?" após decidir que **serviços adicionais só parcelam por cartão de crédito** ([PORTAL-D02](PORTAL-D02-pagamento-servicos-adicionais.md)).
**Escopo:** mensalidades recorrentes de **Onepage e Landing Page** contratados pelo **Portal Imagine**, não parcelamento de orçamento de adicional, nem mensalidade/upgrade do CRM Imagine.
**Fase:** planejamento/documentação exclusivamente. Não iniciar implementação, contratar instituição/PSP, emitir cobrança ou ativar débito real.
**Dependências:** [PORTAL-01 — matriz de pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md); IN-091/092 (pagamentos do site e direito a CRM Básico); IN-119/120/121 (cortesia, período de transição e notificações); [PORTAL-D01 — aceite e provas](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md).

## 1. Decisões anteriores que não devem ser reabertas

- Assinatura mensal do site: valor comercial de referência de planejamento **R$ 79,90/mês**, iniciando no **90º dia** conforme regras comerciais já aprovadas; definição final de preços e marcos contratuais passa pela revisão global.
- Portal gerencia **contrato e pagamentos do site**; assinatura CRM paga/upgrade pertence ao **CRM** e não sofre encerramento automático porque o site atrasou/cancelou.
- Cortesia CRM Básico associada à elegibilidade do(s) site(s), respeitando as políticas já aprovadas de transição de **30 dias** e reativação/suspensão, e sem cobrança automática por benefício expirado.
- Parcelamento dos **serviços adicionais** acima de R$ 270 em até 3x sem juros ocorre **exclusivamente por cartão** (decisão mais recente da PORTAL-D02). Não confundir parcelamento de compra com mensalidade recorrente.

## 2. Proposta de meios de pagamento para mensalidades de site

| Meio | Proposta | Premissas e cuidados |
|---|---|---|
| **Pix Automático** | **Modalidade preferencial proposta** | Cliente fornece **autorização no banco** para recorrência; Portal/PSP envia cobranças conforme periodicidade contratada. Não é empréstimo nem parcelamento. Cada mensalidade é recebida quando efetivamente liquidada. Provedor/integração e tarifas precisam ser avaliados. |
| **Cartão de crédito recorrente** | **Alternativa principal proposta** | Cliente autoriza cobrança periódica, normalmente uma transação por competência; cartão não fica comprometido por um parcelamento de múltiplos meses. Precisa tokenização segura, conciliação, política de falha/atualização e não armazenar PAN/CVV no Portal. |
| **Pix comum (QR dinâmico/copia e cola)** | **Alternativa manual e recuperação de falhas** | Cada competência terá cobrança identificável, valor correto, vencimento e pagamento confirmado; não criar recorrência automática sem consentimento. |
| **Boleto** | **Alternativa opcional em avaliação** | Disponibilidade e tarifas segundo provedor e custo operacional; política final ainda a deliberar. |

**Pix Automático vs Pix parcelado bancário:** o Pix Automático é uma **autorização de cobranças recorrentes por contrato**, não operação de crédito ou financiamento. O cliente pode alterar ou cancelar a autorização segundo regras do serviço. Se a autorização for cancelada, **não significa automaticamente cancelamento da obrigação contratual ou do site**; Portal deve propor outra forma de pagar, respeitando regras contratuais e direitos do consumidor.

**Pix comum financiado pelo banco:** se o cliente tiver crédito bancário, a instituição pode financiar um Pix individual por conta própria, com taxas/juros informados por ela. A Imagine **não comercializará isso como modalidade "Pix parcelado" do Portal**.

## 3. Fluxo funcional sugerido (ainda não aprovado)

1. Cliente contrata Onepage/Landing Page no Portal e **aceita expressamente contrato, preço, data de início e periodicidade**.
2. Portal oferece escolha transparente: **Pix Automático** como sugestão de conveniência, **cartão recorrente** como alternativa, e meios manuais de contingência se aprovados.
3. A autorização do Pix Automático é concedida pelo cliente **no ambiente da instituição financeira ou fluxo homologado**, com permissões de valor, periodicidade, validade e limites. **Aceite do contrato do site não substitui a autorização bancária.**
4. Mensalidade só entra no ciclo **na data contratual prevista (referência atual: 90º dia)**; não cobrar mensalidade imediatamente sem fundamento contratual.
5. A cada ciclo, criar identificador imutável e registro **idempotente**, apresentar vencimento, conciliar confirmação do pagamento e **não duplicar cobranças** em trocas de Pix/cartão/manual.
6. Em falha de cobrança, registrar estado verdadeiro, informar o cliente e possibilitar pagamento manual seguro ou atualização do método; aplicar notificações e tolerância/suspensão **apenas conforme política contratual aprovada**, não criar nova carência aqui.
7. Cobrança e contrato do site seguem separados do **CRM pago**. Mudança de elegibilidade do CRM Básico depende do estado contratual efetivo e das IN-119–121, com cuidado para não suspender benefício por mera falha de webhook ou atraso de confirmação bancária.
8. Cancelamento da autorização de Pix Automático, substituição de cartão, cancelamento contratual do site e reativação são **eventos distintos**, com confirmação, auditoria e políticas próprias.

## 4. Incentivos comerciais e riscos

- A diretriz geral de incentivo ao Pix à vista foi manifestada no planejamento da PORTAL-D02; eventual **incentivo para Pix Automático em mensalidades recorrentes** requer **aprovação separada de percentuais, margem, elegibilidade, continuidade da oferta e transparência**.
- Não declarar Pix Automático grátis para empresa: o **Banco Central permite tarifa para o recebedor**. Consultar custo de bancos/PSPs e comparar com cartão, retries, chargeback, custo de boletos e taxas de falhas.
- A disponibilidade do Pix Automático para recebedor e integração via PSP depende da instituição, certificação/implementação e condições contratuais. **Sem fornecedor ou custo já aprovado.**
- Pix Automático só funciona com autorização válida e condições de saldo/limites; **não prometer liquidação automática garantida**.
- Evitar operar dois métodos de cobrança simultâneos no mesmo ciclo. Mudança de método exige reconciliação e prevenção de dupla cobrança.
- Proteção de dados, identidade, trilha de autorização, tratamento de contestação, links seguros, fraude e reembolso ficam sujeitos à especificação técnica/jurídica.

## 5. Pendências posteriores

- **Decisão principal desta pauta:** aprovar ou ajustar a oferta de **Pix Automático + cartão recorrente** para mensalidades dos sites e **Pix manual como alternativa**, com boleto opcional a deliberar.
- Datas/vencimentos (90º dia; dia fixo/mês; feriados e limites), cobrança inicial, comunicação antes do vencimento.
- Falhas de renovação, janelas de retentativa, troca de forma de pagamento, cancelamento do mandato Pix, autorização de cartão, conciliação de múltiplos meios, inadimplência e retomada.
- Preços/reajustes, possíveis descontos para Pix Automático, tarifas, planos de PSP, NFS-e e obrigações fiscais; revisão financeira geral ao final do projeto.
- Fluxo jurídico de aceite do contrato e autorização bancária separada, notificação, guarda de evidências e LGPD.

## 6. Fontes oficiais e referência temporal

Consultadas em 02/11/2026:
- Banco Central — Pix Automático: https://www.bcb.gov.br/estabilidadefinanceira/pix-automatico
- Banco Central — perguntas frequentes sobre pagamento recorrente e Pix Agendado: https://www.bcb.gov.br/meubc/faqs/s/pix
- Banco Central — Pix Automático para recebedores: https://www.bcb.gov.br/estabilidadefinanceira/pix-automatico-recebedores
- Banco Central — regulamentação de crédito durante iniciação de Pix: https://www.bcb.gov.br/estabilidadefinanceira/exibenormativo?numero=603&tipo=Resolu%C3%A7%C3%A3o+BCB

**Conclusão: a escolha do responsável sobre cartão como único parcelamento dos adicionais já foi registrada na PORTAL-D02. Os meios de pagamento recorrente desta PORTAL-D04 são somente PROPOSTA, aguardando decisão expressa. Não iniciar programação nem cobrança.**


## Proposta comercial posterior — mensalidade R$ 87 e Pix Automático 10% (02/11/2026)

O responsável manifestou estar **considerando**, sem aprovação final, alterar a mensalidade-base de **R$ 79,90 para R$ 87,00**, aplicando **10% de desconto em Pix Automático** (**R$ 78,30 efetivos**) e **sem desconto em cartão recorrente** (**R$ 87,00**). Na política anterior de referência, mensalidade-base R$ 79,90 e desconto Pix Automático de 5% levariam a R$ 75,91 efetivos. Assim, o Pix proposto de R$ 78,30 é **R$ 1,60 menor que a mensalidade-base antiga, mas R$ 2,39 maior que a antiga mensalidade Pix descontada**. As novas condições **são hipótese para análise e não substituem preços/descontos aprovados**. O estudo está em [PORTAL-COM-02](PORTAL-COM-02-proposta-revisao-precos-pix-20-cartao-4x-mensalidade-87.md). Confirmar aplicabilidade a Onepage/Landing Page, início no 90º dia, proteção de contratos vigentes, custos de tarifas e margem antes de qualquer decisão/publicação. Não autoriza cobrança ou programação.


## Diretriz comercial posterior aprovada — Pix Automático com 10% de desconto (02/11/2026)

O responsável esclareceu que a mensalidade de cliente que habilite **Pix Automático recebe 10% de desconto**, como camada permanente de incentivo à recorrência, **independente do desconto de 20% do Pix à vista da implantação**. Portanto o percentual **10%** não é mera hipótese pendente: está decidido como diretriz funcional. **Exemplo:** se a mensalidade-base de R$ 87 anteriormente proposta for efetivamente aprovada para determinado produto, a cobrança mensal será R$ 78,30 por Pix Automático e R$ 87 por cartão recorrente. **A mensalidade-base por produto e os meios técnicos de cobrança ainda exigem aprovação final**; não ativar cobrança ou modificar contratos legados. O desconto não se aplica automaticamente a Pix manual ou cartão. Base documental: [PORTAL-COM-03 — Motor promocional](PORTAL-COM-03-motor-precos-promocoes-pix-contadores.md).


## Complemento De/Por da mensalidade por produto (02/11/2026)

O responsável **aprovou a mesma estratégia de tabela/“De/Por” para mensalidades**, com **preço-base mensal próprio de cada serviço**, em vez de preço mensal único obrigatório. A **mensalidade promocional contratada** é calculada separadamente da implantação; sobre ela aplica-se **mais 10% se o cliente habilitar e pagar por Pix Automático**, não aplicável a cartão recorrente. Exemplo somente explicativo: **mensalidade de tabela R$ 120 → oferta R$ 87 → Pix Automático R$ 78,30/mês**, enquanto cartão recorrente fica em R$ 87. Não aprovar R$ 120 ou R$ 87 como preços reais por esta decisão. O desconto de 20% Pix na implantação também é separado. Documentar condições de permanência e reajuste do preço promocional para contratos já celebrados; **a promoção encerrar para novos clientes não significa reajustar automaticamente o contratado**. Ver [PORTAL-COM-04 — Preços individuais e De/Por mensalidade](PORTAL-COM-04-precos-individuais-implantacao-mensalidade-de-por.md). Sem programação ou cobranças.


## Modelo de mensalidade contratual aprovado — prazo de meses ou vida do contrato; índice anual (02/11/2026)

O responsável aprovou dois tipos configuráveis **administrativamente por campanha**: (A) mensalidade promocional válida por **N meses definidos na oferta**, com preço posterior previamente informado/aceito; e (B) mensalidade promocional válida **enquanto o contrato permanecer ativo**, sem perder desconto pelo encerramento público da campanha. **Reajuste por índice previsto no contrato somente depois de 12 meses de contrato e depois em periodicidade anual**, em ambos os tipos, sem índice escolhido ainda. Não confundir expiração de uma condição temporária expressamente aceita com reajuste por índice; tudo deve ser claro antes da contratação e homologado juridicamente. O início das mensalidades no 90º dia e a contagem de N meses precisam de marco contratual explícito. O desconto Pix Automático de 10% incide sobre a mensalidade elegível vigente; cartão não recebe esse incentivo. Documento central: [COM-05](PORTAL-COM-05-gestao-administrativa-precos-campanhas-reajuste-anual.md). Sem alteração de contratos e sem programação.


## Decisão funcional posterior COM-06 — primeiro vencimento configurável por serviço/campanha (02/11/2026)

O responsável aprovou **configuração administrativa do prazo até a primeira mensalidade** separadamente para cada serviço/produto e cada campanha, preservando **90 dias como padrão atualmente previsto**. Ao contratar, o Portal deve **calcular, exibir e registrar a data da primeira cobrança** com o prazo vigente da oferta, sem que a edição posterior da campanha modifique o contrato anterior. **Campanha com mensalidade promocional por N meses começa a contar no primeiro ciclo de mensalidade devida**, não no dia da assinatura; **reajuste pelo índice pactuado apenas após 12 meses de contrato** segue calendário independente. Campanhas podem variar o prazo de início para novas vendas, mas não redefinem contratos já aceitos. Marco de contagem (data exata, dias úteis/corridos e feriados), periodicidade de vencimento, janelas/retentativas, cobrança e reconciliação técnica ainda exigem detalhamento. Documento: [COM-06](PORTAL-COM-06-inicio-mensalidade-configuravel-por-campanha.md). Sem início de cobranças nem programação.


## Decisão complementar COM-07 — vencimentos mensais configuráveis (02/11/2026)

**Aprovado funcionalmente:** dia de vencimento recorrente administrável, com **padrão no mesmo dia da primeira mensalidade devida**. Dias especiais podem ser definidos em ofertas novas conforme termo aceito; mudanças de calendário comercial não afetam contratos existentes. O marco da primeira cobrança permanece configurável por produto/campanha (atualmente padrão de 90 dias); o prazo de N mensalidades com promoção conta a partir da primeira mensalidade e o índice reajusta somente depois de 12 meses de contrato. **Meses curtos aprovados em decisão posterior de 02/11/2026:** usar último dia calendário do mês que não tiver o dia contratado (por exemplo, fevereiro 28/29 para dia 31), recuperando a data âncora nos meses seguintes. **Pendentes:** vencimento em fins de semana/feriados, calendário de débito Pix Automático/PSP e mudança de dia solicitada pelo assinante. Documento detalhado: [COM-07](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md). Sem cobranças reais nem programação.


## Complemento COM-07 — meses curtos aprovados (02/11/2026)

Foi aprovado expressamente por **“ok”** do responsável que o vencimento mensal em dia 29/30/31 será ajustado para **último dia calendário do mês** quando esse mês não tiver tal número. O vencimento **retorna ao dia originalmente pactuado** nos demais meses. A alteração pontual não reconfigura a assinatura, os N meses promocionais ou o reajuste anual. **Fins de semana e feriados seguem pendentes**, conforme [COM-07](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md). Sem implementação.


## Decisão posterior COM-07 — cobrar na data mesmo em dia não útil; tolerância após falha (02/11/2026)

O responsável **corrigiu expressamente a proposta anterior de postergar cobranças para o próximo dia útil**: deve-se **tentar efetuar pagamento automático no dia contratualmente configurado mesmo quando sábado, domingo ou feriado bancário** (quando permitido pelo meio/provedor e juridicamente válido). Se o pagamento **falhar em dia não útil**, o cliente poderá **regularizar até o próximo dia útil bancário sem juros nem multa**, sem suspensão por essa falha durante tal janela. **Registrar distintamente data contratada, tentativa, situação de liquidação e data-limite sem encargos**. Não deslocar o dia âncora dos próximos ciclos, não cobrar em duplicidade. **Pix Automático:** o PSP pode adotar ajuste-padrão ao próximo dia útil; escolher e homologar configuração para não úteis, quando juridicamente admitida. **Cartão:** sujeitar ao suporte do processador e conciliação. Retentativas, feriados locais e políticas posteriores de atraso permanecem pendentes. Detalhes em [PORTAL-COM-07](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md). Não iniciar cobrança real ou programação.


## Decisão parcial sobre inadimplência — após o prazo sem encargos (02/11/2026)

O responsável respondeu **“ok”** à proposta expressa: **se a mensalidade não for paga até o prazo de regularização concedido após uma falha em fim de semana/feriado bancário**, o Portal deverá:
1. **marcar o ciclo como pendente de pagamento**, registrando vencimento contratual, falha de tentativa, prazo de regularização sem encargos e status financeiro comprovado;
2. **enviar notificação ao cliente** sobre o pagamento pendente, com forma segura para regularização e informações claras, sem assumir que um pagamento feito e ainda em conciliação não existe;
3. **não suspender imediatamente o site** por essa pendência. A duração da tolerância antes de suspensão e os avisos prévios serão aprovados em decisão específica futura;
4. preservar dados, contrato, histórico e controles de duplicidade. **Não presumir suspensão imediata do CRM Básico Cortesia** por mera falha de cobrança: os gatilhos são separados, sujeitos às regras já aprovadas de elegibilidade e transição;
5. distinguir o período aprovado **sem juros e multa até o próximo dia útil em caso de falha no dia não útil** de qualquer política posterior de encargos, tolerância e suspensão. **Juros, multa, sua incidência e carência após esse limite ainda não foram aprovados**;
6. se o pagamento for confirmado na conciliação, quitar a competência, encerrar a pendência e impedir notificações/cobranças duplicadas.

**Aprovação somente parcial:** prazo para suspensão (**quantos dias**), contagem de dias corridos/úteis, política de encargos, reenvios, notificações finais, suspensão, reativação e repercussão no CRM ainda **pendentes**. O presente aceite não autoriza cobrança real nem programação. Referência da regra anterior de vencimento: [COM-07](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md).


## Decisão posterior COM-08 — 7 dias, bloqueio cortesia e beneficio por campanha (02/11/2026)

O responsável determinou **alinhar a tolerância por inadimplência do site aos 7 dias corridos do CRM Imagine**, além do prazo de regularização de falha em dia não útil aprovado na COM-07. **Depois do prazo, conciliar e notificar antes de suspender o site**. Na **suspensão efetiva por inadimplência**, **suspender também o acesso operacional do CRM Básico concedido como cortesia, se a conta CRM depender exclusivamente daquele site**. Se outro site elegível ativo sustentar a mesma conta, **não bloqueá-la**. Se houver **assinatura CRM paga independente**, preservá-la e não suspender por débito do site. **Sem exclusão automática de dados nem nova cobrança CRM.** Reabilitar após regularização e revalidação dos vínculos. **Perda definitiva do último vínculo por cancelamento** segue transição de **30 dias** das IN-119/121, distinta de suspensão por dívida; integração dos estados pendente. A concessão de **CRM Básico como cortesia pode ser configurada por serviço e campanha**, de maneira explícita no aceite e **apenas para novas ofertas**, preservando direitos adquiridos em contratos vigentes. Definições e riscos documentados em [COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md). A referência de 7 dias é aprovada, mas marco exato, multa/juros, notificações e cancelamento final ainda exigem detalhamento. **Não iniciar programação nem cobrança.**


## Complemento COM-09 — 30 dias restritos após cancelamento por dívida (02/11/2026)

Aprovado: **após suspensão do site por inadimplência e seu posterior cancelamento formal**, quando for o **último vínculo elegível da conta CRM Básico cortesia**, o cliente terá **30 dias de transição com acesso operacional CRM bloqueado**, mas poderá **exportar/baixar dados e contratar CRM pago expressamente**, sem cobrança automática ou exclusão. **No cancelamento comum** do último site, preservar **30 dias de operação normal** da cortesia conforme IN-119–121. O prazo de 30 dias tem como marco o cancelamento do último vínculo, **não** a primeira falha de pagamento nem a suspensão após sete dias. Outros sites elegíveis e CRM pagos independentes **não são afetados**. A política de quando cancelar formalmente um site suspenso ainda está pendente. Documento [COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md). Sem programação/cobrança.


## Decisão COM-10 — recuperação de site cancelado mediante taxa (02/11/2026)

**Aprovado:** cliente poderá solicitar **reativação de site formalmente cancelado** dentro da **janela comercial padrão de 60 dias corridos, configurável administrativamente**, desde que arquivos/backup e demais recursos estejam disponíveis. A recuperação envolverá **taxa de reativação**, com valor e contrato/orçamento previamente informados e aceitos pelo cliente (**valor ainda não definido**); diferente de parcelas de implantação, mensalidade recorrente e débitos antigos. **Site só suspenso:** pagamento e reativação da assinatura existente, sem presumir taxa pós-cancelamento. **Site não recuperável:** orçamento independente de nova produção. **Não estender automaticamente desconto Pix 20% da implantação nem 10% Pix Automático da mensalidade à taxa de reativação.** O sistema não poderá converter pagamento tardio em novo contrato sem aceite. A política de backup de site e custos (IN-101), cancelamento formal por inadimplência e taxas concretas seguem pendentes. Documento [COM-10](PORTAL-COM-10-proposta-reativacao-site-apos-cancelamento.md). Sem programação/cobranças.


## Complemento COM-10 — taxa de reativação administrável por serviço e campanha (02/11/2026)

**Aprovado expressamente:** a taxa de reativação de site formalmente cancelado terá **valor-base próprio para cada serviço/produto**, com **possibilidade de alteração/substituição por campanha**, configurável no **painel administrativo sem programação**. A taxa concreta da oferta é mostrada e aceita pelo cliente antes do pagamento, ficando vinculada à versão contratada; novas alterações não mudam cobranças já aceitas. **Ainda não há preços numéricos aprovados**, descontos especiais, parcelamento ou meio de pagamento específico para essa taxa. Ela é **separada de nova implantação, mensalidade e débitos antigos**; os descontos de 20% no Pix da implantação e 10% no Pix Automático da assinatura **não são transferidos automaticamente** à reativação. Mantêm-se a janela comercial de 60 dias configurável e a necessidade de backup real. Referência [COM-10](PORTAL-COM-10-proposta-reativacao-site-apos-cancelamento.md). Sem implementação/cobrança.
